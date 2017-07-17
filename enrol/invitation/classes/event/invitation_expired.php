<?php
// This file is part of the UCLA Site Invitation Plugin for Moodle - http://moodle.org/
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
 * Adds new instance of enrol_invitation or edits current instance.
 *
 * @package    enrol_invitation
 * @copyright  2013 UC Regents
 * @copyright  2011 Jerome Mouneyrac {@link http://www.moodleitandme.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_invitation\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Invitation extended event class.
 *
 * Class for event to be triggered when an invitation is expired.
 *
 * @package    enrol_invitation
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class invitation_expired extends \core\event\base {

    /**
     * init()
     * Creates the event.
     * */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = 'enrol_invitation';
    }

    /**
     * get_name()
     * @return the name of the event.
     * */
    public static function get_name() {
        return get_string('eventinvitationexpired', 'enrol_invitation');
    }

    /**
     * get_description()
     * @return a short description for the event log.
     * */
    public function get_description() {
        return "The user with id '{$this->userid}' has an expired invitation with enrol_invitation id '{$this->objectid}'.";
    }

    /**
     * get_url()
     * @return the URL of the event.
     * */
    public function get_url() {
        return new \moodle_url('/enrol/invitation/history.php', array('courseid' => $this->courseid));
    }

    /**
     * get_legacy_logdata()
     * Used to add log data to legacy log, this is only called if legacy logging
     * is enabled through the legacy logging plugin.
     *
     * @return an array that imitates the arguments that are used to be passed
     * to the old add_to_log function.
     * */
    public function get_legacy_logdata() {
        $logurl = new \moodle_url('/enrol/invitation/history.php', array('courseid' => $this->courseid));
        return array($this->courseid, 'enrol_invitation', 'invitation expired', $logurl, $this->other);
    }
}
