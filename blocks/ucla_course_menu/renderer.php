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
 * Contains block course menu renderer class.
 *
 * @package block_ucla_course_menu
 * @copyright 2016 UC Regent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/navigation/renderer.php');

/**
 * Block course menu renderer class.
 * @copyright 2016 UC Regent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_ucla_course_menu_renderer extends block_navigation_renderer {
    /**
     * Produces a navigation node for the navigation tree
     *
     * @param navigation_node[] $i
     * @param array $a
     * @param int $e
     * @param array $o
     * @param int $d
     * @return string
     */
    public function navigation_node($i, $a=array(), $e=null,
            array $o=array(), $d=1) {
        return parent::navigation_node($i, $a, $e, $o, $d);
    }
}
