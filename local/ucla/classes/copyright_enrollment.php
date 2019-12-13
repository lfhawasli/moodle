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
 * CCLE-6765 - Automate Copyright site Enrollment on regular basis.
 *
 * @package    local_ucla
 * @copyright  2019 UC Regent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Event handler class file.
 *
 * @copyright  2019 UC Regent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_ucla_copyright_enrollment {

    /**
     * Enrolls all instructors from given term given course with given role.
     *
     * @param string $term
     * @param int $courseid
     * @param int $roleid
     * @param object $enrolinstance     Self enrollment plugin instance.
     * @return int                      Count of number of users enrolled.
     */
    public static function enroll_all($term, $courseid, $roleid, $enrolinstance) {
        global $DB;

        // Get enrollment plugin.
        $enrolplugin = enrol_get_plugin('self');

        // Find roleid's for roles with instructor privileges.
        $editinginstructor = self::get_roleid('editinginstructor');
        $tainstructor = self::get_roleid('ta_instructor');

        // Find the users with instructor priveledges in course.
        $sql = "SELECT DISTINCT ra.userid AS id
                           FROM {role_assignments} ra
                           JOIN {context} cxt ON ra.contextid = cxt.id
                           JOIN {ucla_request_classes} urc ON cxt.instanceid = urc.courseid
                          WHERE ra.roleid IN (:editinginstructor, :tainstructor)
                            AND urc.term = :term
                            AND cxt.contextlevel = 50";
        $users = $DB->get_recordset_sql($sql,
                ['editinginstructor' => $editinginstructor,
                    'tainstructor' => $tainstructor, 'term' => $term]);

        $count = 0;
        if ($users->valid()) {
            $coursecontext = context_course::instance($courseid);
            foreach ($users as $user) {
                // If user is already in course, then don't enroll.
                if (!is_enrolled($coursecontext, $user->id, '', true)) {
                    $enrolplugin->enrol_user($enrolinstance, $user->id, $roleid);
                    ++$count;
                }
            }
        }
        $users->close();

        return $count;
    }

    /**
     * Returns roleid to use.
     *
     * @param string $shortname     Role shortname.
     * @return int
     */
    public static function get_roleid($shortname) {
        global $DB;
        return $DB->get_field('role', 'id', ['shortname' => $shortname], MUST_EXIST);
    }

    /**
     * Returns the self enrollment plugin instance for given course.
     *
     * Throws error if course does not have self enrollment plugin added.
     *
     * @param int $courseid
     * @return object
     */
    public static function get_self_enrol($courseid) {
        $instances = enrol_get_instances($courseid, true);
        foreach ($instances as $instance) {
            if ($instance->enrol == 'self') {
                return $instance;
            }
        }
        throw new moodle_exception('noselfenrolplugin', 'local_ucla', '', $courseid);
    }

    /**
     * Updates enrollment for the Copyright Basics course with the current
     * term's instructors.
     *
     * @param \block_ucla_weeksdisplay\event\week_changed $event
     */
    public static function sync(\block_ucla_weeksdisplay\event\week_changed $event) {
        global $CFG, $DB;

        $weeknum = $event->other['week'];
        // Only sync when new quarter stars with Week 1.
        if (1 != $weeknum) {
            return true;
        }
        if (empty($CFG->currentterm)) {
            // For some reason, currentterm is empty, just exit.
            mtrace('No currentterm set; skipping CopyrightBasicsInstructors sync');
            return true;
        }

        // Find Copyright course.
        $courseid = $DB->get_field('course', 'id',
                ['shortname' => 'CopyrightBasicsInstructors'], IGNORE_MISSING);
        if (empty($courseid)) {
            mtrace('No CopyrightBasicsInstructors found; skipping sync');
            return true;
        }
        // Get participant role.
        $roleid = self::get_roleid('participant');
        // Get self enrollment plugin instance.
        $enrolinstance = self::get_self_enrol($courseid);
        // First unenroll all participants with self-enrollment.
        $numunerolled = self::unenroll_all($courseid, $roleid, $enrolinstance);

        // First enroll instructors/ta_instructors as participants.
        $numerolled = self::enroll_all($CFG->currentterm, $courseid, $roleid,
                        $enrolinstance);

        // Finished syncing courses, notify admins.
        $to = get_config('local_ucla', 'admin_email');
        if (empty($to)) {
            // Did not have admin contact setup, just exit.
            return true;
        }

        $subj = 'Syncing CopyrightBasicsInstructors for ' . $CFG->currentterm;
        $body = sprintf("Unenrolled %d users.\n\n", $numunerolled);
        $body .= sprintf("Enrolled %d users.\n\n", $numerolled);
        ucla_send_mail($to, $subj, $body);

        return true;
    }

    /**
     * Unenroll all users with given role for given enrollment plugin in given
     * course.
     *
     * @param int $courseid
     * @param int $roleid
     * @param object $enrolinstance     Self enrollment plugin instance.
     * @return int                      Returns number of users unenrolled.
     */
    public static function unenroll_all($courseid, $roleid, $enrolinstance) {
        $participants = user_get_participants($courseid, 0, 0, 0, $roleid,
                $enrolinstance->id, ENROL_USER_ACTIVE, '');

        // A recordset is returned. Check if any results returned.
        $count = 0;
        if ($participants->valid()) {
            $enrol = enrol_get_plugin('self');
            foreach ($participants as $participant) {
                $enrol->unenrol_user($enrolinstance, $participant->id);
                ++$count;
            }
        }
        $participants->close();

        return $count;
    }

}
