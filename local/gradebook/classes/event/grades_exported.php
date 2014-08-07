<?php
// This file is part of UCLA gradebook customizations local plugin for 
// Moodle - http://moodle.org/
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

namespace local_gradebook\event;

defined('MOODLE_INTERNAL') || die();

class grades_exported extends \core\event\base {

    /**
     * Creates the event.
     */
    protected function init() {
        $this->data['crud'] = 'r'; // c(reate), r(ead), u(pdate), d(elete)
        $this->data['edulevel'] = self::LEVEL_TEACHING;
    }

    /**
     * Returns the name of the event.
     * @return string
     */
    public static function get_name() {
        return get_string('eventgradesexported', 'local_gradebook');
    }

    /**
     * Returns a short description for the event that includes filename and
     * contenthash.
     * @return string
     */
    public function get_description() {
        $user = $this->userid;
        $type = $this->other['type'];
        $course = $this->courseid;
        return "The user with id '{$user}' has exported '{$type}' grades from course with id '{$course}'.";
    }

    /**
     * Returns URL to the course page.
     * @return moodle_url
     */
    public function get_url() {
        return new \moodle_url('/grade/export/'.$this->other['type'].'/index.php', array('id' => $this->courseid));
    }

    /**
     * Add data to legacy log.
     *
     * @return array
     */
    public function get_legacy_logdata() {
        return array($this->courseid, 'grade', "export {$this->other['type']}",
                '/export/'.$this->other['type'].'/index.php?id='.$this->courseid);
    }
}