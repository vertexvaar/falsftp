<?php

$bootFalSftp = function () {
    $driverRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
        \TYPO3\CMS\Core\Resource\Driver\DriverRegistry::class
    );
    if ($driverRegistry->registerDriverClass(
        \VerteXVaaR\FalSftp\Driver\SftpDriver::class,
        'sftp',
        'SFTP Driver',
        'FILE:EXT:falsftp/Configuration/Resource/Driver/SftpDriverFlexForm.xml'
    )) {
        $driverRegistry->addDriversToTCA();
    }
};

$bootFalSftp();
unset($bootFalSftp);
