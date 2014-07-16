<?php
/**
 * UCLA Site indicator: Orphan site report
 * 
 * @package     tool
 * @subpackage  uclasiteindicator
 * @copyright   UC Regents
 */

require_once(dirname(__FILE__) . '/../../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$thisdir = '/' . $CFG->admin . '/tool/uclasiteindicator/';
require_once($CFG->dirroot . $thisdir . 'lib.php');
require_once($CFG->dirroot . $thisdir . 'siteindicator_form.php');

$baseurl = $CFG->wwwroot . '/' . $CFG->admin . '/tool/uclasiteindicator';

require_login();

$syscontext = context_system::instance();
require_capability('tool/uclasiteindicator:view', $syscontext);

// Initialize $PAGE
$PAGE->set_url($thisdir . 'index.php');
$PAGE->set_context($syscontext);
$PAGE->set_heading(get_string('pluginname', 'tool_uclasiteindicator'));
$PAGE->set_pagetype('admin-*');
$PAGE->set_pagelayout('admin');

// Prepare and load Moodle Admin interface
admin_externalpage_setup('uclasiteindicator');

// prepare table sorting functionality
$tableid = setup_js_tablesorter('uclasiteindicator_orphan_report');

// Render page
echo $OUTPUT->header();

// Heading
echo $OUTPUT->heading(get_string('orphans', 'tool_uclasiteindicator'), 2, 'headingblock');
echo html_writer::link($baseurl . '/index.php', get_string('back', 'tool_uclasiteindicator'));

$orphans = siteindicator_manager::get_orphans();

if (empty($orphans)) {
    echo html_writer::tag('p', get_string('noorphans', 'tool_uclasiteindicator'));
} else {
    $table = new html_table();
    $table->id = $tableid;    
    $table->attributes['class'] = 'generaltable';
    $table->align = array('left', 'left');
    $table->head = array(get_string('shortname') . ' (' . count($orphans) . ')', 
        get_string('category'), get_string('fullname'));

    $category_cache = array();
    
    foreach($orphans as $orphan) {
        $row = array();
        $row[] = html_writer::link(new moodle_url($CFG->wwwroot . 
                '/course/view.php', array('id' => $orphan->id)), 
                $orphan->shortname, array('target' => '_blank'));

        // print category
        if (empty($category_cache[$orphan->category])) {
            $category_cache[$orphan->category] = 
                    siteindicator_manager::get_categories_list($orphan->category);  
        }        
        $row[] = $category_cache[$orphan->category];        
        
        $row[] = $orphan->fullname;
        
        $table->data[] = $row;
    }

    echo html_writer::table($table);
}

echo $OUTPUT->footer();
