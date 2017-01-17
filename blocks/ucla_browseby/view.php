<?php

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/blocks/ucla_browseby/block_ucla_browseby.php');
require_once($CFG->dirroot . '/blocks/ucla_browseby/'
    . 'browseby_handler_factory.class.php');
require_once($CFG->dirroot . '/blocks/ucla_browseby/renderer.php');
require_once($CFG->dirroot . '/blocks/ucla_search/block_ucla_search.php');
        
$type = required_param('type', PARAM_TEXT);
$term = optional_param('term', $CFG->currentterm, PARAM_TEXT);

$cache = cache::make('block_ucla_browseby', 'browsebycache');

$argvls = array('term' => $term, 'type' => $type);

$handler_factory = new browseby_handler_factory();
$handler = $handler_factory->get_type_handler($type);

if (!$handler) {
    print_error('illegaltype', 'block_ucla_browseby', '', $type);
}

$args = $handler->get_params();

// Iterate through all possible arguments in this display
foreach ($args as $arg) {
    ${$arg} = optional_param($arg, null, PARAM_RAW);
    if (${$arg} !== null) {
        $argvls[$arg] = ${$arg};
    }
}

$PAGE->set_url('/blocks/ucla_browseby/view.php', $argvls);

$PAGE->set_course($SITE);

$PAGE->set_pagetype('site-index');
$PAGE->set_pagelayout('coursecategory');

// Check cache for title and contents.
$cachekey = str_replace(' ', '_', implode('_', $argvls));
$title = $cache->get('title_' . $cachekey);
$innercontents = $cache->get('contents_' . $cachekey);

// If not in cache, then query DB.
if (!$title || !$innercontents) {
    // This function will alter the $PAGE->navbar object.
    list($title, $innercontents) = $handler->run_handler($argvls);
    $data = array(
        'title_' . $cachekey => $title,
        'contents_' . $cachekey => $innercontents
    );
    // Cache the title and returned contents.
    $cache->set_many($data);
} else {
    // If contents have been cached, manually load the autosubmit module for the term selector.
    $selectid = preg_replace('/.*select id=\"(url_select\w*).*/','$1', $innercontents);
    $PAGE->requires->yui_module('moodle-core-formautosubmit',
        'M.core.init_formautosubmit',
        array(array('selectid' => $selectid, 'nothing' => ''))
    );
}
if (!$title) {
    print_error('illegaltype', 'block_ucla_browseby', '', $type);
}

$PAGE->set_title($title);
$PAGE->navbar->add($title);

// I have no idea when this is used...
$PAGE->set_heading($SITE->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading($title, 1, 'headingblock');

// CCLE-3141 - Prepare for post M2 deployment
$cutoff = get_config('local_ucla', 'remotetermcutoff');
if (!$cutoff) {
    // CCLE-3526: Backwards compatibility, previously hard-coded value
    $cutoff = '12S';
}

if ($term == $cutoff) {
    echo $OUTPUT->notification(get_string('cutoff' . $cutoff, 'block_ucla_browseby'), 'notifywarning');
} else if (term_cmp_fn($term, $cutoff) == -1) {
    echo $OUTPUT->notification(get_string('before' . $cutoff, 'block_ucla_browseby'), 'notifywarning');    
}

echo html_writer::tag('div', $innercontents, array('id' => 'browsebymain'));

echo $OUTPUT->footer();
