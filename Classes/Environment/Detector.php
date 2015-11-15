<?php
namespace VerteXVaaR\FalSftp\Environment;

use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use VerteXVaaR\FalSftp\Driver\SftpDriver;

/**
 * Class Detector
 */
class Detector
{
    const PHPSECLIB_SSH_CLASS = 'phpseclib\\Net\\SSH2';
    const PHPSSH_FUNCTION = 'ssh2_connect';

    /**
     * @return bool
     */
    public function isPhpseclibAvailable()
    {
        return class_exists(self::PHPSECLIB_SSH_CLASS);
    }

    /**
     * @return bool
     */
    public function isPhpSshAvailable()
    {
        return function_exists(self::PHPSSH_FUNCTION);
    }

    /**
     * @param array $params
     */
    public function getItemsForAdapterSelection(array $params)
    {
        if ($this->isPhpSshAvailable()) {
            $params['items'][SftpDriver::ADAPTER_PHPSSH] = [
                LocalizationUtility::translate('flexform.general.adapter.phpSshAdapter', 'falsftp'),
                SftpDriver::ADAPTER_PHPSSH,
            ];
        }
        if ($this->isPhpseclibAvailable()) {
            $params['items'][SftpDriver::ADAPTER_PHPSECLIB] = [
                LocalizationUtility::translate('flexform.general.adapter.phpseclibAdapter', 'falsftp'),
                SftpDriver::ADAPTER_PHPSECLIB,
            ];
        }
    }
}
