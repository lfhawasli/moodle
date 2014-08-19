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
 * Strings for multiple role import and export.
 *
 * @package    tool_uclarolesmigration
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['do_not_import']            = 'Do not import';
$string['exportroles']              = 'Export roles';
$string['export']                   = 'Export';
$string['import']                   = 'Import';
$string['importroles']              = 'Import roles';
$string['import_assigns']           = 'Import allow assigns';
$string['import_desc']              = 'If you want to import roles to this system, please select a file and click on the \"Next step\" button.';
$string['import_export']            = 'Import/Export';
$string['import_finished']          = 'Import finished. Please verify the role assignments and role overrides using the tabs above.';
$string['import_new']               = 'Import, creating new role with these values.';
$string['import_overrides']         = 'Import, allow overrides.';
$string['import_replacing']         = 'Import, replacing with the selected existing role.';
$string['importing']                = 'Importing';
$string['link_to_define_roles']     = 'Now that your role is imported, you will need to visit the <a href="{$a}">Define Roles</a> page to set it up.';
$string['name']                     = 'Display Name';
$string['next_step']                = 'Next step';
$string['new_role_created']         = 'New role created: {$a->newname} ({$a->newshort}), ID: {$a->newid} - Was originally: {$a->oldname} ({$a->oldshort})';
$string['no_roles_in_import']       = 'No roles found in import file.';
$string['pluginname']               = 'UCLA Roles Migration';
$string['role_ignored']             = 'Role ignored: {$a}';
$string['role_replaced']            = 'Role updated: The {$a->new} role has been overwritten with capabilities from the imported {$a->replaced} role.';
$string['selectrolestoexport']      = 'Select all roles you wish to export';
$string['shortname']                = 'Short name';
$string['submitexport']             = 'Create export file';
$string['unknown_import_action']    = 'Unknown import action ({$a->action}) for role {$a->shortname}';

// Error strings.
$string['error_noselect']           = 'Please select at least one role to export';
$string['error_nofile']             = 'Please select a valid ZIP file with XML definitions';
$string['error_invalidzip']         = 'The selected file is not a valid zip file';
$string['error_invalidxml']         = 'One or more XML contents are not valid role XML definitions';
$string['error_noaction']           = 'No import actions specified (Debug message)';
$string['error_update_role']        = 'Unable to update role information';

$string['rolesmigration:view'] = 'Ability to use role migration tool';