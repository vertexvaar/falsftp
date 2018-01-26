<?php
namespace VerteXVaaR\FalSftp\Adapter;

/* Copyright (C) 2015 Oliver Eglseder <php@vxvr.de>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

use phpseclib\Crypt\RSA;
use phpseclib\Net\SFTP;
use phpseclib\Net\SSH2;
use TYPO3\CMS\Core\Type\File\FileInfo;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use VerteXVaaR\FalSftp\Driver\SftpDriver;

/**
 * Class PhpseclibAdapter
 */
class PhpseclibAdapter extends AbstractAdapter
{
    const WRITABLE = 2;
    const READABLE = 4;

    /**
     * @var array
     */
    protected $configuration = [];

    /**
     * @var SSH2
     */
    protected $ssh = null;

    /**
     * @var SFTP
     */
    protected $sftp = null;

    /**
     * @var array
     */
    protected $info = [
        'userId' => 0,
        'groupIds' => [],
    ];

    /**
     * AdapterInterface constructor.
     *
     * @param array $configuration
     */
    public function __construct(array $configuration)
    {
        if (!class_exists('phpseclib\\Net\\SFTP') || !class_exists('phpseclib\\Net\\SSH2')) {
            throw new \LogicException('Can not use phpseclib adapter when package is not available', 1476624469);
        }
        $this->configuration = $configuration;
    }

    /**
     *
     */
    public function connect()
    {
        $this->ssh = new SSH2(
            $this->configuration['hostname'],
            $this->configuration['port']
        );

        $authenticationMethod = $this->configuration[SftpDriver::CONFIG_AUTHENTICATION_METHOD];
        if (static::AUTHENTICATION_PASSWORD === (int)$authenticationMethod) {
            $authentication = $this->configuration['password'];
        } elseif (static::AUTHENTICATION_PUBKEY === (int)$authenticationMethod) {
            $authentication = new RSA();
            if (!empty($this->configuration['privateKeyPassword'])) {
                $authentication->setPassword($this->configuration['privateKeyPassword']);
            }
            $authentication->loadKey(file_get_contents($this->configuration['privateKey']));
        } else {
            throw new \LogicException('Wrong authentication type for phpseclibAdapter', 1476626149);
        }

        $sshConnected = $this->ssh->login(
            $this->configuration['username'],
            $authentication
        );

        if ($sshConnected) {
            $this->sftp = new SFTP(
                $this->configuration['hostname'],
                $this->configuration['port']
            );
            $sftpConnected = $this->sftp->login(
                $this->configuration['username'],
                $authentication
            );
            if ($sftpConnected) {
                $this->info['userId'] = (int)$this->ssh->exec('echo $EUID');
                $this->info['groupIds'] = GeneralUtility::intExplode(' ', $this->ssh->exec('echo ${GROUPS[*]}'), true);
                return true;
            }
        }
        return false;
    }

    /**
     * @param string $hashingMethod "sha1" or "md5"
     * @return mixed
     */
    public function getForeignKeyFingerprint($hashingMethod)
    {
        switch ($hashingMethod) {
            case self::HASHING_SHA1:
                $hashingMethod = function ($string) {
                    return sha1($string);
                };
                break;
            case self::HASHING_MD5:
            default:
                $hashingMethod = function ($string) {
                    return md5($string);
                };
        }
        $foreignPublicKey = explode(' ', $this->ssh->getServerPublicHostKey());
        return $hashingMethod(base64_decode($foreignPublicKey[1]));
    }

    /**
     * @param string $identifier
     * @param bool $files
     * @param bool $folders
     * @param bool $recursive
     * @return array
     */
    public function scanDirectory($identifier, $files = true, $folders = true, $recursive = false)
    {
        $directoryEntries = [];
        if (!$files && !$folders) {
            return $directoryEntries;
        }
        $items = $this->sftp->nlist($identifier, $recursive);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $itemIdentifier = $identifier . $item;
            if ($files && $this->sftp->is_file($itemIdentifier)) {
                $directoryEntries[$itemIdentifier] = $this->getShortInfo($identifier, 'file');
            } elseif ($folders && $this->sftp->is_dir($itemIdentifier)) {
                $directoryEntries[$itemIdentifier] = $this->getShortInfo($identifier, 'dir');
            }
        }
        return $directoryEntries;
    }

    /**
     * @param string $identifier
     * @param string $type
     * @return bool
     */
    public function exists($identifier, $type = self::TYPE_FILE)
    {
        if ($type === self::TYPE_FILE) {
            return $this->sftp->is_file($identifier);
        } elseif ($type === self::TYPE_FOLDER) {
            return $this->sftp->is_dir($identifier);
        }
        return false;
    }

    /**
     * @param string $identifier
     * @param bool $recursive
     * @return string
     */
    public function createFolder($identifier, $recursive = true)
    {
        $this->sftp->mkdir($identifier, $this->configuration[SftpDriver::CONFIG_FOLDER_MODE], $recursive);
        return $identifier;
    }

    /**
     * @param string $identifier
     * @return array
     */
    public function getPermissions($identifier)
    {
        $permissions = [
            'r' => false,
            'w' => false,
        ];

        $filePerms = array_map(
            function ($var) {
                return (int)$var;
            },
            str_split(substr(decoct($this->sftp->fileperms($identifier)), -3, 3))
        );

        if ($this->info['userId'] === $this->sftp->fileowner($identifier)) {
            $permissions['r'] = ($filePerms[0] & self::READABLE) === self::READABLE;
            $permissions['w'] = ($filePerms[0] & self::WRITABLE) === self::WRITABLE;
        }
        if (in_array($this->sftp->filegroup($identifier), $this->info['groupIds'])) {
            $permissions['r'] = $permissions['r'] ?: ($filePerms[1] & self::READABLE) === self::READABLE;
            $permissions['w'] = $permissions['w'] ?: ($filePerms[1] & self::WRITABLE) === self::WRITABLE;
        }
        $permissions['r'] = $permissions['r'] ?: ($filePerms[2] & self::READABLE) === self::READABLE;
        $permissions['w'] = $permissions['w'] ?: ($filePerms[2] & self::WRITABLE) === self::WRITABLE;
        return $permissions;
    }

    /**
     * @param string $identifier
     * @return array
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function getDetails($identifier)
    {
        $details = [];
        $details['size'] = $this->sftp->size($identifier);
        $details['atime'] = $this->sftp->fileatime($identifier);
        $details['mtime'] = $this->sftp->filemtime($identifier);
        // ctime is not returned by phpseclinb so we just use mtime
        // because it will mostly be the same.
        // @see http://www.linux-faqs.info/general/difference-between-mtime-ctime-and-atime
        $details['ctime'] = $details['mtime'];

        $mimeType = false;

        if ($this->sftp->is_file($identifier)) {
            $fileExtMapping = $GLOBALS['TYPO3_CONF_VARS']['SYS']['FileInfo']['fileExtensionToMimeType'];
            $lcFileExtension = strtolower(substr($identifier, strrpos($identifier, '.') + 1));
            if (!empty($fileExtMapping[$lcFileExtension])) {
                $mimeType = $fileExtMapping[$lcFileExtension];
            }
        }

        if (false === $mimeType
            && isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][FileInfo::class]['mimeTypeGuessers'])
            && is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][FileInfo::class]['mimeTypeGuesser'])
        ) {
            $mimeTypeGuessers = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][FileInfo::class]['mimeTypeGuesser'];
            foreach ($mimeTypeGuessers as $mimeTypeGuesser) {
                $hookParameters = ['mimeType' => &$mimeType];

                GeneralUtility::callUserFunction(
                    $mimeTypeGuesser,
                    $hookParameters,
                    $this
                );
            }
        }
        $details['mimetype'] = $mimeType;

        return $details;
    }

    /**
     * @param string $identifier
     * @param string $hashAlgorithm
     * @return string
     */
    public function hash($identifier, $hashAlgorithm)
    {
        switch ($hashAlgorithm) {
            case 'sha1':
                return $this->ssh->exec('sha1sum "' . $identifier . '"');
            case 'md5':
                return $this->ssh->exec('md5 "' . $identifier . '"');
            default:
        }
        return '';
    }

    /**
     * @param string $identifier
     * @param string $target
     * @return string
     */
    public function downloadFile($identifier, $target)
    {
        return ($this->sftp->get($identifier, $target) === true) ? $identifier : '';
    }

    /**
     * @param string $source
     * @param string $identifier
     * @return string
     */
    public function uploadFile($source, $identifier)
    {
        return $this->sftp->put($identifier, $source, SFTP::SOURCE_LOCAL_FILE);
    }

    /**
     * @param string $identifier
     * @return void
     */
    public function dumpFile($identifier)
    {
        echo $this->sftp->get($identifier);
    }

    /**
     * @param string $identifier
     * @param bool $recursive
     * @return bool
     */
    public function delete($identifier, $recursive)
    {
        return $this->sftp->delete($identifier, $recursive);
    }

    /**
     * @param string $identifier
     * @return string
     */
    public function readFile($identifier)
    {
        return $this->sftp->get($identifier);
    }

    /**
     * @param string $oldIdentifier
     * @param string $newIdentifier
     * @return bool
     */
    public function rename($oldIdentifier, $newIdentifier)
    {
        if ($this->exists($newIdentifier)) {
            $this->delete($newIdentifier, true);
        }
        return $this->sftp->rename($oldIdentifier, $newIdentifier);
    }

    /**
     * @param string $sourceIdentifier
     * @param string $targetIdentifier
     * @return bool
     */
    public function copy($sourceIdentifier, $targetIdentifier)
    {
        $result = $this->ssh->exec(
            'cp -RPf ' . escapeshellarg($sourceIdentifier) . ' ' . escapeshellarg($targetIdentifier)
        );
        return $result === '';
    }

    /**
     * "Proper" disconnect
     */
    public function __destruct()
    {
        if ($this->ssh instanceof SSH2) {
            $this->ssh->disconnect();
        }
        if ($this->sftp instanceof SFTP) {
            $this->sftp->disconnect();
        }
    }
}
