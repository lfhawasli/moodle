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
 * Settings for tool_uclarolesmigration.
 *
 * @package    tool_uclarolesmigration
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

// Build new admin page object for EXPORT ROLES.
$exportobject = new admin_externalpage(
    'exportroles',
    get_string('exportroles', 'tool_uclarolesmigration'),
    new moodle_url('/admin/tool/uclarolesmigration/exportroles.php'),
    'moodle/role:manage'
);

// Build new admin page object for IMPORT ROLES.
$importobject = new admin_externalpage(
    'importroles',
    get_string('importroles', 'tool_uclarolesmigration'),
    new moodle_url('/admin/tool/uclarolesmigration/importroles.php'),
    'moodle/role:manage'
    );

// Register new admin page object under User -> Roles.
$ADMIN->add('roles', $exportobject);
$ADMIN->add('roles', $importobject);
