<?php
namespace VerteXVaaR\FalSftp\Adapter;

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
            foreach (array_keys($directoryEntries) as $directoryEntry) {
                foreach ($this->scanDirectory($directoryEntry, $files, $folders, $recursive) as $identifier => $info) {
                    $directoryEntries[$identifier] = $info;
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
    public function getPermissions($identifier)
    {
        $path = $this->sftpWrapper . $identifier;
        return array(
            'r' => (bool)is_readable($path),
            'w' => (bool)is_writable($path)
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
}
