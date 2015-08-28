<?php
/*
 * Class to override default moodle import course search to find teachers of courses in searchsql
 * @package local_ucla
 * @copyright  2013 UC Regents
 **/
class local_ucla_import_course_search extends import_course_search {
    protected function get_searchsql() {
        global $DB;
        $tablealias = 'ctx';
        $contextlevel = CONTEXT_COURSE;
        $joinon = 'c.id';
        list($ctxselect, $ctxjoin) = array((", " . context_helper::get_preload_record_columns_sql($tablealias)),
                "LEFT JOIN {context} $tablealias ON ($tablealias.instanceid = $joinon AND $tablealias.contextlevel = $contextlevel)");

        $params = array(
            'fullnamesearch' => '%'.$this->get_search().'%',
            'shortnamesearch' => '%'.$this->get_search().'%',
            'teacherfirstnamesearch' => '%'.$this->get_search().'%',
            'teacherlastnamesearch' => '%'.$this->get_search().'%',
            'teacherfullnamesearch' => '%'.$this->get_search().'%',
            'siteid' => SITEID
        );
        $select = "      SELECT c.id, c.format,c.fullname,c.shortname,c.visible,c.sortorder, usr.lastname, usr.firstname ";
        $from     = "      FROM {course} c ";
        $join     = " LEFT JOIN {role_assignments} ra ON ra.contextid = ctx.id
                      LEFT JOIN {role} r ON r.id = ra.roleid
                      LEFT JOIN {user} usr ON usr.id = ra.userid ";
        $where   = "      WHERE (".$DB->sql_like('CONCAT(usr.lastname, ", ", usr.firstname)', ':teacherfullnamesearch', false). "
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
