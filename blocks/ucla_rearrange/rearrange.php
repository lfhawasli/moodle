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
 * Rearrange sections and course modules
 *
 * @package block_ucla_rearrange
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  UC Regents
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/course/lib.php');

// Tool requires UCLA format.
require_once($CFG->dirroot . '/course/format/ucla/lib.php');

$thispath = '/blocks/ucla_rearrange';
require_once($CFG->dirroot . $thispath . '/block_ucla_rearrange.php');
require_once($CFG->dirroot . $thispath . '/rearrange_form.php');

require_once($CFG->dirroot . '/local/ucla/lib.php');

global $CFG, $PAGE, $OUTPUT;

$courseid = required_param('courseid', PARAM_INT);
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

require_login($course, true);
$context = context_course::instance($courseid);

// Make sure you can view this page.
require_capability('moodle/course:update', $context);
require_capability('moodle/course:manageactivities', $context);

$format = course_get_format($course);
// See what section we are on.
$sectionnum = $format->figure_section();

// Set up the page.
$PAGE->set_context($context);

$PAGE->set_pagelayout('course');
$PAGE->set_pagetype('course-view-' . $course->format);

$PAGE->set_url('/blocks/ucla_rearrange/rearrange.php',
        array('courseid' => $courseid, 'section' => $sectionnum));

// Set editing url to be section or default page.
$gobackurl = new moodle_url('/course/view.php',
                array('id' => $courseid, 'section' => $sectionnum));
set_editing_mode_button($gobackurl);

$sections = $format->get_sections();

$sectnums = array();
$sectionnames = array();
$sectionvisibility = array();
foreach ($sections as $section) {
    $sid = $section->id;
    $sectids[$sid] = $sid;
    $sectnums[$sid] = $section->section;
    $sectionnames[$sid] = $format->get_section_name($section);
    $sectionvisibility[$sid] = $section->visible;
}

// Before loading modinfo, make sure section information is correct.
local_ucla_course_section_fixer::fix_problems($course);

$modinfo = get_fast_modinfo($course);
$mods = $modinfo->get_cms();

$sectionnodeshtml = block_ucla_rearrange::get_section_modules_rendered(
                $courseid, $sections, $mods, $modinfo
);

$sectionlist = block_ucla_rearrange::SECTIONLIST;

// Consolidate into a single thingee.
$sectionshtml = html_writer::start_tag(
                'ul',
                array(
                    'class' => block_ucla_rearrange::SECTIONLISTCLASS,
                    'id' => $sectionlist
                )
);

// Make the expand/collapse button.
$expandtext = get_string('sectionexpand', 'block_ucla_rearrange');
$collaptext = get_string('sectioncollapse', 'block_ucla_rearrange');
$expandbutton = html_writer::tag('div', $collaptext,
                array('class' => 'expand-button'));

$sectionzero = false;

// Hack a wrap around each set of HTML to generate the section wrappers.
foreach ($sectionnodeshtml as $section => $snh) {
    $siattr = array(
        'id' => 's-section-' . $section,
        'class' => 'section-item'
    );

    $sectnum = $sectnums[$section];

    if ($sectnum == 0) {
        $sectionzero = $section;
        $siattr['class'] .= ' ' . block_ucla_rearrange::SECTIONZERO;
    }

    $ishiddentext = '';
    if (!$sectionvisibility[$section]) {
        $ishiddentext = ' ' . html_writer::tag('span',
                        '(' . get_string('hidden', 'block_ucla_rearrange') . ')',
                        array('class' => block_ucla_rearrange::HIDDENCLASS));
    }

    $sectionshtml .= html_writer::tag(
                    'li',
                    html_writer::tag(
                        'div',
                        $sectionnames[$section] . $ishiddentext . $expandbutton,
                        array('class' => 'sectiontitle')
                    ) . $snh,
                    $siattr
    );
}

if ($sectionzero === false) {
    debugging(get_string('missing_section_zero', 'block_ucla_rearrange'));
}

$sectionshtml .= html_writer::end_tag('ul');

//   // Here is the primary setup for sortables.
//   $customvars = array(
//       'containerjq' => '#' . block_ucla_rearrange::PRIMARY_DOMNODE,
//       'expandtext' => $expandtext,
//       'collapsetext' => $collaptext,
//       'expandalltext' => get_string('allexpand', 'block_ucla_rearrange'),
//       'collapsealltext' => get_string('allcollapse', 'block_ucla_rearrange'),
//       'expandalljq' => '.expandall'
//   );
//   
//   // This enables nested sortables for all objects in the page with the class
//   // of "nested-sortables".
//   block_ucla_rearrange::setup_nested_sortable_js($sectionshtml,
//           '.' . block_ucla_rearrange::PAGELISTCLASS, $customvars);

// Used later to determine which section to redirect to after successful form submit.
$sectionredirect = $sectionnum;

// All prepped, now we need to add the actual rearrange form
// The form is useful since it lets us maintain serialized data and
// helps us filter stuff.
$rearrangeform = new ucla_rearrange_form(
                null,
                array(
                    'courseid' => $courseid,
                    'section' => $sectionnum,
                    'sectionshtml' => $sectionshtml
                ),
                'post',
                '',
                array('class' => 'ucla_rearrange_form')
);

if ($data = $rearrangeform->get_data()) {
    // document?
    $sectiondata = json_decode($data->serialized, true);

    $sectioncontents = array();
    $sectionorder = array();
    foreach ($sectiondata as $index => $section) {
        $id = $section['id'];
        $sectioncontents[$id] = empty($section['children']) ? array() : modnode::flatten($section['children']);
        $sectionorder[$id] = $index;
    }

    // Redirect eventually?

    // Section id to redirect to after moving the sections around.
    $sectionid = $DB->get_field('course_sections', 'id', array('course' => $course->id, 'section' => $sectionnum));

    // We're going to skip the API calls because it uses too many DBQ's.
    block_ucla_rearrange::move_modules_section_bulk($sectioncontents,
            $sectionorder);

    // Set the section correct value after moving sections around.
    if ( !$sectionredirect = $DB->get_field('course_sections', 'section', array('id' => $sectionid)) ) {
        // If no field is found, then the section we were on was either 'Site info' or 'Show all'.
        $sectionredirect = $sectionnum;
    }
    $_POST['section'] = $sectionredirect;

    // Now we need to swap all the contents in each section...
    rebuild_course_cache($courseid);
}

$restr = get_string('rearrange_sections', 'block_ucla_rearrange');
$restrc = "$restr: {$course->shortname}";

$PAGE->set_title($restrc);
$PAGE->set_heading($restrc);

echo $OUTPUT->header();
echo $OUTPUT->heading($restr, 2, 'headingblock');

if ($data != false) {
    $message = html_writer::tag('h3', get_string('success', 'block_ucla_rearrange'));
    $message .= get_string('rearrange_success', 'block_ucla_rearrange');

    // Return to site/section (where the user was).
    $secturl = new moodle_url('/course/view.php',
                    array('id' => $courseid, 'section' => $sectionredirect));
    if ($sectionredirect == UCLA_FORMAT_DISPLAY_ALL) {
        $secturl->remove_params('section');
        $secturl->param('show_all', 1);
    }
    $returntositebutton = new single_button($secturl,
            get_string('returntosite', 'block_ucla_rearrange'), 'get');

    // Rearrange more.
    $rearrangeurl = new moodle_url($PAGE->url,
            array('courseid' => $courseid, 'section' => $sectionredirect));
    $rearrangebutton = new single_button($rearrangeurl,
            get_string('rearrangemore', 'block_ucla_rearrange'), 'get');

    echo $OUTPUT->confirm($message, $returntositebutton, $rearrangebutton, 'success');

    $event = \block_ucla_rearrange\event\module_rearranged::create(array(
        'context' => $context
    ));
    $event->trigger();

} else {
    /* For section < 0, the secid doesnt matter because we will expand all.
     * However, if will give warning if we use $secid = ($sections[$sectionnum]->id);
     * as there is no secid for section < 0.
     */
    if ($sectionnum < 0) {
        $secid = ($sections[0]->id);
    } else {
        $secid = ($sections[$sectionnum]->id);
    }

    $rearrangeform->display();
    $PAGE->requires->js_call_amd('block_ucla_rearrange/rearrange', 'init', array($sectionnum, $secid));
//    $PAGE->requires->js_init_code(
//            "M.block_ucla_rearrange.initialize_rearrange_tool('$sectionnum', '$secid')"
//    );
}

echo $OUTPUT->footer();

// EOF.
