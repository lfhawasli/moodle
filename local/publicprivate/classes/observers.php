<?php
// This file is part of the UCLA public/private plugin for Moodle - http://moodle.org/
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
 * Observers file.
 * 
 * @package    local_publicprivate
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_publicprivate;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/publicprivate/lib.php');

class observers {

    /**
     * Section groups synced.
     * 
     * Called by Events API when new section groups are created for a course. Adds the new groups to
     * the course's public/private grouping.
     * 
     * @param \block_ucla_group_manager\event\section_groups_synced $event
     */
    public static function section_groups_synced(\block_ucla_group_manager\event\section_groups_synced $event) {
        global $CFG;
        require_once($CFG->dirroot . '/local/publicprivate/lib/course.class.php');
        $groupids = $event->other['groupids'];
        $course = get_course($event->courseid);
        if (\PublicPrivate_Course::is_publicprivate_capable($course)) {
            $ppcourse = \PublicPrivate_Course::build($course);
            if ($ppgroupingid = $ppcourse->get_grouping()) {
                require_once($CFG->dirroot . '/group/lib.php');
                foreach ($groupids as $groupid) {
                    groups_assign_grouping($ppgroupingid, $groupid);
                }
            }
        }
    }
}
