<?php
// This file is part of the UCLA course menu block for Moodle - http://moodle.org/
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
 * Event handlers.
 * 
 * @package    block_ucla_course_menu
 * @copyright  2016 UC Regent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/* Course Creation/Restore:
 *  - ensure that ucla_course_menu block is properly located in the top of the 
 *    left-hand side of the screen
 */

$observers = array(
    array(
        'eventname' => '\core\event\course_created',
        'callback'  => 'block_ucla_course_menu_observer::move_course_menu_block_created'
    ),
    
    array(
        'eventname' => '\core\event\course_restored',
        'callback'  => 'block_ucla_course_menu_observer::move_course_menu_block_restored'
    )
);