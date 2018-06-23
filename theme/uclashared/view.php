<?php
// This file is part of the UCLA shared course theme for Moodle - http://moodle.org/
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
 * Configuration for UCLA's Shared Server theme.
 *
 * For full information about creating Moodle themes, see:
 *  http://docs.moodle.org/en/Development:Themes_2.0
 * @package    theme_uclashared
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2017 UC Regents
 * CCLE-2493
 * UCLA footer links
 */

require_once(dirname(__FILE__) . '/../../config.php');

require_login();

$page = required_param('page', PARAM_ALPHA);

$PAGE->set_url('/theme/uclashared/view.php', array('page' => $page));
$PAGE->set_course($SITE);
$PAGE->set_pagelayout('standard');

if (!in_array($page, array('copyright', 'privacy', 'links'))) {
    $title = get_string('error', 'theme_uclashared');
    $page = 'error';
} else {
    $title = get_string($page, 'theme_uclashared');
}

$PAGE->set_title($title);
$PAGE->navbar->add($title);
$PAGE->set_heading($SITE->fullname);
echo $OUTPUT->header();

$includepath = dirname(__FILE__) . '/layout/links/' . basename($page) . '.php';
if ($page != 'error' && file_exists($includepath)) {
    include($includepath);
} else {
    print_error(get_string('page_notfound', 'theme_uclashared'));
}

echo $OUTPUT->footer();