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
 * Kaltura video resource LTI launch page.
 *
 * @package    mod_kalvidres
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2014 Remote Learner.net Inc http://www.remote-learner.net
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(dirname(dirname(__FILE__))).'/local/kaltura/locallib.php');

global $USER;

require_login();

$courseid = required_param('courseid', PARAM_INT);
$height = required_param('height', PARAM_INT);
$width = required_param('width', PARAM_INT);
$withblocks = optional_param('withblocks', 0, PARAM_INT);
$source = optional_param('source', '', PARAM_URL);

$context = context_course::instance($courseid);

// START UCLA MOD: CCLE-5021 - Guests can't access "public" Kaltura Video Resources
//// If the user isn't a teacher or they are not enrolled in the course context then return with an error.
//if (!has_capability('mod/kalvidres:addinstance', $context) && is_guest($context)) {
//    echo get_string('nocapabilitytousethisservice', 'error');
//    die();
//}
// Check public/private and if it is private, then use original Kaltura check.
require_once($CFG->dirroot . '/local/publicprivate/lib/module.class.php');
$isprivate = true;
$kalvidresid = optional_param('id', null, PARAM_INT);
$ppcourse = PublicPrivate_Course::build($courseid);
// The 'id' is not passed in when creating a new video resource or on mod_form.
if (!is_null($kalvidresid) && $ppcourse->is_activated()) {
    // Get course module record directly from DB, because it is too hard to get
    // it from get_fast_modinfo().
    $sql = "SELECT cm.id
              FROM {course_modules} cm
              JOIN {modules} m ON (cm.module=m.id)
             WHERE cm.instance=? AND
                   m.name='kalvidres'";
    $coursemoduleid = $DB->get_field_sql($sql, array($kalvidresid));
    $pp = new PublicPrivate_Module($coursemoduleid);
    if (!$pp->is_private()) {
        $isprivate = false;
    }
}
if ($isprivate) {
    if (!has_capability('mod/kalvidres:addinstance', $context) && is_guest($context)) {
        echo get_string('nocapabilitytousethisservice', 'error');
        die();
    }
}
// END UCLA MOD: CCLE-5021

$course = get_course($courseid);

$launch = array();
$launch['id'] = 1;
$launch['cmid'] = 0;
$launch['title'] = 'Kaltura video resource';
$launch['module'] = KAF_BROWSE_EMBED_MODULE;
$launch['course'] = $course;
$launch['width'] = $width;
$launch['height'] = $height;
$launch['custom_publishdata'] = '';

$source = local_kaltura_add_kaf_uri_token($source);

if (false === local_kaltura_url_contains_configured_hostname($source) && !empty($source)) {
    echo get_string('invalid_source_parameter', 'mod_kalvidres');
    die;
} else {
    $launch['source'] = urldecode($source);
}

if (local_kaltura_validate_browseembed_required_params($launch)) {
    $content = local_kaltura_request_lti_launch($launch, $withblocks);
    echo $content;
} else {
    echo get_string('invalid_launch_parameters', 'mod_kalvidres');
}