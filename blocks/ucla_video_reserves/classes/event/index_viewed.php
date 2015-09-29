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
 * Video reserve 'index viewed' logging event handler.
 *
 * @package    block_ucla_video_reserves
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_ucla_video_reserves\event;

defined('MOODLE_INTERNAL') || die();

class index_viewed extends \core\event\base {
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
        return get_string('eventindexviewed', 'block_ucla_video_reserves');
    }

    /**
     * Returns info on when a user with ID has viwed a control panel module (tab).
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '{$this->userid}' viewed the index.";
    }

    /**
     * Returns URL to video viewed.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/blocks/ucla_video_reserves/index.php',
                array('courseid' => $this->courseid));
    }

    /**
     * Legacy log.
     *
     * @return array
     */
    public function get_legacy_logdata() {
        return array($this->courseid, 'course', 'video reserve index',
            '../blocks/ucla_video_reserves/index.php?courseid='.$this->courseid);
    }
}