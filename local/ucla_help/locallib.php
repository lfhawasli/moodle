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
 */

defined('MOODLE_INTERNAL') || die;

spl_autoload_register(function ($class) {
    require_once(__DIR__ . '/sidebar/sidebar.php');

    list($mod, $name) = explode('_', $class);

    if ($mod === 'sidebar') {
        require_once(__DIR__ . '/sidebar/' . $name . '/sidebar.php');
    }
});

global $PAGE;

$PAGE->requires->jquery();
$PAGE->requires->js('/theme/uclashared/package/sematic-ui/uncompressed/modules/sidebar.js');
$PAGE->requires->css('/theme/uclashared/package/sematic-ui/uncompressed/modules/sidebar.css');

// Load CSS dependencies
$PAGE->requires->css('/theme/uclashared/package/sematic-ui/uncompressed/modules/accordion.css');
$PAGE->requires->css('/theme/uclashared/package/sematic-ui/uncompressed/views/list.css');
$PAGE->requires->css('/theme/uclashared/package/sematic-ui/uncompressed/elements/segment.css');
$PAGE->requires->css('/theme/uclashared/package/sematic-ui/uncompressed/elements/header.css');
$PAGE->requires->css('/theme/uclashared/package/sematic-ui/uncompressed/elements/label.css');
$PAGE->requires->css('/theme/uclashared/package/sematic-ui/uncompressed/elements/divider.css');
$PAGE->requires->css('/theme/uclashared/package/sematic-ui/uncompressed/elements/button.css');
$PAGE->requires->css('/theme/uclashared/package/sematic-ui/uncompressed/collections/form.css');

class ucla_sidebar {

    public static function help(array $args) {
        global $PAGE;
        $PAGE->requires->js('/local/ucla_help/js/help_toggle.js');

        $content = array_reduce($args, function($carry, $item) {
                $carry .= $item->render();
                return $carry;
        }, '');
        $hide = html_writer::tag('button', 'Hide help', array('class' => 'btn btn-info help-toggle btn-block'));
        return html_writer::div($hide . $content, 'ui main help sidebar');
    }

    public static function block_pre($block) {
        global $PAGE;
        $PAGE->requires->js('/local/ucla_help/js/block_toggle.js');

        $blockpre = html_writer::div($block, 'block-region', array('id' => 'region-pre'));
        $sidebar = html_writer::div(
                        html_writer::div($blockpre, 'ui raised segment'), 'ui pre block sidebar'
        );

        return $sidebar;
    }

}