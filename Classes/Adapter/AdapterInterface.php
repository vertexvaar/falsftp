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
     * @param bool $recursive
     * @return string
     */
    public function createFolder($identifier, $recursive);

    /**
     * @param string $identifier
     * @return mixed
     */
    public function getPermissions($identifier);
}
