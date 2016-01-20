<?php
// This file is part of the UCLA group management plugin for Moodle - http://moodle.org/
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
 * @package    block_ucla_group_manager
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['eventsection_groups_synced'] = 'Section groups synced';
$string['group_name'] = '{$a->subj_area} {$a->acttype} {$a->sectnum}';
$string['group_desc'] = 'This group was automatically created to help keep track of which students come from which sections. This group represents the students that have come from {$a->subj_area} {$a->acttype} {$a->sectnum}.';
$string['grouping_crosslist_name'] = '{$a->subj_area} {$a->coursenum}';
$string['grouping_crosslist_desc'] = 'This grouping was automatically created to help you keep track of which sections came from which course';
$string['grouping_section_type_name'] = '{$a->acttype} {$a->sectnum}';
$string['grouping_section_type_desc'] = 'This grouping was automatically created to help maintain different sets of sections.';
$string['pluginname'] = 'UCLA group manager';
$string['ucla_groupmanagercannotremove'] = 'This grouping is a special grouping corresponding to UCLA course sections. This grouping can neither be modified nor removed.';
$string['ucla_groupmanagercannotremove_one'] = 'The group selected is a special group for UCLA course sections. It cannot be removed.';
$string['ucla_groupmanagercannotremove_oneof'] = 'One of the groups selected is a special group for UCLA course sections. It cannot be removed.';
