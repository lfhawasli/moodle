<?php
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
require_once($CFG->dirroot . '/local/ucla/classes/local_ucla_course_section_fixer.php');

global $CFG, $PAGE, $OUTPUT;

$courseid = required_param('courseid', PARAM_INT);
$justshowsuccessmessage = optional_param('success', 0, PARAM_INT);

// Carry the previously viewed section over and adjust it if it moves
// via the course section modifier.
$section_num = optional_param('section', 0, PARAM_INT);
$show_all = optional_param('show_all', 0, PARAM_INT);
$adjustnum = optional_param('adjustnum', 0, PARAM_BOOL);

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$format = course_get_format($course);
$format_compstr = 'format_' . $format->get_format();

require_login($course, true);
$context = get_context_instance(CONTEXT_COURSE, $courseid);

// Make sure you can view this page.
require_capability('moodle/course:update', $context);
require_capability('moodle/course:manageactivities', $context);

// set editing url to be section or default page
$allsections = $format->get_sections();
$sections = array();
$numsections = $format->get_course()->numsections;
foreach ($allsections as $k => $section) {    

    if ($section->section > $numsections) {
        continue;
    }

    $s = new stdClass();
    $s->name = $format->get_section_name($section);
    $s->id = $section->id;
    $s->section = $section->section;
    $s->visible = $section->visible;
    $s->course = $section->course;
    $s->sequence = $section->sequence;

    $sections[$k] = $s;
}

$courseviewurl = new moodle_url('/course/view.php', array('id' => $courseid, 'section' => $section_num));

// Set up the page.
$PAGE->set_context($context);

$PAGE->set_pagelayout('course');
$PAGE->set_pagetype('course-view-' . $course->format);
$params = array('courseid' => $courseid, 'section' => $section_num, 'show_all' => $show_all);
$PAGE->set_url('/blocks/ucla_modify_coursemenu/modify_coursemenu.php', 
        $params);

$confirmationurl = new moodle_url($PAGE->url,
    array(
            'courseid' => $courseid, 
            'section' => $section_num,
            'success' => true, 
        ));

$restr = get_string('pluginname', 'block_ucla_modify_coursemenu');
$restrc = "$restr: {$course->shortname}";

$PAGE->set_title($restrc);
$PAGE->set_heading($restrc);

// Sorry, but early escape, don't bother with work
if ($justshowsuccessmessage) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading($restr, 2, 'headingblock');

    $allsectionsurl = new moodle_url('/course/view.php', array('id' => $courseid));

    $allsectionsbutton = new single_button($allsectionsurl, get_string(
                'returntocourse', 'block_ucla_rearrange'
            ), 'get');

    if ($show_all) {
        $courseviewurl->remove_params('section');
        $courseviewurl->param('show_all', 1);
    }
    
    $sectionbutton = new single_button($courseviewurl, get_string(
                'returntosection', 'block_ucla_rearrange'
            ), 'get');

    echo $OUTPUT->confirm(get_string('successmodify', 
            'block_ucla_modify_coursemenu'), $allsectionsbutton,
        $sectionbutton);

    echo $OUTPUT->footer();
    die();
} else if ($adjustnum) {
    // Fix problem and redirect back to same page.
    $result = local_ucla_course_section_fixer::detect_numsections($course, $adjustnum);
    if ($result) {
        flash_redirect($PAGE->url, get_string('successnumsections', 'block_ucla_modify_coursemenu'));
    } else {
        $OUTPUT->notification(get_string('failurenumsections', 'block_ucla_modify_coursemenu'));
    }
}

// Before loading modinfo, make sure section information is correct.
local_ucla_course_section_fixer::fix_problems($course);

$modinfo = get_fast_modinfo($course);

// Get format configs.
$format_options = course_get_format($courseid)->get_format_options();
$landing_page = $format->get_course()->landing_page;
$hide_autogenerated_content = isset($format_options['hide_autogenerated_content']) ? 
        $format_options['hide_autogenerated_content'] : false;

// Note that forms will call $OUTPUT->pix_url which uses
// PAGE->theme which will autoload stuff I'm not certain enough to
// document here, so call $PAGE->set_context before loading forms
$modify_coursemenu_form = new ucla_modify_coursemenu_form(
    null,
    array(
            'courseid' => $courseid, 
            'section' => $section_num,
            'landing_page' => $landing_page,
            'hide_autogenerated_content' => $hide_autogenerated_content,
        ),
    'post',
    '',
    array('class' => 'ucla_modify_coursemenu_form')
);

// This is needed if we're deleting sections
$verifyform = new verify_modification_form();
$verifydata = false;

$redirector = null;

// Used to tell users that they cannot delete
$sectionsnotify = array();
$passthrudata = null;

//extract the data from the form and update the database
if ($modify_coursemenu_form->is_cancelled()) {
    redirect($courseviewurl);
} else if ($data = $modify_coursemenu_form->get_data()) {
    // TODO see if some of the fields can be parsed from within the MForm
    parse_str($data->serialized, $unserialized);
    parse_str($data->sectionsorder, $sectionorderparsed);
    
    // TODO make it consistent IN CODE how section id's are generated
    $sectionorder = array();
    foreach ($sectionorderparsed['sections-order'] as $k => $sectionid) {
        $sectnum = str_replace('section-', '', $sectionid);
        if ($sectnum == UCLA_FORMAT_DISPLAY_ALL 
                || $sectnum == '0') {
            continue;
        }

        // subtract 1 since we have to compensate for the pseudo-show-all
        // section
        if (is_int($k)) {        
            $k--;
        }

        $sectionorder[$k] = $sectnum;
    }

    // TODO make it consistent IN CODE how all these fields are generated
    $sectiondata = array();
    foreach ($unserialized as $fieldid => $value) {
        list($fieldtype, $sectionkey) = explode('-', $fieldid);
        if (!isset($sectiondata[$sectionkey])) {
            $sectiondata[$sectionkey] = array();
        }

        // Try to synchronize with Moodle field names
        if ($fieldtype == 'hidden') {
            $fieldtype = 'visible';
            $value = 0;
        }

        if ($fieldtype == 'title') {
            $fieldtype = 'name';
        }

        $sectiondata[$sectionkey][$fieldtype] = $value;
    }

    // Compare submitted data and current sections,
    // Set the ordering and the to-be-deleted sections
    $tobedeleted = array();
    $couldnotdelete = array();

    // Parse the landing page, for new sections, replace the data with proper
    // section number
    $landingpage = $data->landingpage;

    $landingpage_changed = false;
    $newsectnum = 0;
    foreach ($sectiondata as $oldsectnum => $sectdata) {
        $newsectnum++;

        // Fetch the old section's data
        if (!isset($sections[$oldsectnum])) {
            // It's a brand spanking new section, no existing sections to
            // even remotely use.
            $sectdata['course'] = $courseid;
            $section = (object) $sectdata;
        } else {
            // It a section that existed, and was < course.numsections
            $section = $sections[$oldsectnum];
        }

        // check to delete
        if (!empty($sectdata['delete'])) {
            // Notification mode needs to be turned on if the section is
            // not empty
            if (!block_ucla_modify_coursemenu::section_is_empty($section)) {
                $sectionsnotify[$oldsectnum] = $section;
            }

            $tobedeleted[] = $section;
            unset($sections[$oldsectnum]);
            $newsectnum--;
            continue;
        }
        
        $section->section = $newsectnum;
            
        if ($landingpage == $oldsectnum && !$landingpage_changed) {
            $landingpage = $newsectnum;
            $landingpage_changed = true;
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

    $passthrudata = new object();
    $passthrudata->sections = $sections;
    $passthrudata->deletesectionids = $deletesectionids;
    $passthrudata->landingpage = $landingpage;
    $passthrudata->coursenumsections = $newsectnum;

    // We need to add a validation thing for deleting sections
    if (!empty($sectionsnotify)) {
        // Generate html to display in the verifcation form
        $formdisplayhtml = get_string('deletesectioncontents',
            'block_ucla_modify_coursemenu') . html_writer::empty_tag('br')
                . $OUTPUT->heading(get_string('tbdel', 
                    'block_ucla_modify_coursemenu'), 2);

        // note: this section has potential to be copied if adding
        // delete functionality in JIT buttons
        // However, I do not want to function-ize it without properly
        // interfaced renderers
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
    
    // BEGIN UCLA MOD: CCLE-3273-Carry-over-previously-viewed-section
    $move_to_landingpage = false;
    
    $pos1 = array_keys($unserialized);
    
    // Remove sections that were deleted
    $rm_delete = preg_grep("/delete-/", $pos1);
    foreach ($rm_delete as $key => $val) {
        if ($key == $section_num) {
            $move_to_landingpage = true;
        }
        unset($pos1[$key]);
        unset($pos1[$key-1]);
    }
    
    // Remove sections that were hidden
    $rm_hidden = preg_grep("/hidden-/", $pos1);
    foreach ($rm_hidden as $key => $val) {
        unset($pos1[$key]);
    }
    
    // Array of sections after removing deleted and hidden sections
    $pos2 = array_values($pos1);

    if ($move_to_landingpage) {
        // If the section we are on was deleted, then move to the landing page
        $new_section = $landingpage;
    } else {
        // If we are on 'Site info' then stay on the same section
        // Otherwise we are on a section that can be moved; find where it moved
        $new_section = $section_num == 0 ? $section_num : array_search("title-$section_num", $pos2) + 1;
    }
    
    $confirmationurl = new moodle_url($PAGE->url,
        array(
            'courseid' => $courseid, 
            'section' => $new_section,
            'success' => true, 
        )); 
    
    // Redirect to 'Show all' if we were initially on it or
    // if the section we were on was deleted and the landing page is 'Show all'
    if ($section_num == UCLA_FORMAT_DISPLAY_ALL || $new_section == UCLA_FORMAT_DISPLAY_ALL) {
        $confirmationurl->remove_params('section');
        $confirmationurl->param('show_all', 1);
    }
    
    // END UCLA MOD: CCLE-3273

    $redirector = $confirmationurl;
} else if ($verifyform->is_cancelled()) {
    // Fill in data with state that has been changed.
    // TODO be more accurate
    $modify_coursemenu_form = new ucla_modify_coursemenu_form(
        null,
        array(
                'courseid' => $courseid, 
                'section' => $section_num,
                'sections'  => $sections,
                'landing_page' => $landing_page,
                'hide_autogenerated_content' => $hide_autogenerated_content,
            ),
        'post',
        '',
        array('class' => 'ucla_modify_coursemenu_form')
    );
}

// If we've verified we want to delete sections, or if we don't need
// to verify
$verifydata = $verifyform->get_data();
if ($passthrudata || $verifydata) {
    if (!$passthrudata) {
        $passthrudata = unserialize($verifydata->passthrudata);
    }

    $deletesectionids = $passthrudata->deletesectionids;
    if (!empty($deletesectionids)) {
        $DB->delete_records_list('course_sections', 'id', 
            $deletesectionids);
    }

    // Sections that were modified.
    $newsections = $passthrudata->sections;
    
    // Get all sections so that we have a reference of previous state.
    $originalsections = array();
    $sectionrecords = $DB->get_records('course_sections', array('course' => $courseid));
    // Set to be keyed by ID
    foreach ($sectionrecords as $r) {
        $originalsections[$r->id] = $r;
    }
        
    // Keep IDs of records that will be modified
    $updatedrecords = array();
    
    // Try the update with a transaction so that we can rollback if we fail.
    $moodletransaction = $DB->start_delegated_transaction();
    
    try {
        
        // Begin updating.  This will try to do two things:
        //  1. Rearrange sections
        //  2. Create new sections
        foreach($newsections as $section) {
            // Skip 'Site info' section
            if ($section->section == 0) {
                continue;
            }

            // Course/section pair is a unique index, and thus needs to be checked for duplicates
            $course_section_pair = array('course' => $section->course, 'section' => $section->section);

            // Modify section # to be negative that we don't get index key collisions.
            $section->section = -$section->section;

            // Conditions:
            //  A missing section ID => we have a new section
            //      In this case, we check if the record already exists,
            //      if so, then we update it with the new section info
            //      else we create a new record with section info.
            //
            //  If we have a section ID, then we are only updating the section record.
            //
            if (!isset($section->id)) {
                if ($record = $DB->get_record('course_sections', $course_section_pair)) {
                    $section->id = $record->id;
                    $DB->update_record('course_sections', $section);
                    $updatedrecords[] = $section->id;
                } else {
                    $newid = $DB->insert_record('course_sections', $section);
                    $updatedrecords[] = $newid;
                }
            } else {
                $DB->update_record('course_sections', $section);
                $updatedrecords[] = $section->id;
            }
        }

        // After we've organized our sections, we need to fix section #s.
        // We saved the modified record ids, so that we don't touch anything else.
        $sections = $DB->get_records_list('course_sections', 'id', $updatedrecords);

        foreach ($sections as $s) {
            // Fix section #
            $s->section = -$s->section;
            $DB->update_record('course_sections', $s);

            // Set section content visibility ONLY if section has content
            // and its visibility was updated.
            if (!empty($s->sequence) && $originalsections[$s->id]->visible != $s->visible) {
                set_section_visible($courseid, $s->section, $s->visible);
            }
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
    
    // Update the landing page
    course_get_format($courseid)->update_course_format_options(
            array('landing_page'=> $passthrudata->landingpage));

    // Update the course numsections
    course_get_format($courseid)->update_course_format_options(
            array('numsections'=> $passthrudata->coursenumsections));

    // Get the new values for sectioncache and modinfo
    // Maybe there is a better way?
    unset($course->sectioncache);
    unset($course->modinfo);
    rebuild_course_cache($course->id);
    
    $redirector = $confirmationurl;
}

// Before doing any heavy PAGE-related lifting, see if we should redirect to
// the success screen
// This will come here when the modifier form is submitted, but a section 
// with content is discovered, BUT the verify form has not been submitted
if ($data && empty($sectionsnotify) || $verifydata) {
    redirect($redirector);
}

// CCLE-3685 - If the course contains a syllabus, add it to array of sections
// Allows for the syllabus to be selected as the landing page
require_once($CFG->dirroot . '/local/ucla_syllabus/locallib.php');
$syllabus_manager = new ucla_syllabus_manager($course);
$syllabus_data = new StdClass();
$syllabus_data->can_host_syllabi = $syllabus_manager->can_host_syllabi();
if ($syllabus_data->can_host_syllabi) {
    $syllabus_list = $syllabus_manager->get_syllabi();
    $syllabus_section = is_null($syllabus_list[UCLA_SYLLABUS_TYPE_PRIVATE]) ? 
        $syllabus_list[UCLA_SYLLABUS_TYPE_PUBLIC] : $syllabus_list[UCLA_SYLLABUS_TYPE_PRIVATE];
    $syllabus_data->display_name = is_null($syllabus_section) ? 
        get_string('syllabus_needs_setup', 'local_ucla_syllabus') : $syllabus_section->__get('display_name');
    $syllabus_data->section = UCLA_FORMAT_DISPLAY_SYLLABUS;
}

$PAGE->requires->js('/blocks/ucla_modify_coursemenu/js/jquery-1.3.2.min.js');
$PAGE->requires->js('/blocks/ucla_modify_coursemenu/js/jquery.tablednd_0_5.js');
$PAGE->requires->js('/blocks/ucla_modify_coursemenu/modify_coursemenu.js');

$PAGE->requires->string_for_js('section0name', $format_compstr);
$PAGE->requires->string_for_js('newsection', 'block_ucla_modify_coursemenu');
$PAGE->requires->string_for_js('new_sectnum', 'block_ucla_modify_coursemenu');

// Load other things here for consistency 
$maintableid = block_ucla_modify_coursemenu::maintable_domnode;
block_ucla_modify_coursemenu::many_js_init_code_helpers(array(
        'course_format'  => $format_compstr,
        'table_id'       => $maintableid,
        'primary_id'     => block_ucla_modify_coursemenu::primary_domnode,
        'newsections_id' => block_ucla_modify_coursemenu::newnodes_domnode,
        'landingpage_id' => 
            block_ucla_modify_coursemenu::landingpage_domnode,
        'sectionsorder_id' => 
            block_ucla_modify_coursemenu::sectionsorder_domnode,
        'serialized_id' => 
            block_ucla_modify_coursemenu::serialized_domnode,
        'sectiondata' => $sections,
        'syllabusdata' => $syllabus_data,
    ));

$PAGE->requires->js_init_code(
    js_writer::set_variable('M.block_ucla_modify_coursemenu.pix.handle',
        $OUTPUT->pix_url('handle', 'block_ucla_modify_coursemenu')->out()
    ));

$PAGE->requires->string_for_js('show_all', $format_compstr);
block_ucla_modify_coursemenu::js_init_code_helper(
        'showallsection', UCLA_FORMAT_DISPLAY_ALL
    );

$PAGE->requires->js_init_code('M.block_ucla_modify_coursemenu.initialize()');

set_editing_mode_button($courseviewurl);

echo $OUTPUT->header();
echo $OUTPUT->heading($restr, 2, 'headingblock');

// Give alert if there are more sections than there are numsections.
$extrasections = local_ucla_course_section_fixer::detect_numsections($course);
if ($extrasections !== false) {
    $sectionlist = html_writer::alist($extrasections);
    $message = get_string('alertnumsections', 'block_ucla_modify_coursemenu', $sectionlist);
    $redirecturl = $PAGE->url;
    $redirecturl->param('adjustnum', true);
    $continue = new single_button($redirecturl, get_string('buttonnumsections', 'block_ucla_modify_coursemenu'));

    $output = $OUTPUT->box_start('generalbox', 'notice');
    $output .= html_writer::tag('p', $message);
    $output .= html_writer::tag('div', $OUTPUT->render($continue), array('class' => 'buttons'));
    $output .= $OUTPUT->box_end();
    echo $output;
}

// Any messages that need displaying?
flash_display();

if ($data && !empty($sectionsnotify) && !$verifydata) {
    $verifyform->display();
} else {
    $tablestructure = new html_table();
    $tablestructure->id = $maintableid;

    // Basics
    $ts_head = array('', 'section', 'title', 'hide', 'delete');

    // This is an add-on
    $ts_head[] = 'landing_page';

    $ts_headstrs = array();
    foreach ($ts_head as $ts_header) {
        if (!empty($ts_header)) {
            $ts_header = get_string($ts_header, 'block_ucla_modify_coursemenu');
        }

        $ts_headstrs[] = $ts_header;
    }

    $tablestructure->head = $ts_headstrs;

    echo html_writer::table($tablestructure);
    $modify_coursemenu_form->display();
}

echo $OUTPUT->footer();
