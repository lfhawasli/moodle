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

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/../moodleblock.class.php');

class block_ucla_video_furnace extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_ucla_video_furnace');
    }

    function applicable_formats() {
        return array(
            'site-index' => false,
            'course-view' => false,
            'my' => false,
            'not-really-applicable' => true
        );
    }

    /**
     *  This will create a link to the ucla video furnace page.
     * */
    static function get_action_link($courseid) {
        return new moodle_url('/blocks/ucla_video_furnace/view.php', 
                array('courseid' => $courseid));
    }

    public function get_content() {
        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass;

        return $this->content;

        if (!isset($this->course)) {
            global $COURSE;
            $this->course = $COURSE;
        }

        return get_action_link($this->course);
    }

    public static function get_navigation_nodes($course) {
        global $DB;
        $nodes = array();
        $courseid = $course['course']->id;
        
        $recordsfound = $DB->record_exists('ucla_video_furnace',
                array('courseid' => $courseid));
        
        if ($recordsfound) {
            $node = navigation_node::create(get_string('title',
                    'block_ucla_video_furnace'), self::get_action_link($courseid));
            $node->add_class('video-furnace-link');
            $nodes[] = $node;
        }
        
        return $nodes;
    }

}

