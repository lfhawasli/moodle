<?php
// This file is part of the UCLA MyEngineer plugin for Moodle - http://moodle.org/
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
 * Injects link into Site menu block for Engineering courses.
 *
 * @package    block_ucla_myengineer
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once(dirname(dirname(dirname(__FILE__))) . '/local/ucla/lib.php');

/**
 * Block class.
 *
 * @package    block_ucla_myengineer
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_ucla_myengineer extends block_base {

    /**
     * Called by moodle.
     */
    public function init() {
        $this->title = get_string('title', 'block_ucla_myengineer');
    }

    /**
     * If course is an engineering course then add link to UCLA site menu block.
     *
     * @param array $course
     * @return array
     */
    public static function get_navigation_nodes($course) {
        if (is_engineering($course['course']->id)) {
            $myengineerurl = 'https://my.engineering.ucla.edu';
            $urlobj = new moodle_url($myengineerurl);
            $node = navigation_node::create(get_string('title', 'block_ucla_myengineer'), 
                    $urlobj, global_navigation::TYPE_CUSTOM, null, null, new pix_icon('spacer', ''));

            $node->add_class('myengineer-link');
            return $node;
        }
        return null;
    }

    /**
     * Called by moodle. We don't want the block to be added anywhere.
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
}
