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
 * UCLA support tools plugin.
 *
 * @package    local_ucla_support_tools
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/local/ucla/lib.php');

// Needs user to be logged in.
require_login();

// Need system context.
$context = context_system::instance();

$thisdir = '/local/ucla_support_tools';
$thisfile = $thisdir . '/index.php';
// Initialize $PAGE
$PAGE->set_url($thisdir);
$PAGE->set_context($context);
$PAGE->set_heading(get_string('pluginname', 'local_ucla_support_tools'));
$PAGE->set_pagetype('admin-*');
$PAGE->set_pagelayout('report');
$PAGE->set_title(get_string('pluginname', 'local_ucla_support_tools'));

// Editing.
if (has_capability('local/ucla_support_tools:edit', $context)) {
    // Editing mode button.
    $supporttoolurl = new moodle_url($thisfile);
    $buttons = $OUTPUT->edit_button($supporttoolurl);
    $PAGE->set_button($buttons);
    
    if ($PAGE->user_is_editing()) {
        $PAGE->requires->yui_module('moodle-local_ucla_support_tools-categoryorganizer', 'M.local_ucla_support_tools.categoryorganizer.init', array());
        $PAGE->requires->yui_module('moodle-local_ucla_support_tools-toolorganizer', 'M.local_ucla_support_tools.toolorganizer.init', array());
    }
}

// Logging.
$PAGE->requires->yui_module('moodle-local_ucla_support_tools-usagelog', 'M.local_ucla_support_tools.usagelog.init', array());

// Favorites.
$PAGE->requires->yui_module('moodle-local_ucla_support_tools-favorite', 'M.local_ucla_support_tools.favorite.init', array());

// Tool and category filters.
$PAGE->requires->yui_module('moodle-local_ucla_support_tools-filter', 'M.local_ucla_support_tools.filter.tools', array(array(
    'input_node_id' =>  '#ucla-support-filter-input',
    'target_nodes' => '.ucla-support-tool-grid .ucla-support-tool',
    'filter_nodes' => '.ucla-support-tool-grid li',
    'category_nodes' => '#cat-grid .ucla-support-category'
)));

// Filtering.
$PAGE->requires->yui_module('moodle-local_ucla_support_tools-filter', 'M.local_ucla_support_tools.filter.categories', array());

// Column tiles.
$PAGE->requires->js('/theme/uclashared/javascript/salvattore.min.js');

// Prepare and load Moodle Admin interface
require_capability('local/ucla_support_tools:view', $context);

/* @var local_ucla_support_tools_renderer */
$render = $PAGE->get_renderer('local_ucla_support_tools');

echo $OUTPUT->header();

echo $OUTPUT->heading('UCLA support tools', 3);

echo html_writer::start_div('content');
    echo html_writer::start_div('row ucla-support-tool-accent');
        echo html_writer::start_div('col-md-12 ucla-support-tool-filter');
            echo $render->all_tools_filter();
        echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::start_div('row');
        echo html_writer::start_div('col-md-2');
            echo $render->category_labels();
        echo html_writer::end_div();
        echo html_writer::start_div('col-md-10 ucla-support-tool-category-list');
            echo $render->categories();
        echo html_writer::end_div();
    echo html_writer::end_div();

    echo html_writer::start_div('row ucla-support-tool-accent');
        echo $OUTPUT->heading('All available tools', 4);
    echo html_writer::end_div();
echo html_writer::end_div();

// Conditionally render a 'tool create' button
if ($render->is_editing()) {
    echo $render->tool_create_button();
}

echo $OUTPUT->box_start('clearfix ucla-support-tool-alltools ucla-support-tool-grid');
echo $render->tools();
echo $OUTPUT->box_end();

if ($render->is_editing()) {
    echo $render->tool_export_button();
    echo $render->tool_import_button();
}

echo $OUTPUT->footer();
