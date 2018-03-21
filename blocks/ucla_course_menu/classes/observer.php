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
 * Observer class.
 *
 * @package    block_ucla_course_menu
 * @copyright  2018 UC Regent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Block course menu observer class.
 *
 * @package    block_ucla_course_menu
 * @copyright  2018 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_ucla_course_menu_observer {

    /**
     * Checks if course has a ucla_course_menu_block. If so, then remove it.
     *
     * @param \core\event\course_restored $event
     *
     * @return boolean
     */
    public static function remove_course_menu_block_restored(\core\event\course_restored $event) {
        global $DB;
        // Only respond to course restores.
        if ($event->other['type'] != backup::TYPE_1COURSE) {
            return true;
        }

        // Get course context.
        $context = context_course::instance($event->courseid);
        if (empty($context)) {
            return false;
        }

        // Get block instance, if any.
        $blockinstance = $DB->get_record('block_instances',
                array('blockname' => 'ucla_course_menu',
                      'parentcontextid' => $context->id));
        blocks_delete_instance($blockinstance);
        return true;
    }
}
