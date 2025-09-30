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
 * @copyright  2024 YuJa
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die('Must access from moodle');
global $CFG;
require_once($CFG->dirroot. '/lib/externallib.php');
use external_settings;

/**
 * Helper functions for YuJa Panorama file replacement plugin.
 */
class common_helper_panorama {

    /**
     * Generates a random alphanumeric string of variable length.
     * @param mixed $length
     * @return mixed
     */
    public static function generate_random_string($length = 16) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $shuffled = str_shuffle($characters);
        return substr($shuffled, 0, $length);
    }

    /**
     * Finds and returns moodle course module ID.
     * @param mixed $modulename
     * @param mixed $id
     * @return mixed
     */
    public static function get_instance_with_course_module($modulename, $id) {
        global $DB;

        if (!core_component::is_valid_plugin_name('mod', $modulename)) {
            throw new coding_exception('Invalid modulename parameter');
        }

        $instance = $DB->get_record($modulename, ['id' => $id]);

        list($courses, $warnings) = external_util::validate_courses([$instance->course], []);

        list($coursessql, $params) = $DB->get_in_or_equal(array_keys($courses), SQL_PARAMS_NAMED, 'c0');
        $params['modulename'] = $modulename;
        $params['instanceid'] = $instance->id;

        $query = "SELECT cm.id AS coursemodule, m.*, cw.section, cm.visible AS visible, cm.groupmode, cm.groupingid ";
        $query .= "\n FROM {course_modules} cm, {course_sections} cw, {modules} md, {".$modulename."} m ";
        $query .= "\n WHERE cm.course $coursessql AND cm.instance = m.id AND cm.section = cw.id ";
        $query .= "AND md.name = :modulename AND md.id = cm.module AND m.id = :instanceid";
        $instance = $DB->get_records_sql($query, $params);
        $instance = reset($instance);

        return $instance;
    }

    /**
     * Rewrites urls to be reformatted to contain the @@PLUGINFILE@@ placeholder,
     * which is the expected format for URLs to be saved in the db.
     * Eg. <img src=\"@@PLUGINFILE@@/filename.jpg\" alt=\"h\" width=\"640\"
     *      height=\"640\" class=\"img-fluid atto_image_button_text-bottom\">
     * Moodle dynamically rewrites URLs when retrieving the data from the moodle API.
     * Before saving the activities in the db, this function should be called to rewrite the URL back to its original format
     * with the @@PLUGINFILE@@ placeholder.
     * @param mixed $text
     * @param mixed $contextid
     * @param mixed $component
     * @param mixed $filearea
     * @param mixed $itemid
     * @param mixed $filename
     * @return mixed
     */
    public static function reformat_urls_for_db_update($text, $contextid, $component, $filearea, $itemid = null, $filename = null) {
        if (is_null($filename)) {
            $settings = external_settings::get_instance();
            $filename = $settings->get_file();
        }
        $options = ['reverse' => true];
        $updatedtext = file_rewrite_pluginfile_urls($text, $filename, $contextid, $component, $filearea, $itemid, $options);
        return $updatedtext;
    }

    /**
     * Fetches the signed URL for a Panorama document, if one matches.
     * @param mixed $documentid
     * @param mixed $identifierkey
     * @return mixed
     */
    public static function get_signed_url($documentid, $identifierkey) {
        $serverurl = self::get_server_url();
        $url = self::get_server_url() . '/api/downloadFormatUrl';
        $data = [
            'documentId' => $documentid,
            'identifierKey' => $identifierkey,
            'format' => 'source',
            'language' => 'default',
            'excludeResponseHeaders' => true,
        ];
        $options = [
            'http' => [
                'header'  => "Content-type: application/json\r\n",
                'method'  => 'POST',
                'content' => json_encode($data),
            ],
        ];
        $context = stream_context_create($options);
        $response = file_get_contents($url, false, $context);
        if ($response === false) {
            throw new \Exception('Failed to get signed URL from API.');
        }
        return trim($response);
    }

    /**
     * Finds the correct regional server URL for connecting to Panorama.
     * @return mixed
     */
    public static function get_server_url() {
        $config = get_config('panorama');
        $serverurl = 'UNKNOWN';

        switch ($config->environment) {
            case "Staging":
                $serverurl = "https://staging-panorama-api.yuja.com";
                break;
            case "Production US":
                $serverurl = "https://panorama-api.yuja.com";
                break;
            case "Production CA":
                $serverurl = "https://panorama-api-cz.yuja.com";
                break;
            case "Production EU":
                $serverurl = "https://panorama-api-ez.yuja.com";
                break;
            case "Production AZ":
                $serverurl = "https://panorama-api-az.yuja.com";
                break;
        }

        return $serverurl;
    }

}
