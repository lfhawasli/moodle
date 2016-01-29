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
 * Displays different views of the logs.
 *
 * @package report_emaillog
 * @copyright  2015 UC Regents
 */

require('../../config.php');
require_once($CFG->dirroot.'/report/emaillog/lib.php');
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->dirroot.'/lib/tablelib.php');

$id          = optional_param('id', 0, PARAM_INT);// Course ID.
$user        = optional_param('user', 0, PARAM_INT); // User to display.
$date        = optional_param('date', 0, PARAM_INT); // Date to display.
$page        = optional_param('page', '0', PARAM_INT);     // Which page to show.
$perpage     = optional_param('perpage', '100', PARAM_INT); // How many per page.
$showcourses = optional_param('showcourses', false, PARAM_BOOL); // Whether to show courses if we're over our limit.
$showusers   = optional_param('showusers', false, PARAM_BOOL); // Whether to show users if we're over our limit.
$chooselog   = optional_param('chooselog', false, PARAM_BOOL);

$params = array();

if (!empty($id)) {
    $params['id'] = $id;
} else {
    $site = get_site();
    $id = $site->id;
}
if ($user !== 0) {
    $params['user'] = $user;
}
if ($date !== 0) {
    $params['date'] = $date;
}
if ($page !== '0') {
    $params['page'] = $page;
}
if ($perpage !== '100') {
    $params['perpage'] = $perpage;
}
if ($showcourses) {
    $params['showcourses'] = $showcourses;
}
if ($showusers) {
    $params['showusers'] = $showusers;
}
if ($chooselog) {
    $params['chooselog'] = $chooselog;
}

$url = new moodle_url("/report/emaillog/index.php", $params);

// Get course details.
if (empty($id)) {
    require_login();
    $context = context_system::instance();
    $coursename = format_string($SITE->fullname, true, array('context' => $context));
} else {
    $course = get_course($id);
    require_login($course);
    $context = context_course::instance($course->id);
    $coursename = format_string($course->fullname, true, array('context' => $context));
}
require_capability('report/emaillog:view', $context);

$PAGE->set_url($url);
$PAGE->set_pagelayout('report');

// Create table.
$emaillog = new report_emaillog_renderable($course, $user, $showcourses, $showusers,
        true, false, $url, $date, $page, $perpage, 'timestamp DESC');

$output = $PAGE->get_renderer('report_emaillog');
$emaillog->setup_table();

echo $output->header();
echo $output->render($emaillog);
echo $output->footer();