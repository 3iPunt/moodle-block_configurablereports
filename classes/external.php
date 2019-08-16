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
 * External API.
 *
 * @package     block_configurable_reports
 * @copyright   2019 Mitxel Moriana @ 3iPunt Moodle Partner <mitxel@tresipunt.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_configurable_reports;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");

use context;
use external_api;
use external_description;
use external_function_parameters;
use external_value;
use moodle_exception;

/**
 * External API class.
 *
 * @package     block_configurable_reports
 * @copyright   2019 Mitxel Moriana @ 3iPunt Moodle Partner <mitxel@tresipunt.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class external extends external_api {
    /**
     * Describes the input parameters.
     *
     * @return external_function_parameters
     */
    public static function send_report_by_email_parameters() {
        return new external_function_parameters([
            'contextid' => new external_value(PARAM_INT, 'The context id for the course'),
            'reportid' => new external_value(PARAM_INT, 'The report id'),
            'jsonformdata' => new external_value(PARAM_RAW, 'The data from the send email form, encoded as a json array')
        ]);
    }

    /**
     * Main method.
     *
     * @param int $contextid The context id for the course.
     * @param int $reportid The report id.
     * @param string $jsonformdata The data from the form, encoded as a json array.
     * @return int new group id.
     */
    public static function send_report_by_email($contextid, $reportid, $jsonformdata) {
        // We always must pass webservice params through validate_parameters.
        $params = self::validate_parameters(self::send_report_by_email_parameters(), [
            'contextid' => $contextid,
            'reportid' => $reportid,
            'jsonformdata' => $jsonformdata,
        ]);

        $context = context::instance_by_id($params['contextid']);

        // We always must call validate_context in a webservice.
        self::validate_context($context);

        require_capability('block/configurable_reports:viewreports', $context);

        $data = [];
        $serialiseddata = json_decode($params['jsonformdata'], true);
        parse_str($serialiseddata, $data);
        $data = (object) $data;

        $editoroptions = send_email_form::get_editor_options($context);
        $data = file_prepare_standard_editor($data, 'content', $editoroptions, $context, 'block_configurable_reports',
            'send_report_email_body');

        $mform = new send_email_form(null, ['context' => $context], 'post', '', null, true, (array) $data);

        $validateddata = $mform->get_data();
        if (!$validateddata) {
            throw new moodle_exception('errorvalidatingformdata', 'block_configurable_reports');
        }

        $sent = api::send_report_by_email($reportid, $validateddata);
        if (!$sent) {
            throw new moodle_exception('erroremailreport', 'block_configurable_reports');
        }

        return $params['reportid'];
    }

    /**
     * Describes the output parameters.
     *
     * @return external_description
     * @since Moodle 3.0
     */
    public static function send_report_by_email_returns() {
        return new external_value(PARAM_INT, 'report id');
    }

    /**
     * Describes the input parameters.
     *
     * @return external_function_parameters
     */
    public static function upload_report_to_ftp_parameters() {
        return new external_function_parameters([
            'contextid' => new external_value(PARAM_INT, 'The context id for the course'),
            'reportid' => new external_value(PARAM_INT, 'The report id'),
            'jsonformdata' => new external_value(PARAM_RAW, 'The data from the send email form, encoded as a json array')
        ]);
    }

    /**
     * Main method.
     *
     * @param int $contextid The context id for the course.
     * @param int $reportid The report id.
     * @param string $jsonformdata The data from the form, encoded as a json array.
     * @return int new group id.
     */
    public static function upload_report_to_ftp($contextid, $reportid, $jsonformdata) {
        // We always must pass webservice params through validate_parameters.
        $params = self::validate_parameters(self::upload_report_to_ftp_parameters(), [
            'contextid' => $contextid,
            'reportid' => $reportid,
            'jsonformdata' => $jsonformdata,
        ]);

        $context = context::instance_by_id($params['contextid']);

        // We always must call validate_context in a webservice.
        self::validate_context($context);

        require_capability('block/configurable_reports:viewreports', $context);

        $data = [];
        $serialiseddata = json_decode($params['jsonformdata'], true);
        parse_str($serialiseddata, $data);

        $mform = new upload_to_ftp_form(null, ['context' => $context], 'post', '', null, true, $data);

        $validateddata = $mform->get_data();
        if (!$validateddata) {
            throw new moodle_exception('errorvalidatingformdata', 'block_configurable_reports');
        }

        $uploaded = api::upload_report_to_ftp_server($reportid, $validateddata);
        if (!$uploaded) {
            throw new moodle_exception('ftpuploaderror', 'block_configurable_reports');
        }

        return $params['reportid'];
    }

    /**
     * Describes the output parameters.
     *
     * @return external_description
     * @since Moodle 3.0
     */
    public static function upload_report_to_ftp_returns() {
        return new external_value(PARAM_INT, 'report id');
    }
}
