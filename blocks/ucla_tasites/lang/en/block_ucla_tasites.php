<?php
// This file is part of the UCLA TA sites block for Moodle - http://moodle.org/
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
 * Language file.
 *
 * @package    block_ucla_tasites
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['build_tasites'] = 'Create TA sites';
$string['build_tadesc'] = 'Create TA site for {$a->fullname}';
$string['built_tasite'] = 'TA site <a href="{$a->courseurl}">{$a->courseshortname}</a> for {$a->fullname} was successfully built.';
$string['bysection'] = 'Section';
$string['bysectiondesc'] = 'Which sections should in included in the TA site?';
$string['bysectionheader'] = 'By section';
$string['bysectionlabel'] = 'Sec {$a->sec}';
$string['bysectionlabelall'] = 'Course';
$string['bysectiontext'] = 'TAs - {$a->tas}';
$string['cachedef_tasitemapping'] = 'Holds mapping of sections to TAs and vice versa.';
$string['coursenamedesc'] = 'If a short name or full name is not given, then they will be automatically generated.';
$string['create'] = 'Create TA site';
$string['delete_tasites'] = 'Delete existing TA sites';
$string['delete_tadesc'] = 'Check to delete TA site <a href="{$a->courseurl}">{$a->courseshortname}</a> for {$a->fullname}.';
$string['deleted_tasite'] = 'Deletion of TA sites by this interface has been disabled.';
$string['errbadresponse'] = 'Unknown action {$a}';
$string['errconfirmation'] = 'Agreement is required.';
$string['errinvalidsetup'] = 'Cannot create a TA site with more than one option.';
$string['errinvalidsetupselected'] = 'Please select a section to create a TA site.';
$string['errisnottasite'] = 'Course is not a TA site.';
$string['erristasite'] = 'Cannot make TA sites from within a TA site.';
$string['errsetupenrol'] = 'The enrollment plugin "meta" needs to be enabled system-wide in order to use TA sites.';
$string['errnosetupselected'] = 'Please select a setup type.';
$string['hidden'] = 'Hidden';
$string['listgrouping'] = 'Default grouping: {$a}';
$string['liststatus'] = 'Status: {$a}';
$string['listtaadmin'] = 'Owner: {$a}';
$string['noexistingtasites'] = 'No existing TA sites.';
$string['notasites'] = 'There is no eligible {$a} to make a site for.';
$string['pluginname'] = 'UCLA TA sites';
$string['returntocourse'] = 'Return to main course';
$string['suchidsite'] = 'Successfully hid TA site {$a}';
$string['sucshowsite'] = 'Successfully un-hid TA site {$a}';
$string['tasite'] = 'TA site';
$string['tasitecreateconfirm'] = 'I understand and agree that I will use this site for legitimate purposes in accordance with UCLA and UC policy and regulations.';
$string['tasitefor'] = '{$a->fullname} TA site: {$a->course_fullname}';
$string['tasitefullname'] = 'TA site ({$a->sections}): {$a->fullname}';
$string['ucla_make_tasites'] = 'TA sites';
$string['ucla_make_tasites_post'] = 'Create or view existing TA sites.';
$string['view_tadesc'] = '{$a->fullname}';
// gone
$string['view_tadesc_grouping'] = ' (default grouping: {$a})';
$string['view_tasite'] = 'View TA site';
$string['view_tasites'] = 'Existing TA sites';
