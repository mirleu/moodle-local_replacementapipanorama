<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * YuJa Panorama Local Replacement API
 * @package    local_replacementapipanorama
 * @subpackage yuja
 * @copyright  2025 YuJa
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die('Must access from moodle');

$functions = [
    'local_replacementapipanorama_update_file' => [
        'classname'   => 'local_replacementapipanorama\external\panorama_update_file',
        'description' => 'Replaces the file at the given ID path with a new file..',
        'type'        => 'write',
        'ajax'        => true,
        'services' => [
            MOODLE_OFFICIAL_MOBILE_SERVICE,
        ],
        'capabilities' => 'moodle/site:config',
        'methodname'  => 'execute',
    ],
    'local_replacementapipanorama_update_html' => [
        'classname'   => 'local_replacementapipanorama\external\panorama_update_html',
        'description' => 'Replaces the html content at the given ID path.',
        'type'        => 'write',
        'ajax'        => true,
        'services' => [
            MOODLE_OFFICIAL_MOBILE_SERVICE,
        ],
        'capabilities' => 'moodle/site:config',
        'methodname'  => 'execute',
    ],
    'local_replacementapipanorama_update_attachment' => [
        'classname'   => 'local_replacementapipanorama\external\panorama_update_attachment',
        'description' => 'Replaces the content of a file attached to a resource.',
        'type'        => 'write',
        'ajax'        => true,
        'services' => [
            MOODLE_OFFICIAL_MOBILE_SERVICE,
        ],
        'capabilities' => 'moodle/site:config',
        'methodname'  => 'execute',
    ],
];
