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
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot.'/report/emaillog/lib.php');
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->dirroot.'/lib/tablelib.php');

$id             = optional_param('id', 0, PARAM_INT);// Course ID.
$sender         = optional_param('sender', 0, PARAM_INT); // Sender to display.
$recipient      = optional_param('recipient', 0, PARAM_INT); // Recipient to display.
$date           = optional_param('date', 0, PARAM_INT); // Date to display.
$forum          = optional_param('forum', 0, PARAM_INT); // Forum to display.
$discussion     = optional_param('discussion', 0, PARAM_INT); // Discussion to display.
$post           = optional_param('post', 0, PARAM_INT); // Post to display.
$page           = optional_param('page', '0', PARAM_INT);     // Which page to show.
$perpage        = optional_param('perpage', '100', PARAM_INT); // How many per page.
$showcourses    = optional_param('showcourses', false, PARAM_BOOL); // Whether to show courses if we're over our limit.
$showsenders    = optional_param('showsenders', false, PARAM_BOOL); // Whether to show senders if we're over our limit.
$showrecipients = optional_param('showrecipients', false, PARAM_BOOL); // Whether to show recipients if we're over our limit.
$chooselog      = optional_param('chooselog', false, PARAM_BOOL);

$params = array();

if (!empty($id)) {
    $params['id'] = $id;
} else {
    $site = get_site();
    $id = $site->id;
}
if ($sender !== 0) {
    $params['sender'] = $sender;
}
if ($recipient !== 0) {
    $params['recipient'] = $recipient;
}

if ($date !== 0) {
    $params['date'] = $date;
}
if ($forum !== 0) {
    $params['forum'] = $forum;
}
if ($discussion !== 0) {
    $params['discussion'] = $discussion;
}
if ($post !== 0) {
    $params['post'] = $post;
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
if ($showsenders) {
    $params['showsenders'] = $showsenders;
}
if ($showrecipients) {
    $params['showrecipients'] = $showrecipients;
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

if (!get_config('report_emaillog', 'enable')) {
    print_error('disabled', 'report_emaillog');
}

$PAGE->set_url('/report/emaillog/index.php', array('id' => $id));
$PAGE->set_pagelayout('report');

if (!empty($page)) {
    $strlogs = get_string('pluginname', 'report_emaillog'). ": ". get_string('page', 'report_emaillog', $page + 1);
} else {
    $strlogs = get_string('pluginname', 'report_emaillog');
}

$PAGE->set_title($course->shortname .': '. $strlogs);
$PAGE->set_heading($course->fullname);

// Create table.
$emaillog = new report_emaillog_renderable($course, $sender, $recipient, $forum, $discussion, $post,
        $showcourses, $showsenders, $showrecipients, $chooselog, true,
        $url, $date, $page, $perpage, 'timestamp DESC');

$output = $PAGE->get_renderer('report_emaillog');

echo $output->header();

if (!empty($chooselog)) {
    // Delay creation of table, till called by user with filter.
    $emaillog->setup_table();
} else {
    echo $output->heading(get_string('chooseforumlogs', 'report_emaillog', get_config('report_emaillog', 'daysexpire')) .':');
}

echo $output->render($emaillog);
echo $output->footer();
