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
 * Helper class to aid the filtering of course_enrolment_manager.
 *
 * @package local_ucla
 * @copyright 2015 UC Regents
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
require_once("$CFG->dirroot/enrol/locallib.php");

/**
 * Table control used for filtering users
 *
 * @copyright 2015 UC Regents
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_ucla_participants extends course_enrolment_manager {

    /**
     * The total number of users with a specified filter.
     * Populated by local_ucla_participants::get_total_users
     * @var int
     */
    protected $usercount = 0;

    /**
     * Gets the list of roles assigned to this course context only.
     *
     * @param context_course $context
     * @return array    Roles sorted alphabetically.
     */
    function get_roles_used_in_course(context_course $context) {
        global $DB;

        // For some reason cannot have rn.contextid = ra.contextid in ON clause.
        $params[] = $context->id;
        $params[] = $context->id;

        $sql = "SELECT DISTINCT r.id, r.name, r.shortname, rn.name AS coursealias
                  FROM {role_assignments} ra, {role} r
             LEFT JOIN {role_names} rn ON (rn.contextid = ? AND rn.roleid = r.id)
                 WHERE r.id = ra.roleid
                       AND ra.contextid = ?
              ORDER BY r.name ASC";

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Gets an array of users for display, this includes minimal user information
     * as well as minimal information on the users roles, groups, and enrolments.
     *
     * @param course_enrolment_manager $manager
     * @param int $sort
     * @param string $direction ASC or DESC
     * @param int $page
     * @param int $perpage
     * @param string $firstinitial
     * @param string $lastinitial
     * @return array
     */
    public function get_users_for_display(course_enrolment_manager $manager,
            $sort, $direction, $page, $perpage, $firstinitial='', $lastinitial='') {
        global $DB;
        $pageurl = $manager->get_moodlepage()->url;
        $users = $this->get_users($sort, $direction, $page, $perpage, $firstinitial, $lastinitial);

        $now = time();
        $straddgroup = get_string('addgroup', 'group');
        $strunenrol = get_string('unenrol', 'enrol');
        $stredit = get_string('edit');

        $allroles   = $this->get_all_roles();
        $assignable = $this->get_assignable_roles();
        $allgroups  = $this->get_all_groups();
        $context    = $this->get_context();
        $canmanagegroups = has_capability('moodle/course:managegroups', $context);

        $url = new moodle_url($pageurl, $this->get_url_params());
        $extrafields = get_extra_user_fields($context);

        $enabledplugins = $this->get_enrolment_plugins(true);

        $userdetails = array();
        foreach ($users as $user) {
            $details = $this->prepare_user_for_display($user, $extrafields, $now);

            // Roles.
            $details['roles'] = array();
            foreach ($this->get_user_roles($user->id) as $rid => $rassignable) {
                $unchangeable = !$rassignable;
                if (!is_siteadmin() and !isset($assignable[$rid])) {
                    $unchangeable = true;
                }
                $details['roles'][$rid] = array('text' => $allroles[$rid]->localname, 'unchangeable' => $unchangeable);
            }

            // Users.
            $usergroups = $this->get_user_groups($user->id);
            $details['groups'] = array();
            $details['groupings'] = array();
            foreach ($usergroups as $gid => $unused) {
                $details['groups'][$gid] = $allgroups[$gid]->name;
                $groupingids = $DB->get_records('groupings_groups', array('groupid'=>$gid), '', 'groupingid');
                foreach ($groupingids as $groupingid=>$unused) {
                    $grouping = groups_get_grouping($groupingid);
                    $details['groupings'][$groupingid] = $grouping->name;
                }
            }

            // Enrolments.
            $details['enrolments'] = array();

            foreach ($this->get_user_enrolments($user->id) as $ue) {
                if (!isset($enabledplugins[$ue->enrolmentinstance->enrol])) {
                    $details['enrolments'][$ue->id] = array(
                        'text' => $ue->enrolmentinstancename,
                        'period' => null,
                        'dimmed' => true,
                        'actions' => array()
                    );
                    continue;
                } else if ($ue->timestart and $ue->timeend) {
                    $period = get_string('periodstartend', 'enrol',
                            array('start' => userdate($ue->timestart), 'end' => userdate($ue->timeend)));
                    $periodoutside = ($ue->timestart && $ue->timeend &&
                            ($now < $ue->timestart || $now > $ue->timeend));
                } else if ($ue->timestart) {
                    $period = get_string('periodstart', 'enrol',
                            userdate($ue->timestart, get_string('strftimedatefullshort', 'langconfig')));
                    $periodoutside = ($ue->timestart && $now < $ue->timestart);
                } else if ($ue->timeend) {
                    $period = get_string('periodend', 'enrol',
                            userdate($ue->timeend, get_string('strftimedatefullshort', 'langconfig')));
                    $periodoutside = ($ue->timeend && $now > $ue->timeend);
                } else {
                    // If there is no start or end show when user was enrolled.
                    $period = get_string('periodnone', 'enrol',
                            userdate($ue->timecreated, get_string('strftimedatefullshort', 'langconfig')));
                    $periodoutside = false;
                }
                $details['enrolments'][$ue->id] = array(
                    'text' => $ue->enrolmentinstancename,
                    'period' => $period,
                    'dimmed' => ($periodoutside or $ue->status != ENROL_USER_ACTIVE or
                            $ue->enrolmentinstance->status != ENROL_INSTANCE_ENABLED),
                    'actions' => $ue->enrolmentplugin->get_user_enrolment_actions($manager, $ue)
                );
            }
            $userdetails[$user->id] = $details;
        }
        return $userdetails;
    }

    /**
     * Gets all of the users enrolled in this course.
     *
     * If a filter was specified this will be the users who were enrolled
     * in this course by means of that instance. If role or search filters were
     * specified then these will also be applied.
     *
     * @param string $sort
     * @param string $direction ASC or DESC
     * @param int $page First page should be 0
     * @param int $perpage Defaults to 25
     * @param string $firstinitial
     * @param string $lastinitial
     * @return array
     */
    public function get_users($sort, $direction='ASC', $page=0, $perpage=25,
            $firstinitial='', $lastinitial='') {
        global $DB;
        if ($direction !== 'ASC') {
            $direction = 'DESC';
        }
        $key = md5("$sort-$direction-$page-$perpage");
        if (!array_key_exists($key, $this->users)) {
            list($instancessql, $params, $filter) = $this->get_instance_sql();
            list($filtersql, $moreparams) = $this->get_filter_sql();
            $params += $moreparams;
            $extrafields = get_extra_user_fields($this->get_context());
            $extrafields[] = 'lastaccess';
            $ufields = user_picture::fields('u', $extrafields);
            $filtersql .= " AND u.firstname LIKE '$firstinitial%' AND .u.lastname LIKE '$lastinitial%'";
            $sql = "SELECT DISTINCT $ufields, ul.timeaccess AS lastseen
                      FROM {user} u
                      JOIN {user_enrolments} ue ON (ue.userid = u.id  AND ue.enrolid $instancessql)
                      JOIN {enrol} e ON (e.id = ue.enrolid)
                 LEFT JOIN {user_lastaccess} ul ON (ul.courseid = e.courseid AND ul.userid = u.id)
                 LEFT JOIN {groups_members} gm ON u.id = gm.userid
                     WHERE $filtersql";
            if ($sort === 'firstname') {
                $sql .= " ORDER BY IF(ISNULL(u.alternatename), u.firstname, u.alternatename) $direction, u.lastname $direction";
            } else if ($sort === 'lastname') {
                $sql .= " ORDER BY u.lastname $direction, IF(ISNULL(u.alternatename), u.firstname, u.alternatename) $direction";
            } else if ($sort === 'email') {
                $sql .= " ORDER BY u.email $direction, u.lastname $direction, IF(ISNULL(u.alternatename), u.firstname, u.alternatename) $direction";
            } else if ($sort === 'lastseen') {
                $sql .= " ORDER BY ul.timeaccess $direction, u.lastname $direction, IF(ISNULL(u.alternatename), u.firstname, u.alternatename) $direction";
            } else if ($sort === 'lastcourseaccess') {
                $sql .= " ORDER BY ul.timeaccess $direction, u.lastname $direction, IF(ISNULL(u.alternatename), u.firstname, u.alternatename) $direction";
            } else if ($sort === 'idnumber') {
                $sql .= " ORDER BY u.idnumber $direction, u.lastname $direction, IF(ISNULL(u.alternatename), u.firstname, u.alternatename) $direction";
            }
            $input = $DB->get_records_sql($sql, $params);
            $this->users[$key] = array_slice($input, $page * $perpage, $perpage);
            $this->usercount = count($input);
        }
        return $this->users[$key];
    }

    /**
     * Prepare a user record for display
     *
     * This function is called by both {@link get_users_for_display} and {@link get_other_users_for_display} to correctly
     * prepare user fields for display
     *
     * Please note that this function does not check capability for moodle/coures:viewhiddenuserfields
     *
     * @param object $user The user record
     * @param array $extrafields The list of fields as returned from get_extra_user_fields used to determine which
     * additional fields may be displayed
     * @param int $now The time used for lastaccess calculation
     * @return array The fields to be displayed including userid, courseid, picture, firstname, lastseen and any
     * additional fields from $extrafields
     */
    private function prepare_user_for_display($user, $extrafields, $now) {
        $details = array(
            'userid'           => $user->id,
            'courseid'         => $this->get_course()->id,
            'picture'          => new user_picture($user),
            'firstname'        => fullname($user, has_capability('moodle/site:viewfullnames', $this->get_context())),
            'lastseen'         => get_string('never'),
            'lastcourseaccess' => get_string('never'),
        );
        foreach ($extrafields as $field) {
            $details[$field] = $user->{$field};
        }

        // Last time user has accessed the site.
        if ($user->lastaccess) {
            $details['lastseen'] = format_time($now - $user->lastaccess);
        }

        // Last time user has accessed the course.
        if ($user->lastseen) {
            $details['lastcourseaccess'] = format_time($now - $user->lastseen);
        }
        return $details;
    }

    /**
     * Gets the total number of users with a specified filter
     *
     * @return int
     */
    public function get_usercount() {
        return $this->usercount;
    }

}
