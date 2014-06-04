<?php

/**
 *  UCLA Control Panel content index.
 *  Try not add logic to this file, if your module requires additional logic
 *  it should be a new class in it of itself.
 **/
require_once(dirname(__FILE__) . '/ucla_cp_module.php');
require_once(dirname(__FILE__) . '/modules/ucla_cp_text_module.php');
require_once(dirname(__FILE__) . '/modules/ucla_cp_myucla_row_module.php');

global $CFG, $DB, $USER;
require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/local/ucla_syllabus/locallib.php');
require_once($CFG->dirroot . '/blocks/ucla_course_download/locallib.php');
// Please note that we should have
// $course - the course that we are currently on
// $context - the context of the course

/******************************** Common Functions *********************/
// Special section for special people
$temp_cap = 'moodle/course:manageactivities';
// Saving typing time
$temp_tag = array('ucla_cp_mod_common');

// The container for the special section
$modules[] = new ucla_cp_module('ucla_cp_mod_common', null, null, $temp_cap);

// Capability needed for things that TAs can also do
$ta_cap = 'moodle/course:viewparticipants';

// Course Forum Link
if (ucla_cp_module::load('email_students')) {
    $modules[] = new ucla_cp_module_email_students($course);
}

// For editing, it is a special UI case
$spec_ops = array('pre' => false, 'post' => true);

$modules[] = new ucla_cp_module('turn_editing_on', new moodle_url(
                        $CFG->wwwroot . '/course/view.php',
                        array('id' => $course->id, 'edit' => 'on', 'sesskey' => sesskey())),
                $temp_tag, $temp_cap, $spec_ops);

/******************************** MyUCLA Functions *********************/

$course_info = ucla_get_course_info($course->id);
// Do not display these courses if the user is not currently on a valid course page.
if (!empty($course_info)) {
    $temp_tag = array('ucla_cp_mod_myucla');

    $modules[] = new ucla_cp_module('ucla_cp_mod_myucla', null, null, $ta_cap);        
    
    // If this is a summer course
    $session = get_session_code($course_info[0]);

    // Add individual links for each crosslisted course
    foreach ($course_info as $info_for_one_course) {
        $myucla_row = new ucla_cp_myucla_row_module($temp_tag, $ta_cap);
        if (count($course_info) > 1) {
            // add course title if displaying cross-listed courses
            $myucla_row->add_element(new ucla_cp_text_module($info_for_one_course->subj_area
                             . $info_for_one_course->coursenum . '-' . $info_for_one_course->sectnum,
                             $temp_tag, $temp_cap));
        }

        $course_term = $info_for_one_course->term;
        $course_srs = $info_for_one_course->srs;
        $myucla_row->add_element(new ucla_cp_module('download_roster',
                        new moodle_url("https://be.my.ucla.edu/login/directLink.aspx?featureID=74&term="
                                . $course_term . "&srs=" . $course_srs), $temp_tag, $ta_cap));
        $myucla_row->add_element(new ucla_cp_module('photo_roster',
                        new moodle_url("https://be.my.ucla.edu/login/directLink.aspx?featureID=148&term="
                                . $course_term . "&srs=" . $course_srs . "&spp=30&sd=true"), $temp_tag, $ta_cap));
        $myucla_row->add_element(new ucla_cp_module('myucla_gradebook',
                        new moodle_url("https://be.my.ucla.edu/login/directLink.aspx?featureID=75&term="
                                . $course_term . "&srs=" . $course_srs), $temp_tag, $ta_cap));
        $myucla_row->add_element(new ucla_cp_module('turn_it_in',
                        new moodle_url("https://be.my.ucla.edu/login/directLink.aspx?featureID=48&term="
                                . $course_term . "&srs=" . $course_srs), $temp_tag, $ta_cap));
        $myucla_row->add_element(new ucla_cp_module('email_roster',
                        new moodle_url("https://be.my.ucla.edu/login/directLink.aspx?featureID=73&term="
                                . $course_term . "&srs=" . $course_srs), $temp_tag, $ta_cap));
        $myucla_row->add_element(new ucla_cp_module('asucla_textbooks',
                        new moodle_url('http://www.collegestore.org/textbookstore/main.asp?remote=1&ref=ucla&term='
                                . $course_term . $session . '&course=' . $course_srs . '&getbooks=Display+books'), $temp_tag, $ta_cap));
        $modules[] = $myucla_row;
    }
}
/******************************** Admin Functions *********************/
if(has_capability('tool/uclasupportconsole:view', context_system::instance()) &&
        !empty($course_info)) {
    $modules[] = new ucla_cp_module('ucla_cp_mod_admin_advanced', null, null, 'moodle/course:manageactivities');

    // Saving typing...again
    $temp_tag = array('ucla_cp_mod_admin_advanced');

    if (ucla_cp_module::load('run_prepop')) {
        $modules[] = new ucla_cp_module_run_prepop($course);
    }

    if (ucla_cp_module::load('push_grades')) {
        $modules[] = new ucla_cp_module_push_grades($course);
    }
    
    /////////////////////////////////////////////////////////////////
    // new way of approaching by using the code from myUCLA functions
    $temp_tag = array('ucla_cp_mod_admin_advanced');
    // Add individual links for each crosslisted course
    foreach ($course_info as $info_for_one_course) {
        $myucla_row = new ucla_cp_myucla_row_module($temp_tag);
        
        // add course title
        $myucla_row->add_element(new ucla_cp_text_module(
                ucla_make_course_title($info_for_one_course),
                        $temp_tag, $temp_cap));
        
        $course_term = $info_for_one_course->term;
        $course_srs = $info_for_one_course->srs;
        $link_arguments = array('console'=>'ccle_courseinstructorsget', 'term'=> $course_term, 'srs'=> $course_srs);
        $myucla_row->add_element(new ucla_cp_module('ccle_courseinstructorsget',
                                new moodle_url($CFG->wwwroot. '/admin/tool/uclasupportconsole/index.php',
                                $link_arguments, $temp_tag), $temp_tag));
        $link_arguments = array('console'=>'ccle_roster_class', 'term'=> $course_term, 'srs'=> $course_srs);
        $myucla_row->add_element(new ucla_cp_module('ccle_roster_class',
                                new moodle_url($CFG->wwwroot. '/admin/tool/uclasupportconsole/index.php',
                                $link_arguments, $temp_tag), $temp_tag));
        $modules[] = $myucla_row;
    }
}

/******************************** Other Functions *********************/
// Other Functions
$modules[] = new ucla_cp_module('ucla_cp_mod_other', null, null, $ta_cap);

// Saving typing...
$temp_tag = array('ucla_cp_mod_other');
//Redundency in case prev temp_cap isn't this one.
$temp_cap = 'moodle/course:update';
// Edit user profile!
$modules[] = new ucla_cp_module('edit_profile', new moodle_url(
                        $CFG->wwwroot . '/user/edit.php'), $temp_tag, 'moodle/user:editownprofile');

/* Import from classweb!? TODO
  $modules[] = new ucla_cp_module('import_classweb', new moodle_url('view.php'),
  $temp_tag, $temp_cap);
  /* Import from classweb */

// Import from existing moodle course
$modules[] = new ucla_cp_module('import_moodle', new moodle_url($CFG->wwwroot . 
        '/backup/import.php', array('id' => $course->id)), $temp_tag, 
        'moodle/restore:restoretargetimport');

/* Create a TA-Site TODO 
  $modules[] = new ucla_cp_module('create_tasite', new moodle_url('view.php'),
  $temp_tag, $ta_cap);
  /* Create a TA-Site */

// View moodle participants
$modules[] = new ucla_cp_module('view_roster', new moodle_url(
                        $CFG->wwwroot . '/user/index.php', array('id' => $course->id)),
                $temp_tag, 'moodle/course:viewparticipants');

// Site invitation
if (enrol_invitationenrol_available($course->id)) {
    $modules[] = new ucla_cp_module('invitation', new moodle_url(
                            $CFG->wwwroot . '/enrol/invitation/invitation.php', array('courseid' => $course->id)),
                    $temp_tag, 'enrol/invitation:enrol');    
}

// Syllabus tool
$syllabus_manager = new ucla_syllabus_manager($course);
if ($syllabus_manager->can_host_syllabi()) {
    $modules[] = new ucla_cp_module('manage_syllabus', new moodle_url(
            '/local/ucla_syllabus/index.php', array('id' => $course->id)),
            $temp_tag, 'local/ucla_syllabus:managesyllabus');
}

// Course Content Download
$modules[] = new ucla_cp_module('course_download', new moodle_url(
        '/blocks/ucla_course_download/view.php', array('courseid' => $course->id)),
        $temp_tag, 'block/ucla_course_download:requestzip');

/******************************** Advanced Functions *********************/
$modules[] = new ucla_cp_module('ucla_cp_mod_advanced', null, null, 'moodle/course:manageactivities');

// Saving typing...again
$temp_tag = array('ucla_cp_mod_advanced');

// Role assignments for particular courses.
if (ucla_cp_module::load('assign_roles')) {
    // This is when you want to make more than one module.
    $enrols = enrol_get_instances($course->id, true);
    $meta_links = array();
    foreach ($enrols as $enrolment) {
        if ($enrolment->enrol == 'meta') {
            $meta_links[] = $enrolment;
        }
    }

    // Load the course we are currently viewing.
    $modules[] = new ucla_cp_module_assign_roles($course, true);

    // Load any other courses we have linked.
    foreach ($meta_links as $meta_link) {
        $meta_course = $DB->get_record('course', array('id' =>
            $meta_link->customint1));

        $modules[] = new ucla_cp_module_assign_roles($meta_course);
    }
}

// Backup
$modules[] = new ucla_cp_module('backup_copy', new moodle_url(
                        $CFG->wwwroot . '/backup/backup.php', array('id' => $course->id)),
                $temp_tag, 'moodle/backup:backupcourse');


// Restore
$modules[] = new ucla_cp_module('backup_restore', new moodle_url(
                        $CFG->wwwroot . '/backup/restorefile.php', array('contextid' =>
                    $context->id)),
                $temp_tag, 'moodle/restore:restorecourse');

// Change course settings!
$modules[] = new ucla_cp_module('course_edit', new moodle_url(
                        $CFG->wwwroot . '/course/edit.php', array('id' => $course->id)),
                $temp_tag, 'moodle/course:update');

// This is for course files!
$modules[] = new ucla_cp_module('course_files', new moodle_url(
                        $CFG->wwwroot . '/files/coursefilesedit.php',
                        array('contextid' => $context->id)),
                $temp_tag, 'moodle/course:managefiles');

// Grade viewer
$modules[] = new ucla_cp_module('course_grades', new moodle_url(
                        $CFG->wwwroot . '/grade/index.php', array('id' => $course->id)),
                $temp_tag, 'gradereport/grader:view');

// Activity report (In M19 there was one link to get all reports, however in 
// M2 no such link exists anymore. The most used report is the activity report
// so that's why we are linking to it from control panel. Other report links
// can be found in the course administration section in the settings block. 
$modules[] = new ucla_cp_module('reports', 
        new moodle_url('/report/outline/index.php', array('id' => $course->id)), 
        $temp_tag, 'report/outline:view');

// Groups
$modules[] = new ucla_cp_module('groups',
        new moodle_url('/group/index.php', array('id' => $course->id)), 
        $temp_tag, 'moodle/course:managegroups');

// Quiz question bank
// Look at code from question/editlib.php: question_edit_setup lines 1606-1607 
// (as of Moodle 2.2.2) to determine if user has any ability to manage questions
require_once($CFG->libdir . '/questionlib.php');
$question_edit_contexts = new question_edit_contexts($context);
if ($question_edit_contexts->have_one_edit_tab_cap('questions')) {
    $modules[] = new ucla_cp_module('quiz_bank', 
            new moodle_url('/question/edit.php', array('courseid' => $course->id)), 
            $temp_tag);    
}

/* * ****************************** Student Functions ******************** */
//Only display this section if the user is a student in the course
//  or if a user "switches roles" to "student".
//TODO: this module currently depends on the myucla_row_renderer since that
//renderer opens links in new tabs (which the normal renderer does not normally
//do. If the control panel is to be refactored later, make this not terrible

$is_role_switched_student = false;

if (is_role_switched($course->id)) {
    $student_role_id = $DB->get_field('role', 'id', array('shortname' => 'student'), IGNORE_MISSING);
    $user_role_id = ($USER->access['rsw'][$context->path]);
    if ($user_role_id == $student_role_id) {
        $is_role_switched_student = true;
    }
}

if (has_role_in_context('student', $context) || $is_role_switched_student) {
    $temp_cap = null;
    $temp_tag = array('ucla_cp_mod_student');
    $modules[] = new ucla_cp_module('ucla_cp_mod_student', null, null, $temp_cap);


    $modules[] = new ucla_cp_module('edit_profile', new moodle_url(
            $CFG->wwwroot . '/user/edit.php'), $temp_tag, 'moodle/user:editownprofile');
    $modules[] = new ucla_cp_module('student_grades', new moodle_url(
            $CFG->wwwroot . '/grade/index.php?id=' . $course->id), $temp_tag, $temp_cap);

    if (student_zip_requestable()) {
        $modules[] = new ucla_cp_module('course_download', new moodle_url(
            '/blocks/ucla_course_download/view.php', array('courseid' => $course->id)),
            $temp_tag, 'block/ucla_course_download:requestzip');
    }

    if ($USER->auth != "shibboleth") {
        $modules[] = new ucla_cp_module('student_change_password', 
                new moodle_url($CFG->wwwroot . '/login/change_password.php?=' . $USER->id), $temp_tag, $temp_cap);
    }

    $course_info = ucla_get_course_info($course->id);
    //Do not display these courses if the user is not currently on a valid course page.
    if (!empty($course_info)) {
        //Add all course related links.
        $temp_tag = array('ucla_cp_mod_myucla');
        $temp_cap = null;
        $modules[] = new ucla_cp_module('ucla_cp_mod_myucla', null, null, $temp_cap);

        $myucla_row = new ucla_cp_myucla_row_module($temp_tag, $temp_cap);
        $tmp = array_values($course_info);
        $first_course = array_shift($tmp);
        $course_term = $first_course->term;
        $course_srs = $first_course->srs;
        $myucla_row->add_element(new ucla_cp_module('student_myucla_grades', 
                new moodle_url('https://be.my.ucla.edu/directLink.aspx?featureID=71&term=' . 
                        $course_term . '&srs=' . $course_srs), 'ucla_cp_mod_myucla', $temp_cap));
        $myucla_row->add_element(new ucla_cp_module('student_myucla_classmates', 
                new moodle_url('https://be.my.ucla.edu/directLink.aspx?featureID=72&term=' . 
                        $course_term . '&srs=' . $course_srs), 'ucla_cp_mod_myucla', $temp_cap));

        $session = get_session_code($first_course);

        $myucla_row->add_element(new ucla_cp_module('student_myucla_textbooks', 
                new moodle_url('http://www.collegestore.org/textbookstore/main.asp?remote=1&ref=ucla&term=' . 
                        $course_term . $session . '&course=' . $course_srs . 
                        '&getbooks=Display+books'), 'ucla_cp_mod_myucla', $temp_cap));

        $modules[] = $myucla_row;
    }
}
//Functions for features that haven't been implemented in moodle 2.0 yet.
/*   if (!is_collab($course->id)){
  }

  //START UCLA Modification
  //tumland - CCLE-1660 - added tab for user preferences
  //check to see if the user has any applicable preferences
  $userprefs = find_applicable_preferences($USER->id);
  if(!empty($userprefs)){
  //add list object if any preferences are found
  echo '<a href="'.$CFG->wwwroot.'/user/userprefs_editor/index.php?id='.$USER->id.'&course='.$course->id.'">'.get_string('cptitle','userprefs').'</a></dt>';
  print_string('cpdescription','userprefs');

  }

  if(!empty($CFG->block_course_menu_studentmanual_url)) {
  echo '<dd>'.get_string('studentmanualclick','block_course_menu').'</dd>';
  }
 */

/**
 * Is the site invitation enrollment plugin installed for course?
 * 
 * @param int $courseid
 * @return bool
 */
function enrol_invitationenrol_available($courseid) {
    $result = false;

    $enrolinstances = enrol_get_instances($courseid, true);
    foreach($enrolinstances as $instance) {
        if ($instance->enrol == 'invitation') {
            $result = true;
            break;
        }
    }

    return $result;
}

/**
 * Returns term session code for course, if any.
 * 
 * @global type $DB
 * @param object $first_course
 * @return string 
 */
function get_session_code($first_course) {
    global $DB;
    $session = '';
    if (is_summer_term($first_course->term)) {//summer course
        $session = $DB->get_field('ucla_reg_classinfo', 'session_group', array('term' => $first_course->term,
            'srs' => $first_course->srs));
    }
    return $session;
}
