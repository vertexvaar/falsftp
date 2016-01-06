TYPO3 FAL SFTP DRIVER
=====================

## What does it do?

This driver enables TYPO3 to connect to another server via ssh2 and use its filesystem.
After installation and configuration you will see another file storage in the Filelist module, where you can manage
your assets. The configured storage is available everywhere, where FAL is being used.
It can replace the good old fileadmin or used as secondary file storage.
Multiple storages can be configured.

## Features
* Support for native php ssh2 functions (ssh2_connect, ssh2_sftp_rename, stream wrapper "ssh2.sftp://" etc.)
* Support for phpseclib (https://github.com/phpseclib/phpseclib)
* Faster that the old fal_sftp version (which had caching!)
* Password authentication
* Pubkey authentication (not yet done)

## Installation

Install via composer:

```
composer require vertexvaar/falsftp
```

AND add following code to your LocalConfiguration.php into the SYS section

```
'fal' => [
    'registeredDrivers' => [
        'Sftp' => [
            'class' => 'VerteXVaaR\\FalSftp\\Driver\\SftpDriver',
            'flexFormDS' => 'FILE:EXT:falsftp/Configuration/FlexForm/DriverConfiguration.xml',
            'label' => 'SFTP Driver',
            'shortName' => 'Sftp',
        ],
    ],
],
```
