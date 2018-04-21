<?php
// This file is part of the UCLA library research portal plugin for Moodle - http://moodle.org/
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
 * Block class for UCLA Library Research Portal
 *
 * @package    block_ucla_library_portal
 * @copyright  2012 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later 
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/ucla/lib.php');

class block_ucla_library_portal extends block_base {

    /**
     * Make sure the block cannot be added anywhere.
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
     * Block doesn't have any content to display.
     * 
     * @return null
     */
    public function get_content() {
        return null;
    }

    /**
     * Use UCLA Course menu block hook.
     *
     * @param array $courseinfo     An array from the ucla_course_menu block
     *                              with a 'course' index with a course object.
     *
     * @return array               Nodes that will be displayed in the menu.
     */
    public static function get_navigation_nodes($courseinfo) {
        $course = $courseinfo['course'];

        $libraryurl = get_config('block_ucla_library_portal', 'url');

        // Check to see if course is non-srs.
        if (is_collab_site($course) || empty($libraryurl)) {
            return null;
        }

        $params = array();
        
        $reginfos = ucla_get_course_info($course->id);
        // Serious error if no results were returned.
        $coursedetails = array();

        if (!empty($reginfos)) {
            // Let's make an array of term, subject area, unformatted
            // catalog and section numbers.
            $maxrecords = get_config('block_ucla_library_portal', 'maxrecords');
            $count = 0;
            foreach ($reginfos as $index => $reginfo) {
                $coursedetails[$index]['t'] = $reginfo->term;
                $coursedetails[$index]['sub'] = $reginfo->subj_area;
                $coursedetails[$index]['cat'] = $reginfo->crsidx;
                $coursedetails[$index]['sec'] = $reginfo->classidx;

                // Make sure we don't go over the max records limit.
                ++$count;
                if ($count >= $maxrecords) {
                    break;
                }
            }
        }
        $libraryurl .= '?'.http_build_query($coursedetails, 'c');        
        
        $urlobj = new moodle_url($libraryurl, $params);
        $node = navigation_node::create(get_string('portalname', 'block_ucla_library_portal'), 
                $urlobj, global_navigation::TYPE_CUSTOM, null, null, new pix_icon('spacer', ''));
        $node->add_class('library-research-portal');
        return $node;
    }

    /**
     * Returns true because block has a settings.php file.
     *
     * @return boolean  True
     */
    public function has_config() {
        return true;
    }

    /**
     * Do barebones initialization.
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_ucla_library_portal');
    }

    }
