<?php

namespace block_configurable_reports;

use moodleform;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

class send_email_form extends moodleform {
    public function definition(): void {
        $mform =& $this->_form;

        $mform->addElement('hidden', 'usersids', $this->_customdata['usersids']);

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

        // TODO add date of sending

        // TODO add periodic sending options

        $mform->setDisableShortforms();
        $this->add_action_buttons(false, get_string('email_send', 'block_configurable_reports'));
    }
}
