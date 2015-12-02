<?php

/*
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
