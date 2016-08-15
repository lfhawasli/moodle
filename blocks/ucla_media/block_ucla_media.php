<?php
// This file is part of the UCLA Media block for Moodle - http://moodle.org/
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
 * Block definition file for UCLA Media
 *
 * @package    block_ucla_media
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(dirname(dirname(__FILE__))) . '/local/ucla/lib.php');

class block_ucla_media extends block_base {

    /**
     * Called by moodle
     */
    public function init() {

        // Initialize title and name.
        $this->title = get_string('title', 'block_ucla_media');
        $this->name = get_string('pluginname', 'block_ucla_media');
    }

    /**
     * Called by moodle
     */
    public function get_content() {

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass;

        return $this->content;
    }

    /**
     * Hook into UCLA Site menu block.
     *
     * @param object $course
     *
     * @return array
     */
    public static function get_navigation_nodes($course) {
        global $DB;
        $nodes = array();
        $courseid = $course['course']->id;
        $bruincastfound = $DB->record_exists('ucla_bruincast',
                array('courseid' => $courseid));

        $videoreservesfound = $DB->record_exists('ucla_video_reserves',
                array('courseid' => $courseid));

        $libraryreservesfound = $DB->get_records('ucla_library_reserves',
                array('courseid' => $courseid));

        if ($bruincastfound or $videoreservesfound or $libraryreservesfound) {
            $node = navigation_node::create(get_string('title',
                    'block_ucla_media'), new moodle_url('/blocks/ucla_media/bcast.php', array('courseid' => $courseid)));
            $node->add_class('video-reserves-link');
            $nodes[] = $node;
        }

        return $nodes;
    }

    /**
     *  Called by moodle
     */
    public function applicable_formats() {

        return array(
            'site-index' => false,
            'course-view' => false,
            'my' => false,
            'block-ucla_bruincast' => false,
            'not-really-applicable' => true
        );
        // Hack to make sure the block can never be instantiated.
    }

    /**
     *  Called by moodle
     */
    public function instance_allow_multiple() {
        return false; // Disables multiple block instances per page.
    }

    /**
     *  Called by moodle
     */
    public function instance_allow_config() {
        return false; // Disables instance configuration.
    }

}
