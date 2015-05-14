<?php
// This file is part of UCLA Subject Links block for Moodle - http://moodle.org/
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
 * Subject area links page 'viewed' logging event handler.
 *
 * @package    block_ucla_subject_links
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_ucla_subject_links\event;

defined('MOODLE_INTERNAL') || die();

class page_viewed extends \core\event\base {
    protected function init() {
        $this->data['crud'] = 'r'; // c(reate), r(ead), u(pdate), d(elete)
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    /**
     * Returns name of the event.
     * 
     * @return string
     */
    public static function get_name() {
        return get_string('eventpageviewed', 'block_ucla_subject_links');
    }
 
    /**
     * Returns a short description for the event.
     * 
     * @return string
     */
    public function get_description() {
        return "The user with id '{$this->userid}' viewed the subject area links "
            . "page for {$this->other['subjarea']}.";
    }
 
    /**
     * Returns URL of the event.
     * 
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/blocks/ucla_subject_links/view.php', 
                array('course_id' => $this->courseid, 'subj_area' => $this->other['subjarea']));
    }
 
    /**
     * Add data to legacy log.
     * 
     * @return array
     */
    public function get_legacy_logdata() {
        return array($this->courseid, 'course', 'ucla subject links view',
            '../blocks/ucla_subject_links/view.php?course_id=' . $this->courseid . "&subj_area=" . $this->other['subjarea']);
    }
}