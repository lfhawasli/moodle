<?php
// This file is part of the UCLA course download plugin for Moodle - http://moodle.org/
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
 * Plugin lib file.
 *
 * Used for integrations with Moodle, such as file downloads.
 *
 * @package     block_ucla_course_download
 * @copyright   2014 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Serves the zipped course content files.
 *
 * @param stdClass $course course object
 * @param stdClass $cm This will be ignored.
 * @param stdClass $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - just send the file
 */
function block_ucla_course_download_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    global $CFG, $DB, $USER;
    require_once($CFG->dirroot . '/blocks/ucla_course_download/classes/base.php');

    // Expecting course context.
    if ($context->contextlevel != CONTEXT_COURSE) {
        return false;
    }

    // Does the user have the ability to get zip files?
    require_course_login($course, true);
    if (!has_capability('block/ucla_course_download:requestzip', $context)) {
        print_error('noaccess', 'block_ucla_course_download');
    }

    // Depending on the file area, it will tell us which class to load.
    $classname = 'block_ucla_course_download_' . $filearea;
    $classfile = $CFG->dirroot . '/blocks/ucla_course_download/classes/' . $filearea . '.php';
    if (!file_exists($classfile)) {
        return false;
    }
    require_once($classfile);
    $coursecontentclass = new $classname($course->id, $USER->id);

    $coursecontentclass->download_zip();
}

/**
 * Alert students that course content archives are available for download.
 * 
 * @param object $course to get course information and $courseinfo default to null.
 * @return boolean              Returns early if we don't want to show alert
 *                              to user.
 */

function block_ucla_course_download_ucla_format_notices($course) {
    global $CFG, $USER;
    require_once($CFG->dirroot . '/blocks/ucla_course_download/alert_form.php');
    require_once($CFG->dirroot . '/blocks/ucla_course_download/locallib.php');

    // Make sure this is a course site.
    if (is_collab_site($course)) {
        return true;
    }

    // Check if this is a student that can download the archive.
    $coursecontext = context_course::instance($course->id);
    $isinstructor = has_capability('moodle/course:manageactivities', $coursecontext);
    $canrequest = has_capability('block/ucla_course_download:requestzip', $coursecontext);

    // Don't show alert if user is an course admin, cannot get download archives,...
    // ...or it isn't time to show alert for students.
    if ($isinstructor || !$canrequest || !student_zip_requestable($course)) {
        return true;
    }

    // Check if the user chose to dismiss the alert before.
    $noprompt = get_user_preferences('ucla_course_download_noprompt_' .
                $course->id, null, $USER->id);

    if(!is_null($noprompt) && (intval($noprompt) === 0)) {
        return true;
    }

    // Render the alert.
    $alertform = new course_download_alert_form(new moodle_url('/blocks/ucla_course_download/alert.php',
                array('id' => $course->id)), null, 'post', '',
                array('class' => 'alert alert-info'));

    $alertform->display();

    return true;
}

/**
 * Adds download course materials link to admin panel.
 *
 * @param navigation_node $navigation The navigation node to extend.
 * @param stdClass        $course     The course object for the tool.
 * @param context         $context    The context of the course.
 */
function block_ucla_course_download_extend_navigation_course($navigation, $course, $context) {
    if (has_capability('block/ucla_course_download:requestzip', $context)) {
        $setting = navigation_node::create(get_string('coursedownload', 'block_ucla_course_download'),
                new moodle_url('/blocks/ucla_course_download/view.php',
                        array('courseid' => $course->id)), navigation_node::TYPE_SETTING);
        $navigation->add_node($setting);
    }
};
