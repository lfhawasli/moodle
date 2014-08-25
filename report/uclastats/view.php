<?php
/**
 * Displays specific report for UCLA stats console.
 *
 * @package    report
 * @subpackage uclastats
 * @copyright  UC Regents
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot.'/report/uclastats/report_forms.php');
require_once($CFG->dirroot.'/report/uclastats/locallib.php');
require_once($CFG->dirroot.'/local/ucla/lib.php');

require_login();
$context = context_course::instance(SITEID);
$PAGE->set_context($context);
require_capability('report/uclastats:view', $context);

$reports = get_all_reports();
$report     = required_param('report', PARAM_ALPHAEXT);
$resultid   = optional_param('resultid', null, PARAM_INT);
$action     = optional_param('action', null, PARAM_ALPHAEXT);
$confirmdel = optional_param('confirmdel', 0, PARAM_BOOL);

$action_confirmed = (($action != UCLA_STATS_ACTION_DELETE) ||
                    ($action == UCLA_STATS_ACTION_DELETE) && $confirmdel);

// make sure user is accessing a valid report
if (!array_key_exists($report, $reports)) {
    print_error('invalidreport', 'report_uclastats');
}

$PAGE->set_url(new moodle_url('/report/uclastats/view.php',
        array('report' => $report)));

admin_externalpage_setup('reportuclastats');

// create report object and render it
require_once($CFG->dirroot . '/report/uclastats/reports/' . $report . '.php');
$report_object = new $report($USER);

// handle if user is trying to export report
$export_type = optional_param('export', false, PARAM_ALPHA);
if (!empty($export_type) && !empty($resultid)) {
    // NOTE: all exports will die after they send their file
    if ($export_type == 'xls') {
        $report_object->export_result_xls($resultid);
    }
}

// handle if user is trying to run a report
$report_form = $report_object->get_run_form();
$params = $report_form->get_data();
if (!empty($params) && confirm_sesskey()) {
    // fail if user cannot run queries
    require_capability('report/uclastats:query', $context);

    // data from form is a object and has 'submitbutton'
    $params = (array) $params;
    unset($params['submitbutton']);

    // run query and then redirect to result page
    $resultid = $report_object->run($params);
    redirect(new moodle_url('/report/uclastats/view.php',
            array('report' => $report, 'resultid' => $resultid)));
}

//handle deletion/locking of cached results
if (!empty($action) && !empty($resultid)) {    
    //fail if user cannot modify cached results
    require_capability('report/uclastats:manage', $context);    
    $undefined_action = false;    
    switch ($action) {
        case UCLA_STATS_ACTION_DELETE:
            if($action_confirmed) {                
                if(uclastats_result::delete($resultid)) {                    
                    $success_msg = get_string('successful_delete', 'report_uclastats');                                    
                } else {                    
                    //indicate the deletion failed
                    //either because it was locked 
                    print_error('error_delete_locked', 'report_uclastats',
                                 new moodle_url('/report/uclastats/view.php',
                                 array('report' => $report , 'resultid' => $resultid)));
                }                
            } 
            break;
        case UCLA_STATS_ACTION_LOCK: 
            uclastats_result::lock($resultid);
            $success_msg = get_string('successful_lock', 'report_uclastats');
            break;
        case UCLA_STATS_ACTION_UNLOCK:
            uclastats_result::unlock($resultid);
            $success_msg = get_string('successful_unlock', 'report_uclastats');
            break;
        default:
            $undefined_action = true;
    }
    
    if ($undefined_action) {        
        print_error('undefined_action','report_uclastats',
                    new moodle_url('/report/uclastats/view.php',
                    array('report' => $report)),$action);
        
    } else if ($action_confirmed) {
        switch ($action) {
            case UCLA_STATS_ACTION_DELETE:
                $event = \report_uclastats\event\uclastats_deleted::create(array(
                    'context' => $context,
                    'other' => array('report' => $report, 'resultid' => $resultid)
                ));
                $event->trigger();
                break;
            case UCLA_STATS_ACTION_LOCK: 
                $event = \report_uclastats\event\uclastats_locked::create(array(
                    'context' => $context,
                    'other' => array('report' => $report, 'resultid' => $resultid)
                ));
                $event->trigger();
                break;
            case UCLA_STATS_ACTION_UNLOCK:
                $event = \report_uclastats\event\uclastats_unlocked::create(array(
                    'context' => $context,
                    'other' => array('report' => $report, 'resultid' => $resultid)
                ));
                $event->trigger();
                break;
        }
        
        $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : "";
        //escape / in base url pattern for regex
        $base_pattern = '/' . preg_replace('/\//','\/',
        new moodle_url('/report/uclastats/')) . '/';
        
        //When (un)locking a result, the cached result is loaded. The cached result 
        //shouldn't be loaded, just locked and keep whatever 
        //what was viewed still on the screen.
        //this is done by redirecting back to the referer
       
        //also check that referer comes within jursidictions of the plugin
        //otherwise redirect to the specified redirect params
        if(($action == UCLA_STATS_ACTION_LOCK || $action == UCLA_STATS_ACTION_UNLOCK)
            && !empty($referer) && preg_match($base_pattern,$referer)) {
            $redirect_url = $referer;
        } else {
            $redirect_url = new moodle_url('/report/uclastats/view.php',
                    array('report' => $report));
        }
        flash_redirect($redirect_url, $success_msg);
    }
}

// if user is viewing a result table, make it sortable
if (!empty($resultid)) {
    setup_js_tablesorter('uclastats-results-table');
}

echo $OUTPUT->header();
$output = $PAGE->get_renderer('report_uclastats');
echo $output->render_header($report);

if ($action_confirmed) {   
    //print out flash message if a result has been deleted/(un)locked
    flash_display();
    echo $output->render_report_list($reports, $report);
    echo $output->render_report($report_object, $resultid);    
} else {    
    //render confirm button for deletion
    echo   $OUTPUT->confirm( get_string('confirm_delete', 'report_uclastats'),
                    new moodle_url('/report/uclastats/view.php',
                                   array('report' => $report,
                                         'action' => $action,
                                         'resultid' => $resultid,
                                         'confirmdel' => 1)),
                    new moodle_url('/report/uclastats/view.php',
                                   array('report' => $report,
                                         'resultid' => $resultid)));
}

echo $OUTPUT->footer();
