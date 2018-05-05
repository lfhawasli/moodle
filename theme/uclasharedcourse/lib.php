<?php
// This file is part of the UCLA course theme for Moodle - http://moodle.org/
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
 * Theme functions called by Moodle core code.
 *
 * @package     theme_uclasharedcourse
 * @copyright   2014 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/admin/tool/uclasiteindicator/lib.php');
require_once($CFG->dirroot . '/theme/uclashared/lib.php');

/**
 * Callback to add head elements.
 *
 * @return str valid html head content
 */
function theme_uclasharedcourse_before_standard_html_head() {
    return theme_uclashared_before_standard_html_head();
}

/**
 * Call same method as parent theme in inject preprocessing.
 *
 * @param moodle_page $page
 */
function theme_uclasharedcourse_page_init(moodle_page $page) {
    theme_uclashared_page_init($page);
}

/**
 * Serves course logo images
 *
 * @param object $course
 * @param mixed $cm             Not used.
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 */
function theme_uclasharedcourse_pluginfile($course, $cm, $context, $filearea, array $args, $forcedownload, array $options=array()) {

    $itemid = clean_param(array_shift($args), PARAM_INT);
    $filename = clean_param(array_shift($args), PARAM_TEXT);

    // If a site is 'private', then we only display logos to enrolled users.
    if ($collabsite = siteindicator_site::load($course->id)) {
        global $USER;
        if ($collabsite->property->type == siteindicator_manager::SITE_TYPE_PRIVATE &&
                (!is_enrolled($context, $USER) && !has_capability('moodle/course:update', $context))) {
            send_file_not_found();
        }
    }

    // Grab stored file.
    $fs = get_file_storage();
    $storedfile = $fs->get_file($context->id, 'theme_uclasharedcourse', $filearea, $itemid, '/', $filename);

    // Serve.
    send_stored_file($storedfile, 86400, 0, $forcedownload);
}
