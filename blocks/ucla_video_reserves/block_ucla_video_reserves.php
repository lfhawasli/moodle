<?php
// This file is part of the UCLA video reserves block for Moodle - http://moodle.org/
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
 * Video reserves block.
 *
 * @package    block_ucla_video_reserves
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Block implementation file.
 *
 * @package    block_ucla_video_reserves
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_ucla_video_reserves extends block_base {

    /**
     * Titles block.
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_ucla_video_reserves');
    }

    /**
     * Hide block from being able to be added anywhere.
     *
     * @return array
     */
    public function applicable_formats() {
        return array(
            'site-index' => false,
            'course-view' => false,
            'my' => false,
            'not-really-applicable' => true
        );
    }

    /**
     * This will create a link to the ucla video reserves page.
     *
     * @param int $courseid
     * @return moodle_url
     */
    public static function get_action_link($courseid) {
        return new moodle_url('/blocks/ucla_video_reserves/index.php',
                array('courseid' => $courseid));
    }

    /**
     * Returns an array of navigation nodes that can be used in Site menu block.
     *
     * @param object $course
     * @return array
     */
    public static function get_navigation_nodes($course) {
        global $DB;
        $nodes = array();
        $courseid = $course['course']->id;

        $recordsfound = $DB->record_exists('ucla_video_reserves',
                array('courseid' => $courseid));

        if ($recordsfound) {
            $node = navigation_node::create(get_string('title',
                    'block_ucla_video_reserves'), self::get_action_link($courseid));
            $node->add_class('video-reserves-link');
            $nodes[] = $node;
        }

        return $nodes;
    }

    /**
     * Returns true because block has a settings.php file.
     *
     * @return boolean
     */
    public function has_config() {
        return true;
    }

}
