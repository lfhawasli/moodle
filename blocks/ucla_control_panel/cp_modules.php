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
 * UCLA Control Panel content index.
 *
 * Try not to add logic to this file, if your module requires additional logic
 * it should be a new class in and of itself
 * @package block_ucla_control_panel
 * @copyright  UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License
 **/

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/ucla_cp_module.php');
require_once(dirname(__FILE__) . '/modules/ucla_cp_text_module.php');
require_once(dirname(__FILE__) . '/modules/course_download.php');
require_once(dirname(__FILE__) . '/modules/ucla_cp_myucla_row_module.php');

global $CFG, $DB, $USER;
require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/local/ucla_syllabus/locallib.php');
require_once($CFG->dirroot . '/blocks/ucla_course_download/locallib.php');
// Please note that we should have
// $course - the course that we are currently on
// $context - the context of the course.

// Common Functions.
// Special section for special people.
$tempcap = 'moodle/course:manageactivities';
// Saving typing time.
$temptag = array('ucla_cp_mod_common');

// The container for the special section.
$modules[] = new ucla_cp_module('ucla_cp_mod_common', null, null, $tempcap);

// Capability needed for things that TAs can also do.
$tacap = 'moodle/course:viewparticipants';

// Course Forum Link.
if (ucla_cp_module::load('email_students')) {
    $modules[] = new ucla_cp_module_email_students($course);
}

// Site invitation.
if (enrol_invitationenrol_available($course->id)) {
    $modules[] = new ucla_cp_module('invitation', new moodle_url(
                $CFG->wwwroot . '/enrol/invitation/invitation.php', array('courseid' => $course->id)),
                $temptag, 'enrol/invitation:enrol');
}

// For editing, it is a special UI case.
$specops = array('pre' => false, 'post' => true);

$modules[] = new ucla_cp_module('turn_editing_on', new moodle_url(
                        $CFG->wwwroot . '/course/view.php',
                        array('id' => $course->id, 'edit' => 'on', 'sesskey' => sesskey())),
                $temptag, $tempcap, $specops);


// Turnitin V2 Assignment (Instructor).
$modules[] = new ucla_cp_module('turnitin', new moodle_url(
                        $CFG->wwwroot . '/course/modedit.php', array('add' => 'turnitintooltwo', 'course' => $course->id,
                            'section' => 0)),
                $temptag, 'mod/turnitintooltwo:addinstance');


/******************************** MyUCLA Functions *********************/

$courseinfo = ucla_get_course_info($course->id);
// Do not display these courses if the user is not currently on a valid course page.
if (!empty($courseinfo)) {
    $temptag = array('ucla_cp_mod_myucla');

    $modules[] = new ucla_cp_module('ucla_cp_mod_myucla', null, null, $tacap);

    // If this is a summer course...
    $session = get_session_code($courseinfo[0]);

    // Add individual links for each crosslisted course.
    foreach ($courseinfo as $infoforonecourse) {
        $myuclarow = new ucla_cp_myucla_row_module($temptag, $tacap);
        if (count($courseinfo) > 1) {
            // Add course title if displaying cross-listed courses.
            $myuclarow->add_element(new ucla_cp_text_module($infoforonecourse->subj_area
                             . $infoforonecourse->coursenum . '-' . $infoforonecourse->sectnum,
                             $temptag, $tempcap));
        }

        $courseterm = $infoforonecourse->term;
        $coursesrs = $infoforonecourse->srs;
        $myuclarow->add_element(new ucla_cp_module('download_roster',
                        new moodle_url("https://be.my.ucla.edu/login/directLink.aspx?featureID=74&term="
                                . $courseterm . "&srs=" . $coursesrs), $temptag, $tacap));
        $myuclarow->add_element(new ucla_cp_module('photo_roster',
                        new moodle_url("https://be.my.ucla.edu/login/directLink.aspx?featureID=148&term="
                                . $courseterm . "&srs=" . $coursesrs . "&spp=30&sd=true"), $temptag, $tacap));
        $myuclarow->add_element(new ucla_cp_module('myucla_gradebook',
                        new moodle_url("https://be.my.ucla.edu/login/directLink.aspx?featureID=75&term="
                                . $courseterm . "&srs=" . $coursesrs), $temptag, $tacap));
        $myuclarow->add_element(new ucla_cp_module('email_roster',
                        new moodle_url("https://be.my.ucla.edu/login/directLink.aspx?featureID=73&term="
                                . $courseterm . "&srs=" . $coursesrs), $temptag, $tacap));
        $myuclarow->add_element(new ucla_cp_module('asucla_textbooks',
                        new moodle_url('http://www.collegestore.org/textbookstore/main.asp?remote=1&ref=ucla&term='
                                . $courseterm . $session . '&course=' . $coursesrs . '&getbooks=Display+books'), $temptag, $tacap));
        $modules[] = $myuclarow;
    }
}
/******************************** Admin Functions *********************/
if (has_capability('tool/uclasupportconsole:view', context_system::instance()) &&
        !empty($courseinfo)) {
    $modules[] = new ucla_cp_module('ucla_cp_mod_admin_advanced', null, null, 'moodle/course:manageactivities');

    // Saving typing...again.
    $temptag = array('ucla_cp_mod_admin_advanced');

    if (ucla_cp_module::load('run_prepop')) {
        $modules[] = new ucla_cp_module_run_prepop($course);
    }

    if (ucla_cp_module::load('push_grades')) {
        $modules[] = new ucla_cp_module_push_grades($course);
    }

    // New way of approaching by using the code from myUCLA functions.
    $temptag = array('ucla_cp_mod_admin_advanced');
    // Add individual links for each crosslisted course.
    foreach ($courseinfo as $infoforonecourse) {
        $myuclarow = new ucla_cp_myucla_row_module($temptag);

        // Add course title.
        $myuclarow->add_element(new ucla_cp_text_module(
                ucla_make_course_title($infoforonecourse),
                        $temptag, $tempcap));

        $courseterm = $infoforonecourse->term;
        $coursesrs = $infoforonecourse->srs;
        $linkarguments = array('console' => 'ccle_courseinstructorsget', 'term' => $courseterm, 'srs' => $coursesrs);
        $myuclarow->add_element(new ucla_cp_module('ccle_courseinstructorsget',
                                new moodle_url($CFG->wwwroot. '/admin/tool/uclasupportconsole/index.php',
                                $linkarguments), $temptag));
        $linkarguments = array('console' => 'ccle_roster_class', 'term' => $courseterm, 'srs' => $coursesrs);
        $myuclarow->add_element(new ucla_cp_module('ccle_roster_class',
                                new moodle_url($CFG->wwwroot. '/admin/tool/uclasupportconsole/index.php',
                                $linkarguments), $temptag));
        $modules[] = $myuclarow;
    }
}

/******************************** Advanced Functions *********************/
$modules[] = new ucla_cp_module('ucla_cp_mod_advanced', null, null, 'moodle/course:manageactivities');

// Saving typing...again.
$temptag = array('ucla_cp_mod_advanced');

// Role assignments for particular courses.
if (ucla_cp_module::load('assign_roles')) {
    // This is when you want to make more than one module.
    $enrols = enrol_get_instances($course->id, true);
    $metalinks = array();
    foreach ($enrols as $enrolment) {
        if ($enrolment->enrol == 'meta') {
            $metalinks[] = $enrolment;
        }
    }

    // Load the course we are currently viewing.
    $modules[] = new ucla_cp_module_assign_roles($course, true);

    // Load any other courses we have linked.
    foreach ($metalinks as $metalink) {
        $metacourse = $DB->get_record('course', array(
            'id' => $metalink->customint1
        ));

        $modules[] = new ucla_cp_module_assign_roles($metacourse);
    }
}

// Backup.
$modules[] = new ucla_cp_module('backup_copy', new moodle_url(
                        $CFG->wwwroot . '/backup/backup.php', array('id' => $course->id)),
                $temptag, 'moodle/backup:backupcourse');


// Restore.
$modules[] = new ucla_cp_module('backup_restore', new moodle_url(
                        $CFG->wwwroot . '/backup/restorefile.php', array(
                            'contextid' => $context->id
                        )),
                $temptag, 'moodle/restore:restorecourse');

// Change course settings!
$modules[] = new ucla_cp_module('course_edit', new moodle_url(
                        $CFG->wwwroot . '/course/edit.php', array('id' => $course->id)),
                $temptag, 'moodle/course:update');

// This is for course files!
$modules[] = new ucla_cp_module('course_files', new moodle_url(
                        $CFG->wwwroot . '/files/coursefilesedit.php',
                        array('contextid' => $context->id)),
                $temptag, 'moodle/course:managefiles');

// Grade viewer.
$modules[] = new ucla_cp_module('course_grades', new moodle_url(
                        $CFG->wwwroot . '/grade/index.php', array('id' => $course->id)),
                $temptag, 'gradereport/grader:view');

// Logs.
$modules[] = new ucla_cp_module('logs',
        new moodle_url('/report/log/index.php', array('id' => $course->id)),
        $temptag, 'report/log:view');

// Edit dates "report" (added per CCLE-4385).
$modules[] = new ucla_cp_module('editdates',
        new moodle_url('/report/editdates/index.php', array('id' => $course->id)),
        $temptag, 'report/editdates:view');

// Groups.
$modules[] = new ucla_cp_module('groups',
        new moodle_url('/group/index.php', array('id' => $course->id)),
        $temptag, 'moodle/course:managegroups');

// Quiz question bank.
// Look at code from question/editlib.php: question_edit_setup lines 1606-1607
// (as of Moodle 2.2.2) to determine if user has any ability to manage questions.
require_once($CFG->libdir . '/questionlib.php');
$questioneditcontexts = new question_edit_contexts($context);
if ($questioneditcontexts->have_one_edit_tab_cap('questions')) {
    $modules[] = new ucla_cp_module('quiz_bank',
            new moodle_url('/question/edit.php', array('courseid' => $course->id)),
            $temptag);
}

// Student Functions
// Only display this section if the user is a student in the course
// or if a user "switches roles" to "student".
// TODO: this module currently depends on the myuclarow_renderer since that
// renderer opens links in new tabs (which the normal renderer does not normally
// do. If the control panel is to be refactored later, make this not terrible.

$isroleswitchedstudent = false;

if (is_role_switched($course->id)) {
    $studentroleid = $DB->get_field('role', 'id', array('shortname' => 'student'), IGNORE_MISSING);
    $userroleid = ($USER->access['rsw'][$context->path]);
    if ($userroleid == $studentroleid) {
        $isroleswitchedstudent = true;
    }
}

// Text and URL for course download.
$coursedownloadstr = 'course_download';
$coursedownloadurl = new moodle_url('/blocks/ucla_course_download/view.php', array('courseid' => $course->id));
$isinstructor = has_capability('moodle/course:manageactivities', context_course::instance($course->id));
$formatoptions = course_get_format($course->id)->get_format_options();
// See if the instructor has disabled downloading for this specific course.
if ($formatoptions['coursedownload'] == 1) {
    if (!$isinstructor && !student_zip_requestable($course)) {
        // Course download is active, but student cannot access link yet.
        $coursedownloadstr = 'course_download_available';
        $coursedownloadurl = null;
    }
} else {
    // Instructor turned off course download.
    if ($isinstructor) {
        $coursedownloadstr = 'course_download_disabled';
    } else {
        $coursedownloadstr = 'course_download_unavailable';
        $coursedownloadurl = null;
    }
}

// Student section/tag.
if (has_role_in_context('student', $context) || $isroleswitchedstudent) {
    $tempcap = null;
    $temptag = array('ucla_cp_mod_student');
    $modules[] = new ucla_cp_module('ucla_cp_mod_student', null, null, $tempcap);

    $modules[] = new ucla_cp_module('edit_profile', new moodle_url(
            $CFG->wwwroot . '/user/edit.php'), $temptag, 'moodle/user:editownprofile');
    $modules[] = new ucla_cp_module('student_grades', new moodle_url(
            $CFG->wwwroot . '/grade/index.php?id=' . $course->id), $temptag, $tempcap);

    $modules[] = new ucla_cp_module_course_download($coursedownloadstr, $coursedownloadurl, $temptag);

    if ($USER->auth != "shibboleth") {
        $modules[] = new ucla_cp_module_course_download('student_change_password',
                new moodle_url($CFG->wwwroot . '/login/change_password.php?=' . $USER->id), $temptag, $tempcap);
    }

    $courseinfo = ucla_get_course_info($course->id);
    // Do not display these courses if the user is not currently on a valid course page.
    if (!empty($courseinfo)) {
        // Add all course related links.
        $temptag = array('ucla_cp_mod_myucla');
        $tempcap = null;
        $modules[] = new ucla_cp_module('ucla_cp_mod_myucla', null, null, $tempcap);

        $myuclarow = new ucla_cp_myucla_row_module($temptag, $tempcap);
        $tmp = array_values($courseinfo);
        $firstcourse = array_shift($tmp);
        $courseterm = $firstcourse->term;
        $coursesrs = $firstcourse->srs;
        $myuclarow->add_element(new ucla_cp_module('student_myucla_grades',
                new moodle_url('https://be.my.ucla.edu/directLink.aspx?featureID=71&term=' .
                        $courseterm . '&srs=' . $coursesrs), 'ucla_cp_mod_myucla', $tempcap));
        $myuclarow->add_element(new ucla_cp_module('student_myucla_classmates',
                new moodle_url('https://be.my.ucla.edu/directLink.aspx?featureID=72&term=' .
                        $courseterm . '&srs=' . $coursesrs), 'ucla_cp_mod_myucla', $tempcap));

        $session = get_session_code($firstcourse);

        $myuclarow->add_element(new ucla_cp_module('student_myucla_textbooks',
                new moodle_url('http://www.collegestore.org/textbookstore/main.asp?remote=1&ref=ucla&term=' .
                        $courseterm . $session . '&course=' . $coursesrs .
                        '&getbooks=Display+books'), 'ucla_cp_mod_myucla', $tempcap));

        $modules[] = $myuclarow;
    }
} else {
    // Other Functions.
    $modules[] = new ucla_cp_module('ucla_cp_mod_other', null, null, null);

    // Saving typing...
    $temptag = array('ucla_cp_mod_other');
    // Redundency in case prev tempcap isn't this one.
    $tempcap = 'moodle/course:update';
    // Edit user profile!
    $modules[] = new ucla_cp_module('edit_profile', new moodle_url(
                            $CFG->wwwroot . '/user/edit.php'), $temptag, 'moodle/user:editownprofile');

      $modules[] = new ucla_cp_module('import_classweb', new moodle_url('view.php'),
      $temptag, $tempcap);
      // Import from classweb.

    // Import from existing moodle course.
    $modules[] = new ucla_cp_module('import_moodle', new moodle_url($CFG->wwwroot .
            '/backup/import.php', array('id' => $course->id)), $temptag,
            'moodle/restore:restoretargetimport');

    /* Create a TA-Site TODO.
      $modules[] = new ucla_cp_module('create_tasite', new moodle_url('view.php'),
      $temptag, $tacap);
      /* Create a TA-Site */

    // View moodle participants.
    $modules[] = new ucla_cp_module('view_roster', new moodle_url(
                            $CFG->wwwroot . '/user/index.php', array('id' => $course->id)),
                    $temptag, 'moodle/course:viewparticipants');

    // Syllabus tool.
    $syllabusmanager = new ucla_syllabus_manager($course);
    if ($syllabusmanager->can_host_syllabi()) {
        $modules[] = new ucla_cp_module('manage_syllabus', new moodle_url(
                '/local/ucla_syllabus/index.php', array('id' => $course->id)),
                $temptag, 'local/ucla_syllabus:managesyllabus');
    }

    // Course Content Download.
    $modules[] = new ucla_cp_module_course_download($coursedownloadstr, $coursedownloadurl, $temptag);
}
// Functions for features that haven't been implemented in moodle 2.0 yet.
/*   if (!is_collab($course->id)){
  }

  //START UCLA Modification.
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
    foreach ($enrolinstances as $instance) {
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
 * @param object $firstcourse
 * @return string
 */
function get_session_code($firstcourse) {
    global $DB;
    $session = '';
    if (is_summer_term($firstcourse->term)) { // Summer course.
        $session = $DB->get_field('ucla_reg_classinfo', 'session_group', array('term' => $firstcourse->term,
            'srs' => $firstcourse->srs));
    }
    return $session;
}
