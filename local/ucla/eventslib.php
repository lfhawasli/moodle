<?php
// This file is part of the UCLA local plugin for Moodle - http://moodle.org/
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

/*
 * Event handlers for the local_ucla plugin.
 *
 * @package    local_ucla
 * @copyright  2014 UC Regents
 */

defined('MOODLE_INTERNAL') || die();

// @todo When automatic class loading is available via Moodle 2.6, we no longer
// need to include the local_ucla_regsender class, so delete it.
require_once($CFG->dirroot . '/local/ucla/classes/local_ucla_regsender.php');

require_once($CFG->dirroot . '/backup/util/interfaces/checksumable.class.php');
require_once($CFG->dirroot . '/backup/backup.class.php');
require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/mod/forum/lib.php');

/**
 * Handles when a UCLA course is deleted by clearing the related course entries
 * in the "ucla_syllabus" table at SRDB.
 *
 * Note, cannot rely on handling deleted syllabus from deleted courses via the
 * ucla_syllabus_deleted event handler, because the entries in the
 * ucla_reg_classinfo table will be deleted by the time the event is fired.
 *
 * @param object $data  Expecting an object with the following fields: courseid,
 *                      array of ucla_request_classes entries, and array
 *                      ucla_reg_classinfo entries.
 * @return bool         Returns false on problems, otherwise true.
 */
function clear_srdb_ucla_syllabus($data) {
    // Clear syllabi for each ucla_reg_classinfo entry.
    $regsender = new local_ucla_regsender();
    $links = array('public' => '', 'private' => '', 'protect' => '');

    foreach ($data->ucla_reg_classinfo as $classinfo) {
        $result = $regsender->set_syllabus_link($classinfo->term,
                $classinfo->subj_area, $classinfo->crsidx, $classinfo->classidx,
                $links);
        if ($result == local_ucla_regsender::FAILED) {
            // Error, try again later.
            return false;
        }
    }

    return true;
}

/**
 * Handles the updating "ucla_syllabus" table at SRDB by getting the course
 * that triggered the event and pushing out links for all the different syllabus
 * types.
 *
 * NOTE: This only responds to the ucla_syllabus_added and ucla_syllabus_deleted
 * events, because the link stays the same if a syllabus is updated.
 * 
 * @param mixed $data   Either syllabus record id or syllabus record object.
 * @return bool         Returns false on problems, otherwise true.
 */
function update_srdb_ucla_syllabus($data) {
    global $DB;
    $courseid = null;
    if (is_object($data)) {
        $courseid = $data->courseid;
    } else {
        $courseid = $DB->get_field('ucla_syllabus', 'courseid',
                array('id' => $data));
    }
    if (empty($courseid)) {
        // Syllabus must have been deleted after it was added, so do not process
        // entry anymore.
        return true;
    }

    // If course is deleted, clearing of syllabi links is done through
    // ucla_course_deleted event handler.
    if (!$DB->record_exists('course', array('id' => $courseid))) {
        // Don't send anything and dequeue.
        return true;
    }

    // If course is a collaboration site, then  don't process syllabus.
    if (is_collab_site($courseid)) {
        return true;
    }

    // Get all syllabi for course and then send links.
    $regsender = new local_ucla_regsender();
    return $regsender->push_course_links($courseid);
}

