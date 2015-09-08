<?php

// This file is part of the UCLA support tools plugin for Moodle - http://moodle.org/
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
 * Tests the install script for the UCLA support tools plugin.
 *
 * @package    local_ucla_support_tools
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Load existing support tools into database.
 */
function xmldb_local_ucla_support_tools_install() {
    if (!PHPUNIT_TEST) {
        load_report_uclastats();
        load_tool_uclaroles();
        load_tool_uclasupportconsole();
    }
}

/**
 * Loads the existing reports from the UCLA stats console.
 */
function load_report_uclastats() {
    global $CFG;
    require_once($CFG->dirroot . '/report/uclastats/locallib.php');

    $reports = get_all_reports();

    // Index will be the class name for the report.
    foreach ($reports as $classname => $reportname) {
        $data = array('url' => '/report/uclastats/view.php?report=' . $classname,
            'name' => $reportname,
            'description' => get_string($classname . '_help', 'report_uclastats'));
        \local_ucla_support_tools_tool::create($data);
    }
}

/**
 * Loads existing reports from UCLA roles reports.
 */
function load_tool_uclaroles() {
    global $CFG;
    $reporttypes = array(
        'listing',
        'rolemappings',
        'remapping'
    );

    foreach ($reporttypes as $type) {
        $data['name'] = get_string($type, 'tool_uclaroles');
        $data['url'] = '/' . $CFG->admin . '/tool/uclaroles/report/' . $type . '.php';
        \local_ucla_support_tools_tool::create($data);
    }    
}

/**
 * Loads existing reports from UCLA site indicator reports.
 */
function load_tool_uclasiteindicator() {
    global $CFG;
    $reporttypes = array(
        'orphans',
        'requesthistory',
        'sitelisting',
        'sitetypes',
    );

    foreach ($reporttypes as $type) {
        $baseurl = '/' . $CFG->admin . '/tool/uclasiteindicator/report/';
        $data['name'] = get_string($type, 'tool_uclasiteindicator');
        $data['url'] = $baseurl . $type . '.php';
        \local_ucla_support_tools_tool::create($data);
    }
}

/**
 * Loads existing support console tools from the UCLA suppport console.
 */
function load_tool_uclasupportconsole() {
    global $CFG;

    // Create array of tools.
    $groups = array();
    $groups['logs'] = array(
        0 => 'syslogs',
        1 => 'moodlelog',
        2 => 'moodlelogins',
        3 => 'moodlelogbyday',
        4 => 'moodlelogbydaycourse',
        5 => 'moodlelogbydaycourseuser',
        6 => 'moodlevideoreserveslist',
        7 => 'moodlelibraryreserveslist',
        8 => 'moodlebruincastlist',
        9 => 'recentlysentgrades',
    );
    $groups['users'] = array(
        0 => 'prepoprun',
        1 => 'moodleusernamesearch',
        2 => 'roleassignments',
        3 => 'countnewusers',
        4 => 'listdupusers',
        5 => 'pushgrades',
    );
    $groups['modules'] = array(
        0 => 'syllabusoverview',
        1 => 'syllabusreoport',
        2 => 'syllabusrecentlinks',
        3 => 'assignmentquizzesduesoon',
        4 => 'modulespercourse',
        5 => 'mediausage',
        6 => 'visiblecontentlist',
        7 => 'unhiddencourseslist',
    );
    $groups['srdb'] = array(
        0 => 'enrollview',
        1 => 'ccle_class_sections',
        2 => 'ccle_coursegetall',
        3 => 'ccle_courseinstructorsget',
        4 => 'ccle_getclasses',
        5 => 'ccle_getinstrinfo',
        6 => 'ccle_get_primary_srs',
        7 => 'ccle_roster_class',
        8 => 'cis_coursegetall',
        9 => 'cis_subjectareagetall',
        10 => 'ucla_getterms',
        11 => 'ucla_get_user_classes',
        12 => 'courseregistrardifferences',
        13 => 'showreopenedclasses',
    );

    // Should now have access to $consoles object.
    foreach ($groups as $tools) {
        foreach ($tools as $tool) {
            $data = array('url' => '/' . $CFG->admin . '/tool/uclasupportconsole/index.php#' . $tool,
                'name' => get_string($tool, 'tool_uclasupportconsole'));
            \local_ucla_support_tools_tool::create($data);
        }
    }
}
