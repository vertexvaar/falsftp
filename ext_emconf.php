<?php

$EM_CONF[$_EXTKEY] = array(
    'title' => 'FAL SFTP Driver',
    'description' =>
        'Adds a Driver to your TYPO3 that lets '
        . 'you create and connect to a file storage accessible via SFTP',
    'category' => 'be',
    'constraints' => array(
        'depends' => array(
            'typo3' => '7.6.0-7.6.99',
        ),
        'conflicts' => array(),
        'suggests' => array(),
    ),
    'state' => 'stable',
    'uploadfolder' => false,
    'createDirs' => '',
    'clearcacheonload' => true,
    'author' => 'Oliver Eglseder',
    'author_email' => 'php@vxvr.de',
    'author_company' => 'vxvr.de',
    'version' => '1.0.0',
);
