<?php
namespace VerteXVaaR\FalSftp\Adapter;

use TYPO3\CMS\Core\Type\File\FileInfo;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Class PhpSshAdapter
 */
class PhpSshAdapter implements AdapterInterface
{
    /**
     * @var string[]|int[]
     */
    protected $configuration = [];

    /**
     * @var resource
     */
    protected $ssh = null;

    /**
     * @var resource
     */
    protected $sftp = null;

    /**
     * @var string
     */
    protected $sftpWrapper = '';

    /**
     * @var int
     */
    protected $sftpWrapperLength = 0;

    /**
     * @var int
     */
    protected $iteratorFlags = 0;

    /**
     * PhpSshAdapter constructor.
     *
     * @param array $configuration
     */
    public function __construct(array $configuration)
    {
        $this->configuration = $configuration;
        $this->ssh = ssh2_connect(
            $this->configuration['hostname'],
            $this->configuration['port']
        );
        // TODO: respect configuration
        ssh2_auth_password($this->ssh, $this->configuration['username'], $this->configuration['password']);
        $this->sftp = ssh2_sftp($this->ssh);
        $this->sftpWrapper = 'ssh2.sftp://' . $this->sftp;
        $this->sftpWrapperLength = strlen($this->sftpWrapper);
        $this->iteratorFlags =
            \FilesystemIterator::UNIX_PATHS
            | \FilesystemIterator::SKIP_DOTS
            | \FilesystemIterator::CURRENT_AS_FILEINFO
            | \FilesystemIterator::FOLLOW_SYMLINKS;
    }

    /**
     * @param $identifier
     * @param bool $files
     * @param bool $folders
     * @param bool $recursive
     * @return array
     */
    public function scanDirectory($identifier, $files = true, $folders = true, $recursive = false)
    {
        $directoryEntries = [];
        $iterator = new \RecursiveDirectoryIterator($this->sftpWrapper . $identifier, $this->iteratorFlags);
        while ($iterator->valid()) {
            /** @var $entry \SplFileInfo */
            $entry = $iterator->current();
            $identifier = substr($entry->getPathname(), $this->sftpWrapperLength);
            if ($files && $entry->isFile()) {
                $directoryEntries[$identifier] = $this->getShortInfo($identifier, 'file');
            } elseif ($folders && $entry->isDir()) {
                $directoryEntries[$identifier] = $this->getShortInfo($identifier, 'dir');
            }
            $iterator->next();
        }
        if ($recursive) {
            foreach ($directoryEntries as $directoryEntry) {
                if ($directoryEntry['type'] === 'dir') {
                    foreach ($this->scanDirectory($directoryEntry['identifier'], $files, $folders, $recursive) as $identifier => $info) {
                        $directoryEntries[$identifier] = $info;
                    }
                }
            }
        }
        return $directoryEntries;
    }

    /**
     * @param string $identifier
     * @return mixed
     */
    public function folderExists($identifier)
    {
        $wrappedIdentifier = $this->sftpWrapper . $identifier;
        return file_exists($wrappedIdentifier) && is_dir($wrappedIdentifier);
    }

    /**
     * @param string $identifier
     * @return mixed
     */
    public function fileExists($identifier)
    {
        $wrappedIdentifier = $this->sftpWrapper . $identifier;
        return file_exists($wrappedIdentifier) && is_file($wrappedIdentifier);
    }

    /**
     * @param string $identifier
     * @return mixed
     */
    public function getPermissions($identifier)
    {
        $path = $this->sftpWrapper . $identifier;
        return array(
            'r' => (bool)is_readable($path),
            'w' => (bool)is_writable($path),
        );
    }

    /**
     * @param string $identifier
     * @param bool $recursive
     * @return string
     */
    public function createFolder($identifier, $recursive)
    {
        ssh2_sftp_mkdir($this->sftp, $identifier, $this->configuration['folderMode'], $recursive);
        return $identifier;
    }

    /**
     * @param string $identifier
     * @param string $type
     * @return array
     */
    protected function getShortInfo($identifier, $type)
    {
        return [
            'identifier' => $identifier,
            'name' => PathUtility::basename($identifier),
            'type' => $type,
        ];
    }

    /**
     * @param string $identifier
     * @return array
     */
    public function getDetails($identifier)
    {
        $fileInfo = new FileInfo($identifier);
        $details = [];
        $details['size'] = $fileInfo->getSize();
        $details['atime'] = $fileInfo->getATime();
        $details['mtime'] = $fileInfo->getMTime();
        $details['ctime'] = $fileInfo->getCTime();
        $details['mimetype'] = (string)$fileInfo->getMimeType();
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
                return sha1_file($this->sftpWrapper . $identifier);
            case 'md5':
                return md5_file($this->sftpWrapper . $identifier);
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
        if (ssh2_scp_recv($this->ssh, $identifier, $target)) {
            return $target;
        }
        throw new \RuntimeException(
            'Copying file "' . $identifier . '" to temporary path "' . $target . '" failed.',
            1447607200
        );
    }

    /**
     * @param string $source
     * @param string $identifier
     * @return string
     */
    public function uploadFile($source, $identifier)
    {
        return ssh2_scp_send($this->ssh, $source, $identifier, $this->configuration['fileMode']);
    }

    /**
     * @param string $identifier
     * @return void
     */
    public function dumpFile($identifier)
    {
        readfile($this->sftpWrapper . $identifier);
    }

    /**
     * @param string $identifier
     * @param bool $recursive
     * @return bool
     */
    public function unlink($identifier, $recursive)
    {
        if (is_dir($this->sftpWrapper . $identifier)) {
            return ssh2_sftp_rmdir($this->sftp, $identifier);
        } elseif (is_file($this->sftpWrapper . $identifier)) {
            return ssh2_sftp_unlink($this->sftp, $identifier);
        } else {
            return false;
        }
    }

    /**
     * @param string $identifier
     * @return string
     */
    public function readFile($identifier)
    {
        return file_get_contents($this->sftpWrapper . $identifier);
    }

    /**
     * @param string $oldIdentifier
     * @param string $newIdentifier
     * @return bool
     */
    public function rename($oldIdentifier, $newIdentifier)
    {
        $this->unlink($newIdentifier, false);
        if (ssh2_sftp_rename($this->sftp, $oldIdentifier, $newIdentifier)) {
            return true;
        } else {
            $this->unlink($oldIdentifier, false);
            return false;
        }
    }

    /**
     * @param string $sourceIdentifier
     * @param string $targetIdentifier
     * @return bool
     */
    public function copy($sourceIdentifier, $targetIdentifier)
    {
        return copy($this->sftpWrapper . $sourceIdentifier, $this->sftpWrapper . $targetIdentifier);
    }
}
