<?php
// This file is part of the UCLA Library Reserves block for Moodle - http://moodle.org/
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
 * Block definition file for UCLA Library Reserves.
 *
 * @package    block_ucla_library_reserves
 * @copyright  2019 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(dirname(dirname(__FILE__))) . '/local/ucla/lib.php');

class block_ucla_library_reserves extends block_base {

    /**
     * Called by moodle
     */
    public function init() {
        // Initialize name and title.
        $this->title = get_string('title', 'block_ucla_library_reserves');
    }

    /**
     * Called by moodle
     */
    public function get_content() {
        global $COURSE;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass;

        return $this->content;
    }

    /**
     * Use UCLA Course menu block hook
     */
    public static function get_navigation_nodes($course) {
        global $DB, $COURSE;

        $nodes = array();
        
        if (!is_collab_site($COURSE)) {
            $nodetitle = get_string('title', 'block_ucla_library_reserves');
            $node = navigation_node::create($nodetitle, new moodle_url('/blocks/ucla_library_reserves/index.php',
                            array('courseid' => $COURSE->id)));
            $node->add_class('library-reserve-link');
            $nodes[] = $node;
        }

        return $nodes;
    }

    /**
     * Displays settings link in admin menu.
     *
     * @return boolean
     */
    public function has_config() {
        return true;
    }

    /**
     * Called by moodle
     */
    public function applicable_formats() {

        return array(
            'site-index' => false,
            'course-view' => false,
            'my' => false,
            'block-ucla_library_reserves' => false,
            'not-really-applicable' => true
        );
        // Hack to make sure the block can never be instantiated.
    }

    /**
     * Called by moodle
     */
    public function instance_allow_multiple() {
        return false; // Disables multiple blocks per page.
    }

    /**
     * Called by moodle
     */
    public function instance_allow_config() {
        return false; // Disables instance configuration.
    }

}
