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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;
use VerteXVaaR\FalSftp\Driver\SftpDriver;

/**
 * Class FlexBuilder
 */
class FlexBuilder
{
    /**
     * @return string
     */
    public function getFlexConfiguration()
    {
        $lllFile = 'LLL:EXT:falsftp/Resources/Private/Language/locallang.xlf:flexform.';
        $configuration = [
            'sheets' => [
                'general' => [
                    'ROOT' => [
                        'TCEforms' => [
                            'sheetTitle' => $lllFile . 'general',
                        ],
                        'el' => [
                            SftpDriver::CONFIG_HOSTNAME => [
                                'TCEforms' => [
                                    'label' => $lllFile . 'general.' . SftpDriver::CONFIG_HOSTNAME,
                                    'config' => [
                                        'type' => 'input',
                                        'size' => 33,
                                        'eval' => 'trim,required',
                                    ],
                                ],
                            ],
                            SftpDriver::CONFIG_PORT => [
                                'TCEforms' => [
                                    'label' => $lllFile . 'general.' . SftpDriver::CONFIG_PORT,
                                    'config' => [
                                        'type' => 'input',
                                        'size' => 33,
                                        'eval' => 'trim,required',
                                    ],
                                ],
                            ],
                            SftpDriver::CONFIG_USERNAME => [
                                'TCEforms' => [
                                    'label' => $lllFile . 'general.' . SftpDriver::CONFIG_USERNAME,
                                    'config' => [
                                        'type' => 'input',
                                        'size' => 33,
                                        'eval' => 'trim,required',
                                    ],
                                ],
                            ],
                            SftpDriver::CONFIG_FOLDER_MODE => [
                                'TCEforms' => [
                                    'label' => $lllFile . 'general.' . SftpDriver::CONFIG_FOLDER_MODE,
                                    'config' => [
                                        'type' => 'input',
                                        'size' => 33,
                                        'eval' => 'trim,required',
                                    ],
                                ],
                            ],
                            SftpDriver::CONFIG_FILE_MODE => [
                                'TCEforms' => [
                                    'label' => $lllFile . 'general.' . SftpDriver::CONFIG_FILE_MODE,
                                    'config' => [
                                        'type' => 'input',
                                        'size' => 33,
                                        'eval' => 'trim,required',
                                    ],
                                ],
                            ],
                            SftpDriver::CONFIG_ROOT_LEVEL => [
                                'TCEforms' => [
                                    'label' => $lllFile . 'general.' . SftpDriver::CONFIG_ROOT_LEVEL,
                                    'config' => [
                                        'type' => 'input',
                                        'size' => 33,
                                        'eval' => 'trim,required',
                                    ],
                                ],
                            ],
                            SftpDriver::CONFIG_PUBLIC_URL => [
                                'TCEforms' => [
                                    'label' => $lllFile . 'general.' . SftpDriver::CONFIG_PUBLIC_URL,
                                    'config' => [
                                        'type' => 'input',
                                        'size' => 33,
                                        'eval' => 'trim',
                                    ],
                                ],
                            ],
                            SftpDriver::CONFIG_AUTHENTICATION_METHOD => [
                                'TCEforms' => [
                                    'label' => $lllFile . 'general.' . SftpDriver::CONFIG_AUTHENTICATION_METHOD,
                                    'onChange' => 'reload',
                                    'config' => [
                                        'type' => 'select',
                                        'items' => [
                                            ['Please choose ...', ''],
                                            ['Password', SftpDriver::AUTHENTICATION_PASSWORD],
                                            ['Public Key', SftpDriver::AUTHENTICATION_PUBKEY],
                                        ],
                                        'eval' => 'required',
                                    ],
                                ],
                            ],
                            SftpDriver::CONFIG_ADAPTER => [
                                'TCEforms' => [
                                    'label' => $lllFile . 'general.' . SftpDriver::CONFIG_ADAPTER,
                                    'config' => [
                                        'type' => 'select',
                                        'itemsProcFunc' => Detector::class . '->getItemsForAdapterSelection',
                                        'items' => [
                                            ['Please choose ...', ''],
                                        ],
                                        'eval' => 'required',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'password' => [
                    'ROOT' => [
                        'TCEforms' => [
                            'sheetTitle' => $lllFile . 'password',
                            'displayCond' => 'FIELD:general.authenticationMethod:=:' . SftpDriver::AUTHENTICATION_PASSWORD,
                        ],
                        'el' => [
                            'password' => [
                                'TCEforms' => [
                                    'label' => $lllFile . 'password.password',
                                ],
                                'config' => [
                                    'type' => 'input',
                                    'size' => 33,
                                    'eval' => 'password,required',
                                ],
                            ],
                        ],
                    ],
                ],
                'pubkey' => [
                    'ROOT' => [
                        'TCEforms' => [
                            'sheetTitle' => $lllFile . 'pubkey',
                            'displayCond' => 'FIELD:general.authenticationMethod:=:'
                                             . SftpDriver::AUTHENTICATION_PUBKEY,
                        ],
                        'el' => [
                            'publicKey' => [
                                'TCEforms' => [
                                    'label' => $lllFile . 'pubkey.publicKey',
                                    'config' => [
                                        'type' => 'input',
                                        'size' => 33,
                                        'eval' => 'password',
                                    ],
                                ],
                            ],
                            'privateKey' => [
                                'TCEforms' => [
                                    'label' => $lllFile . 'pubkey.privateKey',
                                    'config' => [
                                        'type' => 'input',
                                        'size' => 33,
                                        'eval' => 'password',
                                    ],
                                ],
                            ],
                            'privateKeyPassword' => [
                                'TCEforms' => [
                                    'label' => $lllFile . 'pubkey.privateKeyPassword',
                                    'config' => [
                                        'type' => 'input',
                                        'size' => 33,
                                        'eval' => 'password',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        return GeneralUtility::array2xml($configuration, '', '', 'T3DataStructure');
    }
}
