<?php //$Id: mod_form.php,v 1.2.2.3 2009/03/19 12:23:11 mudrd8mz Exp $

/**
 * This file defines the main videoannotation configuration form
 * It uses the standard core Moodle (>1.8) formslib. For
 * more info about them, please visit:
 *
 * http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * The form must provide support for, at least these fields:
 *   - name: text element of 64cc max
 *
 * Also, it's usual to use these fields:
 *   - intro: one htmlarea element to describe the activity
 *            (will be showed in the list of activities of
 *             videoannotation type (index.php) and in the header
 *             of the videoannotation main page (view.php).
 *   - introformat: The format used to write the contents
 *             of the intro field. It automatically defaults
 *             to HTML when the htmleditor is used and can be
 *             manually selected if the htmleditor is not used
 *             (standard formats are: MOODLE, HTML, PLAIN, MARKDOWN)
 *             See lib/weblib.php Constants and the format_text()
 *             function for more info
 */

require_once($CFG->dirroot.'/course/moodleform_mod.php');

class mod_videoannotation_mod_form extends moodleform_mod {

    function data_preprocessing(&$default_values){
        parent::data_preprocessing($default_values);
        global $DB; 
        // If clip select is "instructor picks clip", load that clip's info 
        // because it needs to appear in the add/update activity screen
        
        if (isset($default_values['clipselect']) and $default_values['clipselect'] == 1 and $clip = $DB->get_record('videoannotation_clips', array('videoannotationid'=>$default_values['id'], 'userid'=> null, 'groupid'=> null))) {
            $default_values['clipurl'] = $clip->url;
            $default_values['playabletimestart'] = $clip->playabletimestart;
            $default_values['playabletimeend'] = $clip->playabletimeend;
            $default_values['videowidth'] = $clip->videowidth;
            $default_values['videoheight'] = $clip->videoheight;
        }
        
        // For edit activity only:
        // The caller appears to try to load data from mdl_videoannotation table 
        // followed by mdl_course_modules table into $default_values
        // The problem is they both have a groupmode field, 
        // so $default_values['groupmode'] = mdl_course_modules.groupmode
        // We want $default_values['groupmode'] = mdl_videoannotation.groupmode
        
        if (isset($default_values['id'])) {
            $default_values['groupmode'] = $DB->get_field('videoannotation', 'groupmode', array('id' =>$default_values['id']));
        }
    }

    function definition() {
        global $CFG, $COURSE, $DB;
        $mform =& $this->_form;
        
    /// Adding the "general" fieldset, where all the common settings are showed
        $mform->addElement('header', 'general', get_string('general', 'form'));

    /// Adding the standard "name" field
        $mform->addElement('text', 'name', get_string('videoannotationname', 'videoannotation'), array('size'=>'64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

    /// Adding the required "intro" field to hold the description of the instance
        //DEPRECATED with upgrade to Moodle2.3
        /*$mform->addElement('htmleditor', 'intro', get_string('videoannotationintro', 'videoannotation'));
        $mform->setType('intro', PARAM_RAW);*/
        $this->add_intro_editor(true, get_string('videoannotationintro', 'videoannotation'));
        $mform->addHelpButton('introeditor', 'introeditor', 'videoannotation');
        
        // Clip header
        
        $mform->addElement('header', 'clip', get_string('clip', 'videoannotation'));
        
        // Clip source
        
        $mform->addElement('select', 'clipselect', get_string('clipselect', 'videoannotation'), array(get_string('studentspickownclips', 'videoannotation'), get_string('usespecifiedclip', 'videoannotation')));
		$mform->addRule('clipselect', null, 'required', null, 'client');
        $mform->setDefault('clipselect', 0);
        
		// Clip URL
        
        $mform->addElement('text', 'clipurl', get_string('clipurl', 'videoannotation'));
        $mform->setDefault('clipurl', '');
        $mform->disabledIf('clipurl', 'clipselect', 'eq', 0);
        $mform->setType('clipurl', PARAM_TEXT);
		
		// Playable start time
		
	$mform->addElement('text', 'playabletimestart', get_string('playabletimestart', 'videoannotation'));
        $mform->setDefault('playabletimestart', '0');
        $mform->disabledIf('playabletimestart', 'clipselect', 'eq', 0);
	$mform->setType('playabletimestart', PARAM_INT);	
		// Playable end time
		
		$mform->addElement('text', 'playabletimeend', get_string('playabletimeend', 'videoannotation'));
        $mform->setDefault('playabletimeend', '');
        $mform->disabledIf('playabletimeend', 'clipselect', 'eq', 0);
        $mform->setType('playabletimeend', PARAM_INT);	
		// Video width
		
		$mform->addElement('text', 'videowidth', get_string('videowidth', 'videoannotation'));
        $mform->setDefault('videowidth', '448');
        $mform->disabledIf('videowidth', 'clipselect', 'eq', 0);
        $mform->setType('videowidth', PARAM_INT);	
		// Video height
		
	$mform->addElement('text', 'videoheight', get_string('videoheight', 'videoannotation'));
        $mform->setDefault('videoheight', '336');
        $mform->disabledIf('videoheight', 'clipselect', 'eq', 0);
        $mform->setType('videoheight', PARAM_INT);	
        
        $mform->addElement('button', 'preview', get_string('preview', 'videoannotation'));
        $mform->disabledIf('preview', 'clipselect', 'eq', 0);

        $mform->addElement('html', '<div id="flashPlayerArea1"><div id="flashPlayerArea2"></div></div>');

        // Group header
        
        /*$mform->addElement('header', 'group', get_string('group', 'videoannotation'));
        
        // Group mode
        
        $groupmodes = array(
            NOGROUPS => get_string('groupmodeoff', 'videoannotation'), 
            VIDEOANNOTATION_GROUPMODE_USER_USER => get_string('groupmodeuseruser', 'videoannotation'), 
            VIDEOANNOTATION_GROUPMODE_GROUP_GROUP => get_string('groupmodegroupgroup', 'videoannotation'),
            VIDEOANNOTATION_GROUPMODE_GROUP_USER => get_string('groupmodegroupuser', 'videoannotation'),
            VIDEOANNOTATION_GROUPMODE_ALL_USER => get_string('groupmodealluser', 'videoannotation'),
            VIDEOANNOTATION_GROUPMODE_ALL_GROUP => get_string('groupmodeallgroup', 'videoannotation'),
            VIDEOANNOTATION_GROUPMODE_ALL_ALL => get_string('groupmodeallall', 'videoannotation'),
        );
        
        // Hide options that are not read for use yet
        
        unset($groupmodes[VIDEOANNOTATION_GROUPMODE_USER_USER]);
        unset($groupmodes[VIDEOANNOTATION_GROUPMODE_ALL_USER]);
        unset($groupmodes[VIDEOANNOTATION_GROUPMODE_ALL_GROUP]);
        unset($groupmodes[VIDEOANNOTATION_GROUPMODE_ALL_ALL]);
        

        //$isAdmin = has_capability('moodle/site:doanything', get_context_instance(CONTEXT_SYSTEM));
        $current_groupmode = $DB->get_field('videoannotation', 'groupmode', array('id' => $this->_instance));
       
       /******** Blocking out this section of code until the other groupmodes are ready *********** 
        if (!$isAdmin) {
            foreach ($groupmodes as $groupmode_key => $groupmode_value) {
                if (in_array($current_groupmode, array(VIDEOANNOTATION_GROUPMODE_USER_USER, VIDEOANNOTATION_GROUPMODE_GROUP_USER, VIDEOANNOTATION_GROUPMODE_ALL_USER))) {
                    if (!in_array($groupmode_key, array(VIDEOANNOTATION_GROUPMODE_USER_USER, VIDEOANNOTATION_GROUPMODE_GROUP_USER, VIDEOANNOTATION_GROUPMODE_ALL_USER)))
                        unset($groupmodes[$groupmode_key]);
                } else if (in_array($current_groupmode, array(VIDEOANNOTATION_GROUPMODE_GROUP_GROUP, VIDEOANNOTATION_GROUPMODE_ALL_GROUP, VIDEOANNOTATION_GROUPMODE_ALL_ALL))) {
                    if (!in_array($groupmode_key, array(VIDEOANNOTATION_GROUPMODE_GROUP_GROUP, VIDEOANNOTATION_GROUPMODE_ALL_GROUP, VIDEOANNOTATION_GROUPMODE_ALL_ALL)))
                        unset($groupmodes[$groupmode_key]);
                } else if ($current_groupmode != $groupmode_key)
                    unset($groupmodes[$groupmode_key]);
            }
        }
        ****************   End Block **************************************************************

        $mform->addElement('select', 'groupmode', get_string('groupmode', 'videoannotation'), $groupmodes);
        $mform->addRule('groupmode', null, 'required', null, 'client');
        $mform->setDefault('groupmode', 0);
        */
        //-------------------------------------------------------------------------------
        // add standard elements, common to all modules
        //Moodle 1.9 code, deprecated
        //$this->standard_coursemodule_elements(array('groups' => false, 'groupings' => true, 'groupmembersonly' => true));
        $this->_features->groups = true;
        $this->_features->groupings = true;
        $this->_features->groupmembersonly = true;
        $this->standard_coursemodule_elements();
        //-------------------------------------------------------------------------------

        // add standard buttons, common to all modules
        $this->add_action_buttons();

        // SSC-568,668,799
        // Insert JavaScript code for JWPlayer and the preview logic into the form
        
        $mform->addElement('html', '<script type="text/javascript" src="' . $CFG->wwwroot . '/mod/videoannotation/jquery-ui-1.8.2.custom/js/jquery-1.4.2.min.js"></script>');
        $mform->addElement('html', '<script type="text/javascript" src="' . $CFG->wwwroot . '/mod/videoannotation/jwplayer-6.6/jwplayer.js"></script>' . "\n");
        $mform->addElement('html', '<script type="text/javascript">wwwroot = "' . $CFG->wwwroot . '";</script>' . "\n");
        $mform->addElement('html', '<script type="text/javascript" src="' . $CFG->wwwroot . '/mod/videoannotation/mod_form_js.php"></script>' . "\n");
    }
    
    function validation($data, $files) {
        $errors = parent::validation($data, $files);
        
        if ($data['clipselect'] == 1) {
            if (empty($data['clipurl']))
                $errors['clipurl'] = get_string('err_required', 'form');
            if (!($data['playabletimestart'] < $data['playabletimeend']))
                $errors['playabletimestart'] = $errors['playabletimeend'] = get_string('playabletimestart', 'videoannotation') . ' ' . get_string('mustbelessthan', 'videoannotation') . ' ' . get_string('playabletimeend', 'videoannotation');
            if (!preg_match('/^\d+$/', round($data['playabletimestart'])))
                $errors['playabletimestart'] = get_string('validationpositivenum', 'videoannotation'); 
            if (!preg_match('/^\d+$/', round($data['playabletimeend'])))
                $errors['playabletimeend'] = get_string('validationpositivenum', 'videoannotation'); 
            if (!preg_match('/^\d+$/', round($data['videowidth'])))
                $errors['videowidth'] = get_string('validationpositiveint', 'videoannotation');
            if (!preg_match('/^\d+$/', round($data['videoheight'])))
                $errors['videoheight'] = get_string('validationpositiveint', 'videoannotation');
        }
        
        
        return $errors;
    }
}

?>
