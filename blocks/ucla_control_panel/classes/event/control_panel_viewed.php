<?php
// This file is part of UCLA Control panel block for Moodle - http://moodle.org/
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
 * This file defines the control_panel_viewed class
 *
 * @package    block_ucla_control_panel
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_ucla_control_panel\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Control panel 'viewed' logging event handler.
 *
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class control_panel_viewed extends \core\event\base {
    /**
     * sets vars
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'r'; // CRUD: Create, read, update, delete.
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    /**
     * Returns name of the event.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventcontrolpanelviewed', 'block_ucla_control_panel');
    }

    /**
     * Returns info on when a user with ID has viwed a control panel module (tab).
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '{$this->userid}' viewed the control panel "
            . "tab '{$this->other['module_name']}'.";
    }

    /**
     * Returns URL to control panel module viewed.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/blocks/ucla_control_panel/view.php', array(
                    'course_id' => $this->courseid,
                    'section' => $this->other['section'],
                    'module' => $this->other['module']
                ));
    }

    /**
     * Legacy log.
     *
     * @return array
     */
    public function get_legacy_logdata() {
        return array($this->courseid, 'course', 'control panel view',
            '../blocks/ucla_control_panel/view.php?course_id='.$this->courseid.
            '&section='.$this->other['section'].'&module='.$this->other['module'],
            $this->other['module_name']);
    }
}