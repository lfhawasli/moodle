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
 * Displays the frontpage.
 *
 * Uses its own css in style/frontpage.css so it is not included in the main
 * site css file.
 *
 * @package   theme_uclashared
 * @copyright 2018 UC Regents
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$weeksarr = $OUTPUT->parsed_weeks_display();

$imagearr = theme_uclashared_frontpageimage();

$templatecontext = [
    'sitename' => format_string($SITE->shortname, true, ['context' => context_course::instance(SITEID), "escape" => false]),
    'output' => $OUTPUT,
    'week' => $weeksarr[1],
    'quarter' => $weeksarr[0],
    'bgimage' => $imagearr['image'],
    'imagecredits' => $imagearr['credits'],
    'system_name' => get_config('theme_uclashared', 'system_name'),
    'system_link' => get_config('theme_uclashared', 'system_link'),
    'running_environment' => get_config('theme_uclashared', 'running_environment')
];

$PAGE->requires->jquery();
$PAGE->requires->js('/theme/uclashared/javascript/frontpage.js');

// Use plugin version number to ensure frontpage.css is always newest one.
$plugin = new stdClass();
require($CFG->dirroot . '/theme/uclashared/version.php');
$PAGE->requires->css('/theme/uclashared/style/frontpage.css?v=' . $plugin->version);

$b = block_instance('ucla_browseby');
$templatecontext['browseby'] = $b->get_content()->text;
$s = block_instance('ucla_search');
$templatecontext['search'] = $s::search_form();
$a = block_instance('ucla_alert');
$templatecontext['alert'] = $a->get_content()->text;

echo $OUTPUT->render_from_template('theme_uclashared/frontpage', $templatecontext);
