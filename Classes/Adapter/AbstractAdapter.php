<?php
namespace VerteXVaaR\FalSftp\Adapter;

use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Class AbstractAdapter
 */
abstract class AbstractAdapter implements AdapterInterface
{
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
