<?php
// This file is part of the UCLA local help plugin for Moodle - http://moodle.org/
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
 *
 * UCLA Sidebar
 *
 * @package    block_ucla_help
 * @author     Rex Lorenzo <rex@seas.ucla.edu>
 * @copyright  2011 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once(__DIR__ . '/sidebar/sidebar.php');
require_once(__DIR__ . '/sidebar/docs/sidebar.php');
require_once(__DIR__ . '/sidebar/feedback/sidebar.php');
require_once(__DIR__ . '/sidebar/file/sidebar.php');

global $PAGE;

// Load JS.
$PAGE->requires->yui_module('moodle-block_ucla_help-sidebar', 'M.block_ucla_help.init_sidebar', array(array()));
$PAGE->requires->jquery();
$PAGE->requires->js('/theme/uclashared/javascript/sidebar.min.js');
/**
 * This class is for the sidebar on the help page
 *
 * @copyright  2011 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ucla_sidebar {

    /**
     * Generates HTML for the help sidebar
     *
     * @param array $args
     * @return string
     */
    public static function help(array $args) {
        global $PAGE;
        $PAGE->requires->js('/blocks/ucla_help/js/help_toggle.js');

        $content = array_reduce($args, function($carry, $item) {
                $carry .= $item->render();
                return $carry;
        }, '');
        $hide = html_writer::tag('button', get_string('hidehelp', 'block_ucla_help'),
                array('class' => 'btn btn-info help-toggle btn-block'));
        return html_writer::div($hide . $content, 'ui main help sidebar');
    }

    /**
     * Generates HTML For the help sidebar
     *
     * @param string $block
     * @return string
     */
    public static function block_pre($block) {
        global $PAGE;
        $PAGE->requires->js('/blocks/ucla_help/js/block_toggle.js');

        $blockpre = html_writer::div($block, 'block-region', array('id' => 'region-pre'));
        $sidebar = html_writer::div(
                        html_writer::div($blockpre, 'ui raised segment'), 'ui pre block sidebar'
        );

        return $sidebar;
    }

}