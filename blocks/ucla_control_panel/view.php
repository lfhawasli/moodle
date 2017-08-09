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
 * The control panel section, a collection of several tools.
 *
 * @package block_ucla_control_panel
 * @copyright  UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

require_once(dirname(__FILE__) . '/../../config.php');
global $CFG, $PAGE;

require_once($CFG->libdir . '/blocklib.php');
require_once($CFG->dirroot . '/blocks/moodleblock.class.php');
require_once($CFG->dirroot .
        '/blocks/ucla_control_panel/block_ucla_control_panel.php');
require_once($CFG->dirroot .
        '/blocks/ucla_control_panel/ucla_cp_renderer.php');
require_once($CFG->dirroot .
        '/blocks/ucla_control_panel/modules/ucla_cp_myucla_renderer.php');

require_once($CFG->dirroot . '/local/ucla/lib.php');

// Note that the unhiding of the Announcements forum is handled in
// modules/email_students.php.
// Note that any logic unrelated to the display of the control panel should
// be handled within the module itself.

$courseid = required_param('course_id', PARAM_INT);
$sectionid = optional_param('section', 0, PARAM_INT);
$moduleview = optional_param('module', 'default', PARAM_ALPHANUMEXT);

if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('coursemisconf');
}

require_login($course, true);
$context = context_course::instance($courseid);

// Disallow guest users.
if (isguestuser()) {
    redirect($CFG->wwwroot . '/course/view.php?id=' . $courseid);
}

// Perform actions.
if (has_capability('moodle/course:manageactivities', $context)) {
    $action = optional_param('action', null, PARAM_ALPHANUMEXT);
    switch ($action) {
        // Disallow students to download course materials.
        case 'toggle_course_download':
        case 'toggle_course_download_disabled':
            course_get_format($courseid)->update_course_format_options(
                    array('coursedownload' => ($action == 'toggle_course_download') ? 0 : 1));
            redirect(new moodle_url('view.php', array('course_id' => $courseid)));
            break;
    }
}

// Initialize $PAGE.
$PAGE->set_url('/blocks/ucla_control_panel/view.php',
        array('course_id' => $courseid));
$pagetitle = $course->shortname . ': ' . get_string('pluginname',
                'block_ucla_control_panel');
$PAGE->set_context($context);
$PAGE->set_title($pagetitle);
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('incourse');
$PAGE->set_pagetype('course-view-' . $course->format);

set_editing_mode_button();

// Get all the elements, unfortunately, this is where we check whether
// we are supposed to display the elements at all.
$elements = block_ucla_control_panel::load_cp_elements($course, $context);

// We do this here because of a hack for stylesheets using core renderer.
echo $OUTPUT->header();

if ($course->format != 'ucla') {
    echo $OUTPUT->box(get_string('formatincompatible',
                    'block_ucla_control_panel'));
}

echo html_writer::start_tag('div', array('id' => 'cpanel-wrapper'));
echo html_writer::tag('h1', get_string('name', 'block_ucla_control_panel'),
        array('class' => 'cpheading'));

// So here we need to check which tabs we can actually display.
$tabs = array();
foreach ($elements as $view => $contents) {
    $tabs[] = new tabobject($view,
            new moodle_url($PAGE->url, array('module' => $view)),
            get_string($view, 'block_ucla_control_panel')
            );
}

// Display tags here.
echo html_writer::start_tag('div', array('class' => 'thetabs'));
print_tabs(array($tabs), $moduleview);
echo html_writer::end_tag('div');

// This has to be called manually.
$PAGE->navigation->initialise();

// This is for showing a notice if there are no commands available.
$noelements = true;

$sm = get_string_manager();
// This is actually printing out each section of the control panel.
foreach ($elements as $view => $sectioncontents) {

    if ($moduleview != $view) {
        continue;
    }

    $noelements = false;

    foreach ($sectioncontents as $tags => $modules) {

        // Is this group of stuff from elsewhere?
        if ($sm->string_exists($tags, 'block_ucla_control_panel')) {
            $viewstring = get_string($tags, 'block_ucla_control_panel');
        } else {
            // This is for other blocks.
            $altblockstr = $tags . '_cp_viewtitle';
            if ($sm->string_exists($altblockstr, $tags)) {
                $viewstring = get_string($altblockstr, $tags);
            } else {
                $viewstring = get_string('unknowntag',
                        'block_ucla_control_panel');
            }
        }

        // Start container for tags.
        echo html_writer::start_tag('div', array('class' => 'cpanel-group'));
        echo $OUTPUT->heading($viewstring, 2, 'main copan-title');

        if ($tags == 'ucla_cp_mod_common') {
            $sectioncontents = ucla_cp_renderer::get_content_array($modules, 2);
            echo ucla_cp_renderer::control_panel_contents($sectioncontents,
                    false, 'row', 'general_icon_link');
        } else if ($tags == 'ucla_cp_mod_myucla' ||
                ($tags == 'ucla_cp_mod_admin_advanced')) {
            echo ucla_cp_myucla_row_renderer::control_panel_contents($modules);
        } else {
            $altrend = $tags . '_cp_render';
            if (class_exists($altrend) && method_exists($altrend,
                            'render_cp_items')) {
                echo $altrend::render_cp_items($modules);
            } else {
                echo ucla_cp_renderer::control_panel_contents($modules, true);
            }
        }

        // End container for tags.
        echo html_writer::end_tag('div');
    }
}

if ($noelements) {
    echo $OUTPUT->box(get_string('nocommands', 'block_ucla_control_panel',
                    $moduleview));
}

// Log the tab the user is viewing.
$event = \block_ucla_control_panel\event\control_panel_viewed::create(array(
    'context' => $context,
    'other' => array(
        'module' => $moduleview,
        'module_name' => get_string($moduleview, 'block_ucla_control_panel'),
        'section' => $sectionid
    )
));
$event->trigger();

// This is temporary fix for the bottom border.
echo html_writer::end_tag('div');
echo $OUTPUT->footer();
