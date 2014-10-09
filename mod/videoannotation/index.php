<?php // $Id: index.php,v 1.7.2.2 2009/03/31 13:07:21 mudrd8mz Exp $

/**
 * This page lists all the instances of videoannotation in a particular course
 *
 * @author  Your Name <your@email.address>
 * @version $Id: index.php,v 1.7.2.2 2009/03/31 13:07:21 mudrd8mz Exp $
 * @package mod/videoannotation
 */

/// Replace videoannotation with the name of your module and remove this line

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

$id = required_param('id', PARAM_INT);   // course
global $DB;
if (! $course = $DB->get_record('course', array('id'=>$id))) {
    error('Course ID is incorrect');
}

require_course_login($course);
$PAGE->set_url('/mod/videoannotation/index.php', array('id'=>$course->id));
add_to_log($course->id, 'videoannotation', 'view all', "index.php?id=$course->id", '');


/// Get all required stringsvideoannotation

$strvideoannotations = get_string('modulenameplural', 'videoannotation');
$strvideoannotation  = get_string('modulename', 'videoannotation');


/// Print the header

$navlinks = array();
$navlinks[] = array('name' => $strvideoannotations, 'link' => '', 'type' => 'activity');
$navigation = build_navigation($navlinks);

print_header_simple($strvideoannotations, '', $navigation, '', '', true, '', navmenu($course));

/// Get all the appropriate data

if (! $videoannotations = get_all_instances_in_course('videoannotation', $course)) {
    notice('There are no instances of videoannotation', "../../course/view.php?id=$course->id");
    die;
}

$can_view_some_submissions = false;
$can_view_some_reports = false;
foreach ($videoannotations as $key => $videoannotation) {
    $module_context = get_context_instance(CONTEXT_MODULE, $videoannotation->coursemodule);
    $has_grade_capability = has_capability('mod/videoannotation:grade', $module_context);
    $has_submit_capability = has_capability('mod/videoannotation:submit', $module_context);
    
    $can_view_submission[$key] = $has_grade_capability;
    $can_view_some_submissions |= $has_grade_capability;
    
    $can_view_report[$key] = $has_grade_capability || $has_submit_capability;
    $can_view_some_reports |= $has_grade_capability || $has_submit_capability;
}

/// Print the list of instances (your module will probably extend this)

$timenow  = time();
$strname  = get_string('name');
$strweek  = get_string('week');
$strtopic = get_string('topic');

if ($course->format == 'weeks') {
    $table->head = array($strweek, $strname);
    $table->align = array('center', 'left');
} else if ($course->format == 'topics') {
    $table->head  = array($strtopic, $strname);
    $table->align = array('center', 'left');
} else {
    $table->head  = array($strname);
    $table->align = array('left');
}
if ($can_view_some_submissions) {
    $table->head[] = get_string('viewsubmissions', 'videoannotation');
    $table->align[] = 'left';
}

if ($can_view_some_reports) {
    $table->head[] = get_string('viewreport', 'videoannotation');
    $table->align[] = 'left';
}

foreach ($videoannotations as $key => $videoannotation) {
    if (!$videoannotation->visible) {
        //Show dimmed if the mod is hidden
        $link = "<a class=\"dimmed\" href=\"view.php?id=$videoannotation->coursemodule\">$videoannotation->name</a>";
    } else {
        //Show normal if the mod is visible
        $link = "<a href=\"view.php?id=$videoannotation->coursemodule\">$videoannotation->name</a>";
    }
    
    if ($course->format == 'weeks' or $course->format == 'topics') {
        $data = array($videoannotation->section, $link);
    } else {
        $data = array($link);
    }
    
    //
    
    if ($can_view_submission[$key]) {
        $cm = videoannotation_get_course_module_by_video_annotation($videoannotation->id);
        switch ($videoannotation->groupmode) {
            case NOGROUPS:
                $groupid = null;
                break;
            default:
                $groupid = 'all';
        }
        list($total_submission_count, $ungraded_submission_count) = videoannotation_get_submission_count($videoannotation->id, $groupid);
            
        $view_submissions_link = "<a href='{$CFG->wwwroot}/mod/videoannotation/submissions.php?id={$videoannotation->coursemodule}'>"
        . $total_submission_count . ' ' . get_string('total', 'videoannotation') . ', '
        . $ungraded_submission_count . ' ' . get_string('ungraded', 'videoannotation') . '</a>';

        $data[] = $view_submissions_link;
    } else if ($can_view_some_submissions) {
        $data[] = '';
    }
    
    //
    
    if ($can_view_report[$key]) {
        $view_report_link = "<a href='{$CFG->wwwroot}/mod/videoannotation/report.php?id={$videoannotation->coursemodule}'>"
        . get_string('viewreport', 'videoannotation') . '</a>';
        
        $data[] = $view_report_link;
    } else if ($can_view_some_reports) {
        $data[] = '';
    }
    
    //
    
    $table->data[] = $data;

}

echo $OUTPUT->heading($strvideoannotations);
print_table($table);

/// Finish the page

echo $OUTPUT->footer($course);

?>
