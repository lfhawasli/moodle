<?php

///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
// Moodle - Modular Object-Oriented Dynamic Learning Environment         //
//          http://moodle.org                                            //
//                                                                       //
// Copyright (C) 1999 onwards Martin Dougiamas  http://dougiamas.com     //
//                                                                       //
// This program is free software; you can redistribute it and/or modify  //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation; either version 2 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// This program is distributed in the hope that it will be useful,       //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details:                          //
//                                                                       //
//          http://www.gnu.org/copyleft/gpl.html                         //
//                                                                       //
///////////////////////////////////////////////////////////////////////////

/**
 * Forms associated with requesting courses, and having requests approved.
 * Note that several related forms are defined in this one file.
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package course
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->dirroot . '/admin/tool/uclasiteindicator/lib.php');
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/coursecatlib.php');

/**
 * A form for a user to request a course.
 */
class course_request_form extends moodleform {
    function definition() {
        global $CFG, $DB, $USER;

        $mform =& $this->_form;
        
        // START UCLAMOD CCLE-2389
//        // Do not show pending course requests to unauthorized viewers
//        $context = context_system::instance();
//        if(has_capability('tool/uclasiteindicator:view', $context)) {
//
//            if ($pending = $DB->get_records('course_request', array('requester' => $USER->id))) {
//                $mform->addElement('header', 'pendinglist', get_string('coursespending', 'tool_uclasiteindicator'));
//                $list = array();
//                foreach ($pending as $cp) {
//                    $list[] = format_string($cp->fullname);
//                }
//                $list = implode(', ', $list);
//                $mform->addElement('static', 'pendingcourses', get_string('courses'), $list);
//            }
//        }
        
        // Build the indicator types and display radio option group
        $mform->addElement('header','siteindicator', get_string('req_desc', 'tool_uclasiteindicator'));
        $types = siteindicator_manager::get_types_list();
        $radioarray = array();
        foreach($types as $type) {
            // START UCLA MOD: CCLE-3879 - Option for "TA site" visible on Collab Site Request
            // Suppresses "TA site" option, as it shouldn't be chosen manually 
            if (siteindicator_manager::SITE_TYPE_TASITE == $type['shortname']) {
                continue;
            }
            // END ULCA MOD: CCLE-3879
            $descstring = '<strong>' . $type['fullname'] . '</strong> - ' . $type['description'];
            $attributes = array(
                'class' => 'indicator-form',
                'value' => $type['shortname']
            );
            $radioarray[] = $mform->createElement('radio', 'indicator_type', '', $descstring, $type['shortname'], $attributes);
        }
        
        $mform->addGroup($radioarray, 'indicator_type_radios', get_string('req_type', 'tool_uclasiteindicator'), array('<br/>'), false);
        $mform->addRule('indicator_type_radios', get_string('req_type_error', 'tool_uclasiteindicator'), 'required');
        $mform->addHelpButton('indicator_type_radios', 'req_type', 'tool_uclasiteindicator');

        $displaylist = siteindicator_manager::get_categories_list();
        $mform->addElement('select', 'indicator_category', get_string('req_category', 'tool_uclasiteindicator'), $displaylist);
        $mform->addHelpButton('indicator_category', 'req_category', 'tool_uclasiteindicator');
        $mform->addRule('indicator_category', get_string('req_category_error', 'tool_uclasiteindicator'), 'required', null, 'client');

        // Overriding lang strings
        $mform->addElement('header','coursedetails', get_string('courserequestdetails', 'tool_uclasiteindicator'));

        $mform->addElement('text', 'fullname', get_string('fullnamecourse', 'tool_uclasiteindicator'), 'maxlength="254" size="50"');
        $mform->addHelpButton('fullname', 'fullnamecourse', 'tool_uclasiteindicator');
        $mform->addRule('fullname', get_string('missingfullname'), 'required', null, 'client');
        $mform->setType('fullname', PARAM_TEXT);

        $mform->addElement('text', 'shortname', get_string('shortnamecourse', 'tool_uclasiteindicator'), 'maxlength="100" size="20"');
        $mform->addHelpButton('shortname', 'shortnamecourse', 'tool_uclasiteindicator');
        $mform->addRule('shortname', get_string('missingshortname'), 'required', null, 'client');
        $mform->setType('shortname', PARAM_TEXT);

        if (!empty($CFG->requestcategoryselection)) {
            $displaylist = coursecat::make_categories_list();
            $mform->addElement('select', 'category', get_string('coursecategory'), $displaylist);
            $mform->setDefault('category', $CFG->defaultrequestcategory);
            $mform->addHelpButton('category', 'coursecategory');
        }

        $mform->addElement('editor', 'summary_editor', get_string('summary'), null, course_request::summary_editor_options());
        $mform->addHelpButton('summary_editor', 'coursesummary', 'tool_uclasiteindicator');
        $mform->setType('summary_editor', PARAM_RAW);

        $mform->addElement('header','requestreason', get_string('courserequestreason', 'tool_uclasiteindicator'));

        // changing default size of 'reason' textbox
        $mform->addElement('textarea', 'reason', get_string('courserequestsupport'), array('rows'=>'5', 'cols'=>'50'));
        $mform->addRule('reason', get_string('missingreqreason'), 'required', null, 'client');
        $mform->setType('reason', PARAM_TEXT);

        // Override submit button lang string
        $this->add_action_buttons(true, get_string('requestcourse', 'tool_uclasiteindicator'));
        // END UCLA MOD CCLE-2389

    }

    function validation($data, $files) {
        global $DB;

        $errors = parent::validation($data, $files);
        $foundcourses = null;
        $foundreqcourses = null;

        if (!empty($data['shortname'])) {
            $foundcourses = $DB->get_records('course', array('shortname'=>$data['shortname']));
            $foundreqcourses = $DB->get_records('course_request', array('shortname'=>$data['shortname']));
        }
        if (!empty($foundreqcourses)) {
            if (!empty($foundcourses)) {
                $foundcourses = array_merge($foundcourses, $foundreqcourses);
            } else {
                $foundcourses = $foundreqcourses;
            }
        }

        if (!empty($foundcourses)) {
            foreach ($foundcourses as $foundcourse) {
                if (!empty($foundcourse->requester)) {
                    $pending = 1;
                    $foundcoursenames[] = $foundcourse->fullname.' [*]';
                } else {
                    $foundcoursenames[] = $foundcourse->fullname;
                }
            }
            $foundcoursenamestring = implode(',', $foundcoursenames);

            $errors['shortname'] = get_string('shortnametaken', '', $foundcoursenamestring);
            if (!empty($pending)) {
                $errors['shortname'] .= get_string('starpending');
            }
        }

//        if (empty($data->indicator_category)) {
//            $errors['indicator_category'] = get_string($foundcoursenames)
//        }
        
        return $errors;
    }
}

/**
 * A form for an administrator to reject a course request.
 */
class reject_request_form extends moodleform {
    function definition() {
        $mform =& $this->_form;

        $mform->addElement('hidden', 'reject', 0);
        $mform->setType('reject', PARAM_INT);

        // START UCLA MOD CCLE-2389 - modify reject form to reject without emailing user
        $mform->addElement('header','coursedetails', get_string('coursereasonforrejecting', 'tool_uclasiteindicator'));
        $mform->addElement('selectyesno', 'email', get_string('reject_yesno', 'tool_uclasiteindicator'));
        $mform->addElement('textarea', 'rejectnotice', get_string('coursereasonforrejectingemail'), array('rows'=>'5', 'cols'=>'50'));
        $mform->disabledIf('rejectnotice', 'email', 0);
        $mform->setType('rejectnotice', PARAM_TEXT);
        // END UCLA MOD CCLE-2389
        $this->add_action_buttons(true, get_string('reject'));
    }
}
