<?php

defined('MOODLE_INTERNAL') || die();

class easyupload_subheading_form extends easy_upload_form {
    var $allow_js_select = true;

    function specification() {
        $mform =& $this->_form;
  
        $mform->addElement('editor', 'introeditor', get_string('dialog_add_subheading_box', self::associated_block),
                array('rows' => 3), array('maxfiles' => EDITOR_UNLIMITED_FILES,
                'noclean' => true, 'context' => $this->context, 'collapsed' => true));

        $mform->addRule('introeditor', null, 'required');
    }

    function get_coursemodule() {
        return 'label';
    }
}

// EoF
