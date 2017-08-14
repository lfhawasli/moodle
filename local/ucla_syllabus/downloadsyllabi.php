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
 * Tool used to bulk download course syllabi by category/term.
 *
 * @copyright 2017 UC Regents
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package local_ucla_syllabus
 */

require_once('../../config.php');
require_once($CFG->dirroot.'/lib/coursecatlib.php');
require_once('./downloadsyllabi_form.php');
require_once('./locallib.php');

// Library needed to fetch url-based syllabi and convert them to pdfs.
require_once($CFG->dirroot.'/vendor/autoload.php');
use Knp\Snappy\Pdf;
$htmltopdfconverter = new Pdf($CFG->dirroot.'/vendor/h4cc/wkhtmltopdf-amd64/bin/wkhtmltopdf-amd64');
$htmltopdfconverter->setOption('disable-javascript', true);

$categoryid = required_param('id', PARAM_INT);
$category = coursecat::get($categoryid);

require_login();
$catcontext = $category->get_context();
require_capability('moodle/course:view', $catcontext);

// Set up a moodle page.
$PAGE->set_url('/local/ucla_syllabus/downloadsyllabi.php');
$PAGE->set_context($catcontext);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('bulkdownloadsyllabi', 'local_ucla_syllabus'));
$PAGE->set_heading(get_string('bulkdownloadsyllabiheading', 'local_ucla_syllabus'));

// Instantiate moodleform.
$mform = new downloadsyllabi_form(null, array('category' => $category));

$table = null;
$errorflag = null;
// If the form was submitted, display syllabi.
if ($data = $mform->get_data()) {
    // Retrieve the syllabi corresponding to the selected category and term.
    $courses = $mform->get_category_courses($category, $data->term);

    // Prepare the table.
    $table = new html_table();
    $table->head = array(get_string('tablecolcourse', 'local_ucla_syllabus'),
            get_string('tablecolsyllabus', 'local_ucla_syllabus'));
    $table->data = array();

    // Only prepare a download if the download button was pressed.
    $downloading = !empty($data->downloadbutton);
    $syllabusfiles = array();

    foreach ($courses as $course) {
        // Link to course.
        $courselink = html_writer::link(new moodle_url('/course/view.php' ,
                array('id' => $course->id)),
                $course->shortname, array('target' => '_blank'));

        $manager = new ucla_syllabus_manager($course);

        // Prefer to give private syllabus.
        $syllabi = $manager->get_syllabi();
        $foundsyllabus = null;
        if (!empty($syllabi[UCLA_SYLLABUS_TYPE_PRIVATE])) {
            $foundsyllabus = $syllabi[UCLA_SYLLABUS_TYPE_PRIVATE];
        } else {
            $foundsyllabus = $syllabi[UCLA_SYLLABUS_TYPE_PUBLIC];
        }

        if (!empty($foundsyllabus->url)) {
            // Syllabus is a URL.
            $link = html_writer::link($foundsyllabus->url, $foundsyllabus->url);
            $table->data[] = array($courselink, $link);

            // Try to convert webpage to a pdf and prepare for download in the zipfile.
            if ($downloading) {
                try {
                    $webpagecontent = $htmltopdfconverter->getOutput($foundsyllabus->url);
                    $syllabusfiles[$course->shortname . '_syllabus.pdf'] = array($webpagecontent);
                } catch (moodle_exception $e) {
                    // The page was unable to be converted. Just include a .txt of the URL instead.
                    $syllabusfiles[$course->shortname . '_syllabus_url.txt'] = array($foundsyllabus->url);
                }
            }
        } else {
            // Syllabus is a file.
            $syllabusfile = null;
            try {
                $syllabusfile = $foundsyllabus->locate_syllabus_file();
            } catch (moodle_exception $e) {
                continue;
            }
            if ($syllabusfile = $foundsyllabus->locate_syllabus_file()) {
                $syllabusfilename = $syllabusfile->get_filename();

                // Display the syllabi in a table.
                $fileurl = $foundsyllabus->get_file_url();
                $downloadlink = html_writer::link($fileurl, $foundsyllabus->get_icon() . $syllabusfilename);
                $table->data[] = array($courselink, $downloadlink);

                // Prepare the syllabi for download in the zipfile.
                if ($downloading) {
                    $syllabusfiles[$course->shortname . '_syllabus_' . $syllabusfilename] = $syllabusfile;
                }
            }
        }
    }

    // If download button was pressed, download syllabi in selected term.
    if ($downloading) {
        // Prepare the zipfile.
        $zipname = clean_filename(str_replace(' ', '_', $category->name) . "_syllabi.zip");
        // Save to a specified temporary directory.
        if (!file_exists($CFG->tempdir.'/downloadsyllabi')) {
            mkdir($CFG->tempdir.'/downloadsyllabi');
        }
        $tempzip = tempnam($CFG->tempdir.'/downloadsyllabi'.'/', $zipname);

        // Download the zipfile if there were any syllabi, otherwise display an error.
        $zipper = new zip_packer();
        if (empty($syllabusfiles)) {
            $errorflag = 1; // No syllabi found.
        } else if ($zipper->archive_to_pathname($syllabusfiles, $tempzip)) {
            header("Content-Type: application/zip");
            header("Content-Disposition: attachment; filename=$zipname");
            header("Pragma: no-cache");
            header("Expires: 0");
            readfile($tempzip);
            @unlink($tempzip); // Delete the zipfile after we are done.
            die();
        } else {
            $errorflag = 2; // Error creating zipfile.
        }
    }
}

// Start rendering.
echo $OUTPUT->header();

echo html_writer::tag('h2', get_string('bulkdownloadsyllabiheading', 'local_ucla_syllabus'));
echo html_writer::tag('h4', '<b>'.get_string('category', 'local_ucla_syllabus').': </b>' . $category->name);
echo "<hr>";

$mform->display();

// If any errors occured, display them.
if (isset($errorflag)) {
    $errormsg = '';
    switch ($errorflag) {
        case 1:
            $errormsg = get_string('errornosyllabi', 'local_ucla_syllabus');
            break;
        case 2:
            $errormsg = get_string('errordownload', 'local_ucla_syllabus');
            break;
    }
    echo $OUTPUT->notification($errormsg, 'error');
}

// Display the syllabi in the selected term.
if (!empty($table)) {
    echo "<hr>";
    // Display the selected term.
    if (!empty($data->term)) {
        echo html_writer::tag('h4', '<b>'.get_string('selectedterm', 'local_ucla_syllabus').': </b>' . $data->term);
    }
    echo html_writer::tag('h4', '<b>'.get_string('numberofsyllabi', 'local_ucla_syllabus').': </b>' . count($table->data));
    echo html_writer::table($table);
}

echo $OUTPUT->footer();
