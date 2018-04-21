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
 * Override the two column layout from the boost theme.
 *
 * @package   theme_boost
 * @copyright 2018 UC Regents
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

user_preference_allow_ajax_update('drawer-open-nav', PARAM_ALPHA);
require_once($CFG->libdir . '/behat/lib.php');

if (isloggedin()) {
    $navdraweropen = (get_user_preferences('drawer-open-nav', 'true') == 'true');
} else {
    $navdraweropen = false;
}
$extraclasses = [];
if ($navdraweropen) {
    $extraclasses[] = 'drawer-open-left';
}
$bodyattributes = $OUTPUT->body_attributes($extraclasses);
$blockshtml = $OUTPUT->blocks('side-pre');
$hasblocks = strpos($blockshtml, 'data-block=') !== false;
$regionmainsettingsmenu = $OUTPUT->region_main_settings_menu();
$templatecontext = [
    'sitename' => format_string($SITE->shortname, true, ['context' => context_course::instance(SITEID), "escape" => false]),
    'output' => $OUTPUT,
    'sidepreblocks' => $blockshtml,
    'hasblocks' => $hasblocks,
    'bodyattributes' => $bodyattributes,
    'navdraweropen' => $navdraweropen,
    'regionmainsettingsmenu' => $regionmainsettingsmenu,
    'hasregionmainsettingsmenu' => !empty($regionmainsettingsmenu),
    'system_link' => get_config('theme_uclashared', 'system_link'),
    'system_name' => get_config('theme_uclashared', 'system_name')
];

$templatecontext['flatnavigation'] = $PAGE->flatnav;

// Adding in if we are in a course and if we have editing turned on.
$templatecontext['incourse'] = $COURSE->id === SITEID ? false : true;
$templatecontext['editingon'] = false;
if ($PAGE->user_allowed_editing() && $PAGE->user_is_editing()) {
    $templatecontext['editingon'] = true;

    // Prepare editing quick links.
    if (class_exists('block_ucla_modify_coursemenu')) {
        // Get section user is viewing.
        $displaysection = 0;
        $format = course_get_format($COURSE);
        if (($format->get_format() === 'ucla')) {
            $displaysection = $format->figure_section();
        }

        $params['course'] = $COURSE;
        $params['section'] = $displaysection;

        $templatecontext['modifysections'] = block_ucla_modify_coursemenu::get_editing_link($params);
        $templatecontext['rearrangematerials'] = block_ucla_rearrange::get_editing_link($params);
        $templatecontext['managecopyright'] = block_ucla_copyright_status::get_editing_link($params);
    }
}

echo $OUTPUT->render_from_template('theme_boost/columns2', $templatecontext);

