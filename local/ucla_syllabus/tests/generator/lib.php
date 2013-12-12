<?php
// This file is part of the UCLA syllabus plugin for Moodle - http://moodle.org/
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
 * Data generator.
 *
 * @package     local_ucla_syllabus
 * @copyright   2013 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/ucla_syllabus/locallib.php');

/**
 * Syllabus data generator class.
 *
 * @package     local_ucla_syllabus
 * @copyright   2013 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_ucla_syllabus_generator extends component_generator_base {

    /**
     * @var number of created instances
     */
    protected $instancecount = 0;

    /**
     * Creates a syllabus instance according to given record.
     * 
     * Requires courseid to be set. Will generate a fake file if syllabus_file
     * is empty. For all other syllabus attributes, will set to a default value.
     *
     * Returns syllabus object created from given record.
     *
     * @param array|stdClass $record Syllabus record.
     * @return ucla_public_syllabus|ucla_private_syllabus|null
     */
    public function create_instance($record) {
        global $USER;
        
        // Ensure the record is an object.
        $record = (object)(array)$record;

        // Make sure courseid was passed.
        if (empty($record->courseid)) {
            return null;
        }

        // All other database columns can be auto-generated.
        if (empty($record->display_name)) {
            $record->display_name = 'Syllabus';
        }
        if (empty($record->access_type)) {
            $record->access_type = UCLA_SYLLABUS_ACCESS_TYPE_PUBLIC;
        }
        if (empty($record->syllabus_file) && empty($record->url)) {
            // If URL is empty, then we need to generate a fake file. Using
            // mod_resource data generator as a template.

            if (empty($USER->username) || $USER->username === 'guest') {
                throw new coding_exception('syllabus generator requires a current user');
            }
            $usercontext = context_user::instance($USER->id);

            // Pick a random context id for specified user.
            $draftitemid = file_get_unused_draft_itemid();

            // Add actual file there.
            $filerecord = array('component' => 'user', 'filearea' => 'draft',
                    'contextid' => $usercontext->id, 'itemid' => $draftitemid,
                    'filename' => 'syllabus' . ($this->instancecount+1) . '.txt', 'filepath' => '/');
            $fs = get_file_storage();
            $fs->create_file_from_string($filerecord, 'Test syllabus ' . ($this->instancecount+1) . ' file');

            $record->syllabus_file = $draftitemid;
        } else if (empty($record->syllabus_file)) {
            $record->syllabus_file = file_get_unused_draft_itemid();
        }
        if (!isset($record->url)) {
            $record->url = '';
        }

        // Method save_syllabus() is expecting data to be in a slightly
        // different format.
        $record->id = $record->courseid;
        $record->access_types['access_type'] = $record->access_type;
        $record->syllabus_url = $record->url;

        // Create syllabus manager.
        $course = get_course($record->courseid);
        $syllabusmanager = new ucla_syllabus_manager($course);

        $syllabusid = $syllabusmanager->save_syllabus($record);
        $retval = ucla_syllabus_manager::instance($syllabusid);

        if (!empty($retval)) {
            ++$this->instancecount;
        }

        return $retval;
    }

    /**
     * To be called from data reset code only, do not use in tests.
     * @return void
     */
    public function reset() {
        $this->instancecount = 0;
    }
}
