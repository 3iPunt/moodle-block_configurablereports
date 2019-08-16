<?php

/**
 * API.
 *
 * @package     block_configurable_reports
 * @copyright   2019 Mitxel Moriana @ 3iPunt Moodle Partner <mitxel@tresipunt.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_configurable_reports;

use core_text;
use core_user;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * API class.
 *
 * @package     block_configurable_reports
 * @copyright   2019 Mitxel Moriana @ 3iPunt Moodle Partner <mitxel@tresipunt.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class api {
    public static function send_report_by_email($reportid, $validateddata) {
        $recipient = $validateddata->recipient;
        $subject = $validateddata->subject;
        $messagehtml = $validateddata->content['text'];
        $messagetext = html_to_text($messagehtml);
        $attachment = ''; // TODO generate files to be attached to the the email.
        $attachname = ''; // TODO generate file name for the attached file.

        $recipientuser = self::get_dummy_user_record_with_email($recipient);
        $noreplyuser = core_user::get_noreply_user();

        return email_to_user($recipientuser, $noreplyuser, $subject, $messagetext, $messagehtml, $attachment, $attachname);
    }

    /**
     * Helper function to return a dummy user record with a given email.
     *
     * @param string $email
     * @return stdClass
     */
    protected static function get_dummy_user_record_with_email($email) {
        return (object) [
            'email' => $email,
            'mailformat' => FORMAT_HTML,
            'id' => -30,
            'firstname' => '',
            'username' => '',
            'lastname' => '',
            'confirmed' => 1,
            'suspended' => 0,
            'deleted' => 0,
            'picture' => 0,
            'auth' => 'manual',
            'firstnamephonetic' => '',
            'lastnamephonetic' => '',
            'middlename' => '',
            'alternatename' => '',
            'imagealt' => '',
        ];
    }
}
