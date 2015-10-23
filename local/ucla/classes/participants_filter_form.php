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
 * Overrides enrol_users_filter_form class.
 *
 * @package local_ucla
 * @copyright 2015 UC Regents
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
require_once("$CFG->dirroot/enrol/users_forms.php");

/**
 * Helper class to limit the filtering of the participants page..
 *
 * @copyright 2015 UC Regents
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_ucla_participants_filter_form extends enrol_users_filter_form {
    /**
     * Omits sections of the participants page filter if the user
     * does not have the capabilities to view them.
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
        if (has_capability('moodle/course:enrolconfig', $context) or has_capability('moodle/course:enrolreview', $context)) {
            $mform->addElement('select', 'ifilter', get_string('enrolmentinstances', 'enrol'),
                    array(0 => get_string('all')) + (array)$manager->get_enrolment_instance_names());
        }
        // Role select dropdown includes only roles appearing in the course, 
        // but using course-specific names if applied. 
        if (has_capability('moodle/role:assign', $context)) {
            $allroles = role_fix_names($manager->get_roles_used_in_course($context));
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
        if (has_capability('moodle/course:viewsuspendedusers', $context)) {
            $mform->addElement('select', 'status', get_string('status'),
                    array(-1 => get_string('all'),
                        ENROL_USER_ACTIVE => get_string('active'),
                        ENROL_USER_SUSPENDED => get_string('inactive')));
        }

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
