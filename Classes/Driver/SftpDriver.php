<?php
namespace VerteXVaaR\FalSftp\Driver;

use TYPO3\CMS\Core\Charset\CharsetConverter;
use TYPO3\CMS\Core\Resource\Driver\AbstractHierarchicalFilesystemDriver;
use TYPO3\CMS\Core\Resource\Exception\FileOperationErrorException;
use TYPO3\CMS\Core\Resource\Exception\InvalidFileNameException;
use TYPO3\CMS\Core\Resource\ResourceStorageInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use VerteXVaaR\FalSftp\Adapter\AdapterInterface;
use VerteXVaaR\FalSftp\Adapter\PhpSshAdapter;

/**
 * Class SftpDriver
 */
class SftpDriver extends AbstractHierarchicalFilesystemDriver
{
    const ADAPTER_PHPSSH = 1;
    const ADAPTER_PHPSECLIB = 2;
    const AUTHENTICATION_PASSWORD = 1;
    const AUTHENTICATION_PUBKEY = 2;
    const CONFIG_PUBLIC_URL = 'publicUrl';
    const CONFIG_ROOT_LEVEL = 'rootLevel';
    const CONFIG_ADAPTER = 'adapter';
    const CONFIG_AUTHENTICATION_METHOD = 'authenticationMethod';
    const UNSAFE_FILENAME_CHARACTER_EXPRESSION = '\\x00-\\x2C\\/\\x3A-\\x3F\\x5B-\\x60\\x7B-\\xBF';

    /**
     * @var array
     */
    protected $supportedHashAlgorithms = ['md5', 'sha1'];

    /**
     * @var AdapterInterface
     */
    protected $adapter = null;

    /**
     * @var string
     */
    protected $rootPath = '';

    /**
     * @var int
     */
    protected $rootPathLength = 0;

    /**
     * Processes the configuration for this driver.
     * @return void
     */
    public function processConfiguration()
    {
        $this->configuration['fileMode'] = octdec($this->configuration['fileMode']);
        $this->configuration['folderMode'] = octdec($this->configuration['folderMode']);
        $this->rootPath = '/' . trim($this->configuration[self::CONFIG_ROOT_LEVEL], '/') . '/';
        $this->rootPathLength = strlen($this->rootPath) - 1;
        if (!empty($this->configuration[self::CONFIG_PUBLIC_URL])) {
            $this->capabilities =
                ResourceStorageInterface::CAPABILITY_BROWSABLE
                | ResourceStorageInterface::CAPABILITY_PUBLIC
                | ResourceStorageInterface::CAPABILITY_WRITABLE;
        } else {
            $this->capabilities =
                ResourceStorageInterface::CAPABILITY_BROWSABLE
                | ResourceStorageInterface::CAPABILITY_WRITABLE;
        }
    }

    /**
     *
     */
    public function initialize()
    {
        switch ($this->configuration[self::CONFIG_ADAPTER]) {
            case self::ADAPTER_PHPSSH:
                $this->adapter = new PhpSshAdapter($this->configuration);
                break;
            default:
        }
    }

    /**
     * Merges the capabilities merged by the user at the storage
     * configuration into the actual capabilities of the driver
     * and returns the result.
     *
     * @param int $capabilities
     * @return int
     */
    public function mergeConfigurationCapabilities($capabilities)
    {
        $this->capabilities &= $capabilities;
        return $this->capabilities;
    }

    /**
     * Returns the identifier of the root level folder of the storage.
     *
     * @return string
     */
    public function getRootLevelFolder()
    {
        return '/';
    }

    /**
     * Returns the identifier of the default folder new files should be put into.
     *
     * @return string
     */
    public function getDefaultFolder()
    {
        $defaultFolder = '/user_upload/';
        $identifier = $this->rootPath . $defaultFolder;
        if (!$this->folderExists($identifier)) {
            $this->adapter->createFolder($identifier);
        }
        return $defaultFolder;
    }

    /**
     * Returns the public URL to a file.
     * Either fully qualified URL or relative to PATH_site (rawurlencoded).
     *
     * @param string $identifier
     * @return string
     */
    public function getPublicUrl($identifier)
    {
        return $this->configuration[self::CONFIG_PUBLIC_URL] . $identifier;
    }

    /**
     * Creates a folder, within a parent folder.
     * If no parent folder is given, a root level folder will be created
     *
     * @param string $newFolderName
     * @param string $parentFolderIdentifier
     * @param bool $recursive
     * @return string the Identifier of the new folder
     */
    public function createFolder($newFolderName, $parentFolderIdentifier = '', $recursive = false)
    {
        $newFolderName = $this->canonicalizeAndCheckFolderIdentifier($parentFolderIdentifier . $newFolderName);
        $identifier = $this->canonicalizeAndCheckFolderIdentifier($this->rootPath . $newFolderName);
        $this->adapter->createFolder($identifier, $recursive);
        return $newFolderName;
    }

    /**
     * Renames a folder in this storage.
     *
     * @param string $folderIdentifier
     * @param string $newName
     * @return array A map of old to new file identifiers of all affected resources
     */
    public function renameFolder($folderIdentifier, $newName)
    {
        $parentFolder = $this->canonicalizeAndCheckFolderIdentifier(PathUtility::dirname($folderIdentifier));
        $oldIdentifier = $this->canonicalizeAndCheckFolderIdentifier($parentFolder . PathUtility::basename($folderIdentifier));
        $newIdentifier = $this->canonicalizeAndCheckFolderIdentifier($parentFolder . PathUtility::basename($this->sanitizeFileName($newName)));
        $oldFolderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($this->rootPath . $oldIdentifier);
        $newFolderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($this->rootPath . $newIdentifier);
        $this->adapter->rename($oldFolderIdentifier, $newFolderIdentifier);
        $items = $this->adapter->scanDirectory($newFolderIdentifier, true, true, true);
        $map = $this->createIdentifierMap($items, $oldIdentifier, $newIdentifier);
        return $map;
    }

    /**
     * Removes a folder in filesystem.
     *
     * @param string $folderIdentifier
     * @param bool $deleteRecursively
     * @return bool
     */
    public function deleteFolder($folderIdentifier, $deleteRecursively = false)
    {
        $folderIdentifier = $this->canonicalizeAndCheckFileIdentifier($this->rootPath . $folderIdentifier);
        return $this->adapter->delete($folderIdentifier, $deleteRecursively);
    }

    /**
     * Checks if a file exists.
     *
     * @param string $fileIdentifier
     * @return bool
     */
    public function fileExists($fileIdentifier)
    {
        $fileIdentifier = $this->canonicalizeAndCheckFileIdentifier($this->rootPath . $fileIdentifier);
        return $this->adapter->exists($fileIdentifier);
    }

    /**
     * Checks if a folder exists.
     *
     * @param string $folderIdentifier
     * @return bool
     */
    public function folderExists($folderIdentifier)
    {
        $folderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($this->rootPath . $folderIdentifier);
        return $this->adapter->exists($this->getRootLevelFolder() . $folderIdentifier, AdapterInterface::TYPE_FOLDER);
    }

    /**
     * Checks if a folder contains files and (if supported) other folders.
     *
     * @param string $folderIdentifier
     * @return bool TRUE if there are no files and folders within $folder
     */
    public function isFolderEmpty($folderIdentifier)
    {
        $folderIdentifier = $this->canonicalizeAndCheckFileIdentifier($this->rootPath . $folderIdentifier);
        return count($this->adapter->scanDirectory($folderIdentifier)) === 0;
    }

    /**
     * Adds a file from the local server hard disk to a given path in TYPO3s
     * virtual file system. This assumes that the local file exists, so no
     * further check is done here! After a successful the original file must
     * not exist anymore.
     *
     * @param string $localFilePath (within PATH_site)
     * @param string $targetFolderIdentifier
     * @param string $newFileName optional, if not given original name is used
     * @param bool $removeOriginal if set the original file will be removed
     *                                after successful operation
     * @return string the identifier of the new file
     */
    public function addFile($localFilePath, $targetFolderIdentifier, $newFileName = '', $removeOriginal = true)
    {
        $localFilePath = $this->canonicalizeAndCheckFilePath($localFilePath);
        $targetFileIdentifier = $targetFolderIdentifier . $this->sanitizeFileName($newFileName);
        $identifier = $this->canonicalizeAndCheckFileIdentifier(
            $this->rootPath . $targetFileIdentifier
        );
        if ($this->adapter->uploadFile($localFilePath, $identifier) && $removeOriginal) {
            unlink($localFilePath);
        }
        return $targetFileIdentifier;
    }

    /**
     * Creates a new (empty) file and returns the identifier.
     *
     * @param string $fileName
     * @param string $parentFolderIdentifier
     * @return string
     */
    public function createFile($fileName, $parentFolderIdentifier)
    {
        $fileName = $this->sanitizeFileName($fileName);
        $temporaryFile =
            GeneralUtility::tempnam(
                'fal-tempfile-',
                '.' . PathUtility::pathinfo($fileName, PATHINFO_EXTENSION)
            );
        touch($temporaryFile);
        $fileName = $parentFolderIdentifier . $fileName;
        $identifier = $this->canonicalizeAndCheckFilePath($this->rootPath . $fileName);
        $this->adapter->uploadFile($temporaryFile, $identifier);
        unlink($temporaryFile);
        return $fileName;
    }

    /**
     * Copies a file *within* the current storage.
     * Note that this is only about an inner storage copy action,
     * where a file is just copied to another folder in the same storage.
     *
     * @param string $fileIdentifier
     * @param string $targetFolderIdentifier
     * @param string $fileName
     * @return string the Identifier of the new file
     */
    public function copyFileWithinStorage($fileIdentifier, $targetFolderIdentifier, $fileName)
    {
        $sourceIdentifier = $this->canonicalizeAndCheckFileIdentifier($this->rootPath . $fileIdentifier);
        /*
         * no need to sanitize the identifier since it has been either
         * sanitized by upload or rename
         */
        $newIdentifier = $targetFolderIdentifier . $fileName;
        $targetIdentifier = $this->canonicalizeAndCheckFileIdentifier($this->rootPath . $newIdentifier);
        $this->adapter->copy($sourceIdentifier, $targetIdentifier);
        return $newIdentifier;
    }

    /**
     * Renames a file in this storage.
     *
     * @param string $fileIdentifier
     * @param string $newName The target path (including the file name!)
     * @return string The identifier of the file after renaming
     */
    public function renameFile($fileIdentifier, $newName)
    {
        $folder = $this->canonicalizeAndCheckFolderIdentifier(PathUtility::dirname($fileIdentifier));

        $identifier = $this->canonicalizeAndCheckFileIdentifier($folder . $this->sanitizeFileName($newName));
        $oldIdentifier = $this->canonicalizeAndCheckFileIdentifier($this->rootPath . $fileIdentifier);
        $newIdentifier = $this->canonicalizeAndCheckFileIdentifier($this->rootPath . $identifier);
        $this->adapter->rename($oldIdentifier, $newIdentifier);
        return $identifier;
    }

    /**
     * Replaces a file with file in local file system.
     *
     * @param string $fileIdentifier
     * @param string $localFilePath
     * @return bool TRUE if the operation succeeded
     */
    public function replaceFile($fileIdentifier, $localFilePath)
    {
        \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump([__FUNCTION__, func_get_args()], __CLASS__ . '@' . __LINE__, 20);
        die;
    }

    /**
     * Removes a file from the filesystem. This does not check if the file is
     * still used or if it is a bad idea to delete it for some other reason
     * this has to be taken care of in the upper layers (e.g. the Storage)!
     *
     * @param string $fileIdentifier
     * @return bool TRUE if deleting the file succeeded
     */
    public function deleteFile($fileIdentifier)
    {
        $fileIdentifier = $this->canonicalizeAndCheckFileIdentifier($this->rootPath . $fileIdentifier);
        return $this->adapter->delete($fileIdentifier, false);
    }

    /**
     * Creates a hash for a file.
     *
     * @param string $fileIdentifier
     * @param string $hashAlgorithm The hash algorithm to use
     * @return string
     */
    public function hash($fileIdentifier, $hashAlgorithm)
    {
        $fileIdentifier = $this->canonicalizeAndCheckFileIdentifier($this->rootPath . $fileIdentifier);
        return $this->adapter->hash($fileIdentifier, $hashAlgorithm);
    }

    /**
     * Moves a file *within* the current storage.
     * Note that this is only about an inner-storage move action,
     * where a file is just moved to another folder in the same storage.
     *
     * @param string $fileIdentifier
     * @param string $targetFolderIdentifier
     * @param string $newFileName
     * @return string
     */
    public function moveFileWithinStorage($fileIdentifier, $targetFolderIdentifier, $newFileName)
    {
        \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump([__FUNCTION__, func_get_args()], __CLASS__ . '@' . __LINE__, 20);
        die;
    }

    /**
     * Folder equivalent to moveFileWithinStorage().
     *
     * @param string $sourceFolderIdentifier
     * @param string $targetFolderIdentifier
     * @param string $newFolderName
     * @return array All files which are affected, map of old => new file identifiers
     */
    public function moveFolderWithinStorage($sourceFolderIdentifier, $targetFolderIdentifier, $newFolderName)
    {
        \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump([__FUNCTION__, func_get_args()], __CLASS__ . '@' . __LINE__, 20);
        die;
    }

    /**
     * Folder equivalent to copyFileWithinStorage().
     *
     * @param string $sourceFolderIdentifier
     * @param string $targetFolderIdentifier
     * @param string $newFolderName
     * @return bool
     */
    public function copyFolderWithinStorage($sourceFolderIdentifier, $targetFolderIdentifier, $newFolderName)
    {
        \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump([__FUNCTION__, func_get_args()], __CLASS__ . '@' . __LINE__, 20);
        die;
    }

    /**
     * Returns the contents of a file. Beware that this requires to load the
     * complete file into memory and also may require fetching the file from an
     * external location. So this might be an expensive operation (both in terms
     * of processing resources and money) for large files.
     *
     * @param string $fileIdentifier
     * @return string The file contents
     */
    public function getFileContents($fileIdentifier)
    {
        $fileIdentifier = $this->canonicalizeAndCheckFileIdentifier($this->rootPath . $fileIdentifier);
        return $this->adapter->readFile($fileIdentifier);
    }

    /**
     * Sets the contents of a file to the specified value.
     *
     * @param string $fileIdentifier
     * @param string $contents
     * @return int The number of bytes written to the file
     */
    public function setFileContents($fileIdentifier, $contents)
    {
        $temporaryFile =
            GeneralUtility::tempnam(
                'fal-tempfile-',
                '.' . PathUtility::pathinfo($fileIdentifier, PATHINFO_EXTENSION)
            );
        $bytes = file_put_contents($temporaryFile, $contents);
        do {
            $temporaryIdentifier = $this->canonicalizeAndCheckFileIdentifier(
                $this->rootPath . 'fal-tempfile-' . str_replace('/', '_', $fileIdentifier) . mt_rand(1, PHP_INT_MAX)
            );
        } while ($this->adapter->exists($temporaryIdentifier));

        $this->adapter->uploadFile($temporaryFile, $temporaryIdentifier);
        unlink($temporaryFile);

        $fileIdentifier = $this->canonicalizeAndCheckFileIdentifier($this->rootPath . $fileIdentifier);
        $this->adapter->rename($temporaryIdentifier, $fileIdentifier);
        return $bytes;
    }

    /**
     * Checks if a file inside a folder exists
     *
     * @param string $fileName
     * @param string $folderIdentifier
     * @return bool
     */
    public function fileExistsInFolder($fileName, $folderIdentifier)
    {
        $identifier = $this->canonicalizeAndCheckFileIdentifier($this->rootPath . $folderIdentifier . $fileName);
        return $this->adapter->exists($identifier);
    }

    /**
     * Checks if a folder inside a folder exists.
     *
     * @param string $folderName
     * @param string $folderIdentifier
     * @return bool
     */
    public function folderExistsInFolder($folderName, $folderIdentifier)
    {
        $identifier = $this->canonicalizeAndCheckFolderIdentifier($this->rootPath . $folderIdentifier . $folderName);
        return $this->adapter->exists($identifier, AdapterInterface::TYPE_FOLDER);
    }

    /**
     * Returns a path to a local copy of a file for processing it. When changing the
     * file, you have to take care of replacing the current version yourself!
     *
     * @param string $fileIdentifier
     * @param bool $writable Set this to FALSE if you only need the file for read
     *                       operations. This might speed up things, e.g. by using
     *                       a cached local version. Never modify the file if you
     *                       have set this flag!
     * @return string The path to the file on the local disk
     */
    public function getFileForLocalProcessing($fileIdentifier, $writable = true)
    {
        $fileIdentifier = $this->canonicalizeAndCheckFileIdentifier($this->rootPath . $fileIdentifier);
        return $this->adapter->downloadFile(
            $fileIdentifier,
            GeneralUtility::tempnam(
                'fal-tempfile-' . ($writable ? 'w' : ''),
                '.' . PathUtility::pathinfo($fileIdentifier, PATHINFO_EXTENSION)
            )
        );
    }

    /**
     * Returns the permissions of a file/folder as an array
     * (keys r, w) of boolean flags
     *
     * @param string $identifier
     * @return array
     */
    public function getPermissions($identifier)
    {
        $identifier = $this->canonicalizeAndCheckFileIdentifier($this->rootPath . $identifier);
        return $this->adapter->getPermissions($identifier);
    }

    /**
     * Directly output the contents of the file to the output
     * buffer. Should not take care of header files or flushing
     * buffer before. Will be taken care of by the Storage.
     *
     * @param string $identifier
     * @return void
     */
    public function dumpFileContents($identifier)
    {
        $this->adapter->dumpFile($this->canonicalizeAndCheckFileIdentifier($this->rootPath . $identifier));
    }

    /**
     * Checks if a given identifier is within a container, e.g. if
     * a file or folder is within another folder.
     * This can e.g. be used to check for web-mounts.
     *
     * Hint: this also needs to return TRUE if the given identifier
     * matches the container identifier to allow access to the root
     * folder of a filemount.
     *
     * @param string $folderIdentifier
     * @param string $identifier identifier to be checked against $folderIdentifier
     * @return bool TRUE if $content is within or matches $folderIdentifier
     */
    public function isWithin($folderIdentifier, $identifier)
    {
        $folderIdentifier = $this->canonicalizeAndCheckFileIdentifier($folderIdentifier);
        $entryIdentifier = $this->canonicalizeAndCheckFileIdentifier($identifier);
        if ($folderIdentifier === $entryIdentifier) {
            return true;
        }
        // File identifier canonicalization will not modify a single slash so
        // we must not append another slash in that case.
        if ($folderIdentifier !== '/') {
            $folderIdentifier .= '/';
        }
        return GeneralUtility::isFirstPartOfStr($entryIdentifier, $folderIdentifier);
    }

    /**
     * Returns information about a file.
     *
     * @param string $fileIdentifier
     * @param array $propertiesToExtract Array of properties which are be extracted
     *                                   If empty all will be extracted
     * @return array
     */
    public function getFileInfoByIdentifier($fileIdentifier, array $propertiesToExtract = array())
    {
        $originalIdentifier = $fileIdentifier;
        $fileIdentifier = $this->canonicalizeAndCheckFileIdentifier($this->rootPath . $fileIdentifier);
        $details = $this->getDetails($fileIdentifier, $propertiesToExtract, $originalIdentifier);
        return $details;
    }

    /**
     * @param string $identifier
     * @param array $propertiesToExtract
     * @param string $originalIdentifier
     * @return array
     */
    protected function getDetails($identifier, array $propertiesToExtract, $originalIdentifier)
    {
        if (empty($propertiesToExtract)) {
            $propertiesToExtract = array(
                'size', 'atime', 'atime', 'mtime', 'ctime', 'mimetype', 'name',
                'identifier', 'identifier_hash', 'storage', 'folder_hash',
            );
        }
        $information = $this->adapter->getDetails($identifier);
        $information = $this->enrichInformation($information, $originalIdentifier);
        foreach (array_keys($information) as $property) {
            if (!in_array($property, $propertiesToExtract)) {
                unset($information[$property]);
            }
        }
        return $information;
    }

    /**
     * @param array $information
     * @param string $originalIdentifier
     * @return array
     */
    protected function enrichInformation(array $information, $originalIdentifier)
    {
        $information['name'] = PathUtility::basename($originalIdentifier);
        $information['identifier'] = $originalIdentifier;
        $information['storage'] = $this->storageUid;
        $information['identifier_hash'] = $this->hashIdentifier($originalIdentifier);
        $information['folder_hash'] = $this->hashIdentifier(
            $this->getParentFolderIdentifierOfIdentifier($originalIdentifier)
        );
        return $information;
    }

    /**
     * Returns information about a file.
     *
     * @param string $folderIdentifier
     * @return array
     */
    public function getFolderInfoByIdentifier($folderIdentifier)
    {
        return array(
            'identifier' => $folderIdentifier,
            'name' => PathUtility::basename($folderIdentifier),
            'storage' => $this->storageUid,
        );
    }

    /**
     * Returns the identifier of a file inside the folder
     *
     * @param string $fileName
     * @param string $folderIdentifier
     * @return string file identifier
     */
    public function getFileInFolder($fileName, $folderIdentifier)
    {
        return $this->canonicalizeAndCheckFileIdentifier($folderIdentifier . '/' . $fileName);
    }

    /**
     * Returns a list of files inside the specified path
     *
     * @param string $folderIdentifier
     * @param int $start
     * @param int $numberOfItems
     * @param bool $recursive
     * @param array $filenameFilterCallbacks callbacks for filtering the items
     * @param string $sort Property name used to sort the items.
     *                     Among them may be: '' (empty, no sorting), name,
     *                     fileext, size, tstamp and rw.
     *                     If a driver does not support the given property, it
     *                     should fall back to "name".
     * @param bool $sortRev TRUE to indicate reverse sorting (last to first)
     * @return array of FileIdentifiers
     */
    public function getFilesInFolder(
        $folderIdentifier,
        $start = 0,
        $numberOfItems = 0,
        $recursive = false,
        array $filenameFilterCallbacks = array(),
        $sort = '',
        $sortRev = false
    ) {
        $folderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($this->rootPath . $folderIdentifier);
        $items = $this->adapter->scanDirectory($folderIdentifier, true, false, $recursive);
        $items = $this->processResults($items, $sort, $sortRev, $start, $numberOfItems, $filenameFilterCallbacks);
        return $items;
    }

    /**
     * Returns the identifier of a folder inside the folder
     *
     * @param string $folderName The name of the target folder
     * @param string $folderIdentifier
     * @return string folder identifier
     */
    public function getFolderInFolder($folderName, $folderIdentifier)
    {
        \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump([__FUNCTION__, func_get_args()], __CLASS__ . '@' . __LINE__, 20);
        die;
    }

    /**
     * Returns a list of folders inside the specified path
     *
     * @param string $folderIdentifier
     * @param int $start
     * @param int $numberOfItems
     * @param bool $recursive
     * @param array $folderNameFilterCallbacks callbacks for filtering the items
     * @param string $sort Property name used to sort the items.
     *                     Among them may be: '' (empty, no sorting), name,
     *                     fileext, size, tstamp and rw.
     *                     If a driver does not support the given property, it
     *                     should fall back to "name".
     * @param bool $sortRev TRUE to indicate reverse sorting (last to first)
     * @return array of Folder Identifier
     */
    public function getFoldersInFolder(
        $folderIdentifier,
        $start = 0,
        $numberOfItems = 0,
        $recursive = false,
        array $folderNameFilterCallbacks = array(),
        $sort = '',
        $sortRev = false
    ) {
        $folderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($this->rootPath . $folderIdentifier);
        $items = $this->adapter->scanDirectory($folderIdentifier, false, true, $recursive);
        $items = $this->processResults($items, $sort, $sortRev, $start, $numberOfItems, $folderNameFilterCallbacks);
        return $items;
    }

    /**
     * @param array $items
     * @param string $sort
     * @param bool $sortRev
     * @param int $start
     * @param int $numberOfItems
     * @param array $filterCallbacks
     * @return array
     * @throws \Exception
     */
    protected function processResults(array $items, $sort, $sortRev, $start, $numberOfItems, array $filterCallbacks)
    {
        $items = $this->sortItems($items, $sort, $sortRev);
        $items = $this->omitItems($items, $start, $numberOfItems);
        $items = $this->filterItems($items, $filterCallbacks);
        foreach (array_keys($items) as $identifier) {
            $items[$identifier] = $identifier;
        }
        $items = $this->dropRootPaths($items);
        return $items;
    }

    /**
     * @param array $items
     * @return array
     */
    protected function dropRootPaths(array $items)
    {
        $newItems = [];
        foreach ($items as $identifier) {
            $identifier = substr($identifier, $this->rootPathLength);
            $newItems[$identifier] = $identifier;
        }
        return $newItems;
    }

    /**
     * @param array $items
     * @param string $sort
     * @param bool $sortRev
     * @return array
     * @throws \Exception
     */
    protected function sortItems(array $items, $sort, $sortRev)
    {
        switch ($sort) {
            case 'file':
                $callback = function ($left, $right) {
                    return strnatcasecmp($left['name'], $right['name']);
                };
                break;
            case '':
                $callback = function ($left, $right) {
                    return strnatcasecmp($left['identifier'], $right['identifier']);
                };
                break;
            default:
                throw new \Exception('TODO: \VerteXVaaR\FalSftp\Driver\SftpDriver::sortItems sort by ' . $sort);
        }
        uasort($items, $callback);
        if ($sortRev) {
            $items = array_reverse($items);
        }
        return $items;
    }

    /**
     * @param array $items
     * @param int $start
     * @param int $numberOfItems
     * @return array
     */
    protected function omitItems(array $items, $start, $numberOfItems)
    {
        if ($numberOfItems > 0) {
            $items = array_slice($items, $start, $numberOfItems);
        }
        return array_slice($items, $start);
    }

    /**
     * @param array $items
     * @param array $filterCallbacks
     * @return array
     */
    protected function filterItems(array $items, array $filterCallbacks)
    {
        foreach ($items as $identifier => $info) {
            foreach ($filterCallbacks as $filterCallback) {
                if (is_callable($filterCallback)) {
                    $result = call_user_func(
                        $filterCallback,
                        $info['name'],
                        $identifier,
                        PathUtility::dirname($identifier),
                        array(),
                        $this
                    );
                    if ($result === -1) {
                        unset($items[$identifier]);
                        break;
                    } elseif ($result === false) {
                        throw new \RuntimeException(
                            'Could not apply file/folder name filter ' . $filterCallback[0] . '::' . $filterCallback[1],
                            1447600899
                        );
                    }
                }
            }
        }
        return $items;
    }

    /**
     * Returns the number of files inside the specified path
     *
     * @param string $folderIdentifier
     * @param bool $recursive
     * @param array $filenameFilterCallbacks callbacks for filtering the items
     * @return int Number of files in folder
     */
    public function countFilesInFolder(
        $folderIdentifier,
        $recursive = false,
        array $filenameFilterCallbacks = array()
    ) {
        $folderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($this->rootPath . $folderIdentifier);
        $items = $this->adapter->scanDirectory($folderIdentifier, true, false, $recursive);
        $items = $this->filterItems($items, $filenameFilterCallbacks);
        return count($items);
    }

    /**
     * Returns the number of folders inside the specified path
     *
     * @param string $folderIdentifier
     * @param bool $recursive
     * @param array $folderNameFilterCallbacks callbacks for filtering the items
     * @return int Number of folders in folder
     */
    public function countFoldersInFolder(
        $folderIdentifier,
        $recursive = false,
        array $folderNameFilterCallbacks = array()
    ) {
        $folderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($this->rootPath . $folderIdentifier);
        $items = $this->adapter->scanDirectory($folderIdentifier, false, true, $recursive);
        $items = $this->filterItems($items, $folderNameFilterCallbacks);
        return count($items);
    }

    /**
     * Copied from LocalDriver
     *
     * @param string $fileName
     * @param string $charset
     * @return mixed|string
     * @throws InvalidFileNameException
     */
    public function sanitizeFileName($fileName, $charset = '')
    {
        // Handle UTF-8 characters
        if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['UTF8filesystem']) {
            // Allow ".", "-", 0-9, a-z, A-Z and everything beyond U+C0 (latin capital letter a with grave)
            $cleanFileName = preg_replace(
                '/[' . self::UNSAFE_FILENAME_CHARACTER_EXPRESSION . ']/u',
                '_',
                trim($fileName)
            );
        } else {
            // Define character set
            if (!$charset) {
                if (TYPO3_MODE === 'FE') {
                    $charset = $GLOBALS['TSFE']->renderCharset;
                } else {
                    // default for Backend
                    $charset = 'utf-8';
                }
            }
            // If a charset was found, convert fileName
            if ($charset) {
                $fileName = $this->getCharsetConversion()->specCharsToASCII($charset, $fileName);
            }
            // Replace unwanted characters by underscores
            $cleanFileName = preg_replace(
                '/[' . self::UNSAFE_FILENAME_CHARACTER_EXPRESSION . '\\xC0-\\xFF]/',
                '_',
                trim($fileName)
            );
        }
        // Strip trailing dots and return
        $cleanFileName = rtrim($cleanFileName, '.');
        if ($cleanFileName === '') {
            throw new InvalidFileNameException(
                'File name ' . $fileName . ' is invalid.',
                1320288991
            );
        }
        return $cleanFileName;
    }

    /**
     * Gets the charset conversion object.
     *
     * @return CharsetConverter
     */
    protected function getCharsetConversion()
    {
        if (!isset($this->charsetConversion)) {
            if (TYPO3_MODE === 'FE') {
                $this->charsetConversion = $GLOBALS['TSFE']->csConvObj;
            } elseif (is_object($GLOBALS['LANG'])) {
                // BE assumed:
                $this->charsetConversion = $GLOBALS['LANG']->csConvObj;
            } else {
                // The object may not exist yet, so we need to create it now. Happens in the Install Tool for example.
                $this->charsetConversion = GeneralUtility::makeInstance(CharsetConverter::class);
            }
        }
        return $this->charsetConversion;
    }

    /**
     * Copied from LocalDriver
     *
     * @param array $items
     * @param string $sourceIdentifier
     * @param string $targetIdentifier
     * @return array
     * @throws FileOperationErrorException
     */
    protected function createIdentifierMap(array $items, $sourceIdentifier, $targetIdentifier)
    {
        $identifierMap = array();
        $identifierMap[$sourceIdentifier] = $targetIdentifier;
        foreach ($items as $item) {
            $newIdentifier = substr($item['identifier'], $this->rootPathLength);
            $oldIdentifier = str_replace($targetIdentifier, $sourceIdentifier, $newIdentifier);
            if ($item['type'] == 'dir') {
                $newIdentifier = $this->canonicalizeAndCheckFolderIdentifier($newIdentifier);
            } elseif ($item['type'] == 'file') {
                $newIdentifier = $this->canonicalizeAndCheckFileIdentifier($newIdentifier);
            } else {
                continue;
            }
            $identifierMap[$oldIdentifier] = $newIdentifier;
        }
        return $identifierMap;
    }
}
