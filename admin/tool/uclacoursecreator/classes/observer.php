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
 * Event handler class.
 *
 * @package    tool_uclacoursecreator
 * @copyright  2016 UC Regent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/*
 * Library hold functions that will be called for event handling
 */
require_once($CFG->dirroot . '/' . $CFG->admin . '/tool/uclacoursecreator/uclacoursecreator.class.php');

/**
 * When a course is deleted, also delete the site indicator entry.
 *
 * @copyright  2016 UC Regent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_uclacoursecreator_observer {

    /**
     * Responds to course deletion event. Does the following things:
     *
     * 1) Delete course request in ucla_request_classes.
     * 2) Check MyUCLA url web service to see if course has urls.
     * 2a) If has urls and they aren't pointing to current server, skip them.
     * 2b) If has urls and they are pointing to the current server, then clear them.
     * 3) Delete entries in ucla_reg_classinfo.
     * 4) Trigger ucla_course_deleted event.
     *
     *
     * @param \core\event\course_deleted $event    Course object.
     * @return boolean          False on error, otherwise true.
     */
    public static function handle_course_deleted(\core\event\course_deleted $event) {
        global $DB;
        // Check if course exists in ucla_request_classes.
        $uclarequestclasses = ucla_map_courseid_to_termsrses($event->courseid);
        if (empty($uclarequestclasses)) {
            return true;
        }
        $uclaclassinfo = ucla_get_course_info($event->courseid);

        // 1) Delete course request in ucla_request_classes.
        $DB->delete_records('ucla_request_classes', array('courseid' => $event->courseid));

        // 2) Check MyUCLA url web service to see if course has urls.
        $cc = new uclacoursecreator();
        $myuclaurlupdater = $cc->get_myucla_urlupdater();
        if (empty($myuclaurlupdater)) {
            return true;    // Not installed.
        }

        $haserror = false;
        foreach ($uclarequestclasses as $request) {
            $result = $myuclaurlupdater->set_url_if_same_server($request->term, $request->srs, '');
            if (!($result == $myuclaurlupdater::URL_SET || // Url cleared.
                    $result == $myuclaurlupdater::URL_NOTSET)) { // Url didn't belong to current server.
                $haserror = true;
            }

            // 3) Delete entries in ucla_reg_classinfo.
            $DB->delete_records('ucla_reg_classinfo',
                array('term' => $request->term, 'srs' => $request->srs));
        }

        // Since mappings changed, purge all caches.
        $cache = cache::make('local_ucla', 'urcmappings');
        $cache->purge();

        // 4) Trigger ucla_course_deleted event.
        $eventdata = new stdClass();
        $eventdata->courseid = $event->courseid;
        $eventdata->ucla_request_classes = $uclaclassinfo;
        $eventdata->ucla_reg_classinfo = $uclaclassinfo;
        $eventdata->deleted_requests = $uclaclassinfo;
        $deletedevent = \tool_uclacoursecreator\event\ucla_course_deleted::create(array(
            'other' => json_encode($eventdata)
        ));
        $deletedevent->trigger();

        return !$haserror;
    }

}