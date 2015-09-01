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
 * @author  UCLA Regents
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
require_once("$CFG->dirroot/enrol/locallib.php");
require_once("$CFG->dirroot/enrol/users_forms.php");


/**
 * Form that lets users filter the participants list.
 */
class local_ucla_participants_filter_form extends enrol_users_filter_form {
    /**
     * Omits sections of the participants page filter if the user
     * does not have the capabilities to view them.
     *
     * @global moodle_database $DB
     */
    public function definition() {
        global $CFG, $DB;
        $context = context_course::instance($this->_customdata['id'], MUST_EXIST);
        $manager = $this->_customdata['manager'];
        $mform = $this->_form;
        // Text search box.
        $mform->addElement('text', 'search', get_string('search'));
        $mform->setType('search', PARAM_RAW);
        // Filter by enrolment plugin type.
        if (has_capability('enrol/manual:manage', $context)) {
            $mform->addElement('select', 'ifilter', get_string('enrolmentinstances', 'enrol'),
                    array(0 => get_string('all')) + (array)$manager->get_enrolment_instance_names());
        }
        // Role select dropdown includes all roles, but using course-specific
        // names if applied. The reason for not restricting to roles that can
        // be assigned at course level is that upper-level roles display in the
        // enrolments table so it makes sense to let users filter by them.
        if (has_capability('moodle/role:manage', $context)) {
            $allroles = $manager->get_all_roles();
            $rolenames = array();
            foreach ($allroles as $id => $role) {
                $rolenames[$id] = $role->localname;
            }
            $mform->addElement('select', 'role', get_string('role'),
                    array(0 => get_string('all')) + $rolenames);
        }
        // Filter by group.
        if (has_capability('moodle/course:managegroups', $context)) {
            $allgroups = $manager->get_all_groups();
            $groupsmenu[0] = get_string('allparticipants');
            foreach ($allgroups as $gid => $unused) {
                $groupsmenu[$gid] = $allgroups[$gid]->name;
            }
            if (count($groupsmenu) > 1) {
                $mform->addElement('select', 'filtergroup', get_string('group'), $groupsmenu);
            }
        }
        // Status active/inactive.
        $mform->addElement('select', 'status', get_string('status'),
                array(-1 => get_string('all'),
                    ENROL_USER_ACTIVE => get_string('active'),
                    ENROL_USER_SUSPENDED => get_string('inactive')));
        // Submit button does not use add_action_buttons because that adds
        // another fieldset which causes the CSS style to break in an unfixable
        // way due to fieldset quirks.
        $group = array();
        $group[] = $mform->createElement('submit', 'submitbutton', get_string('filter'));
        $group[] = $mform->createElement('submit', 'resetbutton', get_string('reset'));
        $mform->addGroup($group, 'buttons', '', ' ', false);
        // Add hidden fields required by page.
        $mform->addElement('hidden', 'id', $this->_customdata['id']);
        $mform->setType('id', PARAM_INT);
    }
}

/**
 * Table control used for filtering users
 *
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
    public function get_users_for_display(course_enrolment_manager $manager, $sort, $direction, $page, $perpage, $firstinitial='', $lastinitial='') {
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
            foreach ($usergroups as $gid => $unused) {
                $details['groups'][$gid] = $allgroups[$gid]->name;
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
                    $period = get_string('periodstartend', 'enrol', array('start' => userdate($ue->timestart), 'end' => userdate($ue->timeend)));
                    $periodoutside = ($ue->timestart && $ue->timeend && ($now < $ue->timestart || $now > $ue->timeend));
                } else if ($ue->timestart) {
                    $period = get_string('periodstart', 'enrol', userdate($ue->timestart, get_string('strftimedatefullshort', 'langconfig')));
                    $periodoutside = ($ue->timestart && $now < $ue->timestart);
                } else if ($ue->timeend) {
                    $period = get_string('periodend', 'enrol', userdate($ue->timeend, get_string('strftimedatefullshort', 'langconfig')));
                    $periodoutside = ($ue->timeend && $now > $ue->timeend);
                } else {
                    // If there is no start or end show when user was enrolled.
                    $period = get_string('periodnone', 'enrol', userdate($ue->timecreated, get_string('strftimedatefullshort', 'langconfig')));
                    $periodoutside = false;
                }
                $details['enrolments'][$ue->id] = array(
                    'text' => $ue->enrolmentinstancename,
                    'period' => $period,
                    'dimmed' => ($periodoutside or $ue->status != ENROL_USER_ACTIVE or $ue->enrolmentinstance->status != ENROL_INSTANCE_ENABLED),
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
     * @global moodle_database $DB
     * @param string $sort
     * @param string $direction ASC or DESC
     * @param int $page First page should be 0
     * @param int $perpage Defaults to 25
     * @param string $firstinitial
     * @param string $lastinitial
     * @return array
     */
    public function get_users($sort, $direction='ASC', $page=0, $perpage=25, $firstinitial='', $lastinitial='') {
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
                $sql .= " ORDER BY u.firstname $direction, u.lastname $direction";
            } else if ($sort === 'lastname') {
                $sql .= " ORDER BY u.lastname $direction, u.firstname $direction";
            } else if ($sort === 'email') {
                $sql .= " ORDER BY u.email $direction, u.lastname $direction, u.firstname $direction";
            } else if ($sort === 'lastseen') {
                $sql .= " ORDER BY ul.timeaccess $direction, u.lastname $direction, u.firstname $direction";
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

/**
 * Table control used for enrolled users
 *
 */
class local_ucla_course_enrolment_users_table extends course_enrolment_users_table {

    /**
     * Sets the fields for this table. These get added to the tables head as well.
     *
     * You can also use a multi dimensional array for this to have multiple fields
     * in a single column
     *
     * This is a copy of the original file, but leaves out the checkbox field when
     * bulk operations are present.
     *
     * @param array $fields An array of fields to set
     * @param string $output
     */
    public function set_fields($fields, $output) {
        $this->fields = $fields;
        $this->head = array();
        $this->colclasses = array();
        $this->align = array();
        $url = $this->manager->get_moodlepage()->url;

        foreach ($fields as $name => $label) {
            $newlabel = '';
            if (is_array($label)) {
                $bits = array();
                foreach ($label as $n => $l) {
                    if ($l === false) {
                        continue;
                    }
                    if (!in_array($n, self::$sortablefields)) {
                        $bits[] = $l;
                    } else {
                        $link = html_writer::link(new moodle_url($url, array(self::SORTVAR=>$n)), $fields[$name][$n]);
                        if ($this->sort == $n) {
                            $link .= html_writer::link(new moodle_url($url, array(self::SORTVAR=>$n, self::SORTDIRECTIONVAR=>$this->get_field_sort_direction($n))), $this->get_direction_icon($output, $n));
                        }
                        $bits[] = html_writer::tag('span', $link, array('class'=>'subheading_'.$n));

                    }
                }
                $newlabel = join(' / ', $bits);
            } else {
                if (!in_array($name, self::$sortablefields)) {
                    $newlabel = $label;
                } else {
                    $newlabel  = html_writer::link(new moodle_url($url, array(self::SORTVAR=>$name)), $fields[$name]);
                    if ($this->sort == $name) {
                        $newlabel .= html_writer::link(new moodle_url($url, array(self::SORTVAR=>$name, self::SORTDIRECTIONVAR=>$this->get_field_sort_direction($name))), $this->get_direction_icon($output, $name));
                    }
                }
            }
            $this->head[] = $newlabel;
            $this->colclasses[] = 'field col_'.$name;
        }
    }

    /**
     * Sets the users for this table
     *
     * Also a clone of the original function that leaves out the checkbox column for
     * manual enrolments
     *
     * @param array $users
     * @return void
     */
    public function set_users(array $users) {
        $this->users = $users;
        foreach ($users as $userid=>$user) {
            $user = (array)$user;
            $row = new html_table_row();
            $row->attributes = array('class' => 'userinforow');
            $row->id = 'user_'.$userid;
            $row->cells = array();

            foreach ($this->fields as $field => $label) {
                if (is_array($label)) {
                    $bits = array();
                    foreach (array_keys($label) as $subfield) {
                        if (array_key_exists($subfield, $user)) {
                            $bits[] = html_writer::tag('div', $user[$subfield], array('class'=>'subfield subfield_'.$subfield));
                        }
                    }
                    if (empty($bits)) {
                        $bits[] = '&nbsp;';
                    }
                    $row->cells[] = new html_table_cell(join(' ', $bits));
                } else {
                    if (!array_key_exists($field, $user)) {
                        $user[$field] = '&nbsp;';
                    }
                    $row->cells[] = new html_table_cell($user[$field]);
                }
            }
            $this->data[] = $row;
        }
    }

    /**
     * Returns true if the table is aware of any bulk operations that can be performed on users
     * selected from the currently filtered enrolment plugins.
     *
     * @return bool
     */
    public function has_bulk_operations() {
        return $this->has_bulk_user_enrolment_operations() ||
                has_capability('moodle/course:bulkmessaging', $this->manager->get_context());
    }

}
