# TYPO3 FAL SFTP DRIVER

## What does it do?

This driver enables TYPO3 to connect to another server via SSH and use the remote filesystem as it would be on the local server.
After installation and configuration you will see another file storage in the Filelist module, where you can manage your assets.
The configured storage is available everywhere, where FAL is being used.
It can replace the good old fileadmin or used as secondary file storage.
Multiple storages can be configured.

## Features
* Support for native php ssh2 functions (ssh2_connect, ssh2_sftp_rename, stream wrapper "ssh2.sftp://" etc.)
* Support for phpseclib (https://github.com/phpseclib/phpseclib)
* Faster than the old fal_sftp version (which had caching!)
* Password authentication
* PubKey authentication

## Installation

Install via composer:

```
composer require vertexvaar/falsftp
```

## Update information

If you update falsftp from 2.x to 3.x you will need to adjust your driver configuration.
There is no update script available, so it has to be done manually.
