<?php

/**
 * API.
 *
 * @package     block_configurable_reports
 * @copyright   2019 Mitxel Moriana @ 3iPunt Moodle Partner <mitxel@tresipunt.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_configurable_reports;

use Box\Spout\Writer\WriterFactory;
use core_php_time_limit;
use core_user;
use report_base;
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
        // Large exports are likely to take their time and memory.
        core_php_time_limit::raise();
        raise_memory_limit(MEMORY_EXTRA);

        $recipient = $validateddata->recipient;
        $subject = $validateddata->subject;
        $messagehtml = $validateddata->content['text'];
        $messagetext = html_to_text($messagehtml);
        $table = self::get_report_table($reportid);
        [$tmpdir, $filename] = self::generate_report_file($table, $validateddata->fileformat);
        $attachment = $tmpdir . DIRECTORY_SEPARATOR . $filename;
        $attachname = $filename;
        $recipientuser = self::get_dummy_user_record_with_email($recipient);
        $noreplyuser = core_user::get_noreply_user();

        $sent = email_to_user($recipientuser, $noreplyuser, $subject, $messagetext, $messagehtml, $attachment, $attachname);

        self::delete_generated_report_file($attachment, $tmpdir);

        return $sent;
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

    /**
     * @param $table
     * @param $fileformat
     * @return array
     *      string $tmpdir
     *      string $filename
     */
    private static function generate_report_file($table, $fileformat) {
        global $CFG;

        // Prepare writer and temporary file.
        $writer = WriterFactory::create($fileformat);
        do {
            $tmpdir = $CFG->tempdir . '/' . 'bcrreportgen_' . random_int(0, 9999999);
        } while (file_exists($tmpdir));
        check_dir_exists($tmpdir);
        $filename = 'report.' . $fileformat;
        $tmpfilepath = $tmpdir . '/' . $filename;

        $writer->openToFile($tmpfilepath);
        // Write the first row to the file (header).
        if (!empty($table->head)) {
            $addrow = [];
            foreach ($table->head as $key => $heading) {
                $addrow[] = str_replace("\n", ' ', htmlspecialchars_decode(strip_tags(nl2br($heading))));
            }
            $writer->addRow($addrow);
        }
        // Write the rest of the rows.
        if (!empty($table->data)) {
            foreach ($table->data as $rkey => $row) {
                $addrow = [];
                foreach ($row as $key => $item) {
                    $addrow[] = str_replace("\n", ' ', htmlspecialchars_decode(strip_tags(nl2br($item))));
                }
                $writer->addRow($addrow);
            }
        }
        $writer->close();

        return [$tmpdir, $filename];
    }

    /**
     * @param $reportid
     * @return stdClass
     */
    private static function get_report_table($reportid) {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/blocks/configurable_reports/locallib.php');
        require_once($CFG->dirroot . '/blocks/configurable_reports/report.class.php');
        $report = $DB->get_record('block_configurable_reports', ['id' => $reportid]);
        require_once($CFG->dirroot . '/blocks/configurable_reports/reports/' . $report->type . '/report.class.php');
        $reportclassname = 'report_' . $report->type;
        /** @var report_base $reportclass */
        $reportclass = new $reportclassname($report);
        $reportclass->create_report();

        return $reportclass->finalreport->table;
    }

    /**
     * @param string $attachment
     * @param string $tmpdir
     */
    private static function delete_generated_report_file($attachment, $tmpdir): void {
        @unlink($attachment);
        remove_dir($tmpdir);
    }
}
