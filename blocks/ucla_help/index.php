<?php
// This file is part of the UCLA Help plugin for Moodle - http://moodle.org/
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
 * Script to let users view help information or send feedback.
 *
 * If being called
 * to serve as a modal window, will just output form field & help links.
 * Else, can be called displayed in a site or course context.
 *
 * @package    block_ucla_help
 * @copyright  2011 UC Regents
 * @author     Rex Lorenzo <rex@seas.ucla.edu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/blocks/ucla_help/ucla_help_lib.php');

// Form to process help request.
require_once($CFG->dirroot . '/blocks/ucla_help/help_form.php' );

// Set context.
$courseid = optional_param('course', 0, PARAM_INTEGER);
if ($courseid == SITEID) {
    $courseid = 0;
}
if ($courseid) {
    $course = get_course($courseid);
    $PAGE->set_course($course);
    $context = $PAGE->context;
} else {
    $context = context_system::instance();
    $PAGE->set_context($context);
}

// Set page title/url.
$struclahelp = get_string('pluginname', 'block_ucla_help');
$PAGE->set_title($struclahelp);
$url = new moodle_url('/blocks/ucla_help/index.php');
$PAGE->set_url($url);

// Need to change layout to be embedded if used in ajax call.
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    $isembedded = true;
} else {
    $isembedded = false;
}

// Setup page context.
if (!empty($isembedded)) {
    // If showing up as a modal window, then don't show normal headers/footers.
    $PAGE->set_pagelayout('embedded');
    // Load needed javascript to make form use ajax on submit.
    $PAGE->requires->js_init_call('M.block_ucla_help.init', null, true);
} else {
    // Show header.
    $PAGE->set_heading($struclahelp);
}

// Using core renderer.
echo $OUTPUT->header();

echo html_writer::start_tag('div', array('id' => 'block_ucla_help'));

echo html_writer::start_tag('fieldset', array('id' => 'block_ucla_help_boxtext'));
echo html_writer::tag('legend', get_string('helpbox_header', 'block_ucla_help'),
        array('id' => 'block_ucla_help_boxtext_header'));

// Show specific text for helpbox (should be set in admin settings).
$boxtext = get_config('block_ucla_help', 'boxtext');
if (empty($boxtext)) {
    // No text set, so use default text.
    echo get_string('helpbox_text_default', 'block_ucla_help');
} else {
    echo format_text($boxtext, FORMAT_HTML);
}
echo html_writer::end_tag('fieldset');

echo html_writer::start_tag('fieldset', array('id' => 'block_ucla_help_formbox'));
echo html_writer::tag('legend', get_string('helpform_header', 'block_ucla_help'),
        array('id' => 'block_ucla_help_formbox_header'));

// CCLE-3562 - Get the list of the user's courses for selection.
$sql = 'SELECT DISTINCT {course}.id, {course}.fullname, {course}.shortname
          FROM {context}
          JOIN {course} ON {course}.id = {context}.instanceid
          JOIN {role_assignments} ON {role_assignments}.contextid = {context}.id
         WHERE {role_assignments}.userid=:userid AND
               {context}.contextlevel=:contextlevel
      ORDER BY {role_assignments}.timemodified DESC';
$params['userid'] = $USER->id;
$params['contextlevel'] = CONTEXT_COURSE;

$usercourses = $DB->get_records_sql($sql, $params);
if ($courseid != 0) {
    $usercourses[] = $course;  // Include course user was viewing, if any.
}
$courses = array();
$courses[$SITE->id] = get_string('no_course', 'block_ucla_help');
foreach ($usercourses as $crs) {
    $courses[$crs->id] = $crs->shortname . ' ' . $crs->fullname;
}
$usercourses[] = $SITE;    // Include the site default.

// Create form object for page.
$mform = new help_form(null, array('courses' => $courses));

// Handle form post.
if ($fromform = $mform->get_data()) {

    // Get email address from form submitter (if any).
    $fromaddress = null;
    if (!empty($fromform->ucla_help_email)) {
        $fromaddress = $fromform->ucla_help_email;
    } else if (!empty($USER->email)) {
        $fromaddress = $USER->email;
    } else {
        $fromaddress = $CFG->noreplyaddress;
    }

    // Get message header.
    $header = get_string('message_header', 'block_ucla_help', create_description($fromform));

    // Set context to the selected course.
    $instanceid = 0;
    $fromform->course_name = $SITE->shortname;
    foreach ($usercourses as $c) {
        if ($c->id == $fromform->ucla_help_course) {
            $fromform->course_name = $c->shortname;
            $instanceid = $c->id;
            break;
        }
    }

    // Use system context if no other context was found.
    if (!empty($instanceid)) {
        $context = context_course::instance($instanceid, false) ? : $context;
    }

    // Get message body.
    $body = create_help_message($fromform);

    // Get report type.
    $isfeaturereport = $fromform->ucla_help_reporttype;

    // Get support contact(s).
    $supportcontacts = get_support_contact($context, $isfeaturereport);

    // Get uploaded attachment if there is one.
    if (isloggedin() && !isguestuser() && get_config('block_ucla_help', 'enablefileuploads')) {
        $attachmentname = $mform->get_new_filename('ucla_help_attachment');

        // Save attachment to a temporary directory.
        if ($attachmentname != null) {
            $attachmentfile = "{$CFG->tempdir}/helpupload/{$attachmentname}";
            make_temp_directory('helpupload');
            if (!$uploadresult = $mform->save_file('ucla_help_attachment', $attachmentfile, true)) {
                throw new moodle_exception('uploadproblem');
            }
        } else {
            $attachmentfile = null;
        }
    } else {
        $attachmentname = null;
        $attachmentfile = null;
    }

    $result = true;
    foreach ($supportcontacts as $supportcontact) {
        if (!message_support_contact($supportcontact, $fromaddress,
                $fromform->ucla_help_name, $header, $body, $isfeaturereport, $attachmentfile, $attachmentname)) {
            $result = false;
            break;
        }
    }

    if (!empty($result)) {
        if ($isfeaturereport) {
            echo $OUTPUT->notification(get_string('success_message_cclehome', 'block_ucla_help'), 'notifysuccess');
        } else {
            echo $OUTPUT->notification(get_string('success_message_localsupport', 'block_ucla_help'), 'notifysuccess');
        }
    } else {
        echo $OUTPUT->notification(get_string('error_sending_message', 'block_ucla_help'), 'notifyproblem');
        // TODO: log error Send fails.
    }

    // If embedding help form don't have anything, since I don't know how
    // to hide overlay that script is embedded.
    if (!$isembedded) {
        // Else give continue link to return to course or front page.
        if ($COURSE->id == 1) {
            $url = $CFG->wwwroot;
        } else {
            $url = $CFG->wwwroot . '/course/view.php?id=' . $COURSE->id;
        }
        echo $OUTPUT->single_button($url, get_string('continue'), 'get');
    }

} else {
    // Else display form and header text.
    echo get_string('helpform_text', 'block_ucla_help');
    $mform->display();
}
echo html_writer::end_tag('fieldset');

echo html_writer::end_tag('div');

echo $OUTPUT->footer();
