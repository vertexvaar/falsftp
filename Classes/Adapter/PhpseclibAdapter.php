<?php
namespace VerteXVaaR\FalSftp\Adapter;

use phpseclib\Net\SFTP;
use phpseclib\Net\SSH2;
use TYPO3\CMS\Core\Type\File\FileInfo;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class PhpseclibAdapter
 */
class PhpseclibAdapter extends AbstractAdapter
{
    const WRITABLE = 2;
    const READABLE = 4;

    /**
     * @var string[]|int[]
     */
    protected $configuration = [];

    /**
     * @var null
     */
    protected $ssh = null;

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
        $this->configuration = $configuration;
        $this->ssh = new SSH2(
            $this->configuration['hostname'],
            $this->configuration['port']
        );
        $sshConnectend = $this->ssh->login(
            $this->configuration['username'],
            $this->configuration['password']
        );
        if ($sshConnectend) {
            $this->sftp = new SFTP(
                $this->configuration['hostname'],
                $this->configuration['port']
            );
            $sftpConnectend = $this->sftp->login(
                $this->configuration['username'],
                $this->configuration['password']
            );
            if ($sftpConnectend) {
                $this->info['userId'] = (int)$this->ssh->exec('echo $EUID');
                $this->info['groupIds'] = GeneralUtility::intExplode(' ', $this->ssh->exec('echo ${GROUPS[*]}'), true);
            }
        }
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
        \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump([__METHOD__, func_get_args()], __CLASS__ . '@' . __LINE__, 20);
        die;
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
        $details['mimetype'] = false;

        if ($this->sftp->is_file($identifier)) {
            $fileExtMapping = $GLOBALS['TYPO3_CONF_VARS']['SYS']['FileInfo']['fileExtensionToMimeType'];
            $lcFileExtension = strtolower(substr($identifier, strrpos($identifier, '.') + 1));
            if (!empty($fileExtMapping[$lcFileExtension])) {
                $mimeType = $fileExtMapping[$lcFileExtension];
            }
        }

        if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][FileInfo::class]['mimeTypeGuessers'])
            && is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][FileInfo::class]['mimeTypeGuesser'])
        ) {
            $mimeTypeGuessers = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][FileInfo::class]['mimeTypeGuesser'];
            foreach ($mimeTypeGuessers as $mimeTypeGuesser) {
                $hookParameters = array(
                    'mimeType' => &$mimeType,
                );

                GeneralUtility::callUserFunction(
                    $mimeTypeGuesser,
                    $hookParameters,
                    $this
                );
            }
        }

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
                return $this->ssh->exec('shasum -a1 "' . $identifier . '"');
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
        \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump([__METHOD__, func_get_args()], __CLASS__ . '@' . __LINE__, 20);
        die;
    }

    /**
     * @param string $identifier
     * @param bool $recursive
     * @return bool
     */
    public function delete($identifier, $recursive)
    {
        \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump([__METHOD__, func_get_args()], __CLASS__ . '@' . __LINE__, 20);
        die;
    }

    /**
     * @param string $identifier
     * @return string
     */
    public function readFile($identifier)
    {
        \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump([__METHOD__, func_get_args()], __CLASS__ . '@' . __LINE__, 20);
        die;
    }

    /**
     * @param string $oldIdentifier
     * @param string $newIdentifier
     * @return bool
     */
    public function rename($oldIdentifier, $newIdentifier)
    {
        \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump([__METHOD__, func_get_args()], __CLASS__ . '@' . __LINE__, 20);
        die;
    }

    /**
     * @param string $sourceIdentifier
     * @param string $targetIdentifier
     * @return bool
     */
    public function copy($sourceIdentifier, $targetIdentifier)
    {
        \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump([__METHOD__, func_get_args()], __CLASS__ . '@' . __LINE__, 20);
        die;
    }
}
