<?php
// This file is part of the public/private plugin for Moodle - http://moodle.org/
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
 * Handles restore functions.
 *
 * @package    local_publicprivate
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Course restore step that handles public/private metadata.
 *
 * @package    local_publicprivate
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class Restore_PublicPrivate_Course_Structure_Step extends Restore_Structure_Step {

    /**
     * Defines a structure of data within the <course> element.
     *
     * @return array
     */
    protected function define_structure() {
        $course = new Restore_Path_Element('course', '/course');
        return array($course);
    }

    /**
     * Parses course public/private data and sets public/private attributes.
     *
     * @param array $data
     */
    public function process_course($data) {
        if (intval($data['enable']) == 0) {
            // In backup file, public/private is not enabled.
            return;
        }

        // We want public/private enabled for restored course if it is not
        // already set.
        $courseid = $this->get_courseid();

        $ppcourse = new PublicPrivate_Course($courseid);
        if (!$ppcourse->is_activated()) {
            // Sets group/groupings values.
            $ppcourse->activate();
        }
    }

}
