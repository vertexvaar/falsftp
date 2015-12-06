<?php
namespace VerteXVaaR\FalSftp\Adapter;

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

use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Class AbstractAdapter
 */
abstract class AbstractAdapter implements AdapterInterface
{
    /**
     * @param string $identifier
     * @param string $type
     * @return array
     */
    protected function getShortInfo($identifier, $type)
    {
        return [
            'identifier' => $identifier,
            'name' => PathUtility::basename($identifier),
            'type' => $type,
        ];
    }
}
