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
 * Allows for display and management of syllabi.
 *
 * Includes core functions for course syllabus: uploading, editing,
 * displaying, removing, downloading, and making private. Responsible
 * for actual interface of syllabus page.
 *
 * @package     local_ucla_syllabus
 * @copyright   2012 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// @codingStandardsIgnoreLine
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once(dirname(__FILE__).'/syllabus_form.php');
require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->libdir . '/resourcelib.php');

// Get script variables to be used later.
$id = required_param('id', PARAM_INT);
$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
$syllabusmanager = new ucla_syllabus_manager($course);
$coursecontext = context_course::instance($course->id);
$canmanagesyllabus = $syllabusmanager->can_manage();

// See if user wants to do an action for a given syllabus type.
$action = optional_param('action', null, PARAM_ALPHA);
$type = optional_param('type', null, PARAM_ALPHA);

// Set up page.
$PAGE->set_course($course);
$PAGE->set_url('/local/ucla_syllabus/index.php', array('id' => $id));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($coursecontext);
$PAGE->set_pagelayout('incourse');
$PAGE->set_pagetype('course-view-' . $course->format);

// See if user wants to handle a manually uploaded syllabus. If a user has not
// chosen what to do with a manual syllabus yet, but passing an $action and
// $type, then prompt them what to do next.
$manualsyllabusid = optional_param('manualsyllabus', null, PARAM_INT);

// Check if manually uploaded syllabus is valid.
if (!empty($manualsyllabusid)) {
    $validsyllabus = false;
    if ($canmanagesyllabus && !$syllabusmanager->has_syllabus()) {
        $manualsyllabi = $syllabusmanager->get_all_manual_syllabi();
        foreach ($manualsyllabi as $syllabus) {
            if ($syllabus->cmid == $manualsyllabusid) {
                $validsyllabus = true;
                break;
            }
        }
    }
    // Sliently ignore an invalid manual syllabus.
    if (!$validsyllabus) {
        $manualsyllabusid = null;
    }
}

// Set editing button.
if ($canmanagesyllabus) {
    $url = new moodle_url('/local/ucla_syllabus/index.php',
                    array('id' => $course->id));
    $buttons = $OUTPUT->edit_button($url);
    $PAGE->set_button($buttons);

    // Set up form.
    $syllabusform = new syllabus_form(null,
            array('courseid' => $course->id,
                  'action' => $action,
                  'type' => $type,
                  'ucla_syllabus_manager' => $syllabusmanager,
                  'manualsyllabus' => $manualsyllabusid),
            'post',
            '',
            array('class' => 'syllabus_form'));

    // If the cancel button is clicked, return to non-editing mode of syllabus page.
    if ($syllabusform->is_cancelled()) {
        $url = new moodle_url('/local/ucla_syllabus/index.php',
                array('id' => $course->id,
                      'sesskey' => sesskey(),
                      'edit' => 'on'));
        redirect($url);
    }
}

if (!empty($USER->editing) && $canmanagesyllabus) {
    // Look for submitted data.
    $data = $syllabusform->get_data();

    // If both a file was uploaded and a URL was provided, then check which
    // radio button was last clicked to determined which syllabus to keep.
    if (isset($data->default_source)) {
        if (($data->default_source === 'file' && !$data->fileurl) ||
                ($data->default_source === 'url' && $data->fileurl)) {
            // File radio button was selected. Clear the URL.
            $data->syllabus_url = '';
        } else if (($data->default_source === 'file' && $data->fileurl) ||
                ($data->default_source === 'url' && !$data->fileurl)) {
            // URL radio button was selected. Clear the file.
            $data->syllabus_file = 0;
        }
    }

    // Check if we stored the data in the session (e.g. after the confirm dialog).
    if (empty($data) && isset($_SESSION['ucla_syllabus_data'])) {
        $data = $_SESSION['ucla_syllabus_data'];
        unset($_SESSION['ucla_syllabus_data']);
    }

    // User uploaded/edited a syllabus file, so handle it.
    if (($action == UCLA_SYLLABUS_ACTION_ADD || $action == UCLA_SYLLABUS_ACTION_EDIT)
            && !empty($data) && confirm_sesskey()) {
        $viewcourseurl = new moodle_url('/local/ucla_syllabus/index.php', array(
            'action' => UCLA_SYLLABUS_ACTION_VIEW,
            'id' => $course->id
        ));
        $confirm = optional_param('confirm', 0, PARAM_INT);

        // Present confirmation dialog if: the user has set an insecure URL, and confirm parameter is not set.
        if (!empty($data->syllabus_url) && !is_secure_url($data->syllabus_url) && !$confirm) {
            // We have to save the form data in the session so it can be accessed on the next page.
            $_SESSION['ucla_syllabus_data'] = $data;
            // To continue saving the insecure URL, just set confirm to 1.
            $continueurl = new moodle_url('/local/ucla_syllabus/index.php', array(
                'id' => $course->id,
                'action' => $action,
                'confirm' => 1,
                'sesskey' => sesskey(),
                'type' => $type
            ));
            // If they cancel, go to $viewcourseurl.
            $title = get_string('syllabus_manager', 'local_ucla_syllabus');
            $PAGE->set_title(format_string($course->shortname).": $title");
            display_header($title);
            echo $OUTPUT->confirm(get_string('confirm_insecure_url', 'local_ucla_syllabus', $data->syllabus_url),
                    $continueurl, $viewcourseurl);
            echo $OUTPUT->footer();
            // We are done with output; do not print the syllabus form.
            die();
        } else {
            // Otherwise, they either set a secure URL or confirmed the insecure URL, so save the form.
            $result = $syllabusmanager->save_syllabus($data);
            if ($result) {
                // Upload was successful, give success message to user (redirect to
                // refresh site menu and prevent duplication submission of file).
                if (isset($data->manualsyllabus)) {
                    // Manual syllabus was converted.
                    $successmessage = get_string('manualsuccessfulconversion', 'local_ucla_syllabus');
                } else if (isset($data->entryid)) {
                    // Syllabus was updated.
                    $successmessage = get_string('successful_update', 'local_ucla_syllabus');
                } else {
                    // Syllabus was added.
                    $successmessage = get_string('successful_add', 'local_ucla_syllabus');
                }

                flash_redirect($viewcourseurl, $successmessage);
            }
        }
    } else if ($action == UCLA_SYLLABUS_ACTION_DELETE) {
        // User wants to delete syllabus.
        $syllabi = $syllabusmanager->get_syllabi();
        $todel = null;

        if ($type == UCLA_SYLLABUS_TYPE_PUBLIC && !empty($syllabi[UCLA_SYLLABUS_TYPE_PUBLIC])) {
            $todel = $syllabi[UCLA_SYLLABUS_TYPE_PUBLIC];
        } else if ($type == UCLA_SYLLABUS_TYPE_PRIVATE && !empty($syllabi[UCLA_SYLLABUS_TYPE_PRIVATE])) {
            $todel = $syllabi[UCLA_SYLLABUS_TYPE_PRIVATE];
        }

        if (empty($todel)) {
            print_error('err_syllabus_notexist', 'local_ucla_syllabus');
        } else {
            // CCLE-3685 - If the syllabus is the landing page and it is deleted
            // then default the landing page back to the "Site info" page.
            // Only revert the landing page if only one syllabus exists and is
            // being deleted.
            if (is_null($syllabi[UCLA_SYLLABUS_TYPE_PUBLIC]) ||
                    is_null($syllabi[UCLA_SYLLABUS_TYPE_PRIVATE])
                    ) {
                require_once($CFG->dirroot.'/course/format/ucla/lib.php');

                $formatoptions = course_get_format($course->id)->get_format_options();
                $landingpage = isset($formatoptions['landing_page']) ? $formatoptions['landing_page'] : false;
                if ($landingpage == UCLA_FORMAT_DISPLAY_SYLLABUS) {
                    course_get_format($course->id)->update_course_format_options(
                            array('landing_page' => 0));
                }
            }

            $syllabusmanager->delete_syllabus($todel);

            $url = new moodle_url('/local/ucla_syllabus/index.php',
                    array('action' => UCLA_SYLLABUS_ACTION_VIEW,
                          'id' => $course->id));
            $successmessage = get_string('successful_delete', 'local_ucla_syllabus');
            flash_redirect($url, $successmessage);
        }
    } else if ($action == UCLA_SYLLABUS_ACTION_CONVERT) {
        // User is converting between public or private syllabus.
        $syllabi = $syllabusmanager->get_syllabi();

        $convertto = 0;
        if ($type == UCLA_SYLLABUS_TYPE_PUBLIC) {
            $convertto = UCLA_SYLLABUS_ACCESS_TYPE_PRIVATE;
        } else if ($type == UCLA_SYLLABUS_TYPE_PRIVATE) {
            // Using the stricter version of public - require user login.
            $convertto = UCLA_SYLLABUS_ACCESS_TYPE_LOGGEDIN;
        }

        if ($convertto == 0) {
             print_error('err_syllabus_notexist', 'local_ucla_syllabus');
        } else {
            $syllabusmanager->convert_syllabus($syllabi[$type], $convertto);

            $url = new moodle_url('/local/ucla_syllabus/index.php',
                    array('action' => UCLA_SYLLABUS_ACTION_VIEW,
                          'id' => $course->id));

            if ($convertto == UCLA_SYLLABUS_ACCESS_TYPE_PRIVATE) {
                $successmessage = get_string('successful_restrict', 'local_ucla_syllabus');
            } else {
                $successmessage = get_string('successful_unrestrict', 'local_ucla_syllabus');
            }
            flash_redirect($url, $successmessage);
        }
    }

    $title = get_string('syllabus_manager', 'local_ucla_syllabus');
    $PAGE->set_title(format_string($course->shortname).": $title");
    display_header($title);
    $syllabusform->display();

} else {
    // Just display syllabus.
    $title = ''; $body = '';

    $syllabi = $syllabusmanager->get_syllabi();

    $syllabustodisplay = null;
    if (!empty($syllabi[UCLA_SYLLABUS_TYPE_PRIVATE]) &&
            $syllabi[UCLA_SYLLABUS_TYPE_PRIVATE]->can_view() &&
            $course->visible) {
        // See if logged in user can view private syllabus.
        // Only show private syllabus if course is visible.
        $syllabustodisplay = $syllabi[UCLA_SYLLABUS_TYPE_PRIVATE];
    } else if (!empty($syllabi[UCLA_SYLLABUS_TYPE_PUBLIC]) &&
            $syllabi[UCLA_SYLLABUS_TYPE_PUBLIC]->can_view()) {
        // Fallback on trying to see if user can view public syllabus.
        $syllabustodisplay = $syllabi[UCLA_SYLLABUS_TYPE_PUBLIC];
    }

    // Set up what to display.
    if (empty($syllabustodisplay)) {
        // If there is no syllabus, then display no info.
        $title = get_string('display_name_default', 'local_ucla_syllabus');

        $errorstring = '';
        if (!empty($syllabi[UCLA_SYLLABUS_TYPE_PUBLIC])) {
            $errorstring = get_string('cannot_view_public_syllabus', 'local_ucla_syllabus');
        } else if (!empty($syllabi[UCLA_SYLLABUS_TYPE_PRIVATE])) {
            $errorstring = get_string('cannot_view_private_syllabus', 'local_ucla_syllabus');
        } else {
            $errorstring = get_string('no_syllabus_uploaded', 'local_ucla_syllabus');
        }

        $body = $OUTPUT->notification($errorstring);

        // If user can upload a syllabus, let them know about turning editing on.
        if ($canmanagesyllabus) {
            $body .= html_writer::tag('p',
                    get_string('no_syllabus_uploaded_help', 'local_ucla_syllabus'));
        }
    } else {
        $title = $syllabustodisplay->display_name;

        // Give preference to URL.
        if (empty($syllabustodisplay->url)) {
            $fullurl = $syllabustodisplay->get_file_url();
            $mimetype = $syllabustodisplay->get_mimetype();
            $clicktoopen = get_string('err_noembed', 'local_ucla_syllabus');
            $downloadlink = $syllabustodisplay->get_download_link();

        } else {
            $fullurl = $syllabustodisplay->url;
            $mimetype = 'text/html';
            $clicktoopen = get_string('err_noembed', 'local_ucla_syllabus');
            $downloadlink = $syllabustodisplay->get_icon() .
                    html_writer::link($syllabustodisplay->url, $syllabustodisplay->url);
        }

        // Add download link.
        $body .= html_writer::tag('div', $downloadlink, array('id' => 'download_link'));

        // Only embed file if served from https.
        if (is_secure_url($fullurl)) {
            // Allowed image mimetype.
            $allowedimagetypes = array('image/jpeg', 'image/gif', 'image/png', 'image/bmp', 'image/tiff');
            $allowedothertypes = array('text/plain', 'text/richtext', 'text/html', 'text/calendar');
            // Try to embed file using resource functions.
            if ($mimetype === 'application/pdf') {
                $body .= resourcelib_embed_pdf($fullurl, $title, $clicktoopen);
            } else if (in_array($mimetype, $allowedimagetypes)) {
                $body .= resourcelib_embed_image($fullurl, $title, $clicktoopen);
            } else if (in_array($mimetype, $allowedothertypes)) {
                $body .= resourcelib_embed_general($fullurl, $title, $clicktoopen, $mimetype);
            }
        }

        // If this is a preview syllabus, give some disclaimer text.
        $disclaimertext = '';
        $typetext = '';
        if ($syllabustodisplay instanceof ucla_public_syllabus) {
            if ($syllabustodisplay->is_preview) {
                $typetext = get_string('preview', 'local_ucla_syllabus');
                $disclaimertext = get_string('preview_disclaimer', 'local_ucla_syllabus');
            }
        }

        // Add modified date.
        $modifiedtext = '';
        if (!empty($syllabustodisplay->timemodified)) {
            $modifiedtext = get_string('modified', 'local_ucla_syllabus')
                    . userdate($syllabustodisplay->timemodified);
        }

        if (!empty($typetext)) {
            $title .= sprintf(' (%s)*', $typetext);
            $body .= html_writer::tag('p', '*' . $disclaimertext,
                    array('class' => 'syllabus_disclaimer'));
        }
        $body .= html_writer::tag('p', $modifiedtext,
                array('class' => 'syllabus-modified'));

        // Log for statistics later.
        $event = \local_ucla_syllabus\event\syllabus_viewed::create(array(
            'objectid' => $syllabustodisplay->id,
            'context' => $coursecontext
        ));
        $event->trigger();
    }

    $PAGE->set_title(format_string($course->shortname).": $title");

    // Now display content.
    display_header($title);
    echo $OUTPUT->container($body, 'ucla_syllabus-container');
}

echo html_writer::start_tag('br');
echo $OUTPUT->footer();


/**
 * Display the heading of the page.
 *
 * @param string $pagetitle
 */
function display_header($pagetitle) {
    global $OUTPUT;
    echo $OUTPUT->header();
    echo $OUTPUT->heading($pagetitle, 2, 'headingblock');
    flash_display();    // Display any success messages.
}
