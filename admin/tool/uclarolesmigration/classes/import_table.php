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
        if (!$info = core_role_preset::parse_preset($xml)) {
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

        foreach (array('assign', 'override', 'switch') as $type) {
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
                }
            }
        }
    }

    /**
     * Save the changes to the role definition in the database tables.
     * 
     * @see core_role_define_role_table_advanced::save_changes()
     */
    public function save_changes() {
        global $DB;

        if (!$this->roleid) {
            // Creating role.
            $this->role->id = create_role($this->role->name, $this->role->shortname,
                    $this->role->description, $this->role->archetype);
            $this->roleid = $this->role->id; // Needed to make the parent::save_changes(); call work.
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

        // Permissions.
        foreach ($this->permissions as $capability => $permission) {
            assign_capability($capability, $permission, $this->roleid, $this->context->id, true);
        }
        
        // Force accessinfo refresh for users visiting this context.
        $this->context->mark_dirty();
    }
}
