<?php
// This file is part of UCLA local plugin for Moodle - http://moodle.org/
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
 * Overrides the import course search.
 *
 * @package    local_ucla
 * @copyright  2013 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Override import course search to find instructors of courses in searchsql.
 *
 * @package local_ucla
 * @copyright  2013 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_ucla_import_course_search extends import_course_search {
    /**
     * Adds in ability to also search by instructors.
     *
     * @return array
     */
    protected function get_searchsql() {
        global $DB;
        $tablealias = 'ctx';
        $contextlevel = CONTEXT_COURSE;
        $joinon = 'c.id';

        list($ctxselect, $ctxjoin) = array((", " . context_helper::get_preload_record_columns_sql($tablealias)),
                "LEFT JOIN {context} $tablealias ON ($tablealias.instanceid = $joinon "
                . "AND $tablealias.contextlevel = $contextlevel)");

        $params = array(
            'fullnamesearch' => '%'.$this->get_search().'%',
            'shortnamesearch' => '%'.$this->get_search().'%',
            'teacherfirstnamesearch' => '%'.$this->get_search().'%',
            'teacherlastnamesearch' => '%'.$this->get_search().'%',
            'teacherfullnamesearch' => '%'.$this->get_search().'%',
            'siteid' => SITEID
        );
        $select = "      SELECT DISTINCT c.id, c.format,c.fullname,c.shortname,c.visible,c.sortorder, usr.lastname, usr.firstname";
        $from     = "      FROM {course} c ";
        $join     = " LEFT JOIN {role_assignments} ra ON ra.contextid = ctx.id
                      LEFT JOIN {role} r ON r.id = ra.roleid
                      LEFT JOIN {user} usr ON usr.id = ra.userid ";


        $where   = "      WHERE (r.shortname = 'editinginstructor' OR r.shortname='supervising_instructor')
                            AND (".$DB->sql_like('CONCAT(usr.lastname, ", ", usr.firstname)', ':teacherfullnamesearch', false). "
                             OR ".$DB->sql_like('usr.lastname', ':teacherlastnamesearch', false). "
                             OR ".$DB->sql_like('usr.firstname', ':teacherfirstnamesearch', false). "
                             OR ".$DB->sql_like('c.fullname', ':fullnamesearch', false)."
                             OR ".$DB->sql_like('c.shortname', ':shortnamesearch', false).")
                            AND c.id <> :siteid";
        $orderby  = "  ORDER BY c.sortorder";
        if ($this->currentcourseid !== null && !$this->includecurrentcourse) {
            $where .= " AND c.id <> :currentcourseid";
            $params['currentcourseid'] = $this->currentcourseid;
        }
        return array($select.$ctxselect.$from.$ctxjoin.$join.$where.$orderby, $params);
    }
}
