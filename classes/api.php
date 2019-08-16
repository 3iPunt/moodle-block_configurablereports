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
use context_system;
use core_php_time_limit;
use core_user;
use moodle_exception;
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
        self::raise_system_resources_limits();

        $recipient = $validateddata->recipient;
        $subject = $validateddata->subject;
        $messagehtml = $validateddata->content['text'];
        $messagetext = html_to_text($messagehtml);

        $table = self::get_report_table($reportid);
        list($tmpdir, $filename) = self::generate_report_file($table, $validateddata->fileformat);
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
     * @param string $filepath Complete full path of the file to be deleted.
     * @param string $filedir Directory path of the temporary directory.
     */
    private static function delete_generated_report_file($filepath, $filedir) {
        @unlink($filepath);
        remove_dir($filedir);
    }

    public static function upload_report_to_ftp_server($reportid, $validateddata) {
        self::raise_system_resources_limits();

        $host = $validateddata->ftphost;
        $port = $validateddata->ftpport;
        $user = $validateddata->ftpuser;
        $password = $validateddata->ftppassword;
        $remotepath = $validateddata->ftpremotepath;

        $table = self::get_report_table($reportid);
        list($tmpdir, $filename) = self::generate_report_file($table, $validateddata->fileformat);
        $filepath = $tmpdir . DIRECTORY_SEPARATOR . $filename;
        $uploaded = self::upload_file_to_ftp_server($host, $port, $user, $password, $remotepath, $filepath);
        self::delete_generated_report_file($filepath, $tmpdir);

        return $uploaded;
    }

    /**
     * @param $host
     * @param $port
     * @param $user
     * @param $password
     * @param $remotepath
     * @param $localpath
     * @return bool
     */
    private static function upload_file_to_ftp_server($host, $port, $user, $password, $remotepath, $localpath) {
        if (!$connection = ftp_connect($host, $port)) {
            throw new moodle_exception('ftpconnectionerror', 'block_configurable_reports');
        }
        if (!$login = ftp_login($connection, $user, $password)) {
            ftp_close($connection);
            throw new moodle_exception('ftploginerror', 'block_configurable_reports');
        }
        $uploaded = ftp_put($connection, $remotepath, $localpath, FTP_BINARY);
        ftp_close($connection);

        return $uploaded;
    }

    private static function raise_system_resources_limits() {
        core_php_time_limit::raise();
        raise_memory_limit(MEMORY_EXTRA);
    }
}
