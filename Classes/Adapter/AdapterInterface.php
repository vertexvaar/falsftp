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

/**
 * Interface AdapterInterface
 */
interface AdapterInterface
{
    const TYPE_FILE = 'file';
    const TYPE_FOLDER = 'folder';
    const AUTHENTICATION_PASSWORD = 1;
    const AUTHENTICATION_PUBKEY = 2;

    /**
     * AdapterInterface constructor.
     * Do not already connect in this method.
     *
     * @param array $configuration
     */
    public function __construct(array $configuration);

    /**
     * Establish a connection to the remote host and return true if successful.
     *
     * @return bool
     */
    public function connect();
     * @param string $identifier
     * @param bool $files
     * @param bool $folders
     * @param bool $recursive
     * @return array
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
