<?php

/* Copyright (C) 2015 Oliver Eglseder <php@vxvr.de>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * For everyone: The driver registration ist not handled within the ext_tables.php
 * file because the driver would not be registered when the storage is not public.
 *
 * Technical information:
 * The called URL looks like index.php?eID=dumpFile&t=p&p=2&token=xxx
 * As you can see, it is an eID call. ext_tables.php is not loaded here.
 *
 */
$bootFalSftp = function () {
    $driverRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
        \TYPO3\CMS\Core\Resource\Driver\DriverRegistry::class
    );
    $flexBuilder = new \VerteXVaaR\FalSftp\Environment\FlexBuilder();
    if ($driverRegistry->registerDriverClass(
        \VerteXVaaR\FalSftp\Driver\SftpDriver::class,
        'Sftp',
        'SFTP Driver',
        $flexBuilder->getFlexConfiguration()
    )
    ) {
        $driverRegistry->addDriversToTCA();
    }
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['FileInfo']['fileExtensionToMimeType']['png'] = 'image/png';
};

$bootFalSftp();
unset($bootFalSftp);
