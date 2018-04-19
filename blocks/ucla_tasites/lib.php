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
 * @package   block_ucla_tasites
 * @copyright 2018 UC Regents
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/block_ucla_tasites.php');

/**
 * Adds manage TA sites link to admin panel.
 *
 * @param navigation_node $navigation The navigation node to extend.
 * @param stdClass        $course     The course object for the tool.
 * @param context         $context    The context of the course.
 */
function block_ucla_tasites_extend_navigation_course($navigation, $course, $context) {
    $courseid = $course->id;
    
    $accessible = false;
    try {
        if (block_ucla_tasites::check_access($courseid) && !block_ucla_tasites::is_tasite($courseid)) {
            $mapping = block_ucla_tasites::get_tasection_mapping($courseid);
            $accessible = block_ucla_tasites::get_tasite_users($courseid) ||
                    (isset($mapping['bysection']) && !isset($mapping['bysection']['all']));
        }
    } catch (moodle_exception $e) {
        // Do nothing.
        $accessible = false;
    }
    
    if ($accessible) {
        $setting = navigation_node::create(get_string('managetasites', 'block_ucla_tasites'),
                new moodle_url('/blocks/ucla_tasites/index.php',
                        array('courseid' => $courseid)), navigation_node::TYPE_SETTING);
        $navigation->add_node($setting);
    }
};
