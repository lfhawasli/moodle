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

require_once("../../../config.php");
require_once($CFG->dirroot. '/admin/tool/uclaseniorscholar/seniorscholar_import_form.php');
require_once($CFG->libdir . '/csvlib.class.php');
require_once(dirname(__FILE__) . '/seniorscholar_invitation_form.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once($CFG->dirroot . '/admin/tool/uclacoursecreator/uclacoursecreator.class.php');

$separator     = optional_param('separator', '', PARAM_ALPHA);
$iid           = optional_param('iid', null, PARAM_INT);
$filterterm    = optional_param('filter_term', $CFG->currentterm, PARAM_TEXT);
$maxcrosslistshown = get_config('local_ucla', 'maxcrosslistshown');

$url = new moodle_url('/admin/tool/uclaseniorscholar/seniorscholar_import.php');
if ($separator !== '') {
    $url->param('separator', $separator);
}

$PAGE->set_url($url);

$syscontext = context_system::instance();
$PAGE->set_context($syscontext);
$PAGE->set_pagetype('admin-*');
$PAGE->set_pagelayout('admin');
$PAGE->set_heading(get_string('pluginname', 'tool_uclaseniorscholar'));

require_login();
if (!seniorscholar_has_access($USER)) {
    print_error('nopermissions');
}

echo $OUTPUT->header();

// Set up the import form.
$mform = new seniorscholar_import_form(null, array('filter_term' => $filterterm,
                                                   'includeseparator' => true,
                                                   'verbosescales' => true,
                                                   'acceptedtypes' => array('.csv', '.txt')));

// If the csv file hasn't been imported yet then look for a form submission or
// show the initial submission form.
if (!$iid) {
    // If the import form has been submitted.
    if ($formdata = $mform->get_data()) {

        // Large files are likely to take their time and memory. Let PHP know
        // that we'll take longer, and that the process should be recycled soon
        // to free up memory.
        core_php_time_limit::raise();
        raise_memory_limit(MEMORY_EXTRA);

        $text = $mform->get_file_content('userfile');
        $iid = csv_import_reader::get_new_iid('seniorscholar');
        $csvimport = new csv_import_reader($iid, 'seniorscholar');

        $csvimport->load_csv_content($text, $formdata->encoding, $separator);
        if ($error = $csvimport->get_error()) {
            echo $OUTPUT->notification($error);
            echo $OUTPUT->footer();
            die();
        }

        $header = $csvimport->get_columns();

        // Print a preview of the data.
        $numlines = 0; // 0 lines previewed so far.

        foreach ($header as $i => $h) {
            $h = trim($h); // Remove whitespace.
            $h = clean_param($h, PARAM_RAW); // Clean the header.
            $header[$i] = ucfirst($h);
        }
        // Add course detail info in the front.
        array_unshift($header,
                      get_string('course', 'tool_uclaseniorscholar'),
                      get_string('instructor', 'tool_uclaseniorscholar'));

        // Add status at the end.
        array_push($header, get_string('status', 'tool_uclaseniorscholar'));
        $table = new html_table();
        $table->head = $header;

        // Append data to the header.
        $csvimport->init();
        $previewdata = array();
        $bulkuploaddata = array();
        while ($numlines <= $formdata->previewrows) {
            $lines = $csvimport->next();
            if ($lines) {

                // Get course detail.
                $courseoutput = '';
                $instructor = '';
                $status = get_string('notavailableforimport', 'tool_uclaseniorscholar');
                $srs = $lines[0];
                // Should only return one record.
                $result = seniorscholar_course_check(seniorscholar_get_courses_by_srsterm($srs, $filterterm));
                // Courseid is the key.
                // If no result, then the course is empty.
                if (empty($result) || !key($result)) {
                    // The course is not available for senior scholar.
                    // Course not exists, not the allow course type, course number not under 200.
                    $status = get_string('coursenotexists', 'tool_uclaseniorscholar');
                    // Do nothing.
                } else {
                    // Check courses.
                    $courseid = key($result);
                    $courselist = $result[$courseid];
                    $courseoutput = uclacoursecreator::make_course_shortname($courselist[1]);
                    $instructor = $courselist[1]->instructor;
                    // Loop cross listed courses.
                    $i = 0;
                    while (!empty($courselist[0]) && $i <= $maxcrosslistshown) {
                        $course = array_shift($courselist[0]);
                        $courseoutput .= ' / ' . uclacoursecreator::make_course_shortname($course);
                        $i++;
                    }
                    if ($courselist[1]->enrolstat == 'X') {
                        // If the course is cancelled, mark it.  Will not import.
                        $status = get_string('coursecanlled', 'tool_uclaseniorscholar');
                    } else if ($courselist[1]->email == $lines[1]) {
                        // If the course alreay has invite sent for this email.  Will not import.
                        $status = get_string('alreadyinvite', 'tool_uclaseniorscholar');
                        $courseoutput = html_writer::link(new moodle_url('/admin/tool/uclaseniorscholar/seniorscholar_history.php',
                                        array('courseid' => $courselist[1]->courseid)), $courseoutput, array('target' => '_blank'));
                    } else {
                        $status = get_string('readyforimport', 'tool_uclaseniorscholar');
                        $bulkuploaddata[$courseid][] = $lines[1]; // Email address.
                    }
                }
                // Attach extra column for display.
                array_unshift($lines, $courseoutput, $instructor);
                array_push($lines, $status);
                $previewdata[] = $lines;
            }
            $numlines ++;
        }
        $table->data = $previewdata;
        echo html_writer::table($table);

        // Create a form.
        if (empty($bulkuploaddata)) {
            echo $OUTPUT->container(get_string('nobulkinvite', 'tool_uclaseniorscholar'));
            echo $OUTPUT->continue_button(new moodle_url('/admin/tool/uclaseniorscholar/index.php'));
        } else {
            echo html_writer::start_tag('div');
            echo html_writer::start_tag('form', array('id' => 'tool_uclaseniorscholar_bulkupload',
                                        'action' => new moodle_url('/admin/tool/uclaseniorscholar/seniorscholar_bulkinvites.php'),
                                        'method' => 'post'));
            echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'bulkuploaddata',
                                        'value' => serialize($bulkuploaddata)));
            echo html_writer::empty_tag('input', array('id' => 'send_invites',
                                        'name' => 'submit_button',
                                        'value' => get_string('sendinvites', 'tool_uclaseniorscholar'),
                                        'type' => 'submit'));
            echo html_writer::end_tag('form');
            echo html_writer::end_tag('div');
        }
    } else {
        // Display the standard upload file form.
        echo html_writer::start_tag('div', array('class' => 'clearer'));
        echo html_writer::end_tag('div');
        $mform->display();
        echo $OUTPUT->footer();
        die();
    }
}

echo $OUTPUT->footer();
