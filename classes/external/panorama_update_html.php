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

namespace local_replacementapipanorama\external;

defined('MOODLE_INTERNAL') || die('Must access from moodle');

global $CFG;
require_once($CFG->dirroot. '/lib/externallib.php');
require_once($CFG->dirroot. '/local/replacementapipanorama/lib/common_helper_panorama.php');
use common_helper_panorama;
use external_function_parameters;
use external_single_structure;
use external_value;
use invalid_parameter_exception;
use external_format_value;
use external_util;
use context_module;
use context_system;

/**
 * Class for updating HTML Content in a Moodle resource, generally a page module
 * or an 'intro' section of a module.
 * Given a document ID, an HTML file is fetched from Panorama and its contents overwrite
 * the current module's HTML content.
 */
class panorama_update_html extends \external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'resourceid' => new external_value(PARAM_INT, 'Id of the resource we want to edit.'),
            'name' => new external_value(PARAM_TEXT, 'Updated name of the resource we want to edit.'),
            'documentid' => new external_value(PARAM_TEXT, 'Id of document containing new content'),
            'identifierkey' => new external_value(PARAM_TEXT, 'Identifier Key for Panorama'),
            'tablename' => new external_value(PARAM_TEXT, 'Name of the table of the resource we want to edit', VALUE_REQUIRED),
        ]);
    }

    /**
     * Returns description of method return value.
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new \external_single_structure([
            'status' => new external_value(PARAM_TEXT, 'Status of the operation'),
            'fileid' => new external_value(PARAM_INT, 'The ID of the replaced file'),
            'error' => new external_value(PARAM_TEXT, 'Error message if error exists'),
        ]);
    }

    /**
     * Updates page
     * @param int $resourceid resource id
     * @param string $name name to be updated
     * @param string $documentid id of document containing new content
     * @param string $identifierkey identifier key for panorama
     * @param string $tablename table of resource to be updated
     */
    public static function execute($resourceid, $name, $documentid, $identifierkey, $tablename) {
        global $DB;
        try {
            self::validate_context(context_system::instance());
            $params = self::validate_parameters(self::execute_parameters(),
                compact('resourceid', 'name', 'documentid', 'identifierkey', 'tablename'));

            if ((empty($params['resourceid']) || empty($params['documentid']) ||
                empty($params['identifierkey']) || empty($params['tablename']))) {
                throw new invalid_parameter_exception('Missing parameters:');
            }
            // Determine which field to edit.
            $contentfieldname;
            switch ($tablename) {
                case 'assign':
                case 'forum': // Maps to Announcement.
                case 'book':
                case 'data':
                case 'folder':
                case 'glossary':
                case 'label':
                case 'quiz':
                case 'survey':
                case 'wiki':
                case 'workshop':
                    $contentfieldname = 'intro';
                    break;
                case 'forum_posts': // Maps to Discussion Topic.
                case 'forum_discussions': // Maps to Discussion Topic.
                case 'discussion':
                case 'discussion-topic': // Maps to Forum Topic.
                    $contentfieldname = 'message';
                    break;
                case 'page':
                default:
                    $contentfieldname = 'content';
                    break;
            }

            $resource = $DB->get_record("$tablename", ['id' => $resourceid]);
            if (!$resource) {
                return [
                    'status' => 'failed',
                    'fileid' => 0,
                    'error' => get_string('invalidresourceid', 'local_replacementapipanorama'),
                ];
            }
            try {
                $signedurl = common_helper_panorama::get_signed_url($documentid, $identifierkey);
            } catch (\Exception $e) {
                return [
                    'status' => 'failed',
                    'fileid' => 0,
                    'error' => "Error occurred: " . $e->getMessage(),
                ];
            }
            try {
                // For page, this should be a string of the HTML content of the file.
                $existingcontent = $resource->{$contentfieldname};
                $contenttemp = file_get_contents($signedurl);
                $badtags = ['/<html.*>/', '/<\/html>/', '/<body.*>/', '/<\/body>/', '/<base.*>/'];
                $content = preg_replace($badtags, '', $contenttemp);
                if ($content === false) {
                    return [
                        'status' => 'failed',
                        'fileid' => 0,
                        'error' => get_string('failedtoretrievecontent', 'local_replacementapipanorama'),
                    ];
                }
                $resource->{$contentfieldname} = $content;
                $resource->revision++;
                $resource->timemodified = time();
                if (!$DB->update_record($tablename, $resource)) {
                    return [
                        'status' => 'failed',
                        'fileid' => 0,
                        'error' => get_string('failedtoupdate', 'local_replacementapipanorama'),
                    ];
                }
            } catch (\Exception $e) {
                return [
                    'status' => 'failed',
                    'fileid' => 0,
                    'error' => "Error occurred: " . $e->getMessage(),
                ];
            }
            return ['status' => 'success', 'fileid' => $resourceid, 'error' => "Table: $tablename, id: $resourceid"];
        } catch (\Exception $e) {
            return [
                'status' => 'failed',
                'fileid' => 0,
                'error' => "Error occurred: " . $e->getMessage(),
            ];
        }
    }
}
