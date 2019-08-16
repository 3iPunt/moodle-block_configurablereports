<?php

namespace block_configurable_reports;

use Box\Spout\Common\Type;
use moodleform;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

class send_email_form extends moodleform {
    public function definition() {
        $mform =& $this->_form;

        $mform->addElement('text', 'recipient', get_string('email_to', 'block_configurable_reports'),
            ['maxlength' => '100', 'size' => '25']);
        $mform->setType('recipient', PARAM_EMAIL);
        $mform->addRule('recipient', null, 'required');

        $mform->addElement('text', 'subject', get_string('email_subject', 'block_configurable_reports'),
            ['maxlength' => '100', 'size' => '25']);
        $mform->setType('subject', PARAM_TEXT);
        $mform->addRule('subject', null, 'required');

        $editoroptions = [
            'trusttext' => true,
            'subdirs' => true,
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'context' => $this->_customdata['context']
        ];
        $mform->addElement('editor', 'content', get_string('email_message', 'block_configurable_reports'), null, $editoroptions);

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
        if (!validate_email($data['recipient'])) {
            $errors['recipient'] = get_string('err_email', 'form');
        }
        if (!in_array($data['fileformat'], [Type::CSV, Type::XLSX, Type::ODS], true)) {
            $errors['fileformat'] = get_string('err_required', 'form');
        }

        return $errors;
    }
}
