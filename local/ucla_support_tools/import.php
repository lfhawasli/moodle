<?php
// This file is part of the UCLA support tools plugin for Moodle - http://moodle.org/
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
 * Exports the UCLA support tools listing and categories.
 *
 * @package    local_ucla_support_tools
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__).'/import_form.php');

// CCLE-5955 - Work around to make the file picker load.
$USER->editing = 0;

// Needs user to be logged in.
require_login();
$context = context_system::instance();
$PAGE->set_url('/local/ucla_support_tools/import.php');
$PAGE->set_context($context);
$PAGE->set_heading(get_string('importtitle', 'local_ucla_support_tools'));
$PAGE->set_pagetype('admin-*');
$PAGE->set_pagelayout('report');
$PAGE->set_title(get_string('importtitle', 'local_ucla_support_tools'));

require_capability('local/ucla_support_tools:edit', $context);

// Print the page header.
echo $OUTPUT->header();

// See if file was uploaded and parse contents.
$mform = new import_form();
if ($mform->is_submitted()) {
    $importdata = $mform->get_file_content('importfile');
    if (local_ucla_support_tools_migration::import($importdata)) {
        echo $OUTPUT->notification(get_string('importsuccess', 'local_ucla_support_tools'),
            'notifysuccess');
    } else {
        echo $OUTPUT->notification(get_string('importerror', 'local_ucla_support_tools'),
            'notifyproblem');
    }
} else {
    // Display file picker to allow file upload.
    echo $OUTPUT->notification(get_string('importwarning', 'local_ucla_support_tools'), 'notifywarning');
    $mform->display();
}

echo $OUTPUT->single_button('/local/ucla_support_tools/index.php',
    get_string('backhome', 'local_ucla_support_tools'));

echo $OUTPUT->footer();