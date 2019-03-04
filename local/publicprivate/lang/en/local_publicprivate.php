<?php
// This file is part of the UCLA public/private plugin for Moodle - http://moodle.org/
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
 * Public/Private string file.
 *
 * @package    local_publicprivate
 * @copyright  2019 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['enablepublicprivate'] = 'Enable Public/Private';
$string['enablepublicprivate_description'] = 'This setting enables the use of the experimental public/private modification. <b>&quot;Enable group members only&quot; must be enabled for this to work correctly.</b> NOTE: Disabling this will turn off public/private toggle, but sites already using public/private will still operate via the method.';
$string['enablepublicprivate_help'] = '<h1>Public/Private Functionality</h1>
<p>Enabling public/private functionality for a course alters several settings in order to create a hybrid course with both public and private material.</p>
<p>When enabled, it will create a toggle for all activities that allow for the material to be set either private to course users or public to guests as well. To this end, it creates a special group and grouping. It disables the auto-assign groups and available to guest settings.</p>';
$string['eventpublic_used'] = 'Course module is set to public';
$string['eventprivate_used'] = 'Course module is set to private';
$string['pluginname'] = 'Public/Private';
$string['publicprivateenable'] = 'Enable Public/Private';
$string['publicprivateenable_help'] = 'Enabling public/private functionality for a course alters several settings in order to create a hybrid course with both public and private material.<br><br>When enabled, it will create a toggle for all activities that allow for the material to be set either private to course users or public to guests as well. To this end, it creates a special group ("Course members") and a special grouping ("Private Course Material"). It disables the auto-assign groups and available to guest settings.';
$string['publicprivategroupname'] = 'Course members';
$string['publicprivategroupdeprecated'] = '_old_';
$string['publicprivategroupdescription'] = 'Group created and used for public/private functionality. All users should belong to this group.';
$string['publicprivategroupingname'] = 'Private Course Material';
$string['publicprivategroupingdeprecated'] = '_old_';
$string['publicprivategroupingdescription'] = 'Grouping created and used for public/private functionality. ' . $string['publicprivategroupname'] . ' group should be in this grouping.';
$string['publicprivatemakepublic'] = 'Make public';
$string['publicprivatemakeprivate'] = 'Make private';
$string['publicprivate'] = 'Public/Private';
$string['publicprivateadd'] = 'Add Public/Private functionality';
$string['publicprivateupgradesure'] = 'Public/Private functionality appears to be present, but not installed. <br /><br />
Moodle would now like to install Public/Private functionality on the server.<br /><br />';
$string['publicprivaterestore'] = 'Default Public/Private on Restore';
$string['publicprivaterestore_description'] = 'In the event that a restore occurs from an instance of Moodle that does not include public/private, if this setting is defined, the restored course will default to public/private enabled.';
$string['publicprivate_option_enable'] = 'Course Editors Can Set Public/Private';
$string['publicprivate_option_enable_description'] = 'If checked, course editors can turn public/private on/off. Otherwise, only administrators can do this.';
$string['publicprivatenotice_notenrolled'] = 'This is the public display of the course site. You need to be associated with the course to view private course materials.';
$string['publicprivatenotice_notloggedin'] = 'This is the public display of the course site. If you are enrolled, please log in to view private course materials.';
$string['publicprivatelogin'] = 'Log in';
$string['publicprivatecannotedit'] = 'ERROR: This is a special grouping for public/private. It cannot be edited.';
$string['publicprivatecannotremove_oneof'] = 'ERROR: One of the groups selected is a special group for public/private. It cannot be removed.';
$string['publicprivatecannotremove_one'] = 'ERROR: The group selected is a special group for public/private. It cannot be removed.';
$string['publicprivatecannotremove'] = 'ERROR: This is a special grouping for public/private. It cannot be removed.';
$string['taskcleanup'] = 'Public/Private cleanup task';
