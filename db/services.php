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
 * Web service for Recently accessed items block
 *
 * @package     block_configurable_reports
 * @copyright   2019 Mitxel Moriana @ 3iPunt Moodle Partner <mitxel@tresipunt.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_configurable_reports\external;

defined('MOODLE_INTERNAL') || die();

$functions = [
    'block_configurable_reports_send_report_by_email' => [
        'classname' => external::class,
        'methodname' => 'send_report_by_email',
        'description' => 'Send a given report via email.',
        'type' => 'write',
        'ajax' => true,
    ],
    'block_configurable_reports_upload_report_to_ftp' => [
        'classname' => external::class,
        'methodname' => 'upload_report_to_ftp',
        'description' => 'Upload report to an FTP server.',
        'type' => 'write',
        'ajax' => true,
    ],
];
