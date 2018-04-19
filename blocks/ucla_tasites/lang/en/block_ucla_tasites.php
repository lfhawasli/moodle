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
$string['bysectiondesc'] = 'Which sections should be included in the TA site?';
$string['bysectionheader'] = 'By section';
$string['bysectionchoice'] = 'Sec {$a->sec} {$a->tas}';
$string['bytachoice'] = '{$a->fullname} ({$a->sections})';
$string['bytadesc'] = 'Select a TA:';
$string['bytaentirecourse'] = 'Make TA site available for entire course, <b>ALL</b> sections';
$string['bytaheader'] = 'By TA';
$string['bytanosectionsnote'] = 'TA site will be available for the entire course.';
$string['cachedef_tasitemapping'] = 'Holds mapping of sections to TAs and vice versa.';
$string['coursenamedesc'] = 'If a short name or full name is not given, then they will be automatically generated.';
$string['create'] = 'Create TA site';
$string['delete_tasites'] = 'Delete existing TA sites';
$string['delete_tadesc'] = 'Check to delete TA site <a href="{$a->courseurl}">{$a->courseshortname}</a> for {$a->fullname}.';
$string['deleted_tasite'] = 'Deletion of TA sites by this interface has been disabled.';
$string['enablebysection'] = 'Enable TA sites by sections';
$string['enablebysection_desc'] = 'Enable TA sites creation based by section in addition to by TA.';
$string['errbadresponse'] = 'Unknown action {$a}';
$string['errcantcreatetasite'] = "Can't create a TA site.";
$string['errconfirmation'] = 'Agreement is required.';
$string['errinvalidsetup'] = 'Cannot create a TA site without TAs or sections.';
$string['errinvalidsetupselected'] = 'Please select a section to create a TA site.';
$string['errisnottasite'] = 'Course is not a TA site.';
$string['erristasite'] = 'Cannot make TA sites from within a TA site.';
$string['errsetupenrol'] = 'The enrollment plugin "meta" needs to be enabled system-wide in order to use TA sites.';
$string['errtogglegrouping'] = 'Cannot toggle default grouping';
$string['hidden'] = 'Hide';
$string['listavailablecourse'] = 'Available to: Entire course';
$string['listavailablesections'] = 'Available to: Students only in sections {$a}';
$string['listgrouping'] = 'Default grouping: {$a}';
$string['listvisibility'] = 'Visible: {$a}';
$string['nosectionsexist'] = 'No valid sections exist to create a TA site.';
$string['notaorsection'] = 'There are no eligible TAs or valid sections available to create a TA site.';
$string['notasites'] = 'There is no eligible {$a} to make a site for.';
$string['pluginname'] = 'TA sites';
$string['returntocourse'] = 'Return to main course';
$string['succhangedgroupingpp'] = 'Availability changed. Now <b>ALL</b> the students in the course may access {$a}';
$string['succhangedgroupingta'] = 'Availability changed. Now <b>ONLY</b> students enrolled in the TA\'s sections may access {$a}';
$string['succreatesite'] = 'Successfully created TA site {$a}';
$string['suchidsite'] = 'Successfully changed visibility to <b>HIDE</b>';
$string['sucshowsite'] = 'Successfully changed visibility to <b>VISIBLE</b>';
$string['tainitialbysection'] = 'By section: You choose which sections and the TAs are automatically assigned.';
$string['tainitialbyta'] = 'By TA: You choose which TA and the sections are automatically assigned.';
$string['tainitialdesc'] = 'How do you want the TA site to be setup?';
$string['tasectionchoiceentire'] = 'The entire course';
$string['tasectionchoiceonly'] = 'Students only in my sections ({$a})';
$string['tasectiondesc'] = 'Who do you want to access your TA site?';
$string['tasite'] = 'TA site';
$string['tasitecreateconfirm'] = 'I understand and agree that I will use this site for legitimate purposes in accordance with UCLA and UC policy and regulations.';
$string['tasitefor'] = '{$a->fullname} TA site: {$a->course_fullname}';
$string['tasitefullname'] = 'TA site ({$a->text}): {$a->fullname}';
$string['tasitegroupingname'] = 'TA Section Materials';
$string['togglegroupingcourse'] = 'Make site accessible to entire course';
$string['togglegroupingsection'] = 'Make site accessible only to section';
$string['unavaibletas'] = 'Cannot create any more TA sites by TA, because all TAs already have a TA site assigned to them. Choose "By section" to create more.';
$string['view_tadesc'] = '{$a->fullname}';
$string['viewtasite'] = 'View site';
$string['viewtasites'] = 'Existing TA sites';
$string['viewtasitesec'] = 'Sec {$a}';

// Admin panel strings.
$string['managetasites'] = 'Manage TA Sites';
