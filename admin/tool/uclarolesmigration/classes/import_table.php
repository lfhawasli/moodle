<?php
// This file is part of the UCLA roles migration plugin for Moodle - http://moodle.org/
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
 * Import table class.
 *
 * @package    tool_uclarolesmigration
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Organizes import role data.
 *
 * @package    tool_uclarolesmigration
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_uclarolesmigration_import_table extends core_role_define_role_table_advanced {

    /**
     * Accepts an xml file with a role definition in order to define the import
     * table role variables.  Also accepts options of what fields to process.
     * 
     * @see core_role_define_role_table_advanced::forcepreset($xml, array $options)
     * @param string $xml the role's xml definition
     * @param array $options describes fields we want to process
     */
    public function force_preset($xml, array $options) {
        if (!$info = tool_uclarolesmigration_cleanxml::parse_preset($xml)) {
            throw new coding_exception('Invalid role preset');
        }

        if ($options['shortname']) {
            if (isset($info['shortname'])) {
                $this->role->shortname = $info['shortname'];
            }
        }

        if ($options['name']) {
            if (isset($info['name'])) {
                $this->role->name = $info['name'];
            }
        }

        if ($options['description']) {
            if (isset($info['description'])) {
                $this->role->description = $info['description'];
            }
        }

        if ($options['archetype']) {
            if (isset($info['archetype'])) {
                $this->role->archetype = $info['archetype'];
            }
        }

        if ($options['contextlevels']) {
            if (isset($info['contextlevels'])) {
                $this->contextlevels = $info['contextlevels'];
            }
        }

        foreach (array('assign', 'override', 'switch', 'view') as $type) {
            if ($options['allow'.$type] && isset($info['allow'.$type])) {
                $key = array_search(-1, $info['allow'.$type]);
                if ($key >= 0) {
                    unset($info['allow'.$type][$key]);
                }
                $this->{'allow'.$type} = $info['allow'.$type];
            }
        }

        if ($options['permissions']) {
            foreach ($this->permissions as $k => $v) {
                if (isset($info['permissions'][$k])) {
                    $this->permissions[$k] = $info['permissions'][$k];
                } else {
                    // Set the blanks to CAP_INHERIT so that on updates
                    // we can clear permissions that no longer exist.
                    $this->permissions[$k] = CAP_INHERIT;
                }
            }
        }
    }

    /**
     * Modifies the stored role name variable.
     * @param string $newname the new role name
     */
    public function set_name($newname) {
        $this->role->name = $newname;
    }

    /**
     * Modifies the stored role shortname variable.
     * @param string $newshortname the new shortrole name
     */
    public function set_shortname($newshortname) {
        $this->role->shortname = $newshortname;
    }

    /**
     * Returns the current roleid.
     * @return int $this->roleid
     */
    public function get_roleid() {
        return $this->roleid;
    }

    /**
     * Save the changes to the role definition in the database tables.
     * 
     * @see core_role_define_role_table_advanced::save_changes()
     */
    public function save_changes() {
        global $DB, $USER;

        if (!$this->roleid) {
            // Creating role.
            $this->role->id = create_role($this->role->name, $this->role->shortname,
                    $this->role->description, $this->role->archetype);
            $this->roleid = $this->role->id;
        } else {
            // Updating role.
            $DB->update_record('role', $this->role);
        }

        // Assignable contexts.
        set_role_contextlevels($this->role->id, $this->contextlevels);

        // Set allowed roles.
        $this->save_allow('assign');
        $this->save_allow('override');
        $this->save_allow('switch');
        $this->save_allow('view');

        // Permissions.
        $capabilitiestoupdate = array();
        $capabilitiestoinsert = array();
        // The following permission and capability handling is modified from
        // assign_capability() for performance.
        foreach ($this->permissions as $capability => $permission) {
            if (empty($permission) || $permission == CAP_INHERIT) {
                unassign_capability($capability, $this->roleid, $this->context->id);
                continue;
            }

            $existing = $DB->get_record('role_capabilities', array('contextid' => $this->context->id,
                'roleid' => $this->roleid, 'capability' => $capability));

            $cap = new stdClass();
            $cap->contextid    = $this->context->id;
            $cap->roleid       = $this->roleid;
            $cap->capability   = $capability;
            $cap->permission   = $permission;
            $cap->timemodified = time();
            $cap->modifierid   = empty($USER->id) ? 0 : $USER->id;

            if ($existing) {
                $cap->id = $existing->id;
                $capabilitiestoupdate[] = $cap;
            } else {
                $capabilitiestoinsert[] = $cap;
            }
        }
        // Update database with proper permissions.
        foreach ($capabilitiestoupdate as $cap) {
            $DB->update_record('role_capabilities', $cap);
        }
        $DB->insert_records('role_capabilities', $capabilitiestoinsert);

        // Force accessinfo refresh for users visiting this context.
        $this->context->mark_dirty();
    }
}
