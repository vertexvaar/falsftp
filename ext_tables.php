<?php

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
};

$bootFalSftp();
unset($bootFalSftp);
