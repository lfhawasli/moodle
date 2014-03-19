<?php
// This file is part of the UCLA theme plugin for Moodle - http://moodle.org/
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
 * Override Moodle's core calendar renderer.
 *
 * @package    theme_uclashared
 * @copyright  UC Regents 2014
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/calendar/renderer.php');

/**
 * Overriding the core calendar render (calendar/renderer.php).
 *
 * @copyright  UC Regents 2014
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class theme_uclashared_core_calendar_renderer extends core_calendar_renderer {

    /**
     * Adds notice about sychronizing delays when using external calendar apps.
     *
     * @param bool $allowthisweek
     * @param bool $allownextweek
     * @param bool $allownextmonth
     * @param int $userid
     * @param string $authtoken
     * @return string
     */
    public function basic_export_form($allowthisweek, $allownextweek, $allownextmonth, $userid, $authtoken) {
        global $OUTPUT;

        $notice = $OUTPUT->notification(get_string('calsyncnotice',
                        'theme_uclashared'), 'notifymessage');
        $form = parent::basic_export_form($allowthisweek, $allownextweek,
                        $allownextmonth, $userid, $authtoken);

        return $notice . $form;
    }

}