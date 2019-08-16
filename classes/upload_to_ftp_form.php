<?php

namespace block_configurable_reports;

use Box\Spout\Common\Type;
use moodleform;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

class upload_to_ftp_form extends moodleform {
    public function definition() {
        $mform =& $this->_form;

        $textfieldoptions = ['maxlength' => '100', 'size' => '25', 'autocomplete' => 'off'];
        $mform->addElement('text', 'ftphost', get_string('ftphost', 'block_configurable_reports'),
            $textfieldoptions);
        $mform->setType('ftphost', PARAM_TEXT);
        $mform->addRule('ftphost', null, 'required');

        $mform->addElement('text', 'ftpport', get_string('ftpport', 'block_configurable_reports'),
            $textfieldoptions);
        $mform->setType('ftpport', PARAM_INT);
        $mform->addRule('ftpport', null, 'required');
        $mform->setDefault('ftpport', 21);

        $mform->addElement('text', 'ftpuser', get_string('ftpuser', 'block_configurable_reports'),
            $textfieldoptions);
        $mform->setType('ftpuser', PARAM_TEXT);
        $mform->addRule('ftpuser', null, 'required');

        $mform->addElement('password', 'ftppassword', get_string('ftppassword', 'block_configurable_reports'),
            $textfieldoptions);
        $mform->setType('ftppassword', PARAM_TEXT);
        $mform->addRule('ftppassword', null, 'required');

        $mform->addElement('text', 'ftpremotepath', get_string('ftpremotepath', 'block_configurable_reports'),
            $textfieldoptions);
        $mform->setType('ftpremotepath', PARAM_TEXT);
        $mform->addRule('ftpremotepath', null, 'required');

        $radioarray = [];
        $radioarray[] = $mform->createElement('radio', 'fileformat', '', Type::CSV, Type::CSV);
        $radioarray[] = $mform->createElement('radio', 'fileformat', '', Type::XLSX, Type::XLSX);
        $radioarray[] = $mform->createElement('radio', 'fileformat', '', Type::ODS, Type::ODS);
        $mform->addGroup($radioarray, 'radioar', get_string('fileformat', 'block_configurable_reports'), [' '], false);
        $mform->setDefault('fileformat', Type::CSV);

        $mform->setDisableShortforms();
        $this->add_action_buttons(false, get_string('email_send', 'block_configurable_reports'));
    }

    public function validation($data, $files) {
        $errors = [];
        if (!in_array($data['fileformat'], [Type::CSV, Type::XLSX, Type::ODS], true)) {
            $errors['fileformat'] = get_string('err_required', 'form');
        }

        return $errors;
    }
}
