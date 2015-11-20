<?php
namespace VerteXVaaR\FalSftp\Adapter;

/**
 * Interface AdapterInterface
 */
interface AdapterInterface
{
    const TYPE_FILE = 'file';
    const TYPE_FOLDER = 'folder';

    /**
     * @param string $identifier
     * @param bool $files
     * @param bool $folders
     * @param bool $recursive
     * @return mixed
     */
    public function scanDirectory($identifier, $files = true, $folders = true, $recursive = false);

    /**
     * @param string $identifier
     * @param string $type
     * @return bool
     */
    public function exists($identifier, $type = self::TYPE_FILE);

    /**
     * @param string $identifier
     * @param bool $recursive
     * @return string
     */
    public function createFolder($identifier, $recursive = true);

    /**
     * @param string $identifier
     * @return array
     */
    public function getPermissions($identifier);

    /**
     * @param string $identifier
     * @return array
     */
    public function getDetails($identifier);

    /**
     * @param string $identifier
     * @param string $hashAlgorithm
     * @return string
     */
    public function hash($identifier, $hashAlgorithm);

    /**
     * @param string $identifier
     * @param string $target
     * @return string
     */
    public function downloadFile($identifier, $target);

    /**
     * @param string $source
     * @param string $identifier
     * @return string
     */
    public function uploadFile($source, $identifier);

    /**
     * @param string $identifier
     * @return void
     */
    public function dumpFile($identifier);

    /**
     * @param string $identifier
     * @param bool $recursive
     * @return bool
     */
    public function delete($identifier, $recursive);

    /**
     * @param string $identifier
     * @return string
     */
    public function readFile($identifier);

    /**
     * @param string $oldIdentifier
     * @param string $newIdentifier
     * @return bool
     */
    public function rename($oldIdentifier, $newIdentifier);

    /**
     * @param string $sourceIdentifier
     * @param string $targetIdentifier
     * @return bool
     */
    public function copy($sourceIdentifier, $targetIdentifier);
}
