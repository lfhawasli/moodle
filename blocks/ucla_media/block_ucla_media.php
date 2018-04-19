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

/**
 * Block definition file for UCLA Media
 *
 * @package    block_ucla_media
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_ucla_media extends block_base {

    /**
     * Init function.
     */
    public function init() {

        // Initialize title and name.
        $this->title = get_string('title', 'block_ucla_media');
        $this->name = get_string('pluginname', 'block_ucla_media');
    }

    /**
     * Hook into UCLA Site menu block.
     *
     * @param integer $courseid
     *
     * @return array
     */
    public static function get_navigation_nodes($courseid) {
        global $DB;
        $nodes = array();

        // Make sure user is logged in.
        $context = context_course::instance($courseid);
        if (!is_enrolled($context) && !has_capability('moodle/course:view', $context)) {
            return $nodes;
        }
        
        $bruincastfound = $DB->record_exists('ucla_bruincast',
                array('courseid' => $courseid));
        if ($bruincastfound) {
            $node = navigation_node::create(get_string('title',
                    'block_ucla_media'), new moodle_url('/blocks/ucla_media/bcast.php',
                            array('courseid' => $courseid)));
            $node->add_class('video-reserves-link');
            $nodes[] = $node;
        } else {
            $videoreservesfound = $DB->record_exists('ucla_video_reserves',
                array('courseid' => $courseid));
            if ($videoreservesfound) {
                $node = navigation_node::create(get_string('title',
                        'block_ucla_media'), new moodle_url('/blocks/ucla_media/videoreserves.php',
                                array('courseid' => $courseid)));
                $node->add_class('video-reserves-link');
                $nodes[] = $node;
            } else {
                $libraryreservesfound = $DB->get_records('ucla_library_music_reserves',
                        array('courseid' => $courseid));
                if ($libraryreservesfound) {
                    $node = navigation_node::create(get_string('title',
                            'block_ucla_media'), new moodle_url('/blocks/ucla_media/libreserves.php',
                                    array('courseid' => $courseid)));
                    $node->add_class('video-reserves-link');
                    $nodes[] = $node;
                }
            }
        }

        return $nodes;
    }

    /**
     * Hack to make sure the block can never be instantiated.
     */
    public function applicable_formats() {
        return array(
            'site-index' => false,
            'course-view' => false,
            'my' => false,
            'block-ucla_bruincast' => false,
            'not-really-applicable' => true
        );
    }

    public function has_config() {
        return true;
    }
}
