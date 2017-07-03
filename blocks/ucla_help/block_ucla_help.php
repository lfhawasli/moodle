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
 * Block class for UCLA Help form
 *
 * @package    block_ucla_help
 * @author     Rex Lorenzo <rex@seas.ucla.edu>
 * @copyright  2011 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Block class for UCLA Help form
 *
 * @copyright  2011 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_ucla_help extends block_base {

    /**
     * Sets block title
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_ucla_help');
    }

    /**
     * Returns the content displayed within the block
     *
     * @return stdClass
     */
    public function get_content() {
        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->text = get_string('block_text', 'block_ucla_help');

        return $this->content;
    }

    /**
     * Prevents multiple instances
     *
     * @return boolean
     */
    public function instance_allow_multiple() {
        return false;
    }

    /**
     * Has config
     *
     * @return boolean
     */
    public function has_config() {
        return true;
    }

    /**
     * Link to page to allow users to interact with the block
     *
     * @return string
     */
    public static function get_action_link() {
        global $CFG, $COURSE;
        if (empty($COURSE) || $COURSE->id === SITEID) {
            // Not in a course.
            return $CFG->wwwroot . '/blocks/ucla_help/index.php';
        } else {
            // In a course, so give courseid so that layout is course format.
            return $CFG->wwwroot . '/blocks/ucla_help/index.php?course=' . $COURSE->id;
        }
    }

    /**
     * Lists formats
     *
     * @return array
     */
    public function applicable_formats() {
        return array(
            'site-index' => false,
            'course-view' => false,
            'my' => false,
            'block-ucla_office_hours' => false,
            'not-really-applicable' => true
        );
    }
}
