<?php
// This file is part of the UCLA group management plugin for Moodle - http://moodle.org/
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
 * Handles membership to UCLA course section groupings.
 *
 * @package    block_ucla_group_manager
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Class file.
 *
 * @package    block_ucla_group_manager
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ucla_synced_grouping {

    /**
     * Convenience function to create a tracked grouping, and then assigns a
     * bunch of groups to it.
     *
     * @param object $groupingdata
     * @param array $groups
     * @return int  Returns grouping id.
     */
    public static function create_tracked_grouping($groupingdata, $groups=array()) {
        $exists = self::get_tracked_grouping($groupingdata, $groups);
        if ($exists) {
            return $exists->id;
        }

        $groupingid = groups_create_grouping($groupingdata);
        self::track_new_grouping($groupingid);

        if (!empty($groups)) {
            self::groups_many_assign_grouping($groupingid, $groups);
        }

        return $groupingid;
    }

    /**
     * Get tracked groupings from a course.
     *
     * @param int $courseid
     * @return array
     */
    public static function get_course_tracked_groupings($courseid) {
        global $DB;
        return $DB->get_records_sql(
                'SELECT gg.*
                 FROM {groupings} gg
                 INNER JOIN {ucla_group_groupings} ugg
                    ON ugg.groupingid = gg.id
                 WHERE courseid = ?',
                 array($courseid)
             );
    }

    /**
     * Simple wrapper.
     *
     * @param string $name
     * @param int $courseid
     * @return object
     */
    public static function get_groupingdata($name, $courseid) {
        $groupingdata = new stdClass();
        $groupingdata->name = $name;
        $groupingdata->courseid = $courseid;

        return $groupingdata;
    }

    /**
     * Attempts to search through all course trackings, finding  a matching
     * grouping, based on groups assigned.
     *
     * @param object $groupingdata
     * @param array $groups
     * @return object   Returns matching grouping. If not found, returns false.
     */
    public static function get_tracked_grouping($groupingdata, $groups) {
        $courseid = $groupingdata->courseid;

        $trackedcoursegroupings = self::get_course_tracked_groupings($courseid);

        if (empty($trackedcoursegroupings)) {
            return false;
        }

        $neededgroupids = array();
        foreach ($groups as $gkey => $group) {
            $neededgroupids[] = $group->id;
        }

        // Could use self::groups_equals, but this is optimized.
        sort($neededgroupids, SORT_NUMERIC);

        foreach ($trackedcoursegroupings as $grouping) {
            $groupingsgroups = groups_get_all_groups($courseid,
                null, $grouping->id);
            $providedgroupids = array();

            foreach ($groupingsgroups as $group) {
                $providedgroupids[] = $group->id;
            }

            sort($providedgroupids, SORT_NUMERIC);
            if ($providedgroupids == $neededgroupids) {
                return $grouping;
            }
        }

        return false;
    }

    /**
     * Convenience function, adds many groups to a grouping.
     *
     * @param int $groupingid
     * @param array $groups
     */
    public static function groups_many_assign_grouping($groupingid, $groups) {
        foreach ($groups as $group) {
            if (!isset($group->id)) {
                continue;
            }

            groups_assign_grouping($groupingid, $group->id);
        }
    }

    /**
     * Creates a tracked grouping. Does not check for existance.
     *
     * @param int $groupingid
     */
    public static function track_new_grouping($groupingid) {
        global $DB;

        $dbobj = new stdClass();
        $dbobj->groupingid = $groupingid;

        $DB->insert_record('ucla_group_groupings', $dbobj);
    }
}
