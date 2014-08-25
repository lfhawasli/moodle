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

/**
 * The Grade export view event class.
 *
 * @package    local_gradebook
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_gradebook\event;
defined('MOODLE_INTERNAL') || die();

/**
 * Event class for viewing XLS grade export page.
 *
 * @since     Moodle 2.7
 * @copyright 2014 UC Regents
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class grades_export_viewed_xls extends grades_export_viewed {

    /**
     * Returns the name of the event.
     * @return string
     */
    public static function get_name() {
        return get_string('eventgradesexportviewed', 'local_gradebook', 'xls');
    }
}