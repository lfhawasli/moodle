<?php
// This file is part of the UCLA TA sites block for Moodle - http://moodle.org/
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
 * Handles form responses.
 *
 * @package    block_ucla_tasites
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * This means that the user wishes to make a TA site.
 *
 * @param stdClass $tainfo
 * @return stdClass
 */
function block_ucla_tasites_respond_build($tainfo) {
    $newcourse = block_ucla_tasites::create_tasite($tainfo);
    $courseurl = new moodle_url('/course/view.php',
        array('id' => $newcourse->id));

    $tainfo->courseurl = $courseurl->out();
    $tainfo->courseshortname = $newcourse->shortname;

    $r = new object();
    $r->mstr = 'built_tasite';
    $r->mstra = $tainfo;

    return $r;
}

/**
 * This means that the user wishes to delete the TA site.
 *
 * @param stdClass $tainfo
 * @return stdClass
 */
function block_ucla_tasites_respond_delete($tainfo) {
    /* Disabling for now
    ob_start();
    delete_course($tainfo->tasite->id);
    $tainfo->delete_text = ob_get_clean();
    $tainfo->course_fullname = $tainfo->tasite->fullname;
    */

    $r = new object();
    $r->mstr = 'deleted_tasite';
    $r->mstra = $tainfo;

    return $r;
}