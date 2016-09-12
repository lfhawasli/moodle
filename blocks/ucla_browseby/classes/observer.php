<?php
// This file is part of the UCLA browseby block for Moodle - http://moodle.org/
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
 * Observer class.
 *
 * @package    block_ucla_browseby
 * @copyright  2016 UC Regent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class file.
 *
 * @package    block_ucla_browseby
 * @copyright  2016 UC Regent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_ucla_browseby_observer {
    /**
     * Handles event for course creator finished.
     *
     * @param \tool_uclacoursecreator\event\course_creator_finished $event
     * @return boolean
     */
    public static function browseby_sync_courses(\tool_uclacoursecreator\event\course_creator_finished $event) {
        return self::browseby_extractor_callback('completed_requests', $event);
    }

    /**
     * Handles event for course requests deleted.
     *
     * @param \tool_uclacoursecreator\event\ucla_course_deleted $event
     * @return type
     */
    public static function browseby_sync_deleted(\tool_uclacoursecreator\event\ucla_course_deleted $event) {
        return self::browseby_extractor_callback('deleted_requests', $event);
    }

    /**
     * Updates BrowseBy when UCLA courses are created or deleted.
     *
     * @param string $field
     * @param \core\event\base $event
     * @return boolean
     */
    public static function browseby_extractor_callback($field, \core\event\base $event) {
        $data = $event->get_requests();   
        if (empty($data->{$field})) {
            return true;
        }

        $r = self::browseby_extract_term_subjareas($data->{$field});
        if (!$r) {
            return true;
        }

        list($t, $s) = $r;
        return self::run_browseby_sync($t, $s);
    }

    /**
     * Extracts distinct term and subjectareas from request sets.
     *
     * @param array $requests
     * @return boolean
     */
    public static function browseby_extract_term_subjareas($requests) {
        $subjareas = array();
        $terms = array();
        foreach ($requests as $request) {
            if (is_object($request)) {
                $request = get_object_vars($request);
            }

            if (!empty($request['term'])) {
                $t = $request['term'];

                $terms[$t] = $t;
            }

            if (!empty($request['subj_area'])) {
                $sa = $request['subj_area'];

                $subjareas[$sa] = $sa;
            }
        }

        if (empty($terms)) {
            return false;
        }

        return array($terms, $subjareas);
    }

    /**
     * Starts and runs a browseby instance-sync.
     *
     * @param array $terms
     * @param array $subjareas
     * @param boolean $forceall
     * @return boolean
     */
    public static function run_browseby_sync($terms, $subjareas=null, $forceall=false) {
        if (!$forceall && empty($terms)) {
            return true;
        }

        $b = block_instance('ucla_browseby');
        if ($forceall) {
            $terms = $b->get_all_terms();
        }

        $b->sync($terms, $subjareas);

        return true;
    }
}
