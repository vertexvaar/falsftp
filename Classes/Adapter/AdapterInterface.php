<?php
namespace VerteXVaaR\FalSftp\Adapter;

/**
 * Interface AdapterInterface
 */
interface AdapterInterface
{
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
     * @return mixed
     */
    public function folderExists($identifier);

    /**
     * @param string $identifier
     * @return mixed
     */
    public function fileExists($identifier);

    /**
     * @param string $identifier
     * @param bool $recursive
     * @return string
     */
    public function createFolder($identifier, $recursive);

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
    public function unlink($identifier, $recursive);

    /**
     * @param string $identifier
     * @return string
     */
    public function getFileContents($identifier);

    /**
     * @param string $oldIdentifier
     * @param string $newIdentifier
     * @return bool
     */
    public function rename($oldIdentifier, $newIdentifier);
}
