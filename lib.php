<?php

/**
 * Functions and callbacks
 *
 * @package     block_configurable_reports
 * @copyright   2019 Mitxel Moriana @ 3iPunt Moodle Partner <mitxel@tresipunt.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_configurable_reports\send_email_form;

/**
 * This fragment servers the form to send reports via email.
 *
 * @param array|object $args List of named arguments for the fragment loader.
 * @return string
 */
function block_configurable_reports_output_fragment_send_email_form($args) {
    $args = (object) $args;
    $context = $args->context;
    $formdata = [];
    if (!empty($args->jsonformdata)) {
        $serialiseddata = json_decode($args->jsonformdata, true);
        parse_str($serialiseddata, $formdata);
    }
    $formdata = (object) $formdata;

    require_capability('block/configurable_reports:viewreports', $context);

    $editoroptions = [
        'context' => $context,
        'maxfiles' => EDITOR_UNLIMITED_FILES,
        'maxbytes' => FILE_AREA_MAX_BYTES_UNLIMITED,
        'trust' => false,
        'noclean' => true,
        'subdirs' => false
    ];
    $formdata = file_prepare_standard_editor($formdata, 'content', $editoroptions, $context, 'block_configurable_reports',
        'send_report_email_body');

    $mform = new send_email_form(null, ['context' => $context], 'post', '', null, true, (array) $formdata);
    if (!empty($serialiseddata)) {
        // Call validation functions and show errors if applies.
        $mform->is_validated();
    }

    return $mform->render();
}
