<?php
// This file is part of UCLA Modify Coursemenu plugin for Moodle - http://moodle.org/
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
 *  Rearrange sections and course modules.
 *
 *  How this works:
 *      First, user is sent to the modify_coursemenu_form().
 *      Once that data has been submitted with its funky JS UI,
 *          they come back here, and modify_coursemenu_form()->get_data() is
 *          processed.
 *      If the processing states that a verification form is needed, it will
 *          populate the verify_modifications_form() with "pass-thru" data
 *          and display that form.
 *      Once the verification form is processed, then the DB changes will
 *          occur, and then a success message will be displayed.
 *      If the processing states that no verification is needed, then the
 *          DB changes occur, and then a success message is displayed.
 *
 * @package    block_ucla_modify_coursemenu
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/course/lib.php');
// Hm, dependent on UCLA format...
require_once($CFG->dirroot . '/course/format/ucla/lib.php');
$thispath = '/blocks/ucla_modify_coursemenu';
require_once($CFG->dirroot . $thispath . '/block_ucla_modify_coursemenu.php');
require_once($CFG->dirroot . $thispath . '/modify_coursemenu_form.php');
require_once($CFG->dirroot . $thispath . '/verify_modification_form.php');

require_once($CFG->dirroot . '/local/ucla/lib.php');

global $CFG, $PAGE, $OUTPUT;

$courseid = required_param('courseid', PARAM_INT);
$justshowsuccessmessage = optional_param('success', 0, PARAM_INT);

// Carry the previously viewed section over and adjust it if it moves
// via the course section modifier.
$sectionnum = optional_param('section', 0, PARAM_INT);
$showall = optional_param('show_all', 0, PARAM_INT);

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$format = course_get_format($course);
$formatcompstr = 'format_' . $format->get_format();

require_login($course, true);
$context = context_course::instance($courseid);

// Make sure you can view this page.
require_capability('moodle/course:update', $context);
require_capability('moodle/course:manageactivities', $context);

// Set editing url to be section or default page.
$allsections = $format->get_sections();
$sections = array();
foreach ($allsections as $k => $section) {
    $s = new stdClass();
    $s->name = $format->get_section_name($section);
    $s->id = $section->id;
    $s->section = $section->section;
    $s->visible = $section->visible;
    $s->course = $section->course;
    $s->sequence = $section->sequence;

    $sections[$k] = $s;
}

$courseviewurl = new moodle_url('/course/view.php', array('id' => $courseid, 'section' => $sectionnum));

// Set up the page.
$PAGE->set_context($context);

$PAGE->set_pagelayout('base');
$params = array('courseid' => $courseid, 'section' => $sectionnum, 'show_all' => $showall);
$PAGE->set_url('/blocks/ucla_modify_coursemenu/modify_coursemenu.php',
        $params);

$confirmationurl = new moodle_url($PAGE->url,
    array(
            'courseid' => $courseid,
            'section' => $sectionnum,
            'success' => true,
        ));

$restr = get_string('pluginname', 'block_ucla_modify_coursemenu');
$restrc = "$restr: {$course->shortname}";

$PAGE->set_title($restrc);
$PAGE->set_heading($restrc);

// If we're just showing the success message, exit immediately afterward.
if ($justshowsuccessmessage) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading($restr, 2, 'headingblock');

    $message = html_writer::tag('h3', get_string('success', 'block_ucla_rearrange'));
    $message .= get_string('successmodify', 'block_ucla_modify_coursemenu');

    // Return to site/section (where the user was).
    if ($showall) {
        $courseviewurl->remove_params('section');
        $courseviewurl->param('show_all', 1);
    }
    $returntositebutton = new single_button($courseviewurl,
            get_string('returntosite', 'block_ucla_rearrange'),
            'get');

    // Modify more sections.
    $modifymoreurl = new moodle_url($PAGE->url, $params);
    $modifymorebutton = new single_button($modifymoreurl,
            get_string('modifymore', 'block_ucla_modify_coursemenu'),
            'get');

    echo $OUTPUT->confirm($message, $returntositebutton, $modifymorebutton, 'success');

    echo $OUTPUT->footer();

    $event = \block_ucla_modify_coursemenu\event\course_menu_modified::create(array(
        'context' => $context
    ));
    $event->trigger();

    die();
}

// Before loading modinfo, make sure section information is correct.
local_ucla_course_section_fixer::fix_problems($course);

$modinfo = get_fast_modinfo($course);

// Get format configs.
$formatoptions = course_get_format($courseid)->get_format_options();
$landingpage = $format->get_course()->landing_page;
$optionhideautogenerated = $formatoptions['hide_autogenerated_content'];
$hideautogeneratedcontent = isset($optionhideautogenerated) ? $optionhideautogenerated : false;
// Set and remember the enablelandingpagebydates setting.
$optionenablelandingpagebydates = $formatoptions['enable_landingpage_by_dates'];
$enablelandingpagebydates = isset($optionenablelandingpagebydates) ? $optionenablelandingpagebydates : false;

// JSONify and pass date range information to the front end for "Landing Page by Dates".
$daterangejson = encode_landing_page_by_dates($courseid);

// Note that forms will call $OUTPUT->pix_url which uses
// PAGE->theme which will autoload stuff I'm not certain enough to
// document here, so call $PAGE->set_context before loading forms.
$modifycoursemenuform = new ucla_modify_coursemenu_form(
    null,
    array(
            'courseid' => $courseid,
            'section' => $sectionnum,
            'landing_page' => $landingpage,
            'daterangejson' => $daterangejson,
            'hide_autogenerated_content' => $hideautogeneratedcontent,
            'enable_landingpage_by_dates' => $enablelandingpagebydates,
        ),
    'post',
    '',
    array('class' => 'ucla_modify_coursemenu_form', 'id' => 'add_submit_form')
);

// This is needed if we're deleting sections.
$verifyform = new verify_modification_form();
$verifydata = false;

$redirector = null;

// Used to tell users that they cannot delete.
$sectionsnotify = array();
$passthrudata = null;

// Extract the data from the form and update the database.
if ($modifycoursemenuform->is_cancelled()) {
    redirect($courseviewurl);
} else if ($data = $modifycoursemenuform->get_data()) {
    // TODO see if some of the fields can be parsed from within the MForm.
    parse_str($data->serialized, $unserialized);
    parse_str($data->sectionsorder, $sectionorderparsed);

    // TODO make it consistent IN CODE how section id's are generated.
    $sectionorder = array();
    foreach ($sectionorderparsed['sections-order'] as $k => $sectionid) {
        $sectnum = str_replace('section-', '', $sectionid);
        if ($sectnum == UCLA_FORMAT_DISPLAY_ALL
                || $sectnum == '0') {
            continue;
        }

        // Subtract 1 since we have to compensate for the pseudo-show-all section.
        if (is_int($k)) {
            $k--;
        }

        $sectionorder[$k] = $sectnum;
    }

    // TODO make it consistent IN CODE how all these fields are generated.
    $sectiondata = array();
    foreach ($unserialized as $fieldid => $value) {
        list($fieldtype, $sectionkey) = explode('-', $fieldid);
        if (!isset($sectiondata[$sectionkey])) {
            $sectiondata[$sectionkey] = array();
        }

        // Try to synchronize with Moodle field names.
        if ($fieldtype == 'hidden') {
            $fieldtype = 'visible';
            $value = 0;
        }

        if ($fieldtype == 'title') {
            $fieldtype = 'name';
        }

        $sectiondata[$sectionkey][$fieldtype] = $value;
    }

    // Compare submitted data and current sections.
    // Set the ordering and the to-be-deleted sections.
    $tobedeleted = array();
    $couldnotdelete = array();

    // Parse the landing page, for new sections, replace the data with proper
    // section number.
    $landingpage = $data->landingpage;

    $landingpagechanged = false;
    $newsectnum = 0;
    foreach ($sectiondata as $oldsectnum => $sectdata) {
        $newsectnum++;

        // Fetch the old section's data.
        if (!isset($sections[$oldsectnum])) {
            // It's a brand spanking new section, no existing sections to
            // even remotely use.
            $sectdata['course'] = $courseid;
            $section = (object) $sectdata;
        } else {
            // It's a section that existed.
            $section = $sections[$oldsectnum];
        }

        // Check to delete.
        if (!empty($sectdata['delete'])) {
            // Notification mode needs to be turned on if the section is not empty.
            if (!block_ucla_modify_coursemenu::section_is_empty($section)) {
                $sectionsnotify[$oldsectnum] = $section;
            }

            $tobedeleted[] = $section;
            unset($sections[$oldsectnum]);
            $newsectnum--;
            continue;
        }

        $section->section = $newsectnum;

        if ($landingpage == $oldsectnum && !$landingpagechanged) {
            $landingpage = $newsectnum;
            $landingpagechanged = true;
        }

        $section = block_ucla_modify_coursemenu::section_apply($section,
            $sectdata);

        $sections[$oldsectnum] = $section;
    }

    // Delete some sections...how to do this?
    $deletesectionids = array();
    foreach ($tobedeleted as $todelete) {
        // Double check?
        if (isset($todelete->id)) {
            $deletesectionids[] = $todelete->id;
        }
    }

    $passthrudata = new stdClass();
    $passthrudata->sections = $sections;
    $passthrudata->deletesectionids = $deletesectionids;
    $passthrudata->landingpage = $landingpage;

    // We need to add a validation thing for deleting sections.
    if (!empty($sectionsnotify)) {
        // Generate html to display in the verifcation form.
        $formdisplayhtml = get_string('deletesectioncontents',
            'block_ucla_modify_coursemenu') . html_writer::empty_tag('br')
                . $OUTPUT->heading(get_string('tbdel',
                    'block_ucla_modify_coursemenu'), 2);

        // Note: this section has potential to be copied if adding delete functionality in JIT buttons.
        // However, I do not want to function-ize it without properly interfaced renderers.
        foreach ($sectionsnotify as $oldsectnum => $sectionnotify) {
            $sectionhtml = $OUTPUT->heading($sectionnotify->name, 4)
                . html_writer::start_tag('ul');

            $cminfos = block_ucla_modify_coursemenu::get_section_content(
                    $sectionnotify, $course, $modinfo
                );

            foreach ($cminfos as $cminstance) {
                $instancename = $cminstance->get_formatted_name();

                $sectionhtml .= html_writer::tag(
                        'li',
                        $instancename . ' ('
                            . get_string('modulename',
                                $cminstance->modname) . ')'
                    );
            }

            $sectionhtml .= html_writer::end_tag('ul');

            $formdisplayhtml .= $sectionhtml;
        }

        $formdisplayhtml = $OUTPUT->box($formdisplayhtml,
            'modify-course-sections-summary generalbox');

        $verifyform = new verify_modification_form(
                null,
                array(
                    'passthrudata' => $passthrudata,
                    'courseid' => $courseid,
                    'displayhtml' => $formdisplayhtml,
                )
            );

        $passthrudata = null;
    }

    course_get_format($courseid)->update_course_format_options(
            array('hide_autogenerated_content' => $data->hideautogeneratedcontent));

    // Update enablelandingpagebydates option.
    course_get_format($courseid)->update_course_format_options(
            array('enable_landingpage_by_dates' => $data->enablelandingpagebydates));

    // Create new daterange data records for "Landing Page by Dates".
    $lpdrows = create_landing_page_by_dates_rows(json_decode($data->daterange), $courseid);

    // Attempt to clear and update the database with our new "Landing Page by Dates" dateranges.
    // This is stateful and clears the old records before reinserting the new ones.
    try {
        $transaction = $DB->start_delegated_transaction();
        // Clear the table of all date ranges of the current course ID ("clear the state").
        $DB->delete_records('ucla_modify_coursemenu', array('courseid' => $courseid));
        // Insert the new records into the table.
        $DB->insert_records('ucla_modify_coursemenu', $lpdrows);

        $transaction->allow_commit();

        // Create or update cache.
        $cache = cache::make('block_ucla_modify_coursemenu', 'landingpagebydatesdb');
        // Attempt to update timestamp until it succeeds.
        while(!$cache->set($courseid, time()));
    } catch(Exception $e) {
        $transaction->rollback($e);
    }

    // BEGIN UCLA MOD: CCLE-3273-Carry-over-previously-viewed-section.
    $movetolandingpage = false;

    $pos1 = array_keys($unserialized);

    // Remove sections that were deleted.
    $rmdelete = preg_grep("/delete-/", $pos1);
    foreach ($rmdelete as $key => $val) {
        if ($key == $sectionnum) {
            $movetolandingpage = true;
        }
        unset($pos1[$key]);
        unset($pos1[$key - 1]);
    }

    // Remove sections that were hidden.
    $rmhidden = preg_grep("/hidden-/", $pos1);
    foreach ($rmhidden as $key => $val) {
        unset($pos1[$key]);
    }

    // Array of sections after removing deleted and hidden sections.
    $pos2 = array_values($pos1);

    if ($movetolandingpage) {
        // If the section we are on was deleted, then move to the landing page.
        $newsection = $landingpage;
    } else {
        // If we are on 'Site info' then stay on the same section.
        // Otherwise we are on a section that can be moved; find where it moved.
        $newsection = $sectionnum == 0 ? $sectionnum : array_search("title-$sectionnum", $pos2) + 1;
    }

    $confirmationurl = new moodle_url($PAGE->url,
        array(
            'courseid' => $courseid,
            'section' => $newsection,
            'success' => true,
        ));

    // Redirect to 'Show all' if we were initially on it or
    // if the section we were on was deleted and the landing page is 'Show all'.
    if ($sectionnum == UCLA_FORMAT_DISPLAY_ALL || $newsection == UCLA_FORMAT_DISPLAY_ALL) {
        $confirmationurl->remove_params('section');
        $confirmationurl->param('show_all', 1);
    }

    // END UCLA MOD: CCLE-3273.

    $redirector = $confirmationurl;
} else if ($verifyform->is_cancelled()) {
    // Fill in data with state that has been changed.
    // TODO: Be more accurate.
    $modifycoursemenuform = new ucla_modify_coursemenu_form(
        null,
        array(
                'courseid' => $courseid,
                'section' => $sectionnum,
                'sections'  => $sections,
                'landing_page' => $landingpage,
                'hide_autogenerated_content' => $hideautogeneratedcontent,
                'enable_landingpage_by_dates' => $enablelandingpagebydates,
            ),
        'post',
        '',
        array('class' => 'ucla_modify_coursemenu_form')
    );
}

// If we've verified we want to delete sections. If we haven't, we need to.
$verifydata = $verifyform->get_data();
if ($passthrudata || $verifydata) {
    if (!$passthrudata) {
        $passthrudata = unserialize($verifydata->passthrudata);
    }

    $deletesectionids = $passthrudata->deletesectionids;
    if (!empty($deletesectionids)) {
        foreach ($deletesectionids as &$sectionidstodelete) {
            $sectiontodelete = $DB->get_record('course_sections', array('id' => $sectionidstodelete));
            course_delete_section($courseid, $sectiontodelete, true);
        }
    }

    // Sections that were modified.
    $newsections = $passthrudata->sections;

    // Get all sections so that we have a reference of previous state.
    $originalsections = array();
    $sectionrecords = $DB->get_records('course_sections', array('course' => $courseid));
    // Set to be keyed by ID.
    foreach ($sectionrecords as $r) {
        $originalsections[$r->id] = $r;
    }

    // Keep IDs of records that will be modified.
    $updatedrecords = array();

    // Try the update with a transaction so that we can rollback if we fail.
    $moodletransaction = $DB->start_delegated_transaction();

    try {

        // Begin updating. This will try to do two things:
        // 1. Rearrange sections.
        // 2. Create new sections.
        foreach ($newsections as $section) {
            // Skip 'Site info' section.
            if ($section->section == 0) {
                continue;
            }

            // Course/section pair is a unique index, and thus needs to be checked for duplicates.
            $coursesectionpair = array('course' => $section->course, 'section' => $section->section);

            // Modify section # to be negative that we don't get index key collisions.
            $section->section = -$section->section;

            // Conditions:
            // A missing section ID => we have a new section.
            // In this case, we check if the record already exists,
            // if so, then we update it with the new section info
            // else we create a new record with section info.
            //
            // If we have a section ID, then we are only updating the section record.
            //
            if (!isset($section->id)) {
                if ($record = $DB->get_record('course_sections', $coursesectionpair)) {
                    $section->id = $record->id;
                    $DB->update_record('course_sections', $section);
                    $updatedrecords[] = $section->id;
                } else {
                    // Set summaryformat to FORMAT_HTML so that new sections have the
                    // editor set to HTML by default.
                    $section->summaryformat = FORMAT_HTML;
                    $newid = $DB->insert_record('course_sections', $section);
                    $updatedrecords[] = $newid;
                }
            } else {
                // Set section content visibility ONLY if section has content
                // and its visibility was updated.
                if (!empty($section->sequence) && ($originalsections[$section->id]->visible != $section->visible)) {
                    set_section_visible($courseid, -$section->section, $section->visible);
                }
                // Update section number.
                $DB->update_record('course_sections', $section);
                $updatedrecords[] = $section->id;
            }
        }

        // After we've organized our sections, we need to fix section #s.
        // We saved the modified record ids, so that we don't touch anything else.
        $sections = $DB->get_records_list('course_sections', 'id', $updatedrecords);

        foreach ($sections as $s) {
            // Fix section number.
            $s->section = -$s->section;
            $DB->update_record('course_sections', $s);
        }

    } catch (Exception $e) {
        // We hit an exception, rollback.
        try {
            // Rolling back a transaction will rethrow the error. We want to
            // display a more user friendly message.
            $DB->rollback_delegated_transaction($moodletransaction, $e);
        } catch (Exception $e) {
            print_error('failuremodify', 'block_ucla_modify_coursemenu');
        }
    }

    // We finished successfully, commit transaction.
    $DB->commit_delegated_transaction($moodletransaction);

    // Update the landing page.
    course_get_format($courseid)->update_course_format_options(
            array('landing_page' => $passthrudata->landingpage));

    // Get the new values for sectioncache and modinfo.
    // Maybe there is a better way?
    unset($course->sectioncache);
    unset($course->modinfo);
    rebuild_course_cache($course->id);

    $redirector = $confirmationurl;
}

// Before doing any heavy PAGE-related lifting, see if we should redirect to
// the success screen.
// This will come here when the modifier form is submitted, but a section
// with content is discovered, BUT the verify form has not been submitted.
if ($data && empty($sectionsnotify) || $verifydata) {
    redirect($redirector);
}

// CCLE-3685 - If the course contains a syllabus, add it to array of sections.
// Allows for the syllabus to be selected as the landing page.
require_once($CFG->dirroot . '/local/ucla_syllabus/locallib.php');
$syllabusmanager = new ucla_syllabus_manager($course);
$syllabusdata = new StdClass();
$syllabusdata->can_host_syllabi = $syllabusmanager->can_host_syllabi();
if ($syllabusdata->can_host_syllabi) {
    $syllabuslist = $syllabusmanager->get_syllabi();

    $syllabuslistelement = $syllabuslist[UCLA_SYLLABUS_TYPE_PRIVATE];
    $syllabussection = is_null($syllabuslistelement) ? $syllabuslist[UCLA_SYLLABUS_TYPE_PUBLIC] : $syllabuslistelement;

    $syllabusstring = get_string('syllabus_needs_setup', 'local_ucla_syllabus');
    $syllabusdata->display_name = is_null($syllabussection) ? $syllabusstring : $syllabussection->__get('display_name');
    $syllabusdata->section = UCLA_FORMAT_DISPLAY_SYLLABUS;
}

$PAGE->requires->js('/blocks/ucla_modify_coursemenu/js/jquery-3.3.1.min.js');
$PAGE->requires->js('/blocks/ucla_modify_coursemenu/js/jquery.tablednd.js');
$PAGE->requires->yui_module('moodle-core-formchangechecker',
        'M.core_formchangechecker.init', array(array(
            'formid' => 'tableform'
        )));
$PAGE->requires->yui_module('moodle-core-formchangechecker',
        'M.core_formchangechecker.init', array(array(
            'formid' => 'add_submit_form'
        )));
$PAGE->requires->string_for_js('changesmadereallygoaway', 'moodle');
$PAGE->requires->js('/blocks/ucla_modify_coursemenu/js/flatpickr.min.js');
$PAGE->requires->js('/blocks/ucla_modify_coursemenu/modify_coursemenu.js');
$PAGE->requires->css('/blocks/ucla_modify_coursemenu/css/flatpickr.min.css');

$PAGE->requires->string_for_js('section0name', $formatcompstr);
$PAGE->requires->string_for_js('newsection', 'block_ucla_modify_coursemenu');
$PAGE->requires->string_for_js('new_sectnum', 'block_ucla_modify_coursemenu');
$PAGE->requires->string_for_js('landingpagebydatesto', 'block_ucla_modify_coursemenu');
$PAGE->requires->string_for_js('landingpagebydatesempty', 'block_ucla_modify_coursemenu');
$PAGE->requires->string_for_js('landingpagebydatesequivalent', 'block_ucla_modify_coursemenu');
$PAGE->requires->string_for_js('landingpagebydatessequential', 'block_ucla_modify_coursemenu');
$PAGE->requires->string_for_js('landingpagebydatesstartoverlap', 'block_ucla_modify_coursemenu');
$PAGE->requires->string_for_js('landingpagebydatesrangeoverlap', 'block_ucla_modify_coursemenu');

// Load other things here for consistency.
$maintableid = block_ucla_modify_coursemenu::MAINTABLE_DOMNODE;
block_ucla_modify_coursemenu::many_js_init_code_helpers(array(
        'course_format'  => $formatcompstr,
        'table_id'       => $maintableid,
        'primary_id'     => block_ucla_modify_coursemenu::PRIMARY_DOMNODE,
        'newsections_id' => block_ucla_modify_coursemenu::NEWNODES_DOMNODE,
        'landingpage_id' => block_ucla_modify_coursemenu::LANDINGPAGE_DOMNODE,
        'sectionsorder_id' => block_ucla_modify_coursemenu::SECTIONSORDER_DOMNODE,
        'serialized_id' => block_ucla_modify_coursemenu::SERIALIZED_DOMNODE,
        'daterange_id' => block_ucla_modify_coursemenu::DATERANGE_DOMNODE,
        'sectiondata' => $sections,
        'syllabusdata' => $syllabusdata,
    ));

$PAGE->requires->js_init_code(
    js_writer::set_variable('M.block_ucla_modify_coursemenu.pix.handle',
        $OUTPUT->image_url('handle', 'block_ucla_modify_coursemenu')->out()
    ));

$PAGE->requires->string_for_js('show_all', $formatcompstr);
block_ucla_modify_coursemenu::js_init_code_helper(
        'showallsection', UCLA_FORMAT_DISPLAY_ALL
    );

$PAGE->requires->js_init_code('M.block_ucla_modify_coursemenu.initialize()');

set_editing_mode_button($courseviewurl);

echo $OUTPUT->header();
echo $OUTPUT->heading($restr, 2, 'headingblock');

// Any messages that need displaying?
flash_display();

if ($data && !empty($sectionsnotify) && !$verifydata) {
    $verifyform->display();
} else {
    $tablestructure = new html_table();
    $tablestructure->id = $maintableid;

    // Basics.
    $tshead = array('', 'section', 'title', 'hide', 'delete');

    // This is an add-on.
    $tshead[] = 'landing_page';
    // Add new column for Landing Page by Dates.
    $tshead[] = 'landing_page_by_dates';

    $tsheadstrs = array();
    foreach ($tshead as $tsheader) {
        if (!empty($tsheader)) {
            $tsheader = get_string($tsheader, 'block_ucla_modify_coursemenu');
        }

        $tsheadstrs[] = $tsheader;
    }

    $tablestructure->head = $tsheadstrs;

    // Create the table form.
    echo html_writer::start_tag('form', array('id' => 'tableform', 'class' => 'ucla_modify_coursemenu_form mform',
        'autocomplete' => 'off', 'action' => $PAGE->url->out(), 'method' => 'post', 'accept-charset' => 'utf-8'));
    echo html_writer::table($tablestructure);
    echo html_writer::end_tag('form');

    $modifycoursemenuform->display();
}

echo $OUTPUT->footer();

/**
 * This function is a part of the "Landing Page by Dates" option. It
 * encodes date ranges from the database to send to our Javascript frontend.
 *
 * @param int    $courseid The courseid of the current course
 * @return array JSON encoded array of date ranges
 */
function encode_landing_page_by_dates($courseid) {
    global $DB;
    $daterangearray = array();
    $daterangeresult = $DB->get_records('ucla_modify_coursemenu', array('courseid' => $courseid), null);

    foreach ($daterangeresult as $daterangerow) {
        $daterangeinput = new stdClass();
        $daterangeinput->datestart = $daterangerow->timestart;
        if ($daterangerow->timeend != null) {
            $daterangeinput->dateend = $daterangerow->timeend;
        }

        $daterangearray[$daterangerow->sectionid] = $daterangeinput;
    }
    return(json_encode($daterangearray));
}

/**
 * This function is a part of the "Landing Page by Dates" option. It
 * turns new date range data from the Javascript front end into database ready row objects.
 *
 * @param array  $parsed_json Parsed JSON of date range info from our Javascript frontend
 * @param int    $courseid The courseid of the current course
 * @return array Array of row objects to be inserted in the database table
 */
function create_landing_page_by_dates_rows($parsed_json, $courseid) {
    $daterecords = array();
    foreach ($parsed_json as $daterangeid => $daterange) {
        // Get the IDs, times, and other values to proper types and timestamps.
        $daterecord = new stdClass();
        $timestart = $daterange->datestart;
        if ($daterange->dateend == null) {
            $daterecord->timeend = null;
        } else {
            $daterecord->timeend = $daterange->dateend;
        }
        $daterangeconditions = array(
            'courseid' => $courseid,
            'sectionid' => (int) $daterangeid
        );

        $daterecord->courseid = $courseid;
        $daterecord->sectionid = (int) $daterangeid;
        $daterecord->timestart = $timestart;
        $daterecords[] = $daterecord;
    }
    return $daterecords;
}
