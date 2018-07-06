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
 * Library of interface functions and constants for UCLA syllabus.
 *
 * All the core Moodle functions, needed to allow the module to work
 * integrated in Moodle should be placed here.
 * All the UCLA syllabus specific functions, needed to implement all the plugin
 * logic, should go to locallib.php. This will help to save some memory when
 * Moodle is performing actions across all modules.
 *
 * @package    local_ucla_syllabus
 * @copyright  2012 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Moodle core API.

/**
 * Basic user lookup.
 *
 * Returns a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @param stdClass $course the current course record
 * @param stdClass $user the record of the user we are generating a report for
 * @param cm_info $mod course module info
 * @param stdClass $newmodule the module instance record
 * @return stdClass|null
 */
function local_ucla_syllabus_user_outline($course, $user, $mod, $newmodule) {

    $return = new stdClass();
    $return->time = 0;
    $return->info = '';
    return $return;
}

/**
 * Complete user lookup.
 *
 * Prints a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @param stdClass $course the current course record
 * @param stdClass $user the record of the user we are generating report for
 * @param cm_info $mod course module info
 * @param stdClass $newmodule the module instance record
 * @return void, is supposed to echp directly
 */
function local_ucla_syllabus_user_complete($course, $user, $mod, $newmodule) {
}

/**
 * Find recent course activity.
 *
 * Given a course and a time, this module should find recent activity
 * that has occurred in newmodule activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @param stdClass $course the current course record
 * @param bool $viewfullnames whether or not to view full names
 * @param object $timestart timestamp of activity
 * @return bool
 */
function local_ucla_syllabus_print_recent_activity($course, $viewfullnames, $timestart) {
    return false;  // True if anything was printed, otherwise false.
}

/**
 * Prepares the recent activity data.
 *
 * This callback function is supposed to populate the passed array with
 * custom activity records. These records are then rendered into HTML via
 * {@link newmodule_print_recent_mod_activity()}.
 *
 * @param array $activities sequentially indexed array of objects with the 'cmid' property
 * @param int $index the index in the $activities to use for the next record
 * @param int $timestart append activity since this time
 * @param int $courseid the id of the course we produce the report for
 * @param int $cmid course module id
 * @param int $userid check for a particular user's activity only, defaults to 0 (all users)
 * @param int $groupid check for a particular group's activity only, defaults to 0 (all groups)
 * @return void adds items into $activities and increases $index
 */
function local_ucla_syllabus_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid=0, $groupid=0) {
}

/**
 * View single activity.
 *
 * Prints single activity item prepared by the get recent mod
 * activity function.
 *
 * @param stdClass $activity
 * @param int $courseid
 * @param stdClass $detail
 * @param array $modnames
 * @param bool $viewfullnames
 * @return void
 */
function local_ucla_syllabus_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {
}

/**
 * Syllabus cron job.
 *
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * @return bool
 **/
function local_ucla_syllabus_cron () {
    return true;
}

/**
 * Returns all other capabilities used in the module.
 *
 * @return array
 */
function local_ucla_syllabus_get_extra_capabilities() {
    return array();
}


// File API.

/**
 * Serves the files from the ucla_syllabus file areas.
 *
 * Depending on the syllabus access type, do the following checks:
 *  - Public: allow download
 *  - Logged in: check to see if user is logged in
 *  - Private: check to see if user is associated with course
 *
 * @package local_ucla_syllabus
 * @category files
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the newmodule's context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 */
function local_ucla_syllabus_pluginfile($course, $cm, $context, $filearea, array $args, $forcedownload, array $options=array()) {
    require_once(dirname(__FILE__).'/locallib.php');
    global $CFG;

    // First, get syllabus file.
    $syllabus = ucla_syllabus_manager::instance($args[0]);  // First argument should be syllabus ID.

    // Do some sanity checks.
    if (empty($syllabus) || !(isset($syllabus->stored_file))) {
        // There is no syllabus.
        send_file_not_found();
    } else if ($syllabus->courseid != $course->id ||
            $syllabus->stored_file->get_contextid() != $context->id) {
        // Given file doesn't belong to given course.
        print_error('err_syllabus_mismatch', 'local_ucla_syllabus');
    }

    // See if syllabus allows itself to be viewed.
    if ($syllabus->can_view()) {
        // Finally, send the file.
        send_stored_file($syllabus->stored_file, 86400, 0, $forcedownload);
    } else {
        print_error('err_syllabus_not_allowed', 'local_ucla_syllabus');
    }
}

/**
 * Alert instructors to upload syllabus if they haven't done so already.
 *
 * @param object $course for course information,
 * @param object $courseinfo for term.
 *
 * @return boolean
 */
function local_ucla_syllabus_ucla_format_notices($course, $courseinfo) {
    global $CFG, $USER;
    require_once($CFG->dirroot . '/local/ucla_syllabus/alert_form.php');
    require_once($CFG->dirroot . '/local/ucla_syllabus/locallib.php');

    // Ignore any old terms or if term is not set (meaning it is a collab site).
    if (!isset($courseinfo->term) ||
            term_cmp_fn($courseinfo->term, $CFG->currentterm) == -1) {
        // It is important for event handlers to return true, because false...
        // ...indicates error and event will be reprocessed on the next cron run.
        return true;
    }

    // See if current user can manage syllabi for course.
    $syllabusmanager = new ucla_syllabus_manager($course);

    // Ignore alert if user cannot upload syllabi or if course has one uploaded.
    if (!$syllabusmanager->can_manage() ||
            $syllabusmanager->has_syllabus()) {
        return true;
    }

    $alertform = null;

    // User can add syllabus, but course does not have syllabus. Check to see...
    // ...if someone manually uploaded a syllabus.
    $manuallysyllabi = $syllabusmanager->get_all_manual_syllabi();
    if (!empty($manuallysyllabi)) {
        // There might be multiple manually uploaded syllabus, and user might...
        // ...choose to ignore some of them.
        foreach ($manuallysyllabi as $syllabus) {
            $noprompt = get_user_preferences('ucla_syllabus_noprompt_manual_' .
                    $syllabus->cmid, null, $USER->id);
            if (is_null($noprompt)) {
                // Display form.
                $alertform = new alert_form(new moodle_url('/local/ucla_syllabus/alert.php',
                        array('id' => $course->id)),
                        array('manualsyllabus' => $syllabus), 'post', '',
                        array('class' => 'alert alert-info'));
                // Only want one alert to be shown.
                break;
            }
        }
    }

    if (empty($alertform)) {
        // User can add syllabus, but course doesn't have syllabus, give alert.

        // But first, see if they turned off the syllabus alert for their...
        // ...account ucla_syllabus_noprompt_<courseid>.
        $timestamp = get_user_preferences('ucla_syllabus_noprompt_' .
                $course->id, null, $USER->id);

        // Do not display alert if user turned off syllabus alerts or if remind...
        // ...me time has not passed.
        if (!is_null($timestamp) && (intval($timestamp) === 0 ||
                $timestamp > time())) {
            return true;
        }

        // Now we can display the alert.
        $alertform = new alert_form(new moodle_url('/local/ucla_syllabus/alert.php',
                array('id' => $course->id)), null, 'post', '',
                array('class' => 'alert alert-info'));
    }

    $alertform->display();
    return true;
}

/**
 * Adds a bulk download syllabi link to the course admin menu.
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param context $context The context of the course
 * @return void|null return null if we don't want to display the node.
 */
function local_ucla_syllabus_extend_navigation_category_settings($navigation, $context) {
    global $PAGE;

    if (!has_capability('moodle/course:view', $context)) {
        return null;
    }

    $url = new moodle_url('/local/ucla_syllabus/downloadsyllabi.php', array('id' => $context->instanceid));

    // Add the "Bulk download syllabi" link.
    $node = navigation_node::create(
        get_string('bulkdownloadsyllabi', 'local_ucla_syllabus'),
        $url,
        navigation_node::NODETYPE_LEAF,
        null, null, new pix_icon('i/backup', '')
    );

    if ($PAGE->url->compare($url, URL_MATCH_BASE)) {
        $node->make_active();
    }

    $navigation->add_node($node);
}

