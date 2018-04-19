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
 * @package   block_ucla_modify_coursemenu
 * @copyright 2018 UC Regents
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
/**
 * Adds modify course menu link to admin panel.
 *
 * @param navigation_node $navigation The navigation node to extend.
 * @param stdClass        $course     The course object for the tool.
 * @param context         $context    The context of the course.
 */
function block_ucla_modify_coursemenu_extend_navigation_course($navigation, $course, $context) {
    if (has_capability('moodle/course:update', $context)) {
        $section = optional_param('section', null, PARAM_INT);
        $params = array('courseid' => $course->id);
        if (!is_null($section)) {
            $params['section'] = $section;
        }
        $setting = navigation_node::create(get_string('modifysections', 'block_ucla_modify_coursemenu'),
                new moodle_url('/blocks/ucla_modify_coursemenu/modify_coursemenu.php', $params),
                navigation_node::TYPE_SETTING);
        $navigation->add_node($setting);
    }
};
