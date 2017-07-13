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
 * Redirects users to archive server if course not found locally.
 *
 * @package    local_ucla
 * @copyright  2012 UCLA regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

// This essentially loads ucla library for all course sites.
require_once($CFG->dirroot . '/local/ucla/lib.php');

/**
 * Will redirect a user to corresponding course on archive server.
 *
 * @param string $shortname
 * @param int $id
 * @return boolean|string   Url
 */
function local_ucla_course_view_hook($shortname, $id) {
    global $DB;

    // Save a lot of time.
    if (!$shortname) {
        return false;
    }

    // No way to judge anything.
    $remotetermcutoff = get_config('local_ucla', 'remotetermcutoff');
    if (!$remotetermcutoff) {
        return false;
    }

    $archiveserver = get_config('local_ucla', 'archiveserver');
    if (!$archiveserver) {
        return false;
    }

    $redirurl = $archiveserver .'/course/view/' . $shortname;
    $maybeterm = substr($shortname, 0, 3);

    // No term, just treat it like a non-ucla course.
    if (!ucla_validator('term', $maybeterm)) {
        return false;
    }

    $termcmp = term_cmp_fn($maybeterm, $remotetermcutoff);

    // Do not check for any courses after the specified term.
    if ($termcmp > 0) {
        return false;
    }

    if (!$id) {
        $course = $DB->get_record('course', array('shortname' => $shortname));
        if ($course) {
            $id = $course->id;
        }
    }

    // This course doesn't exist on this local server.
    if (empty($id)) {
        // Then we goto 1.9 server for older terms.
        return $redirurl;
    }

    return false;
}
