<?php
// This file is part of UCLA local plugin for Moodle - http://moodle.org/
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
 * The easy upload page accessed from the easy_upload block.
 *
 * @package    block_ucla_easyupload
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');

require_once($CFG->dirroot . '/course/lib.php');

$thispath = '/blocks/ucla_easyupload';
require_once($CFG->dirroot . $thispath . '/block_ucla_easyupload.php');
require_once($CFG->dirroot . $thispath . '/upload_form.php');

require_once($CFG->dirroot . '/local/publicprivate/lib/module.class.php');

global $CFG, $PAGE, $OUTPUT;

$courseid = required_param('course_id', PARAM_INT);
$type = required_param('type', PARAM_ALPHA);

$currsect = optional_param('section', 0, PARAM_INT);

// Stolen from /course/edit.php.
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$format = course_get_format($courseid);

require_login($course, true);
$context = context_course::instance($courseid);


// Make sure you can view this page.
require_capability('moodle/course:manageactivities', $context);

// Set up the page.
$PAGE->set_context($context);

$PAGE->set_pagelayout('incourse');
$PAGE->set_pagetype('course-view-' . $course->format);

$PAGE->set_url('/blocks/ucla_easyupload/upload.php',
        array('course_id' => $courseid, 'type' => $type));

// TODO Fix this Prep for return.
$cpurl = new moodle_url('/blocks/ucla_control_panel/view.php',
        array('course_id' => $courseid));

$courseurl = new moodle_url('/course/view.php',
        array('id' => $courseid));

// Type was not specified, or the form was cancelled...
if (!$type) {
    redirect($cpurl);
}

// Get all the informations for the form.
$modinfo = get_fast_modinfo($courseid);
$mods = $modinfo->get_cms();
$modnames = get_module_types_names();


// Prep things for activities.
//
// Check out /course/lib.php, under the "get_module_metadata()" function.
// If anything, it appears that $activities and $resources, which are
// used later on in this page, require arrays containing the names of
// the activity and resource modules that the "Add Activities/Resources"
// page will list (in a dropdown menu). "get_module_metadata()" can
// provide that information.
$modulemetadata = get_module_metadata($course, $modnames);
foreach ($modnames as $modname => $modnamestr) {
    if (!isset($modulemetadata[$modname])) {
        // If not set, then user cannot add given module.
        continue;
    }
    if ($modulemetadata[$modname]->archetype == MOD_ARCHETYPE_RESOURCE) {
        $resources[$modname] = $modulemetadata[$modname]->title;
    } else {
        $activities[$modname] = $modulemetadata[$modname]->title;
    }
}

// Prep things for section selector.
$sections = $modinfo->get_section_info_all();

$sectionnames = array();
$indexedsections = array();

$defaultsection = 0;

foreach ($sections as $section) {
    $sid = $section->id;
    if ($section->section == $currsect) {
        $defaultsection = $sid;
    }

    $sectionnames[$sid] = get_section_name($course, $section);

    $indexedsections[$sid] = $section;
}

// Prep things for rearrange.
$rearrangeavail = false;
if (block_ucla_easyupload::block_ucla_rearrange_installed()) {
    $rearrangeavail = true;
    $sectionmodnodes = block_ucla_rearrange::get_sections_modnodes(
        $courseid, $sections, $mods, $modinfo
    );

    $sectionnodeshtml = array();
    foreach ($sectionmodnodes as $index => $smn) {
        $snhtml = '';
        foreach ($smn as $modnode) {
            $snhtml .= $modnode->render();
        }
        $sectionnodeshtml[$index] = $snhtml;
    }

    // Start placing required javascript.
    // This is a set of custom javascript hooks.
    $PAGE->requires->js('/blocks/ucla_easyupload/javascript'
        . '/block_ucla_easyadd.js');
    $PAGE->requires->css('/blocks/ucla_rearrange/styles.css');

    // TODO watch out for multiheader.
    $dli = new modnode('new', null, 0);
    $dlihtml = $dli->render();
    $cv = array('empty_item' => $dlihtml);

    block_ucla_rearrange::setup_nested_sortable_js($sectionnodeshtml,
        '#thelist', $cv);
}

// End rearrange behavior.
$typeclass = block_ucla_easyupload::upload_type_exists($type);
if (!$typeclass) {
    print_error('typenotexists');
}

// Create the upload form. $typeclass should be a string
// of form easyupload_{$type}_form, so this will create the
// appropriate class that extends easy_upload_form.
$uploadform = new $typeclass(null,
    array(
        // Needed to come back to this script w/o error.
        'course' => $course,
        // Needed for some get_string().
        'type' => $type,
        // Needed for the section <SELECT>.
        'sectionnames' => $sectionnames,
        'defaultsection' => $defaultsection,
        // Needed when picking resources.
        'resources' => $resources,
        // Needed when picking activities.
        'activities' => $activities,
        // Needed to enable/disable rearrange.
        'rearrange' => $rearrangeavail
    ), 'post', '', array('class' => 'easyupload_form'));

if ($uploadform->is_cancelled()) {
    redirect($cpurl);
} else if ($data = $uploadform->get_data()) {
    // Confusing distinction between sectionid and sectionnumber.
    $targetsection = $data->section;
    $targetsectnum = $indexedsections[$targetsection]->section;
    $data->section = $targetsectnum;

    if (isset($data->redirectme)) {
        if (!method_exists($uploadform, 'get_send_params')) {
            print_error('redirectimplementationerror');
        }

        // This discrepancy is really terrible.
        $data->section = $targetsectnum;

        $params = $uploadform->get_send_params();

        $subtypes = explode('&', $data->add);

        if (count($subtypes) > 1) {
            $data->add = $subtypes[0];

            unset($subtypes[0]);

            foreach ($subtypes as $subtype) {
                $subtypeassign = explode('=', $subtype);
                $subtypestr = $subtypeassign[0];
                $subtypeval = $subtypeassign[1];

                $params[] = $subtypestr;
                $data->{$subtypestr} = $subtypeval;
            }
        }

        $getsends = array();
        foreach ($params as $param) {
            if ($param == 'private') {
                // Check if user wants to upload public activity/resource.
                if (isset($data->publicprivateradios) &&
                        !empty($data->publicprivateradios)) {
                    $ppsetting = $data->publicprivateradios['publicprivate'];

                    // Wants to upload, so set private=0.
                    if ($ppsetting == 'public') {
                        $getsends[$param] = 0;
                    }
                }
                continue;
            } else if (!isset($data->$param)) {
                print_error('missingparam', $param);
            }

            $getsends[$param] = $data->$param;
        }

        $dest = new moodle_url($data->redirectme, $getsends);

        redirect($dest);
    }

    // Pilfered parts from /course/modedit.php.
    $modulename = $data->modulename;
    // Module resource.
    $moddir = $CFG->dirroot . '/mod/' . $modulename;
    $modform = $moddir . '/mod_form.php';
    if (file_exists($modform)) {
        include_once($modform);
    } else {
        print_error('noformdesc');
    }

    $module = $DB->get_record('modules', array('name' => $modulename),
            '*', MUST_EXIST);

    if (!course_allowed_module($course, $modulename)) {
        print_error('moduledisable');
    }

    $addinstancefn = $modulename . '_add_instance';

    $newcm = new stdclass();
    $newcm->course = $course->id;
    $newcm->section = $targetsection;
    $newcm->module = $module->id;
    $newcm->instance = 0;

    // Observe course/modedit.php.
    if (!empty($CFG->enableavailability)) {
        $newcm->availability = $data->availabilityconditionsjson;
    }

    // Make course content the same visibility as parent section.
    $newcm->visible = $DB->get_field('course_sections', 'visible',
            array('id' => $targetsection));

    if (isset($data->showdescription) && $data->showdescription) {
        $newcm->showdescription = 1;
    }

    $coursemoduleid = add_course_module($newcm);
    if (!$coursemoduleid) {
        print_error('cannotaddnewmodule');
    }

    $data->coursemodule = $coursemoduleid;

    if (plugin_supports('mod', $modulename, FEATURE_MOD_INTRO, true)
            && !empty($data->introeditor)) {
        $introeditor = $data->introeditor;
        unset($data->introeditor);

        $data->intro       = $introeditor['text'];
        $data->introformat = $introeditor['format'];
    }

    $instanceid = $addinstancefn($data, $uploadform);

    if (!$instanceid || !is_number($instanceid)) {
        // Undo everything we can.
        delete_context(CONTEXT_MODULE, $coursemoduleid);

        $DB->delete_records('course_modules', array('id' => $coursemoduleid));

        print_error('cannotaddnewmodule', '',
            'view.php?id=' . $course->id . '#section-' . $data->section,
            $coursemoduleid);
    }

    $sectionid = course_add_cm_to_section($data->course, $data->coursemodule, $data->section);

    $DB->set_field('course_modules', 'instance', $instanceid,
        array('id' => $coursemoduleid));

    // Public Private.
    if (class_exists('PublicPrivate_Module')
            && PublicPrivate_Site::is_enabled()) {
        if (!empty($data->publicprivateradios)) {
            $ppsetting = $data->publicprivateradios['publicprivate'];
        } else {
            $ppsetting = 'public';
        }

        $pp = new PublicPrivate_Module($coursemoduleid);

        if ($ppsetting == 'public') {
            $pp->disable();
        } else {
            $pp->enable();
        }
    }

    if (!isset($data->serialized) || empty($data->serialized)) {
        // Assume that we're not changing the order.
        $sequencearr = false;
    } else {
        parse_str($data->serialized, $parsed);
        $newmods = modnode::flatten($parsed['thelist']);

        $sequencearr = array();
        foreach ($newmods as $newmod) {
            if ($newmod->id == 'new') {
                $newmod->id = $coursemoduleid;
            }

            $sequencearr[$newmod->id] = $newmod->id;
        }
    }

    if (isset($newmods) && $sequencearr) {
        // This implies that we have rearrange available.
        $newmodules = array($sectionid => $newmods);
        block_ucla_rearrange::move_modules_section_bulk($newmodules);
    }

    $event = \block_ucla_easyupload\event\course_module_created::create(array(
        'other' => array(
            'module' => $data->modulename,
            'name' => $data->name,
        ),
        'objectid' => $data->coursemodule,
        'context' => $context
    ));
    $event->trigger();

    $eventdata = new stdClass();
    $eventdata->modulename = $data->modulename;
    $eventdata->name       = $data->name;
    $eventdata->cmid       = $data->coursemodule;
    $eventdata->courseid   = $data->course_id;
    $eventdata->userid     = $USER->id;
    events_trigger_legacy('mod_created', $eventdata);

    rebuild_course_cache($courseid);
}

// Display the rest of the page.
$title = get_string($typeclass, 'block_ucla_easyupload', $course->fullname);

$PAGE->set_title($title);
$PAGE->set_heading($title);

// Print out the header and blocks.
echo $OUTPUT->header();

// Print out a heading.
echo $OUTPUT->heading($title, 2, 'headingblock');

if (!isset($data) || !$data) {
    $uploadform->display();
} else {
    // Do not draw the form!
    $message = get_string('successfuladd', 'block_ucla_easyupload', $type);

    $params = array('id' => $courseid);

    $formatoptions = course_get_format($courseid)->get_format_options();
    if (isset($formatoptions['landing_page'])) {
        $params['section'] = $formatoptions['landing_page'];
    }

    $courseurl = new moodle_url('/course/view.php', $params);
    $courseret = new single_button($courseurl, get_string('returntocourse',
            'block_ucla_easyupload'), 'get');

    $secturl = new moodle_url('/course/view.php', $params);
    $secturl->param('section', $indexedsections[$sectionid]->section);
    $sectret = new single_button($secturl, get_string('returntosection',
            'block_ucla_easyupload'), 'get');

    echo $OUTPUT->confirm($message, $courseret, $sectret);
}

echo $OUTPUT->footer();
