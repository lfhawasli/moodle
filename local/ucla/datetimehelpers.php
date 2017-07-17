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

/**
 * A collection of date/time format helpers.
 *
 * @copyright 2012 UC Regents
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package local_ucla
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Reports the approximate distance in time between two times given in seconds
 * or in a valid ISO string like.
 * For example, if the distance is 47 minutes, it'll return
 * "about 1 hour". See the source for the complete wording list.
 *
 * Integers are interpreted as seconds. So,
 * <tt>$date_helper->distance_of_time_in_words(50)</tt> returns "less than a minute".
 *
 * Set <tt>include_seconds</tt> to true if you want more detailed approximations if distance < 1 minute
 *
 * Code borrowed/inspired from:
 * http://www.8tiny.com/source/akelos/lib/AkActionView/helpers/date_helper.php.source.txt
 *
 * Which was in term inspired by Ruby on Rails' similarly called function
 *
 * @param int $fromtime
 * @param int $totime
 * @param boolean $includeseconds
 * @return string
 */
function distance_of_time_in_words($fromtime, $totime = 0, $includeseconds = false) {
    $fromtime = is_numeric($fromtime) ? $fromtime : strtotime($fromtime);
    $totime = is_numeric($totime) ? $totime : strtotime($totime);
    $distanceinminutes = round((abs($totime - $fromtime)) / 60);
    $distanceinseconds = round(abs($totime - $fromtime));

    if ($distanceinminutes <= 1) {
        if ($includeseconds) {
            if ($distanceinseconds < 5) {
                return get_string('less_than_x_seconds', 'local_ucla', 5);
            } else if ($distanceinseconds < 10) {
                return get_string('less_than_x_seconds', 'local_ucla', 10);
            } else if ($distanceinseconds < 20) {
                return get_string('less_than_x_seconds', 'local_ucla', 20);
            } else if ($distanceinseconds < 40) {
                return get_string('half_minute', 'local_ucla');
            } else if ($distanceinseconds < 60) {
                return get_string('less_minute', 'local_ucla');
            } else {
                return get_string('a_minute', 'local_ucla');
            }
        }
        return ($distanceinminutes == 0) ? get_string('less_minute', 'local_ucla') : get_string('a_minute', 'local_ucla');
    } else if ($distanceinminutes <= 45) {
        return get_string('x_minutes', 'local_ucla', $distanceinminutes);
    } else if ($distanceinminutes < 90) {
        return get_string('about_hour', 'local_ucla');
    } else if ($distanceinminutes < 1440) {
        return get_string('about_x_hours', 'local_ucla', round($distanceinminutes / 60));
    } else if ($distanceinminutes < 2880) {
        return get_string('a_day', 'local_ucla');
    } else {
        return get_string('x_days', 'local_ucla', round($distanceinminutes / 1440));
    }
}
