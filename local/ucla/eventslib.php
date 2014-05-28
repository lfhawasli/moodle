<?php
// This file is part of the UCLA local plugin for Moodle - http://moodle.org/
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

/*
 * Event handlers for the local_ucla plugin.
 *
 * @package    local_ucla
 * @copyright  2014 UC Regents
 */

defined('MOODLE_INTERNAL') || die();

// @todo When automatic class loading is available via Moodle 2.6, we no longer
// need to include the local_ucla_regsender class, so delete it.
require_once($CFG->dirroot . '/local/ucla/classes/local_ucla_regsender.php');

require_once($CFG->dirroot . '/backup/util/interfaces/checksumable.class.php');
require_once($CFG->dirroot . '/backup/backup.class.php');
require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/mod/forum/lib.php');

/**
 * Handles when a UCLA course is deleted by clearing the related course entries
 * in the "ucla_syllabus" table at SRDB.
 *
 * Note, cannot rely on handling deleted syllabus from deleted courses via the
 * ucla_syllabus_deleted event handler, because the entries in the
 * ucla_reg_classinfo table will be deleted by the time the event is fired.
 *
 * @param object $data  Expecting an object with the following fields: courseid,
 *                      array of ucla_request_classes entries, and array
 *                      ucla_reg_classinfo entries.
 * @return bool         Returns false on problems, otherwise true.
 */
function clear_srdb_ucla_syllabus($data) {
    // Clear syllabi for each ucla_reg_classinfo entry.
    $regsender = new local_ucla_regsender();
    $links = array('public' => '', 'private' => '', 'protect' => '');

    foreach ($data->ucla_reg_classinfo as $classinfo) {
        $result = $regsender->set_syllabus_link($classinfo->term,
                $classinfo->subj_area, $classinfo->crsidx, $classinfo->classidx,
                $links);
        if ($result == local_ucla_regsender::FAILED) {
            // Error, try again later.
            return false;
        }
    }

    return true;
}

/**
 * When a course is restored, check if it has duplicate Announcement
 *
 * @param stdClass $data    Event object from restore procedure.
 * @return boolean
 */
function course_restored_dedup_default_forums($data) {
    global $DB;

    // Only respond to course restores.
    if ($data->type != backup::TYPE_1COURSE) {
        return true;
    }

    // Check if restored course has duplicate "Announcements" and/or
    // "Discussion forum" forums. Array is indexed as type => default name.
    $defaultforums = array('news' => get_string('namenews', 'forum'),
        'general' => get_string('discforum', 'format_ucla'));

    foreach ($defaultforums as $type => $defaultname) {
        // Get forum and course module information.
        $sql = "SELECT   cm.id AS cmid,
                        f.id AS id,
                        f.name AS name
               FROM     {forum} f
               JOIN     {course_modules} cm ON (f.id=cm.instance)
               JOIN     {modules} m ON (cm.module=m.id)
               WHERE    f.course = ? AND
                        f.type = ? AND
                        m.name='forum'
               ORDER BY f.id ASC";
        if ($forums = $DB->get_records_sql($sql, array($data->courseid, $type))) {
            if (count($forums) > 1) {
                $forumdelete = array();
                /* Try to keep at least 1 default forum type:
                 * 1) Check if forum has any content, if so, then don't delete
                 *    it. Skip to end.
                 * 2) Check if forum has changed its name from the default, if
                 *    so, then don't delete it. Skip to end.
                 * 3) If forum does not have any content or changed its title,
                 *    then mark it for deletion.
                 * 4) If no $forumdelete is the same as the number of $forums,
                 *    then choose the first entry from $forumdelete to keep and
                 *    delete the rest.
                 */
                foreach ($forums as $forum) {
                    // Any content?
                    if ($DB->record_exists('forum_discussions',
                                    array('forum' => $forum->id))) {
                        continue;
                    }
                    // Changed default name?
                    if ($defaultname != $forum->name) {
                        continue;
                    }
                    $forumdelete[] = $forum;
                }

                if (count($forumdelete) == count($forums)) {
                    // All forums are eligible to be deleted, so keep first.
                    array_shift($forumdelete);
                }

                if (!empty($forumdelete)) {
                    foreach ($forumdelete as $todelete) {
                        // Delete course module entry.
                        course_delete_module($todelete->cmid);
                    }
                }
            }
        }
    }

    return true;
}

/**
 * Makes sure that restored courses have the UCLA database enrollment plugin
 * enabled, if it exists.
 *
 * @param stdClass $data    Event object from restore procedure.
 * @return boolean
 */
function course_restored_enrol_check($data) {
    global $DB;

    // Only respond to course restores.
    if ($data->type == backup::TYPE_1COURSE) {
        $record = $DB->get_record('enrol',
                array('enrol' => 'database',
                      'courseid' => $data->courseid,
                      'status' => ENROL_INSTANCE_DISABLED));
        if (!empty($record)) {
            ucla_reg_enrolment_plugin_cron::update_plugin($data->courseid,
                    $record->id);
        }
    }

    return true;
}

/**
 * Determines if, given current week, whether or not to hide the past term's 
 * courses. Uses local_ucla|student_access_ends_week to determine if courses
 * should be hidden.
 *
 * If local_ucla|student_access_ends_week is 0, not set, or not equal to
 * $weeknum then will do nothing.
 *
 * If local_ucla|student_access_ends_week is equal to $weeknum, then will hide
 * the previous term's courses.
 *
 * Responds to the ucla_weeksdisplay_changed event.
 *
 * @param int $weeknum
 */
function hide_past_courses($weeknum) {
    global $CFG, $DB;
    $config_week = get_config('local_ucla', 'student_access_ends_week');

    // If local_ucla|student_access_ends_week is 0, not set, or not equal to
    // $weeknum then will do nothing.
    if (empty($config_week) || $config_week != $weeknum) {
        return true;
    }

    // If local_ucla|student_access_ends_week is equal to $weeknum, then will
    // hide the previous term's courses.
    if (empty($CFG->currentterm)) {
        // For some reason, currentterm is empty, just exit.
        return true;
    }

    $past_term = term_get_prev($CFG->currentterm);
    if (!ucla_validator('term', $past_term)) {
        // Strange, cannot figure out past_term, just exit.
        return true;
    }

    list($num_hidden_courses, $num_hidden_tasites, $num_problem_courses,
            $error_messages) = hide_courses($past_term);

    // Finished hiding courses, notify admins.
    $to = get_config('local_ucla', 'admin_email');
    if (empty($to)) {
        // Did not have admin contact setup, just exit.
        return true;
    }

    $subj = 'Hiding courses for ' . $past_term;
    $body = sprintf("Hid %d courses.\n\n", $num_hidden_courses);
    $body .= sprintf("Hid %d TA sites.\n\n", $num_hidden_tasites);
    $body .= sprintf("Had %d problem courses.\n\n", $num_problem_courses);
    $body .= $error_messages;
    ucla_send_mail($to, $subj, $body);

    return true;
}

/**
 * Handles the updating "ucla_syllabus" table at SRDB by getting the course
 * that triggered the event and pushing out links for all the different syllabus
 * types.
 *
 * NOTE: This only responds to the ucla_syllabus_added and ucla_syllabus_deleted
 * events, because the link stays the same if a syllabus is updated.
 * 
 * @param mixed $data   Either syllabus record id or syllabus record object.
 * @return bool         Returns false on problems, otherwise true.
 */
function update_srdb_ucla_syllabus($data) {
    global $DB;
    $courseid = null;
    if (is_object($data)) {
        $courseid = $data->courseid;
    } else {
        $courseid = $DB->get_field('ucla_syllabus', 'courseid',
                array('id' => $data));
    }
    if (empty($courseid)) {
        // Syllabus must have been deleted after it was added, so do not process
        // entry anymore.
        return true;
    }

    // If course is deleted, clearing of syllabi links is done through
    // ucla_course_deleted event handler.
    if (!$DB->record_exists('course', array('id' => $courseid))) {
        // Don't send anything and dequeue.
        return true;
    }

    // If course is a collaboration site, then  don't process syllabus.
    if (is_collab_site($courseid)) {
        return true;
    }

    // Get all syllabi for course and then send links.
    $regsender = new local_ucla_regsender();
    return $regsender->push_course_links($courseid);
}

/**
 * After courses are built, run prepop on them.
 *
 * @param array $edata
 * @return boolean
 */
function ucla_sync_built_courses($edata) {
    global $CFG;

    // Don't run during unit tests. Can be triggered via
    // course_creator_finished event.
    if (defined('PHPUNIT_TEST') and PHPUNIT_TEST) {
        return true;
    }

    // This hopefully means that this plugin IS enabled.
    $enrol = enrol_get_plugin('database');
    if (empty($enrol)) {
        debugging('Database enrolment plugin is not installed');
        return false;
    }

    $trace = null;
    if (debugging()) {
        $trace = new text_progress_trace();
    } else {
        $trace = new null_progress_trace();
    }
    $courseids = array();
    foreach ($edata->completed_requests as $key => $request) {
        if (empty($request->courseid)) {
            continue;
        }
        $courseids[] = $request->courseid;
    }

    foreach ($courseids as $courseid) {
        // This will handle auto-groups via events api.
        $enrol->sync_enrolments($trace, $courseid);
    }

    return true;
}

/**
 * When the course creator has finished, check if it is an Engineering course. 
 * If so, check "Do not display auto-generated links" for upon build and add the
 * "Recent activity" and "Upcoming events" blocks upon build and place them
 * above the "Administration" block.
 * 
 * Responds to the course_creator_finished event.
 * 
 * @param array $edata
 * @return boolean
 */
function set_engineering_default($edata) {
    global $DB;

    $page = new moodle_page();
    foreach ($edata->completed_requests as $request) {
        // Only work on hostcourses.
        if (empty($request->hostcourse)) {
            continue;
        }

        // Only work on engineering courses.
        if (!is_engineering($request->courseid)) {
            continue;
        }

        // Check "Do not display auto-generated links" upon build.
        course_get_format($request->courseid)->update_course_format_options(
                array('hide_autogenerated_content' => true));

        // Add the "Recent activity" and "Upcoming events" blocks upon build and
        // place them above the "Administration" block.
        $coursecontext = context_course::instance($request->courseid);
        $page->set_context($coursecontext);
        $page->blocks->add_regions(array(BLOCK_POS_RIGHT));
        $page->blocks->add_block('recent_activity', BLOCK_POS_RIGHT, -10, 0, 'course-view-*');
        $page->blocks->add_block('calendar_upcoming', BLOCK_POS_RIGHT, -9, 0, 'course-view-*');

//        // Set course theme to allow custom logo.
//        $DB->set_field('course', 'theme', 'uclasharedcourse',
//                array('id' => $request->courseid));
    }
    return true;
}