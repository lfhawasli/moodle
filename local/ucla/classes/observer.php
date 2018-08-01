<?php
// This file is part of the local UCLA plugin for Moodle - http://moodle.org/
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
 * Event handler class.
 *
 * @package    local_ucla
 * @copyright  2016 UC Regent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Event handler class file.
 *
 * @copyright  2016 UC Regent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_ucla_observer {

    /**
     * Handles when a UCLA course is deleted by clearing the related course entries
     * in the "ucla_syllabus" table at SRDB.
     *
     * Note, cannot rely on handling deleted syllabus from deleted courses via the
     * ucla_syllabus_deleted event handler, because the entries in the
     * ucla_reg_classinfo table will be deleted by the time the event is fired.
     *
     * @param \tool_uclacoursecreator\event\ucla_course_deleted $event
     * @return bool         Returns false on problems, otherwise true.
     */
    public static function clear_srdb_ucla_syllabus(\tool_uclacoursecreator\event\ucla_course_deleted $event) {
        $data = json_decode($event->other);
        $task = new \local_ucla\task\clear_srdb_ucla_syllabus_task();
        $task->set_custom_data(array('ucla_reg_classinfo' => $data->ucla_reg_classinfo));
        \core\task\manager::queue_adhoc_task($task);
    }

    /**
     * Handles the updating "ucla_syllabus" table at SRDB by getting the course
     * that triggered the event and pushing out links for all the different syllabus
     * types.
     *
     * NOTE: This only responds to the syllabus_added and syllabus_deleted
     * events, because the link stays the same if a syllabus is updated.
     *
     * @param \local_ucla_syllabus\event\syllabus_base $event
     * @return bool         Returns false on problems, otherwise true.
     */
    public static function update_srdb_ucla_syllabus(\local_ucla_syllabus\event\syllabus_base $event) {
        $task = new \local_ucla\task\update_srdb_ucla_syllabus_task();
        $task->set_custom_data(array('courseid' => $event->courseid));
        \core\task\manager::queue_adhoc_task($task);
    }

    /**
     * When a course is restored, check if it has duplicate Announcement
     *
     * @param \core\event\course_restored $event
     * @return boolean
     */
    public static function course_restored_dedup_default_forums(\core\event\course_restored $event) {
        global $DB;

        // Only respond to course restores.
        if ($event->other['type'] != backup::TYPE_1COURSE) {
            return true;
        }

        // Check if restored course has duplicate "Announcements" and/or
        // "Discussion forum" forums. Array is indexed as type => default name.
        $defaultforums = array('news' => get_string('namenews', 'forum'),
            'general' => get_string('discforum', 'format_ucla'));

        foreach ($defaultforums as $type => $defaultname) {
            // Get forum and course module information.
            $sql = "SELECT   cm.id cmid,
                            f.id id,
                            f.name name
                   FROM     {forum} f
                   JOIN     {course_modules} cm ON (f.id=cm.instance)
                   JOIN     {modules} m ON (cm.module=m.id)
                   WHERE    f.course = ? AND
                            f.type = ? AND
                            m.name='forum'
                   ORDER BY f.id ASC";
            if ($forums = $DB->get_records_sql($sql, array($event->courseid, $type))) {
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
     * @param \core\event\course_restored $event
     * @return boolean
     */
    public static function course_restored_enrol_check(\core\event\course_restored $event) {
        global $DB;
        // Only respond to course restores.
        if ($event->other['type'] == backup::TYPE_1COURSE) {
            $record = $DB->get_record('enrol',
                    array('enrol' => 'database',
                          'courseid' => $event->courseid,
                          'status' => ENROL_INSTANCE_DISABLED));
            if (!empty($record)) {
                ucla_reg_enrolment_plugin_cron::update_plugin($event->courseid,
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
     * @param \block_ucla_weeksdisplay\event\week_changed $event
     */
    public static function hide_past_courses(\block_ucla_weeksdisplay\event\week_changed $event) {
        global $CFG;

        $weeknum = $event->other['week'];
        $configweek = get_config('local_ucla', 'student_access_ends_week');

        // If local_ucla|student_access_ends_week is 0, not set, or not equal to
        // $weeknum then will do nothing.
        if (empty($configweek) || $configweek != $weeknum) {
            return true;
        }

        // If local_ucla|student_access_ends_week is equal to $weeknum, then will
        // hide the previous term's courses.
        if (empty($CFG->currentterm)) {
            // For some reason, currentterm is empty, just exit.
            return true;
        }

        $pastterm = term_get_prev($CFG->currentterm);
        if (!ucla_validator('term', $pastterm)) {
            // Strange, cannot figure out past_term, just exit.
            return true;
        }

        list($numhiddencourses, $numhiddentasites, $numproblemcourses,
                $errormessages) = hide_courses($pastterm);

        // Finished hiding courses, notify admins.
        $to = get_config('local_ucla', 'admin_email');
        if (empty($to)) {
            // Did not have admin contact setup, just exit.
            return true;
        }

        $subj = 'Hiding courses for ' . $pastterm;
        $body = sprintf("Hid %d courses.\n\n", $numhiddencourses);
        $body .= sprintf("Hid %d TA sites.\n\n", $numhiddentasites);
        $body .= sprintf("Had %d problem courses.\n\n", $numproblemcourses);
        $body .= $errormessages;
        ucla_send_mail($to, $subj, $body);

        return true;
    }

    /**
     * After courses are built, run prepop on them.
     *
     * @param \tool_uclacoursecreator\event\course_creator_finished $event
     * @return boolean
     */
    public static function ucla_sync_built_courses(\tool_uclacoursecreator\event\course_creator_finished $event) {
        $edata = json_decode($event->other);

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
        foreach ($edata->completed_requests as $request) {
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
     * Opens navigation drawer.
     *
     * This function is used as a callback to respond to the user_loggedin event.
     */
    public static function open_nav_drawer() {
        set_user_preference('drawer-open-nav', 'true');
    }
}
