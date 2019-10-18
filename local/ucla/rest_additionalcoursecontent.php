<?php
// This file is part of the UCLA local_ucla plugin for Moodle - http://moodle.org/
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
 * Checks if a course has any additional content given a course id.
 *
 * @copyright 2014 UC Regents
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package local_ucla
 */

define('AJAX_SCRIPT', true);

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/report/uclastats/reports/active_instructor_focused.php');

require_login();

global $DB, $USER;

$obj = new stdClass();
$obj->status = false;

try {
    $courseid = required_param('courseid', PARAM_INT);
    $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

    // Make sure that user can access course. We use restore capability, because
    // this script is currently only used in the restore page.
    if (has_capability('moodle/restore:restorecourse', context_course::instance($courseid))) {
        $activeinstructorfocused = new active_instructor_focused($USER);
        if ($activeinstructorfocused->has_additional_course_content($course)) {

            $warning = new stdClass();
            $warning->docsiteurl = get_config('block_ucla_help', 'docs_wiki_url') . '/index.php?title=LTI';

            $config = new stdClass;
            $config->title = get_string('deletecoursecontenttitle', 'local_ucla');
            $config->yesLabel = get_string('deletecoursecontentyes', 'local_ucla');
            $config->noLabel = get_string('deletecoursecontentno', 'local_ucla');;
            $config->closeButtonTitle = get_string('close', 'editor');
            $url = new moodle_url('/backup/backup.php', array('id' => $course->id));
            $config->question = get_string('deletecoursecontentwarning', 'local_ucla',
                    array('shortname' => $course->shortname, 'fullname' => $course->fullname, 'backuplink' => (string)$url));
            $config->warning = get_string('lti_warning', 'local_ucla', $warning);
            $config->url = (string) $url;
            // Set status.
            $obj->status = true;
            $obj->config = $config;
        }
    }

} catch (Exception $e) {
    // Both get_record and required_param will fail with exceptions with an invalid courseid.
    $obj->status = false;
}

// Print JSON obj.
header('Content-Type: application/json');
echo json_encode($obj);
