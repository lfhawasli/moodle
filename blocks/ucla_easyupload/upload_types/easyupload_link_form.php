<?php

defined('MOODLE_INTERNAL') || die();

class easyupload_link_form extends easy_upload_form {
    var $allow_renaming = true;
    var $allow_js_select = true;

    function specification() {
        $mform =& $this->_form;

        $mform->addElement('url', 'externalurl',
            get_string('dialog_add_link_box', self::associated_block), 
            array('size' => 60), array('usefilepicker' => false));
        $mform->setType('externalurl', PARAM_URL);

        $mform->addRule('externalurl', null, 'required');

        // Set default URL redirection.
        $mform->setDefault('display', get_config('url', 'display'));
    }

    function get_coursemodule() {
        return 'url';
    }
}
