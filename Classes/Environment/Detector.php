<?php
namespace VerteXVaaR\FalSftp\Environment;

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
