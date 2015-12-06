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
        $generalFields = [];
        foreach (['hostname', 'port', 'username', 'folderMode', 'fileMode', SftpDriver::CONFIG_ROOT_LEVEL] as $fieldName) {
            $generalFields[] = '
                    <' . $fieldName. '>
                        <TCEforms>
                            <label>LLL:EXT:falsftp/Resources/Private/Language/locallang.xlf:flexform.general.' . $fieldName. '</label>
                            <config>
                                <type>input</type>
                                <size>33</size>
                                <eval>trim,required</eval>
                            </config>
                        </TCEforms>
                    </' . $fieldName. '>';
        }

        $flex = '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
<T3DataStructure>
    <sheets>
        <general>
            <ROOT>
                <TCEforms>
                    <sheetTitle>LLL:EXT:falsftp/Resources/Private/Language/locallang.xlf:flexform.general</sheetTitle>
                </TCEforms>
                <type>array</type>
                <el>
                    ' . implode(PHP_EOL, $generalFields) . '
                    <' . SftpDriver::CONFIG_PUBLIC_URL . '>
                        <TCEforms>
                            <label>LLL:EXT:falsftp/Resources/Private/Language/locallang.xlf:flexform.general.' . SftpDriver::CONFIG_PUBLIC_URL . '</label>
                            <config>
                                <type>input</type>
                                <size>33</size>
                                <eval>trim</eval>
                            </config>
                        </TCEforms>
                    </' . SftpDriver::CONFIG_PUBLIC_URL . '>

                    <' . SftpDriver::CONFIG_AUTHENTICATION_METHOD . '>
                        <TCEforms>
                            <label>LLL:EXT:falsftp/Resources/Private/Language/locallang.xlf:flexform.general.' . SftpDriver::CONFIG_AUTHENTICATION_METHOD . '</label>
                            <onChange>reload</onChange>
                            <config>
                                <type>select</type>
                                <items type="array">
                                    <numIndex index="0" type="array">
                                        <numIndex index="0">Please choose ...</numIndex>
                                        <numIndex index="1"></numIndex>
                                    </numIndex>
                                    <numIndex index="1" type="array">
                                        <numIndex index="0">Password</numIndex>
                                        <numIndex index="1">' . SftpDriver::AUTHENTICATION_PASSWORD . '</numIndex>
                                    </numIndex>
                                    <numIndex index="2" type="array">
                                        <numIndex index="0">Public Key</numIndex>
                                        <numIndex index="1">' . SftpDriver::AUTHENTICATION_PUBKEY . '</numIndex>
                                    </numIndex>
                                </items>
                                <eval>required</eval>
                            </config>
                        </TCEforms>
                    </' . SftpDriver::CONFIG_AUTHENTICATION_METHOD . '>

                    <' . SftpDriver::CONFIG_ADAPTER . '>
                        <TCEforms>
                            <label>LLL:EXT:falsftp/Resources/Private/Language/locallang.xlf:flexform.general.' . SftpDriver::CONFIG_ADAPTER . '</label>
                            <config>
                                <type>select</type>
                                <itemsProcFunc>VerteXVaaR\FalSftp\Environment\Detector->getItemsForAdapterSelection</itemsProcFunc>
                                <items type="array">
                                    <numIndex index="0" type="array">
                                        <numIndex index="0">Please choose ...</numIndex>
                                        <numIndex index="1"></numIndex>
                                    </numIndex>
                                </items>
                                <eval>required</eval>
                            </config>
                        </TCEforms>
                    </' . SftpDriver::CONFIG_ADAPTER . '>
                </el>
            </ROOT>
        </general>
        <password>
            <ROOT>
                <TCEforms>
                    <sheetTitle>LLL:EXT:falsftp/Resources/Private/Language/locallang.xlf:flexform.password</sheetTitle>
                    <displayCond>FIELD:general.authenticationMethod:=:' . SftpDriver::AUTHENTICATION_PASSWORD . '</displayCond>
                </TCEforms>
                <type>array</type>
                <el>
                    <password>
                        <TCEforms>
                            <label>LLL:EXT:falsftp/Resources/Private/Language/locallang.xlf:flexform.password.password</label>
                            <config>
                                <type>input</type>
                                <size>33</size>
                                <eval>password,required</eval>
                            </config>
                        </TCEforms>
                    </password>
                </el>
            </ROOT>
        </password>
        <pubkey>
            <ROOT>
                <TCEforms>
                    <sheetTitle>LLL:EXT:falsftp/Resources/Private/Language/locallang.xlf:flexform.pubkey</sheetTitle>
                    <displayCond>FIELD:general.authenticationMethod:=:' . SftpDriver::AUTHENTICATION_PUBKEY . '</displayCond>
                </TCEforms>
                <type>array</type>
                <el>
                    <publicKey>
                        <TCEforms>
                            <label>LLL:EXT:falsftp/Resources/Private/Language/locallang.xlf:flexform.pubkey.publicKey</label>
                            <config>
                                <type>input</type>
                                <size>33</size>
                                <eval>trim,required</eval>
                            </config>
                        </TCEforms>
                    </publicKey>
                    <privateKey>
                        <TCEforms>
                            <label>LLL:EXT:falsftp/Resources/Private/Language/locallang.xlf:flexform.pubkey.privateKey</label>
                            <config>
                                <type>input</type>
                                <size>33</size>
                                <eval>trim,required</eval>
                            </config>
                        </TCEforms>
                    </privateKey>
                    <privateKeyPassword>
                        <TCEforms>
                            <label>LLL:EXT:falsftp/Resources/Private/Language/locallang.xlf:flexform.pubkey.privateKeyPassword</label>
                            <config>
                                <type>input</type>
                                <size>33</size>
                                <eval>password</eval>
                            </config>
                        </TCEforms>
                    </privateKeyPassword>
                </el>
            </ROOT>
        </pubkey>
    </sheets>
</T3DataStructure>
';
        return str_replace(PHP_EOL, '', $flex);
    }
}
