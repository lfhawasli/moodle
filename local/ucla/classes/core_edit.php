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
 * Class to contain miscellaneous methods used in Moodle core edits.
 *
 * @package    local_ucla
 * @copyright  2014 UC Regents
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class file.
 *
 * @package    local_ucla
 * @copyright  2014 UC Regents
 */
class local_ucla_core_edit {
    /**
     * Returns an array of users who only the ability to grade only at the course
     * context. Moodle normally displays all users who have the ability to grade,
     * even those inherited from the category or site context.
     *
     * @param object $course
     * @return array
     */
    public static function get_course_graders($course) {
        global $CFG;
        require_once($CFG->dirroot . '/local/publicprivate/lib/course.class.php');
        $ppcourse = PublicPrivate_Course::build($course);
        $groupid = 0;
        if ($ppcourse->is_activated()) {
            $groupid = $ppcourse->get_group();
        }
        return get_users_by_capability(context_course::instance($course->id),
                'mod/assign:grade', '', '', '', '', $groupid, '', false);
    }
}
