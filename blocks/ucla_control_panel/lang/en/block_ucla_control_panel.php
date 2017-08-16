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
 * Moodle strings
 *
 * @package block_ucla_control_panel
 * @copyright  UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

/** Moodle Strings **/
$string['pluginname'] = 'UCLA control panel';
$string['name'] = 'Control panel';

$string['badsetup'] = 'Folder "modules" is missing.';
$string['badmodule'] = 'Could not load module {$a}.';

$string['nocommands'] = 'There are no available "{$a}" commands.';

$string['formatincompatible'] = 'WARNING: This page is designed to be used with courses with the "ucla" format. Other formats may cause undefined behavior.';

// Dummy control panel module.
$string['dummy'] = '';
$string['dummy_pre'] = '';
$string['dummy_post'] = '';

// Default category name.
$string['default'] = 'Common functions';

$string['unknowntag'] = 'Ungrouped functions';

// Stuff from common.
$string['ucla_cp_mod_common'] = 'Most commonly used';

$string['email_students'] = 'Email students';
$string['email_students_post'] = '(via Announcements forum)';

$string['email_students_exception'] = 'Email students';
$string['email_students_exception_post'] = 'There was a problem detecting your announcements forum. This is most likely caused by the fact you are using an incompatible course format.';

$string['email_students_hidden_pre'] = 'Email students is disabled when the Announcements forum is hidden.';
$string['email_students_hidden'] = 'Make Announcements forum visible';

$string['invitation'] = 'Invite users';
$string['invitation_post'] = '(Invite a user into your site by email)';

$string['modify_sections'] = 'Modify course menu sections';
$string['modify_sections_post'] = '';

$string['turn_editing_on'] = 'Delete or update materials';
$string['turn_editing_on_post'] = '(or duplicate, hide, make public)';

$string['turnitin'] = 'Add a Turnitin Assignment to Site info';
$string['turnitin_post'] = '(' . '<a href = "https://docs.ccle.ucla.edu/w/Turnitin_Assignment_v2" target = "_blank">'
    . 'Learn how to create a Turnitin Assignment</a>' . ')';

// Stuff from myucla functions.
$string['ucla_cp_mod_myucla'] = 'MyUCLA functions';

$string['download_roster'] = 'Download roster';
$string['download_roster_post'] = '';
$string['photo_roster'] = 'Photo roster';
$string['photo_roster_post'] = '';
$string['myucla_gradebook'] = 'MyUCLA gradebook';
$string['myucla_gradebook_post'] = '';
$string['turn_it_in'] = 'Turn-It-In';
$string['turn_it_in_post'] = '';
$string['email_roster'] = 'Email roster';
$string['email_roster_post'] = '';
$string['asucla_textbooks'] = 'ASUCLA textbooks';
$string['asucla_textbooks_post'] = '';

// Stuff from other.
$string['ucla_cp_mod_other'] = 'Other tools';

$string['import_moodle'] = 'Import Moodle course data';
$string['import_moodle_post'] = 'Copy activities and content from one of your Moodle courses to another.';

$string['create_tasite'] = 'Create TA-Sites';
$string['create_tasite_post'] = 'Create teaching assistant sites.';

$string['view_roster'] = 'View participants';
$string['view_roster_post'] = 'Lists most recent logins of all students, TAs, and instructors.';

$string['edit_profile'] = 'Edit user profile';
$string['edit_profile_post'] = 'You can change your personal information, upload a picture, turn your email on or off, etc.';

$string['manage_syllabus'] = 'Manage syllabus';
$string['manage_syllabus_post'] = 'View and edit your course syllabus.';

$string['course_download'] = 'Download course materials';
$string['course_download_post'] = 'Request a zip file of your course materials.';
$string['course_download_toggle'] = 'Disable download course materials for students.';
$string['course_download_available'] = 'Download course materials';
$string['course_download_available_post'] = 'Available when course starts.';
$string['course_download_unavailable'] = 'Download course materials';
$string['course_download_unavailable_post'] = 'Not available for this course';
$string['course_download_disabled'] = 'Download course materials';
$string['course_download_disabled_post'] = 'Disabled for students.';
$string['course_download_disabled_toggle'] = 'Enable download course materials for students.';

// Stuff for advanced.
$string['more_advanced'] = 'Advanced functions';

$string['ucla_cp_mod_advanced'] = 'Advanced functions';

$string['assign_roles'] = 'Assign roles for {$a->shortname}';
$string['assign_roles_post'] = 'Use this to assign roles specifically for {$a->shortname}.';

$string['assign_roles_master'] = 'Assign roles for {$a->shortname} (current course)';
$string['assign_roles_master_post'] = 'Use this to assign roles (change people to students, TAs or guests to grant or remove privileges) specifically for {$a->shortname}.';

$string['backup_copy'] = 'Create course backup';
$string['backup_copy_post'] = 'Make a copy of your course for use on another Moodle site.';

$string['backup_restore'] = 'Restore from backup';
$string['backup_restore_post'] = 'Restore a backed up course, either to the current course or to another course of which you are an administrator.';

$string['course_files'] = 'Files';
$string['course_files_post'] = 'Access course files directory to add or delete files, organize files into folders, upload/expand zip files, etc.';

$string['course_grades'] = 'Grades';
$string['course_grades_post'] = 'View your Moodle gradebook. Activate the advanced gradebook features to use grade weighting, letter grades, extra credit, etc. See the Gradebook Manual for more information.';

$string['course_edit'] = 'Course settings';
$string['course_edit_post'] = 'Edit the full name and description of the course, hide the course from students, or disable course downloads for students.';

$string['logs'] = 'Logs';
$string['logs_post'] = 'View logs to see course activity.';

$string['editdates'] = 'Dates';
$string['editdates_post'] = "Edit the dates for this course's activities.";

$string['groups'] = 'Groups';
$string['groups_post'] = 'Set up and manage course groups. These provide separate work areas for groups of students in Forums, Wikis and other tools.';

$string['quiz_bank'] = 'Quiz question bank';
$string['quiz_bank_post'] = 'Edit the quiz questions for this course.';

// Stuff for Admin.
$string['z_admin_functions'] = 'Admin functions';

$string['ucla_cp_mod_admin_advanced'] = 'Admin functions';
$string['run_prepop'] = 'Run prepop';
$string['run_prepop_post'] = "Updates roster with latest enrollment from Registrar";

$string['ccle_courseinstructorsget'] = 'Get instructors';
$string['ccle_courseinstructorsget_post'] = "";

$string['ccle_roster_class'] = 'Get student roster';
$string['ccle_roster_class_post'] = "";

$string['push_grades'] = 'Push grades';
$string['push_grades_post'] = "Forces all current grades and grade items to be sent to MyUCLA's gradebook service";

// Stuff for Student Control Panel.
$string['ucla_cp_mod_student'] = 'Other commands';
$string['student_grades'] = 'Grades';
$string['student_grades_post'] = 'View your grades on CCLE.';
$string['student_change_password'] = 'Change password';
$string['student_change_password_post'] = 'Change your password.';
$string['student_myucla_grades'] = 'Grades';
$string['student_myucla_grades_post'] = 'View your grades on MyUCLA (if your instructor has made them available).';
$string['student_myucla_classmates'] = 'Classmates';
$string['student_myucla_classmates_post'] = 'View a list of your classmates on MyUCLA.';
$string['student_myucla_textbooks'] = 'Textbooks';
$string['student_myucla_textbooks_post'] = 'View required textbooks for this course on MyUCLA.';

// Logging event handler.
$string['eventcontrolpanelviewed'] = 'Viewed control panel';

// EOF.
