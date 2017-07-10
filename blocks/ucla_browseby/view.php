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
 * Class file to handle Browse-By display.
 *
 * @package    block_ucla_browseby
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Page is viewable by non-logged in users.
// @codingStandardsIgnoreLine
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/blocks/ucla_browseby/block_ucla_browseby.php');
require_once($CFG->dirroot . '/blocks/ucla_browseby/'
    . 'browseby_handler_factory.class.php');
require_once($CFG->dirroot . '/blocks/ucla_browseby/renderer.php');
require_once($CFG->dirroot . '/blocks/ucla_search/block_ucla_search.php');

$type = required_param('type', PARAM_TEXT);
$term = optional_param('term', $CFG->currentterm, PARAM_TEXT);

$argvls = array('term' => $term, 'type' => $type);

$handlerfactory = new browseby_handler_factory();
$handler = $handlerfactory->get_type_handler($type);

if (!$handler) {
    print_error('illegaltype', 'block_ucla_browseby', '', $type);
}

$args = $handler->get_params();

// Iterate through all possible arguments in this display.
foreach ($args as $arg) {
    ${$arg} = optional_param($arg, null, PARAM_NOTAGS);
    if (${$arg} !== null) {
        $argvls[$arg] = ${$arg};
    }
}

$PAGE->set_url('/blocks/ucla_browseby/view.php', $argvls);

$PAGE->set_course($SITE);

$PAGE->set_pagetype('site-index');
$PAGE->set_pagelayout('coursecategory');

// This function will alter the $PAGE->navbar object.
list($title, $innercontents) = $handler->run_handler($argvls);
if (!$title) {
    print_error('illegaltype', 'block_ucla_browseby', '', $type);
}

$PAGE->set_title($title);
$PAGE->navbar->add($title);

// I have no idea when this is used...
$PAGE->set_heading($SITE->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading($title, 1, 'headingblock');

// CCLE-3141 - Prepare for post M2 deployment.
$cutoff = get_config('local_ucla', 'remotetermcutoff');
if (!$cutoff) {
    // CCLE-3526 - Backwards compatibility, previously hard-coded value.
    $cutoff = '12S';
}

if ($term == $cutoff) {
    echo $OUTPUT->notification(get_string('cutoff' . $cutoff, 'block_ucla_browseby'), 'notifywarning');
} else if (term_cmp_fn($term, $cutoff) == -1) {
    echo $OUTPUT->notification(get_string('before' . $cutoff, 'block_ucla_browseby'), 'notifywarning');
}

echo html_writer::tag('div', $innercontents, array('id' => 'browsebymain'));

echo $OUTPUT->footer();
