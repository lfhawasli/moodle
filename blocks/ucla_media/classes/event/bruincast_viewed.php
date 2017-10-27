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
 * BruinCast 'video viewed' logging event handler.
 *
 * @package    block_ucla_media
 * @copyright  2017 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_ucla_media\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Class file.
 *
 * @package    block_ucla_media
 * @copyright  2017 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bruincast_viewed extends \core\event\base {

    /**
     * Initialization method.
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'ucla_bruincast';
    }

    /**
     * Returns name of the event.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventbruincastviewed', 'block_ucla_media');
    }

    /**
     * Returns info on when a user with ID has viewed the bruincast file.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '{$this->userid}' viewed the bruincast "
            . "'{$this->other['name']}'.";
    }

    /**
     * Returns URL to BruinCast video viewed.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/blocks/ucla_media/view.php',
                array('id' => $this->objectid,
                    'mode' => $this->other['mode'],
                    'filename' => $this->other['name']
                ));
    }

    /**
     * Legacy log.
     *
     * @return array
     */
    public function get_legacy_logdata() {
        return array($this->courseid, 'course', 'bruincast view',
            '../blocks/ucla_media/view.php?id='.$this->objectid,
            $this->other['name'], $this->other['mode']);
    }
}