<?php

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');

class add_tag_form extends moodleform {

    public function definition() {
        $mform = $this->_form;

        $mform->addElement('text', 'name', 'Name', array('size' => '10'));
        $mform->setType('name', PARAM_ALPHANUMEXT);
        $mform->addRule('name', 'A tag needs a name', 'required');
        
        $mform->addElement('text', 'color', 'Color (hex value)', array('size' => '6'));
        $mform->setType('color', PARAM_ALPHANUM);
        $mform->addRule('color', 'A tag needs a color', 'required'); 
        
        $this->add_action_buttons(false, 'Create tag');
    }
}
