<?php
/**
 *  UCLA Support Console
 **/

require_once(dirname(__FILE__) . "/../../../config.php");
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/local/ucla/lib.php');
$admintooldir = '/' . $CFG->admin . '/tool/';
require_once($CFG->dirroot . $admintooldir . 'uclasupportconsole/lib.php');
require_once($CFG->dirroot . $admintooldir . 'uclacoursecreator/uclacoursecreator.class.php');
require_once($CFG->dirroot . '/admin/tool/ucladatasourcesync/lib.php');

$PAGE->requires->js('/admin/tool/uclasupportconsole/table.js', true);

// Force debugging errors 
error_reporting(E_ALL); 
ini_set( 'display_errors','1');

$consolecommand = optional_param('console', null, PARAM_ALPHAEXT);
$exportoption = optional_param('export', null, PARAM_ALPHA);
$displayforms = empty($consolecommand);
$alldata = data_submitted();

// Set $inputs['totalcount'] to $totalcount when calling
// supportconsole_render_section_shortcut() for paginated support console tools.
$totalcount = optional_param('count', null, PARAM_INT);

admin_externalpage_setup('reportsupportconsole');

require_login();
require_capability('tool/uclasupportconsole:view', context_system::instance());

// Set up a moodle page.
$PAGE->set_url('/' . $CFG->admin . '/tool/uclasupportconsole/');
$PAGE->set_context(context_system::instance());
$PAGE->set_heading(get_string('pluginname', 'tool_uclasupportconsole'));
$PAGE->set_pagetype('admin-*');
$PAGE->set_pagelayout('admin');

// The primary goal is to keep as much as possible in one script
$consoles = new tool_supportconsole_manager();

////////////////////////////////////////////////////////////////////
// CHECKING LOGS 
////////////////////////////////////////////////////////////////////
$title = "syslogs";
$syslogs_types = array('log_apache_error', 
                       'log_apache_access',
                       'log_apache_ssl_access',
                       'log_apache_ssl_error',
                       'log_apache_ssl_request',
                       'log_shibboleth_shibd',
                       'log_shibboleth_trans',
                       'log_moodle_cron',
                       'log_course_creator',
                       'log_prepop');

$sectionhtml = '';
if ($displayforms) {
    $logselects = array();
    
    // build select list for logs
    foreach ($syslogs_types as $syslogs_type) {
        $attarr = array('value' => $syslogs_type);

        // see if logtype is set and accessible
        $log_location = get_config('tool_uclasupportconsole', $syslogs_type);
        if (empty($log_location) || !file_exists($log_location)) {
            $attarr['disabled'] = true;
        }      
        
        $logselects[$syslogs_type] = html_writer::tag('option', 
            get_string($syslogs_type, 'tool_uclasupportconsole'), $attarr);
    } 
    
    // add "Choose log..."
    $logselects = array('none' => html_writer::tag('option', 
            get_string('syslogs_choose', 'tool_uclasupportconsole'))) + $logselects;    

    $logselect = html_writer::label(get_string('syslogs_select', 'tool_uclasupportconsole'), $title) . 
            html_writer::tag('select', implode('', $logselects),
            array('name' => 'log', 'id' => $title));

    $sectionhtml = supportconsole_simple_form($title, $logselect) . 
            html_writer::tag('p', get_string('syslogs_info', 'tool_uclasupportconsole'));
} else if ($consolecommand == $title) {
    ob_start();
    
    $log_file = required_param('log', PARAM_ALPHAEXT);
    $log_file = basename($log_file);

    // invalid log type
    if (!in_array($log_file, $syslogs_types)) {
        echo "Invalid logfile name. $log_file";
        exit;        
    }
    
    // else try to display it    
    $log_location = get_config('tool_uclasupportconsole', $log_file);    
    
    // if viewing log_course_creator/log_prepop, then get latest log file
    if ($log_file == 'log_course_creator' || $log_file == 'log_prepop') {
        // get last log file
        $last_pre_pop = exec(sprintf('ls -t1 %s | head -n1', $log_location));
        $log_location = $log_location . $last_pre_pop;
    }
    
    echo $log_location . "\n";
    $tail_command = "/usr/bin/tail -1000 ";
    system($tail_command . ' ' . $log_location);

    $sectionhtml = nl2br(htmlspecialchars(ob_get_clean()));
} 
$consoles->push_console_html('logs', $title, $sectionhtml);

////////////////////////////////////////////////////////////////////
$title = "prepoprun";
$sectionhtml = '';
if ($displayforms) {
    $sectionhtml = supportconsole_simple_form($title, 
        html_writer::label('Moodle course.id', 'prepop-courseid')
            . html_writer::empty_tag('input', array(
                    'type' => 'text',
                    'length' => 10,
                    'id' => 'prepop-courseid',
                    'name' => 'courseid'
                )));
} else if ($consolecommand == "$title") { 
    $sectionhtml = '';
    $courseid = required_param('courseid', PARAM_INT);
    $dbenrol = enrol_get_plugin('database');
    // Sadly, this cannot be output buffered...so
    echo html_writer::tag('h1', get_string('prepoprun', 'tool_uclasupportconsole'));
    echo "<pre>";
    $trace = new text_progress_trace();
    $dbenrol->sync_enrolments($trace, $courseid);
    echo "</pre>";

    $consoles->no_finish = true;
}

$consoles->push_console_html('users', $title, $sectionhtml);
////////////////////////////////////////////////////////////////////
$title = 'coursecreatorlogs';
$sectionhtml = '';
if ($displayforms) {
    $sectionhtml = supportconsole_simple_form($title);
} else if ($consolecommand == $title) {
    
}
////////////////////////////////////////////////////////////////////
$title = 'moodlelog';
$sectionhtml = '';

if ($displayforms) { 
    $moodlelog_show_filter = optional_param('moodlelog_show_filter', 0, PARAM_BOOL);

    // Get target/action pairs from cached log.
    $tapairs = get_config('tool_uclasupportconsole', 'moduleactionpairs');
    $tapairs = json_decode($tapairs);

    $checkboxes = array();
    $lasttarget = '';

    if (!empty($tapairs)) {
        foreach($tapairs as $pair) {
            if (!isset($pair->target) || !isset($pair->action)) {
                continue;
            }

            $target = $pair->target;
            $action = $pair->action;

            // If this is the first target in our list of its kind, we must output
            // it as a heading above the actions
            if ($target != $lasttarget) {
                $lasttarget = $target;
                $checkboxes[] = html_writer::tag('h4', $target);
            }

            // Create the checkbox array for holding action types. The actions
            // are represented as "target_action" (for example: user_loggedin).
            $checkboxes[] = html_writer::tag('li',
                    html_writer::checkbox('actiontypes[]', $target . '_' . $action,
                            false, $action));
        }
    }

    // show/hide action type filter, mainly for users with no js enabled
    $form_content = '';
    $action_types_container_params = array('id' => 'log-action-types-container');
    if (!$moodlelog_show_filter) {
        // display link to show filter
        $form_content .= html_writer::start_tag('div');        
        $form_content .= html_writer::link(
                new moodle_url('/admin/tool/uclasupportconsole/index.php', 
                        array('moodlelog_show_filter' => 1), 'moodlelog'), 
                get_string('moodlelog_filter', 'tool_uclasupportconsole'), 
                array('id' => 'show-log-types-filter', 
                    // TODO: there has to be a better way to show/hide using YUI...
                    'onclick' => "YAHOO.util.Dom.setStyle('log-action-types-container', 'display', '');YAHOO.util.Dom.setStyle('show-log-types-filter', 'display', 'none');return false;"));        
        $form_content .= html_writer::end_tag('div');

        // hide action types
        $action_types_container_params['style'] = 'display:none';
    }

    // Display the filter checkbox form.
    $form_content .= html_writer::start_tag('div', $action_types_container_params);
    $form_content .= html_writer::label(get_string('moodlelog_select', 'tool_uclasupportconsole'), 
                'log-action-types') . 
                html_writer::tag('ul', implode('', $checkboxes), 
                array('id' => 'log-action-types'));
    $form_content .= html_writer::end_tag('div');
    
    $sectionhtml = supportconsole_simple_form($title, $form_content);
} else if ($consolecommand == "$title") { 

    // Initialize empty containers for the database query ($logquery), 
    // the array to contain the names of filters applied ($filterstring),
    // the params that must be passed to generate the export options 
    // ($urlparams), and the database query parameters ($whereparams).
    $logquery = '';
    $whereparams = array();
    $filterstring = array();
    $urlparams = array();

    // Get the target, action pairs (represented as target_action).
    // Note that a target can itself contain underscores.
    $targetactions = optional_param_array('actiontypes', array(), PARAM_TEXT);

    // If there are no actions selected, either the form was hidden, or no
    // checkboxes were selected. In both cases, this results in the log not
    // filtering out any entries (the last 100 entries of any type are shown).
    if (empty ($targetactions))
    {
        $logquery = "
            SELECT a.id,
                   from_unixtime(a.timecreated) AS time,
                   b.firstname,
                   b.lastname,
                   ip,
                   c.shortname,
                   c.id AS courseid,
                   target,
                   action
              FROM {logstore_standard_log} a
         LEFT JOIN {user} b ON (a.userid = b.id)
         LEFT JOIN {course} c ON (a.courseid = c.id)
          ORDER BY a.id DESC LIMIT 100
        ";
    }
    else
    {
        // Construct the necessary conditional statements and parameters for
        // the sql WHERE clause in order to implement the event filter.
        // Also add all targetaction pairs to the URL params.
        $wherequery = '';
        $iterator = 0;
        foreach ($targetactions as $ta) {
            $targetactionset = explode("_", $ta);
            // The string after the last underscore of target_action is
            // the action. All but the last string is the target name.
            $action = array_pop($targetactionset);
            $target = implode('_', $targetactionset);
            $filterstring[] = $target . ' ' . $action;

            // Create a query that checks for the target and action together
            // and append it via OR to the overall $wherequery.
            if (!empty($wherequery)) {
                $wherequery .= ' OR ';
            }
            list($actsql, $actparam) = $DB->get_in_or_equal($action);
            list($tarsql, $tarparam) = $DB->get_in_or_equal($target);
            $wherequery .= 'action ' . $actsql . ' AND target ' . $tarsql;
            // Merge parameters into one array for use by the database query.
            $whereparams = array_merge($whereparams, $actparam, $tarparam);

            // A hacky way to add the targetactions array to the url params.
            // Simply passing an array variable is not accepted by moodle_url
            // params.
            $urlparams['actiontypes[' . $iterator . ']'] = $ta;
            $iterator++;
        }

        $logquery = "
            SELECT a.id, 
                   from_unixtime(a.timecreated) AS time,
                   b.firstname,
                   b.lastname,
                   ip,
                   c.shortname,
                   c.id AS courseid,
                   target,
                   action
              FROM {logstore_standard_log} a
         LEFT JOIN {user} b ON (a.userid = b.id)
         LEFT JOIN {course} c ON (a.courseid = c.id)
             WHERE $wherequery
          ORDER BY a.id DESC LIMIT 100
        ";
    }

    $results = $DB->get_records_sql($logquery, $whereparams);

    foreach ($results as $k => $result) {
        if (!empty($result->courseid) && !empty($result->shortname)) {
            $result->shortname = html_writer::link(
                    new moodle_url('/course/view.php', 
                        array('id' => $result->courseid)),
                    $result->shortname
                );
            $results[$k] = $result;
        }
    }

    $headertext = '';
    if (!empty($filterstring)) {
        $headertext = get_string('filterfor', 'tool_uclasupportconsole') . 
                implode(', ', $filterstring);
    }
    $sectionhtml = supportconsole_render_section_shortcut($title, $results,
            $urlparams, $headertext);
}
$consoles->push_console_html('logs', $title, $sectionhtml);

////////////////////////////////////////////////////////////////////
$title='moodlelogins';
$sectionhtml = '';
if ($displayforms) { 
    $sectionhtml = supportconsole_simple_form($title);
} else if ($consolecommand == "$title") { 

    $sql = "SELECT a.id,
                   FROM_UNIXTIME(a.timecreated) as logintime,
                   b.firstname,
                   b.lastname,
                   a.ip,
                   a.userid
              FROM {logstore_standard_log} a
         LEFT JOIN {user} b ON (a.userid=b.id)
             WHERE a.timecreated >= (UNIX_TIMESTAMP()-86400) AND
                   a.action='loggedin' AND
                   a.userid!=?
          ORDER BY a.id desc";
    $rs = $DB->get_recordset_sql($sql, array($CFG->siteguest));

    $result = array();
    foreach($rs as $k => $res) {
        $res->user = html_writer::link(new moodle_url('/user/view.php',
                array('id' => $res->userid)), "$res->firstname $res->lastname",
                array('target' => '_blank'));

        // Unset unneeded data for display.
        unset($res->id);
        unset($res->firstname);
        unset($res->lastname);
        unset($res->userid);

        $result[$k] = $res;
    }
    $rs->close();
    
    $sectionhtml = supportconsole_render_section_shortcut($title, $result);
}
$consoles->push_console_html('logs', $title, $sectionhtml);

////////////////////////////////////////////////////////////////////
// TODO Combine this one with the next one
$title = 'moodlelogbyday';
$sectionhtml = '';
if ($displayforms) { 
    $choiceshtml = html_writer::label('Days', 'days')
        . html_writer::empty_tag('input', array(
                'id' => 'days',
                'type' => 'text',
                'name' => 'days',
                'value' => 7,
                'size' => 3
            ))
        . html_writer::label('Show login entries only', 'radio-login')
        . html_writer::empty_tag('input', array(
                'id' => 'radio-login',
                'type' => 'radio',
                'name' => 'radio',
                'value' => 'login',
                'checked' => true
            ))
        . html_writer::label('Show all entries', 'radio-entries')
        . html_writer::empty_tag('input', array(
                'id' => 'radio-entries',
                'type' => 'radio',
                'name' => 'radio',
                'value' => 'entries'
            ));

    $sectionhtml .= supportconsole_simple_form($title, $choiceshtml);
// save for later when figure out how sql query should look    <input type="radio" name="radio" value="unique" CHECKED>Unique Logins
} else if ($consolecommand == "$title") {
    $filter = required_param('radio', PARAM_TEXT);
    $days = required_param('days', PARAM_INT);

    if ($days < 1 or $days > 999) {
        print_error("Invalid number of days.");
        exit;
    }    

    if ($filter != "login" and $filter != "entries") {
        echo "Invalid search options.<br>\n";
        exit;
    }    

    // Do not return results for guest logins
    $params = array();
    $whereclause = "";
    if ($filter == "login") {
        $whereclause = "AND action = 'loggedin' AND userid <> ?";
        $what = 'Logins';
        $params = array($CFG->siteguest);
    } else {
        $what = 'Log Entries';
    }

    $sectionhtml = "Count of Moodle $what from the Last $days Days";
    $rs = $DB->get_recordset_sql("
        SELECT FROM_UNIXTIME(timecreated,'%Y-%m-%d') AS date,
               COUNT(*) AS count
          FROM {logstore_standard_log}
         WHERE FROM_UNIXTIME(timecreated, '%Y-%m-%d') >= DATE_SUB(CURDATE(), INTERVAL $days DAY)
               $whereclause
      GROUP BY date", $params);

    $result = array();
    if ($rs->valid()) {
        foreach($rs as $record) {
            $result[] = $record;
        }    
    }
    $rs->close();

    $sectionhtml = supportconsole_render_section_shortcut($title, $result,
            array('radio' => $filter, 'days' => $days), $sectionhtml);
}
$consoles->push_console_html('logs', $title, $sectionhtml);

////////////////////////////////////////////////////////////////////
// TODO combine with the next one
$title = "moodlelogbydaycourse";
$sectionhtml = '';
if ($displayforms) {
    $sectionhtml .= supportconsole_simple_form($title);
} else if ($consolecommand == "$title") {
     $result = $DB->get_records_sql("
        SELECT
            a.id,
            FROM_UNIXTIME(a.timecreated,'%Y-%m-%d') AS date,
            c.shortname AS course,
            COUNT(*) AS count
        FROM {logstore_standard_log} a
        LEFT JOIN {course} c ON a.courseid = c.id
        WHERE FROM_UNIXTIME(a.timecreated) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND
                c.id!=:siteid
        GROUP BY date, course
        ORDER BY count DESC
        LIMIT 100
    ", array('siteid' => SITEID));

    $sectionhtml .= supportconsole_render_section_shortcut($title, $result);
}
$consoles->push_console_html('logs', $title, $sectionhtml);

////////////////////////////////////////////////////////////////////
$title = "moodlelogbydaycourseuser";
$sectionhtml = '';

if ($displayforms) {
    $sectionhtml = supportconsole_simple_form($title);
} else if ($consolecommand == "$title") {
    $result = $DB->get_records_sql("
        SELECT
            a.id,
            FROM_UNIXTIME(a.timecreated,'%Y-%m-%d') AS day,
            c.shortname AS course,
            b.firstname,
            b.lastname,
            COUNT(*) AS count
        FROM {logstore_standard_log} a
        LEFT JOIN {user} b ON a.userid = b.id
        LEFT JOIN {course} c ON a.courseid = c.id
        WHERE FROM_UNIXTIME(a.timecreated) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND
            c.id!=:siteid
        GROUP BY day, course, a.userid
        ORDER BY count DESC
        LIMIT 100
    ", array('siteid' => SITEID));

    $sectionhtml = supportconsole_render_section_shortcut($title, $result);
}
$consoles->push_console_html('logs', $title, $sectionhtml);

////////////////////////////////////////////////////////////////////
$title = "moodlevideoreserveslist";
$sectionhtml = '';

if ($displayforms) {
    $sectionhtml = supportconsole_simple_form($title);
} else if ($consolecommand == "$title") {
    $result = get_reserve_data('video_reserves');

    $sourcelocation = get_config('block_ucla_video_reserves', 'sourceurl');
    $sourcelink = html_writer::link($sourcelocation, $sourcelocation, array('target' => '_blank'));
    $sourcefile = get_string('sourcefile', 'tool_uclasupportconsole', $sourcelink);

    $sectionhtml = supportconsole_render_section_shortcut($title, $result, array(), $sourcefile);
}
$consoles->push_console_html('logs', $title, $sectionhtml);

////////////////////////////////////////////////////////////////////
$title = "moodlelibraryreserveslist";
$sectionhtml = '';

if ($displayforms) {
    $sectionhtml = supportconsole_simple_form($title);
} else if ($consolecommand == "$title") {
    $result = get_reserve_data('library_reserves');
    
    $sourcelocation = get_config('block_ucla_library_reserves', 'source_url');
    $sourcelink = html_writer::link($sourcelocation, $sourcelocation, array('target' => '_blank'));
    $sourcefile = get_string('sourcefile', 'tool_uclasupportconsole', $sourcelink);    
    
    $sectionhtml = supportconsole_render_section_shortcut($title, $result, array(), $sourcefile);
}
$consoles->push_console_html('logs', $title, $sectionhtml);

////////////////////////////////////////////////////////////////////
$title = "moodlebruincastlist";
$sectionhtml = '';
$filters = '';

if ($displayforms) {
    $sectionhtml = supportconsole_simple_form($title);
} else if ($consolecommand == "$title") {
    $selected_term = optional_param('term', null, PARAM_ALPHANUM);
    $selected_subj = optional_param('subjarea', null, PARAM_NOTAGS);

    $filters = get_term_selector($title, $selected_term);
    $filters .= get_subject_area_selector($title, $selected_subj);
    $filters = supportconsole_simple_form($title, $filters);

    $result = get_reserve_data('bruincast', array('term' => $selected_term, 'subjarea' => $selected_subj));    
    $sectionhtml = supportconsole_render_section_shortcut($title, $result, array(), null, 'bruincast');
}

// Show the menus regardless of whether any rows are returned.
$consoles->push_console_html('logs', $title, $filters . $sectionhtml);

////////////////////////////////////////////////////////////////////
$title = "moodledigitalmediareserveslist";
$sectionhtml = '';

if ($displayforms) {
    $sectionhtml = supportconsole_simple_form($title);
} else if ($consolecommand == "$title") {
    $result = get_reserve_data('library_music_reserves');
    
    $sourcelocation = get_config('block_ucla_media', 'library_source_url');
    $sourcelink = html_writer::link($sourcelocation, $sourcelocation, array('target' => '_blank'));
    $sourcefile = get_string('sourcefile', 'tool_uclasupportconsole', $sourcelink);    
    
    $sectionhtml = supportconsole_render_section_shortcut($title, $result, array(), $sourcefile);
}
$consoles->push_console_html('logs', $title, $sectionhtml);

////////////////////////////////////////////////////////////////////
// Date selector for both Syllabus overview and Syllabus report
$syllabusdateselector = html_writer::label('Start date ("MM/DD/YYYY")', 'startdate') .
            html_writer::empty_tag('input', array(
                    'type' => 'text',
                    'length' => 10,
                    'name' => 'startdate',
                    'id' => 'startdate'
                )) . 
            html_writer::label('End date ("MM/DD/YYYY")', 'enddate') .
            html_writer::empty_tag('input', array(
                    'type' => 'text',
                    'length' => 10,
                    'name' => 'enddate',
                    'id' => 'enddate'
                ));
// Create IEI checkbox to be used for both syllabus overview and syllabus report.
$syllabusieiselector = html_writer::label(get_string('syllabus_iei', 'tool_uclasupportconsole'), 'iei') .
        html_writer::empty_tag('input', array(
            'type' => 'checkbox',
            'name' => 'iei',
            'value' => 'x',
            'id' => 'iei'
        ));

$title = "syllabusoverview";
$sectionhtml = '';

if ($displayforms) {
    $overview_options = html_writer::tag('option', get_string('syllabus_division', 'tool_uclasupportconsole'), 
            array('value' => 'division'));
    $overview_options .= html_writer::tag('option', get_string('syllabus_subjarea', 'tool_uclasupportconsole'), 
            array('value' => 'subjarea'));
    
    $syllabus_selectors = get_term_selector($title);
    
    $syllabus_selectors .= html_writer::tag('label', get_string('syllabus_browseby', 'tool_uclasupportconsole')) . 
            html_writer::tag('select', $overview_options, array('name' => 'syllabus'));
    
    $syllabus_selectors .= html_writer::start_tag('br') . $syllabusdateselector;
    $syllabus_selectors .= html_writer::empty_tag('br') . $syllabusieiselector;

    $sectionhtml = supportconsole_simple_form($title, $syllabus_selectors);
    
} else if ($consolecommand == "$title") {
    $selected_term = required_param('term', PARAM_ALPHANUM);
    $selected_type = required_param('syllabus', PARAM_ALPHA);
    
    $timestartstr = optional_param('startdate', '', PARAM_RAW);
    $timeendstr = optional_param('enddate', '', PARAM_RAW);
    $filterbyiei = optional_param('iei', '', PARAM_RAW);
    
    $timesql = '';
    $timerange = '';
    $uploaddisplaymsg = get_string('syllabustimerange', 'tool_uclasupportconsole');
    if ($timestart = strtotime($timestartstr)) {
        $timesql .= ' AND s.timecreated >= ' . $timestart;
        $timerange .= $uploaddisplaymsg . ' starting from ' . $timestartstr;
    }
    if ($timeend = strtotime($timeendstr)) {
        $timesql .= ' AND s.timecreated <= ' . $timeend;
        $timerange .= ($timestart ? '' : $uploaddisplaymsg) . ' up to '. $timeendstr;
    }
    
    // If we filter for IEI courses, then we want to include canceled and tutorial
    // classes. If we don't, then we want to exclude them. We also want to display
    // a note that reflects this.
    $joinclause = '';
    $excludeclause = '';
    $message = '';
    if(empty($filterbyiei)) {
        $excludeclause = 'urci.enrolstat != \'X\' AND urci.acttype != \'TUT\' AND';
        $message = get_string('syllabusnotesnoniei', 'tool_uclasupportconsole');
    } else {
        $joinclause = "JOIN {uclaieiclasses} AS uic ON uic.term = urc.term AND uic.srs = urc.srs";
        $message = get_string('syllabusnotesiei', 'tool_uclasupportconsole');
    }

    $sectionhtml .= $OUTPUT->box($message . ' ' . get_string('syllabusoverviewnotes',
            'tool_uclasupportconsole'));

    $syllabus_table = new html_table();
    
    $sql = '';

    $table_colum_name = get_string('syllabus_division', 'tool_uclasupportconsole');
    if ($selected_type == 'subjarea') {
        // List courses by subject area
        $sql = 'SELECT      urci.id, urs.subjarea AS code, urs.subj_area_full AS fullname, 
                            urci.crsidx AS catalognum, urc.courseid
                FROM        {ucla_reg_subjectarea} AS urs,
                            {ucla_reg_classinfo} AS urci,
                            {ucla_request_classes} AS urc
                            ' . $joinclause . '
                WHERE       urci.term =:term AND
                            urs.subjarea = urci.subj_area AND
                            urci.term = urc.term AND 
                            urci.srs = urc.srs AND
                            ' . $excludeclause . '
                            urc.courseid IS NOT NULL
                ORDER BY    urs.subjarea';
        $table_colum_name = get_string('syllabus_subjarea', 'tool_uclasupportconsole');
    } else {
        // List course by division
        $sql = 'SELECT      urci.id, urd.code, urd.fullname, 
                            urci.crsidx AS catalognum, urc.courseid
                FROM        {ucla_reg_division} AS urd,
                            {ucla_reg_classinfo} AS urci,
                            {ucla_request_classes} AS urc
                            ' . $joinclause . '
                WHERE       urci.term =:term AND
                            urd.code = urci.division AND
                            urci.term = urc.term AND 
                            urci.srs = urc.srs AND
                            ' . $excludeclause . '
                            urc.courseid IS NOT NULL
                ORDER BY    urd.fullname';
    }

    $table_colum_name .= ' (' . $selected_term . ')';

    $params = array();
    $params['term'] = $selected_term;
    if ( $course_list = $DB->get_records_sql($sql, $params) ) {
        // include locallib for syllabus constants, not the manager
        // (too much overhead)
        require_once($CFG->dirroot . '/local/ucla_syllabus/locallib.php');

        // setup bins for ugrad/grad syllabus totals
        /* bins are setup as follows:
         * [div or subject area (fullname)]
         *      [total_courses]
         *      [syllabi_courses]
         *      [UCLA_SYLLABUS_ACCESS_TYPE_PUBLIC]
         *      [UCLA_SYLLABUS_ACCESS_TYPE_LOGGEDIN]
         *      [UCLA_SYLLABUS_ACCESS_TYPE_PRIVATE]
         *      [preview]
         *      [manual]
         */
        $ugrad = array();
        $grad = array();

        // go through each course
        $syllabus_cache = array();  // we might be querying the same courseid
                                    // multiple times
        $working_bin = null;    // pointer to array we are incrementing
        foreach ($course_list as $course) {            
            // is this a grad course?
            if (intval(preg_replace("/[a-zA-Z]+/", '', $course->catalognum)) >= 200) {
                $working_bin = &$grad;
            } else {
                $working_bin = &$ugrad;
            }

            if (!isset($working_bin[$course->fullname])) {
                $working_bin[$course->fullname] = array();
            }

            // increment course count (note: decrementing NULL values has no
            // effect, but incrementing them results in 1, so need to check for
            // null values. however, will need to add @ to supress php notices)
            @++$working_bin[$course->fullname]['total_courses'];

            // now get syllabus information
            if (!isset($syllabus_cache[$course->courseid])) {
                $sql = 'SELECT *
                        FROM {ucla_syllabus} AS s
                        WHERE s.courseid =:courseid' . $timesql;
                $params['courseid'] = $course->courseid;
                $syllabus_cache[$course->courseid] = $DB->get_records_sql($sql, $params);
            }
            $syllabi = $syllabus_cache[$course->courseid];
            if (!empty($syllabi)) {
                // course has a syllabus, let's count it
                @++$working_bin[$course->fullname]['syllabi_courses'];
                foreach ($syllabi as $syllabus) {
                    switch ($syllabus->access_type) {
                        case UCLA_SYLLABUS_ACCESS_TYPE_PUBLIC:
                            @++$working_bin[$course->fullname][UCLA_SYLLABUS_ACCESS_TYPE_PUBLIC];
                            break;
                        case UCLA_SYLLABUS_ACCESS_TYPE_LOGGEDIN:
                            @++$working_bin[$course->fullname][UCLA_SYLLABUS_ACCESS_TYPE_LOGGEDIN];
                            break;
                        case UCLA_SYLLABUS_ACCESS_TYPE_PRIVATE:
                            @++$working_bin[$course->fullname][UCLA_SYLLABUS_ACCESS_TYPE_PRIVATE];
                            break;
                        default:
                            break;
                    }
                    if (!empty($syllabus->is_preview)) {
                        @++$working_bin[$course->fullname]['preview'];
                    }
                }
            }

            // Check if there were any manual syllabi.
            $courserecord = get_course($course->courseid);
            $ucla_syllabus_manager = new ucla_syllabus_manager($courserecord);
            $manualsyllabi = $ucla_syllabus_manager->get_all_manual_syllabi($timestart, $timeend);
            if (!empty($manualsyllabi)) {
                @++$working_bin[$course->fullname]['manual'];
                // Only increment number of courses that have syllabi if syllabus tool wasn't used.
                if (empty($syllabi)) {
                    @++$working_bin[$course->fullname]['syllabi_courses'];
                }
            }
            unset($ucla_syllabus_manager);
            
        }
        unset($syllabus_cache); // no need to keep this cache anymore

        // now format both data arrays to calculate totals and create array 
        // suitable for passing as a data array for html_table
        $processing['ugrad'] = array('data' => $ugrad, 'table' => new html_table());
        $processing['grad'] = array('data' => $grad, 'table' => new html_table());
        foreach ($processing as $type => &$data) {
            // keep totals for headers
            $header_totals = array();

            $table_data = array();
            $working_data = $data['data'];
            $working_table = $data['table'];
            foreach ($working_data as $fullname => $syllabi_counts) {
                $table_row = array();

                // col1: divison or subject area
                $table_row[] = $fullname;

                // col2: syllabus/courses
                @$header_totals['total_courses'] += $syllabi_counts['total_courses'];
                @$header_totals['syllabi_courses'] += $syllabi_counts['syllabi_courses'];
                if (empty($syllabi_counts['total_courses'])) {
                    $table_row[] = 0;
                } else {
                    @$table_row[] = sprintf('%d/%d (%d%%)',
                            $syllabi_counts['syllabi_courses'], 
                            $syllabi_counts['total_courses'],
                            round(($syllabi_counts['syllabi_courses']/
                             $syllabi_counts['total_courses'])*100));
                }

                // if there are no courses with syllabi, then we can skip
                if (empty($syllabi_counts['syllabi_courses'])) {
                    $table_row[] = 0;   // public
                    $table_row[] = 0;   // loggedin
                    $table_row[] = 0;   // preview
                    $table_row[] = 0;   // private
                    $table_row[] = 0;   // manual
                } else {
                    // col3: public syllabus
                    @$header_totals[UCLA_SYLLABUS_ACCESS_TYPE_PUBLIC] += $syllabi_counts[UCLA_SYLLABUS_ACCESS_TYPE_PUBLIC];
                    @$table_row[] = sprintf('%d/%d (%d%%)',
                            $syllabi_counts[UCLA_SYLLABUS_ACCESS_TYPE_PUBLIC],
                            $syllabi_counts['syllabi_courses'],
                            round(($syllabi_counts[UCLA_SYLLABUS_ACCESS_TYPE_PUBLIC]/
                             $syllabi_counts['syllabi_courses'])*100));

                    // col4: loggedin syllabus
                    @$header_totals[UCLA_SYLLABUS_ACCESS_TYPE_LOGGEDIN] += $syllabi_counts[UCLA_SYLLABUS_ACCESS_TYPE_LOGGEDIN];
                    @$table_row[] = sprintf('%d/%d (%d%%)',
                            $syllabi_counts[UCLA_SYLLABUS_ACCESS_TYPE_LOGGEDIN],
                            $syllabi_counts['syllabi_courses'],
                            round(($syllabi_counts[UCLA_SYLLABUS_ACCESS_TYPE_LOGGEDIN]/
                             $syllabi_counts['syllabi_courses'])*100));

                    // col5: preview syllabus
                    @$totalpublic = $syllabi_counts[UCLA_SYLLABUS_ACCESS_TYPE_PUBLIC] +
                            $syllabi_counts[UCLA_SYLLABUS_ACCESS_TYPE_LOGGEDIN];
                    @$header_totals['preview'] += $syllabi_counts['preview'];
                    if (empty($totalpublic)) {
                        $table_row[] = 0;
                    } else {
                        @$table_row[] = sprintf('%d/%d (%d%%)',
                                $syllabi_counts['preview'],
                                $totalpublic,
                                round(($syllabi_counts['preview']/
                                 $totalpublic)*100));
                    }

                    // col6: private syllabus
                    @$header_totals[UCLA_SYLLABUS_ACCESS_TYPE_PRIVATE] += $syllabi_counts[UCLA_SYLLABUS_ACCESS_TYPE_PRIVATE];
                    @$table_row[] = sprintf('%d/%d (%d%%)',
                            $syllabi_counts[UCLA_SYLLABUS_ACCESS_TYPE_PRIVATE],
                            $syllabi_counts['syllabi_courses'],
                            round(($syllabi_counts[UCLA_SYLLABUS_ACCESS_TYPE_PRIVATE]/
                             $syllabi_counts['syllabi_courses'])*100));

                    // col7: manual syllabus
                    @$header_totals['manual'] += $syllabi_counts['manual'];
                    @$table_row[] = sprintf('%d/%d (%d%%)',
                            $syllabi_counts['manual'],
                            $syllabi_counts['syllabi_courses'],
                            round(($syllabi_counts['manual']/
                             $syllabi_counts['syllabi_courses'])*100));
                }

                $table_data[] = $table_row;
            }
            $working_table->data = $table_data;

            // create header information
            $syllabus_count = 0;
            if (!empty($header_totals['total_courses'])) {
                $syllabus_count = sprintf('%d/%d (%d%%)',
                        $header_totals['syllabi_courses'],
                        $header_totals['total_courses'],
                        round(($header_totals['syllabi_courses']/
                         $header_totals['total_courses'])*100));
            }

            $public_syllabus_count = 0;
            $loggedin_syllabus_count = 0;
            $preview_syllabus_count = 0;
            $private_syllabus_count = 0;
            $manual_syllabus_count = 0;
            if (!empty($header_totals['syllabi_courses'])) {
                $public_syllabus_count = sprintf('%d/%d (%d%%)',
                        $header_totals[UCLA_SYLLABUS_ACCESS_TYPE_PUBLIC],
                        $header_totals['syllabi_courses'],
                        round(($header_totals[UCLA_SYLLABUS_ACCESS_TYPE_PUBLIC]/
                         $header_totals['syllabi_courses'])*100));
                $loggedin_syllabus_count = sprintf('%d/%d (%d%%)',
                        $header_totals[UCLA_SYLLABUS_ACCESS_TYPE_LOGGEDIN],
                        $header_totals['syllabi_courses'],
                        round(($header_totals[UCLA_SYLLABUS_ACCESS_TYPE_LOGGEDIN]/
                         $header_totals['syllabi_courses'])*100));
                $totalpublic = $header_totals[UCLA_SYLLABUS_ACCESS_TYPE_PUBLIC] +
                        $header_totals[UCLA_SYLLABUS_ACCESS_TYPE_LOGGEDIN];
                if (!empty($totalpublic)) {
                    $preview_syllabus_count = sprintf('%d/%d (%d%%)',
                            $header_totals['preview'],
                            $totalpublic,
                            round(($header_totals['preview']/
                             $totalpublic)*100));
                }
                $private_syllabus_count = sprintf('%d/%d (%d%%)',
                        $header_totals[UCLA_SYLLABUS_ACCESS_TYPE_PRIVATE],
                        $header_totals['syllabi_courses'],
                        round(($header_totals[UCLA_SYLLABUS_ACCESS_TYPE_PRIVATE]/
                         $header_totals['syllabi_courses'])*100));
                $manual_syllabus_count = sprintf('%d/%d (%d%%)',
                        $header_totals['manual'],
                        $header_totals['syllabi_courses'],
                        round(($header_totals['manual']/
                         $header_totals['syllabi_courses'])*100));
            }

            $working_table->head = array($table_colum_name,
                get_string('syllabus_count', 'tool_uclasupportconsole',
                        $syllabus_count),
                get_string('public_syllabus_count', 'tool_uclasupportconsole',
                        $public_syllabus_count),
                get_string('loggedin_syllabus_count', 'tool_uclasupportconsole',
                        $loggedin_syllabus_count),
                get_string('preview_syllabus_count', 'tool_uclasupportconsole',
                        $preview_syllabus_count),
                get_string('private_syllabus_count', 'tool_uclasupportconsole',
                        $private_syllabus_count),
                get_string('manual_syllabus_count', 'tool_uclasupportconsole',
                        $manual_syllabus_count));
        }
    } else {
        // No records found.
        if(!empty($filterbyiei)) {
            // Warn user that reason for no results may be that IEI information has not been uploaded.
            $sectionhtml .= $OUTPUT->box(get_string('syllabusieiwarning', 'tool_uclasupportconsole'));
        }
    }
        
    $sectionhtml .= $OUTPUT->box_start();
    $sectionhtml .= html_writer::tag('h3',
            get_string('syllabus_ugrad_table', 'tool_uclasupportconsole') . html_writer::start_tag('br') .
            $timerange);
    $sectionhtml .= isset($processing['ugrad']['table']) ? html_writer::table($processing['ugrad']['table']) : 
        get_string('nocourses', 'tool_uclasupportconsole');
    $sectionhtml .= $OUTPUT->box_end();
    
    $sectionhtml .= $OUTPUT->box_start();
    $sectionhtml .= html_writer::tag('h3',
            get_string('syllabus_grad_table', 'tool_uclasupportconsole') . html_writer::start_tag('br') .
            $timerange);
    $sectionhtml .= isset($processing['grad']['table']) ? html_writer::table($processing['grad']['table']) : 
        get_string('nocourses', 'tool_uclasupportconsole');
    $sectionhtml .= $OUTPUT->box_end();
}

$consoles->push_console_html('modules', $title , $sectionhtml);

////////////////////////////////////////////////////////////////////
require_once($CFG->dirroot . '/local/ucla_syllabus/locallib.php');

$title = "syllabusreoport";
$sectionhtml = '';

if ($displayforms) {
    
    $syllabus_selectors = get_term_selector($title);
    $syllabus_selectors .= get_subject_area_selector($title);
    
    $syllabus_selectors .= html_writer::start_tag('br') . $syllabusdateselector;
    $syllabus_selectors .= html_writer::empty_tag('br') . $syllabusieiselector;
    
    $sectionhtml = supportconsole_simple_form($title, $syllabus_selectors);
    
} else if ($consolecommand == "$title") {
    $selected_term = required_param('term', PARAM_ALPHANUM);
    $selected_subj = required_param('subjarea', PARAM_NOTAGS);
    
    $timestartstr = optional_param('startdate', '', PARAM_RAW);
    $timeendstr = optional_param('enddate', '', PARAM_RAW);
    $filterbyiei = optional_param('iei', '', PARAM_RAW);
    
    $timesql = '';
    $timerange = '';
    $uploaddisplaymsg = get_string('syllabustimerange', 'tool_uclasupportconsole');
    if ($timestart = strtotime($timestartstr)) {
        $timesql .= ' AND s.timecreated >= ' . $timestart . ' ';
        $timerange .= $uploaddisplaymsg . ' starting from ' . $timestartstr;
    }
    if ($timeend = strtotime($timeendstr)) {
        $timesql .= ' AND s.timecreated <= ' . $timeend . ' ';
        $timerange .= ($timestart ? '' : $uploaddisplaymsg) . ' up to '. $timeendstr;
    }

    // If we filter for IEI courses, then we want to include canceled and tutorial
    // classes. If we don't, then we want to exclude them.
    $joinclause = '';
    $excludeclause = '';
    $message = '';
    if(empty($filterbyiei)) {
        $excludeclause = 'AND uri.enrolstat != \'X\' AND uri.acttype != \'TUT\'';
        $message = get_string('syllabusnotesnoniei', 'tool_uclasupportconsole');
    } else {
        $joinclause = "JOIN {uclaieiclasses} AS uic ON uic.term = urc.term AND uic.srs = urc.srs";
        $message = get_string('syllabusnotesiei', 'tool_uclasupportconsole');
    }

    $sectionhtml .= $OUTPUT->box($message . ' ' . get_string('syllabusreoportnotes',
            'tool_uclasupportconsole'));

    $sql = "SELECT      CONCAT(COALESCE(s.id, ''), urc.srs) AS idsrs, 
                        urc.department,
                        urc.course,
                        s.access_type,
                        urc.courseid
            FROM        {ucla_request_classes} AS urc
            JOIN        {ucla_reg_classinfo} AS uri ON (
                        urc.term=uri.term AND
                        urc.srs=uri.srs)
            LEFT JOIN   {ucla_syllabus} AS s ON (urc.courseid = s.courseid {$timesql})
            $joinclause
            WHERE       urc.term =:term AND
                        urc.department =:department
                        $excludeclause
            ORDER BY    uri.term, uri.subj_area, uri.crsidx, uri.secidx";
    
    $params = array();
    $params['term'] = $selected_term; 
    $params['department'] = $selected_subj;
    
    $syllabus_info = array();
    $num_public = 0;
    $num_private = 0;
    $num_courses = 0;
    $num_manual = 0;
    if ($syllabus_report = $DB->get_records_sql($sql, $params)) {        
        foreach ($syllabus_report as $crs_syl) {
            $access_public = $crs_syl->access_type == UCLA_SYLLABUS_ACCESS_TYPE_PUBLIC
                    || $crs_syl->access_type == UCLA_SYLLABUS_ACCESS_TYPE_LOGGEDIN;
            $access_private = $crs_syl->access_type == UCLA_SYLLABUS_ACCESS_TYPE_PRIVATE;
            
            $course_name = $crs_syl->department . ' ' . $crs_syl->course;
            $course_name = html_writer::link($CFG->wwwroot . '/course/view.php?id=' .
                    $crs_syl->courseid, $course_name, array('target' => '_blank'));
            $syllabus_record = array($course_name);
            
            if (empty($crs_syl->access_type)) {
                $syllabus_record[2] = '';
                $syllabus_record[3] = '';
            } else if ($access_public) {
                $syllabus_record[2] = 'x';
                $syllabus_record[3] = '';
                $syllabus_record[4] = '';
                $num_public++;
            } else if ($access_private) {
                $syllabus_record[2] = '';
                $syllabus_record[3] = 'x';
                $syllabus_record[4] = '';
                $num_private++;
            }

            // Check if course has a manual syllabus.
            $courserecord = $DB->get_record('course', array('id' =>  $crs_syl->courseid));
            $ucla_syllabus_manager = new ucla_syllabus_manager($courserecord);
            $manualsyllabi = $ucla_syllabus_manager->get_all_manual_syllabi($timestart, $timeend);
            if (!empty($manualsyllabi)) {
                $syllabus_record[4] = count($manualsyllabi);
                $num_manual++;
            } else {
                $syllabus_record[4] = '';
            }
            unset($ucla_syllabus_manager);

            // If the previous course processed is the same course, then just update
            // that course instead of creating a new row
            if ($num_courses > 0 && $syllabus_info[$num_courses - 1][0] == $course_name) {
                if ($access_public) {
                    $syllabus_info[$num_courses - 1][2] = 'x';
                } else if ($access_private) {
                    $syllabus_info[$num_courses - 1][3] = 'x';
                }
            } else {
                $syllabus_info[$num_courses] = $syllabus_record;
                $num_courses++;
            }
        }
    } else {
        // No records found.
        if(!empty($filterbyiei)) {
            // Warn user that reason for no results may be that IEI information has not been uploaded.
            $sectionhtml .= $OUTPUT->box(get_string('syllabusieiwarning', 'tool_uclasupportconsole'));
        }
    }
    
    $head_info = new stdClass();
    $head_info->term = $selected_term;
    $head_info->num_courses = $num_courses;
    $syllabus_table = new html_table();
    $syllabus_table->id = 'syllabusreoport';
    $table_headers = array(get_string('syllabus_header_course', 'tool_uclasupportconsole', $head_info) .
        html_writer::start_tag('br') . $timerange,
        get_string('syllabus_header_public', 'tool_uclasupportconsole', $num_public), 
        get_string('syllabus_header_private', 'tool_uclasupportconsole', $num_private),
        get_string('syllabus_header_manual', 'tool_uclasupportconsole', $num_manual)
        );

    $syllabus_table->head = $table_headers;
    $syllabus_table->data = $syllabus_info;
    
    $sectionhtml .= html_writer::table($syllabus_table);
}

$consoles->push_console_html('modules', $title , $sectionhtml);

////////////////////////////////////////////////////////////////////
// Recently updated syllabus links at the Registrar's ucla_syllabus table.

$title = "syllabusrecentlinks";
$sectionhtml = '';

if ($displayforms) {
    $input = html_writer::empty_tag('input', array(
                    'type' => 'text',
                    'name' => 'syllabuslinkslimit',
                    'id' => 'syllabuslinkslimit',
                    'value' => 10,
                    'size' => 3,
                    'maxlength' => 3
                ));
    $numselector = html_writer::label(
            get_string('syllabuslinkslimit', 'tool_uclasupportconsole', $input),
                       'syllabuslinkslimit');

    $sectionhtml .= supportconsole_simple_form($title, $numselector);
} else if ($consolecommand == "$title") {
    $limit = optional_param('syllabuslinkslimit', 10, PARAM_INT);
    $regsender = new local_ucla_regsender();
    $results = $regsender->get_recent_syllabus_links($limit);

    $sectionhtml .= supportconsole_render_section_shortcut($title, $results, array('syllabuslinkslimit' => $limit));
}

$consoles->push_console_html('modules', $title , $sectionhtml);

////////////////////////////////////////////////////////////////////
// TODO ghost courses in request classes table

////////////////////////////////////////////////////////////////////
$title = 'moodleusernamesearch';
$sectionhtml = '';
// Note: this report has an additional column at the end, with an SRDB button that points to the enroll2 Registrar class lookup
if ($displayforms) { 
    $sectionhtml .= supportconsole_simple_form($title,
        html_writer::label('Full or any part of name', 'name-lookup')
            . html_writer::empty_tag('input', array(
                    'type' => 'text',
                    'name' => 'fullname',
                    'id' => 'name-lookup'
                )));
} else if ($consolecommand == "$title") { 
    $fullname = required_param('fullname', PARAM_TEXT);
    $users = get_users(true, $fullname, false, null, 'lastname, firstname ASC',
        '', '', '', 100, 
        'id AS userid, auth, username, firstname, lastname, idnumber, email, FROM_UNIXTIME(lastaccess) AS last_access, lastip');

    foreach($users as $k => $user) {
        if (!empty($user->idnumber)) {
            $user->srdblink = supportconsole_simple_form('enrollview',
                html_writer::empty_tag('input', array(
                    'type' => 'hidden',
                    'name' => 'uid',
                    'value' => $user->idnumber
                )));
        }
    }

    $sectionhtml .= supportconsole_render_section_shortcut($title, $users, array('fullname' => $fullname));
} 

$consoles->push_console_html('users', $title, $sectionhtml);
////////////////////////////////////////////////////////////////////
// REGISTRAR DIRECT FEEDS LIONS MEAT
////////////////////////////////////////////////////////////////////
$title = "enrollview";
// Note: this has code which allows post from Name Lookup report 
$sectionhtml = '';
if ($displayforms) {
    $sectionhtml .= supportconsole_simple_form($title, get_uid_input($title));
} else if ($consolecommand == $title) {
    # tie-in to link from name lookup
    $uid = required_param('uid', PARAM_RAW);
    ucla_require_registrar();
    $adodb = registrar_query::open_registrar_connection();

    if (ucla_validator('uid', $uid)) {
        $recset = $adodb->Execute('SELECT * FROM enroll2_test WHERE uid = ' . $uid 
            . ' ORDER BY term_int DESC, subj_area, catlg_no, sect_no');

        $usercourses = array();
        if (!empty($recset) && !$recset->EOF) {
            while($fields = $recset->FetchRow()) {
                $usercourses[] = $fields;
            }
        }

        $sectionhtml .= supportconsole_render_section_shortcut($title,
                $usercourses, array('uid' => $uid));
    } else {
        $sectionhtml .= $OUTPUT->box($OUTPUT->heading($title, 3));
        $sectionhtml .= 'Invalid UID: [' . $uid . ']';
    }
}

$consoles->push_console_html('srdb', $title, $sectionhtml);

// Dynamic hardcoded (TODO make reqistrar_query return parameter types it expects)
ucla_require_registrar();
$qs = get_all_available_registrar_queries();

foreach ($qs as $query) {
    $sectionhtml = '';
    $input_html = '';
    if ($displayforms) {
        // generate input parameters
        $storedproc = registrar_query::get_registrar_query($query);

        if (!$storedproc) {
            continue;
        }

        $params = $storedproc->get_query_params();

        foreach ($params as $param) {
            switch($param) {
                case 'term':
                    $input_html .= get_term_selector($query);
                    break;
                case 'subjarea':
                    $input_html .= get_subject_area_selector($query);
                    break;
                case 'uid':
                    $input_html .= get_uid_input($query);
                    break;
                case 'srs':
                    $input_html .= get_srs_input($query);
                    break;
                case 'crsidx':
                case 'secidx':
                    $input_html .= get_generic_input($query, $param);
                    break;
                default:
                    $input_html .= get_string('unknownstoredprocparam',
                        'tool_uclasupportconsole');
                    break;
            }
        }

        if (empty($input_html)) {
            continue;   // skip it
        }     

        $sectionhtml .= supportconsole_simple_form($query, $input_html);
    } else if ($consolecommand == $query) {
        // generate input parameters (optimized by putting inside 
        // conditionals)
        $storedproc = registrar_query::get_registrar_query($query);
        $spparams = $storedproc->get_query_params();

        foreach ($spparams as $param_name) {
            if ($param_value = optional_param($param_name, '', PARAM_NOTAGS)) {
                $params[$param_name] = $param_value;
            }
        }

        
        // get all data, even bad, and uncached
        $results = registrar_query::run_registrar_query($query, $params, false);
        
        if (!$good_data = $results[registrar_query::QUERY_RESULTS]) {
            $good_data = array();
        }        
        $results = array_merge($good_data, $results[registrar_query::FAILED_OUTPUTS]);
        
        $sectionhtml .= supportconsole_render_section_shortcut($query,
            $results, $params);
    }

    $consoles->push_console_html('srdb', $query, $sectionhtml);
}

///////////////////////////////////////////////////////////////////////////////
$title = "countmodules";

// Use API
$item_names = array();

if ($displayforms) {
    // $result = $DB->get_records_sql();
    // Show number of courses per term?

} else if ($consolecommand == "$title") {
	$itemfile = $_POST['itemname'];
	$term     = $_POST['term'];

    echo "<h3>$title $itemfile</h3>\n";
    echo "<i>Term: $term Resource/Activity: $itemfile</i><br>\n";
	
	if($itemfile=='forumposts'){
		$log_query="SELECT c.id as ID, c.shortname as COURSE ,count(*) as Posts, c.fullname as Full_Name
							FROM mdl_course c 
							INNER JOIN  mdl_forum_discussions d ON d.course = c.id 
							INNER JOIN mdl_forum_posts p ON p.discussion = d.id
							WHERE c.idnumber LIKE '$term%'
							GROUP by c.id
							ORDER BY Posts DESC
							";
	} else {
	$log_query="SELECT c.id, COUNT(l.id) as count, c.shortname
        FROM {$CFG->prefix}$itemfile l
        		INNER JOIN {$CFG->prefix}course c on l.course = c.id
        WHERE c.idnumber like '$term%'        
        GROUP BY left(c.idnumber,3), course
        ORDER BY left(c.idnumber,3), count DESC";
    }

    $result=$DB->get_records_sql($log_query);

// Display results with course edit and view links for forum posts
// Display results with just course view links for others...
// Split forum posts out of this
}

// TODO UCLA Datasync library views

////////////////////////////////////////////////////////////////////
// CLASS SITES, CAMP SITES, HIND SIGHTS, MASS HEIGHTS
////////////////////////////////////////////////////////////////////
$title="collablist";
$sectionhtml = '';
if ($displayforms) {
    $sectionhtml .= supportconsole_simple_form($title);
} else if ($consolecommand == "$title") {  # tie-in to link from name lookup
    $result=mysql_query("select "
        . "elt(c.visible + 1, 'Hidden', 'Visible') as Hidden,elt(c.guest + 1, 'Private', 'Public') as Guest,c.format,cc.name, concat('<a href=\"{$CFG->wwwroot}/course/view.php?id=', c.id, '\">', c.shortname, '</a>') as 'Link', c.fullname "
        . "from mdl_course c "
        . "left join mdl_ucla_tasites t using(shortname) "
        . "left join mdl_course_categories cc on c.category=cc.id "
        . 'where idnumber="" '
        . 'and t.shortname is NULL '
        . 'and format <>"uclaredir" '
        . 'and cc.name not in ("To Be Deleted", "Demo/Testing") '
        . 'order by cc.name, c.shortname') or die(mysql_error());
    $days = $_POST['days'];

    echo "<h3>$title</h3>\n";

    $num_rows = mysql_num_rows($result);
    echo "There are $num_rows courses.<P>";
    echo "<table>\n";
    $cols = 0;
    while ($get_info = mysql_fetch_assoc($result)){
		if($cols == 0) {
            $cols = 1;
            echo "<tr>";
            foreach($get_info as $col => $value) {
                echo "<th align='left'>$col</th>";
            }
            echo "<tr>\n";
        }
        echo "<tr>\n";
        foreach ($get_info as $field) {
            echo "\t<td>$field</td>\n";
        }
        echo "</tr>\n";
    }
    echo "</table>\n";
}

////////////////////////////////////////////////////////////////////
$title = "courseregistrardifferences";
$sectionhtml = '';

if ($displayforms) {
    $sectionhtml .= supportconsole_simple_form($title, get_term_selector($title));
} else if ($consolecommand == "$title") {  # tie-in to link from name lookup
    $term = required_param('term', PARAM_ALPHANUM);    
    $sql = "SELECT  c.id AS courseid,
                    c.shortname AS course,
                    regc.crs_desc AS old_description,
                    c.summary AS new_description,
                    regc.coursetitle,
                    regc.sectiontitle
            FROM    {course} AS c,
                    {ucla_reg_classinfo} AS regc,
                    {ucla_request_classes} AS reqc
            WHERE   reqc.term=:term AND
                    reqc.courseid=c.id AND
                    reqc.term=regc.term AND
                    reqc.hostcourse=1 AND
                    reqc.srs=regc.srs AND
                    STRCMP(c.summary, regc.crs_desc)!=0 AND
                    (c.summary!='' AND
                     c.summary IS NOT NULL)";
    $result = $DB->get_records_sql($sql, array('term' => $term));

    foreach ($result as $k => $course) {
        if (isset($course->courseid)) {
            $course->courselink = html_writer::link(new moodle_url(
                    '/course/view.php', array('id' => $course->courseid)
                ), $course->course);
            unset($course->course);
            $result[$k] = $course;
        }
    }

    $sectionhtml .= supportconsole_render_section_shortcut($title, $result,
        array('term' => $term));
}

$consoles->push_console_html('srdb', $title, $sectionhtml);

////////////////////////////////////////////////////////////////////
$title = "showreopenedclasses";
$sectionhtml = '';

if ($displayforms) {
    $sectionhtml .= supportconsole_simple_form($title, get_term_selector($title));
} else if ($consolecommand == "$title") {  # tie-in to link from name lookup
    $term = required_param('term', PARAM_ALPHANUM);
    $currentterm = $CFG->currentterm;
    if (term_cmp_fn($currentterm, $term) == 1){
        # We use a dummy row as the first column since get_records_sql will replace
        # duplicate results with the same values in the first column
        $sql = "SELECT  (@cnt := @cnt + 1) AS rownumber,
                        c.id AS courseid,
                        regc.term AS term,
                        regc.srs AS srs,
                        regc.subj_area AS subject_area,
                        regc.coursenum AS course_num,
                        regc.sectnum AS section,
                        c.shortname AS course,
                        regc.coursetitle AS coursetitle,
                        regc.sectiontitle AS sectiontitle,
                        u.lastname AS inst_lastname,
                        u.firstname AS inst_firstname
                FROM    {ucla_request_classes} AS reqc,
                        {ucla_reg_classinfo} AS regc,
                        ({role_assignments} ra
                            JOIN {user} u ON u.id = ra.userid
                            JOIN {role} r ON r.id = ra.roleid AND r.name = 'Instructor'
                            JOIN {context} co ON co.id = ra.contextid
                            RIGHT JOIN {course} c ON c.id = co.instanceid)
                        CROSS JOIN (SELECT @cnt := 0) AS dummy
                WHERE   reqc.term=:term AND
                        reqc.courseid=c.id AND
                        reqc.term=regc.term AND
                        reqc.hostcourse=1 AND
                        reqc.srs=regc.srs AND
                        c.visible=1
                ORDER BY subject_area, course_num, section";
        $result = $DB->get_records_sql($sql, array('term' => $term));

        foreach ($result as $k => $course) {
            if (isset($course->courseid)) {
                $course->courselink = html_writer::link(new moodle_url(
                        '/course/view.php', array('id' => $course->courseid)
                    ), $course->course);
                unset($course->course);
                $output[$k] = (object) array('courseid' => $course->courseid, 'srs' => $course->srs,
                    'courselink' => $course->courselink, 'subject_area' => $course->subject_area, 'course_num' => $course->course_num,
                    'section' => $course->section, 'coursetitle' => $course->coursetitle, 'sectiontitle' => $course->sectiontitle,
                    'inst_lastname' => $course->inst_lastname, 'inst_firstname' => $course->inst_firstname);
            }
        }

        if ($result == null){
            $output = null;
        }

    } else {
        $output = null;
    }

    $sectionhtml .= supportconsole_render_section_shortcut($title, $output,
        array('term' => $term));
}

$consoles->push_console_html('srdb', $title, $sectionhtml);

////////////////////////////////////////////////////////////////////
$title = "assignmentquizzesduesoon";
$sectionhtml = '';

if ($displayforms) {
    $sectionhtml .= supportconsole_simple_form($title,
        html_writer::label('Start date ("MM/DD/YYYY")', 'startdate')
            . html_writer::empty_tag('input', array(
                    'type' => 'text',
                    'length' => 10,
                    'name' => 'startdate',
                    'id' => 'startdate',
                    'value' => date('m/d/Y')
                ))
            . html_writer::label('Days from start', 'datedays')
            . html_writer::empty_tag('input', array(
                    'type' => 'text',
                    'name' => 'datedays',
                    'id' => 'datedays',
                    'value' => 7
                )));
} else if ($consolecommand == "$title") {  # tie-in to link from name lookup
    $timefromstr = required_param('startdate', PARAM_RAW);
    $timefrom = strtotime($timefromstr);
    
    $days = required_param('datedays', PARAM_NUMBER);
    $daysec = $days * 86400;
    $timeto = $timefrom + $daysec;

    $sql = "SELECT (@s := @s+1) AS identifier, m.Due_date, c.shortname,
                   (SELECT COUNT(DISTINCT u.id)
                      FROM {course} AS subc
                      JOIN {context} AS ctx ON subc.id = ctx.instanceid
                      JOIN {role_assignments} AS ra ON ra.contextid = ctx.id
                      JOIN {role} AS r ON r.id = ra.roleid
                      JOIN {user} AS u ON u.id = ra.userid
                     WHERE subc.id=c.id) AS participants,
                   c.fullname, m.modtype, m.Name
              FROM (
                     (SELECT 'quiz' AS modtype, course, name,
                             FROM_UNIXTIME(timeclose) AS Due_Date
                        FROM {quiz}
                       WHERE timeclose BETWEEN $timefrom AND $timeto
                     ) UNION (
                      SELECT 'assignment' AS modtype, course, name,
                             FROM_UNIXTIME(duedate) AS Due_Date
                        FROM {assign}
                       WHERE duedate BETWEEN $timefrom AND $timeto
                     ) UNION (
                      SELECT 'turnitintool' AS modtype, tiit.course,
                             CONCAT_WS(' ', tiit.name, partname) AS name,
                             FROM_UNIXTIME(dtdue) AS Due_Date
                        FROM {turnitintool} tiit
                        JOIN {turnitintool_parts} ON tiit.id = turnitintoolid
                       WHERE dtdue BETWEEN $timefrom AND $timeto
                     ) UNION (
                      SELECT 'turnitintooltwo' AS modtype, tiit2.course,
                             CONCAT_WS(' ', tiit2.name, partname) AS name,
                             FROM_UNIXTIME(dtdue) AS Due_Date
                        FROM {turnitintooltwo} tiit2
                        JOIN {turnitintooltwo_parts} ON tiit2.id = turnitintooltwoid
                       WHERE dtdue BETWEEN $timefrom AND $timeto)
                   ) AS m
              JOIN (SELECT @s := 0) AS increment
              JOIN {course} c ON c.id = m.course
          ORDER BY `m`.`Due_Date` ASC";

    $results = $DB->get_records_sql($sql);

    foreach ($results as $k => $result) {
        if (isset($result->courseid)) {
            $result->courseid = html_writer::link(new moodle_url(
                    '/course/view.php', array('id' => $result->courseid)
                ), $result->courseid);
            $results[$k] = $result;
        }
    }

    $a = new stdClass();
    $a->start = $timefromstr;
    $a->end = date('m/d/Y', $timeto);
    $a->days = $days;
    $sectionhtml .= supportconsole_render_section_shortcut($title, $results,
            array('startdate' => $timefromstr, 'datedays' => $days),
            get_string('assignmentquizzesduesoonmoreinfo', 'tool_uclasupportconsole', $a));
}

$consoles->push_console_html('modules', $title, $sectionhtml);

//////////////////////////////////////////////////////////////////////////////////////////
$title = "modulespercourse";
$sectionhtml = '';

if ($displayforms) {
    // add filter for term/subject area, because this table can get very big
    // and the query get return a ton of data
    $input_html = get_term_selector($title);
    $input_html .= get_subject_area_selector($title);        
    
    $sectionhtml = supportconsole_simple_form($title, $input_html);
} else if ($consolecommand == "$title") {  
    
    // get optional filters
    $term = optional_param('term', null, PARAM_ALPHANUM);
    if (!ucla_validator('term', $term)) {
        $term = null;
    }    
    $subjarea = optional_param('subjarea', null, PARAM_NOTAGS);
    
    // Mapping of [course shortname, module name] => count of 
    // instances of this module in this course
    // count($course_indiv_module_counts[<course shortname>]) has 
    // the number kinds of modules used in this course
    $courseindivmodulecounts = array();
    
    // Mapping of course shortname => count of instances of 
    // all modules in this course
    $coursetotalmodulecounts = array();
    
    $params = array();
    $sql = "SELECT  cm.id,
                    c.id AS courseid,
                    c.shortname AS shortname,
                    m.name AS modulename, 
                    count(*) AS cnt
            FROM    {course} c
            JOIN    {course_modules} cm ON c.id = cm.course
            JOIN    {modules} m ON cm.module = m.id";
    
    // handle term/subject area filter
    if (!empty($term) || !empty($subjarea)) {
        $sql .= " JOIN  {ucla_request_classes} urc ON (urc.courseid=c.id)";
    }        
    if (!empty($term) && !empty($subjarea)) {
        $sql .= " WHERE urc.term=:term AND
                        urc.department=:subjarea";
        $params['term'] = $term;
        $params['subjarea'] = $subjarea;        
    } else if (!empty($term)) {
        $sql .= " WHERE urc.term=:term";
        $params['term'] = $term;    
    } else if (!empty($subjarea)) {
        $sql .= " WHERE urc.department=:subjarea";
        $params['subjarea'] = $subjarea;    
    }    
    
    $sql .= " GROUP BY c.id, m.id
             ORDER BY c.shortname";
    
    $results = $DB->get_records_sql($sql, $params);
    
    $courseshortnames = array();

    foreach ($results as $result) {
        $sn = $result->courseid;
        $mn = $result->modulename;
        $courseshortnames[$sn] = $result->shortname;

        if (!isset($courseindivmodulecounts[$sn][$mn])) {
            $courseindivmodulecounts[$sn][$mn] = 0;
        }

        $courseindivmodulecounts[$sn][$mn] += $result->cnt;
    }
    
    $tabledata = array();
    foreach ($courseindivmodulecounts as $courseid => $modulecounts) {
        $rowdata = array(
            'course' => html_writer::link(new moodle_url(
                    '/course/view.php', array('id' => $courseid)
                ), $courseshortnames[$courseid]),
            'total' => array_sum($modulecounts)
        );


        foreach ($modulecounts as $modulename => $moduleinst) {
            $rowdata[$modulename] = $moduleinst;
        }

        $tabledata[] = $rowdata;
    }
    
    // Create an array with only the module names: $field
    $field = array();
    $tempfield = array();
    
    foreach ($tabledata as $tabledatum) {
        foreach ($tabledatum as $tablef => $tablev) {
            if ($tablef == 'id') {
                continue;
            }
            
            if ($tablef == 'course' || $tablef == 'total') {
                $field[$tablef] = $tablef;
                continue;
            }
            
            $tempfield[$tablef] = $tablef;
        }
    }
    
    asort($tempfield);
    $field = array_merge($field, $tempfield);
    
    foreach ($tabledata as & $courses) {
        $tempfield = $field;
        // Merge the courses array into the tempfield array.
        $courses = array_merge($tempfield, $courses);
        foreach ($courses as $module => & $count) {
            // If course does not have the module, make its count = 0.
            if ($module != 'course' && !is_numeric($count)) {
                $count = NULL;
            }   
        }    
    }
    $sectionhtml .= supportconsole_render_section_shortcut($title,
             $tabledata, $params);


}

$consoles->push_console_html('modules', $title, $sectionhtml);

//////////////////////////////////////////////////////////////////////////////////////////
$title = "modulespertacourse";
$sectionhtml = '';

if ($displayforms) {
    // Add filter for term/subject area, because this table can get very big
    // and the query get return a ton of data.
    $input_html = get_term_selector($title);
    $input_html .= get_subject_area_selector($title);

    $sectionhtml = supportconsole_simple_form($title, $input_html);
} else if ($consolecommand == "$title") {

    // Get optional filters.
    $term = optional_param('term', null, PARAM_ALPHANUM);
    if (!ucla_validator('term', $term)) {
        $term = null;
    }
    $subjarea = optional_param('subjarea', null, PARAM_NOTAGS);

    // Mapping of [course shortname, module name] => count of
    // instances of this module in this course
    // count($courseindivmodulecounts[<course shortname>]) has
    // the number kinds of modules used in this course.
    $courseindivmodulecounts = array();

    // Mapping of course shortname => count of instances of
    // all modules in this course
    $coursetotalmodulecounts = array();

    // Return list of TA sites that match criteria.
    $params = array();
    $sql = "SELECT  cm.id,
                    tasite.id AS courseid,
                    tasite.shortname AS shortname,
                    m.name AS modulename,
                    count(*) AS cnt
            FROM    {course} tasite
            JOIN    {ucla_siteindicator} si ON 
                    (si.courseid=tasite.id AND si.type='tasite')
            JOIN    {course_modules} cm ON tasite.id = cm.course
            JOIN    {modules} m ON cm.module = m.id";

    // Handle term/subject area filter on parent course for TA site.
    if (!empty($term) || !empty($subjarea)) {
        // Need to join on meta enrollment plugin specific for TA sites.
        $sql .= " JOIN {enrol} metaenrol ON
                (metaenrol.enrol='meta' AND metaenrol.courseid=tasite.id)";
        $sql .= " JOIN {course} parentsite ON (metaenrol.customint1=parentsite.id)";
        $sql .= " JOIN  {ucla_request_classes} urc ON (urc.courseid=parentsite.id)";
    }
    if (!empty($term) && !empty($subjarea)) {
        $sql .= " WHERE urc.term=:term AND
                        urc.department=:subjarea";
        $params['term'] = $term;
        $params['subjarea'] = $subjarea;
    } else if (!empty($term)) {
        $sql .= " WHERE urc.term=:term";
        $params['term'] = $term;
    } else if (!empty($subjarea)) {
        $sql .= " WHERE urc.department=:subjarea";
        $params['subjarea'] = $subjarea;
    }

    $sql .= " GROUP BY tasite.id, m.id
             ORDER BY tasite.shortname";

    $results = $DB->get_records_sql($sql, $params);

    $courseshortnames = array();

    foreach ($results as $result) {
        $sn = $result->courseid;
        $mn = $result->modulename;
        $courseshortnames[$sn] = $result->shortname;

        if (!isset($courseindivmodulecounts[$sn][$mn])) {
            $courseindivmodulecounts[$sn][$mn] = 0;
        }

        $courseindivmodulecounts[$sn][$mn] += $result->cnt;
    }

    $tabledata = array();
    foreach ($courseindivmodulecounts as $courseid => $modulecounts) {
        $rowdata = array(
            'TA site' => html_writer::link(new moodle_url(
                    '/course/view.php', array('id' => $courseid)
                ), $courseshortnames[$courseid]),
            'total' => array_sum($modulecounts)
        );


        foreach ($modulecounts as $modulename => $moduleinst) {
            $rowdata[$modulename] = $moduleinst;
        }

        $tabledata[] = $rowdata;
    }

    // Create an array with only the module names: $field.
    $field = array();
    $tempfield = array();

    foreach ($tabledata as $tabledatum) {
        foreach ($tabledatum as $tablef => $tablev) {
            if ($tablef == 'id') {
                continue;
            }

            if ($tablef == 'TA site' || $tablef == 'total') {
                $field[$tablef] = $tablef;
                continue;
            }

            $tempfield[$tablef] = $tablef;
        }
    }

    asort($tempfield);
    $field = array_merge($field, $tempfield);

    foreach ($tabledata as & $courses) {
        $tempfield = $field;
        // Merge the courses array into the tempfield array.
        $courses = array_merge($tempfield, $courses);
        foreach ($courses as $module => & $count) {
            // If course does not have the module, make its count = 0.
            if ($module != 'TA site' && !is_numeric($count)) {
                $count = NULL;
            }
        }
    }
    $sectionhtml .= supportconsole_render_section_shortcut($title,
             $tabledata, $params);
}

$consoles->push_console_html('modules', $title, $sectionhtml);

////////////////////////////////////////////////////////////////////
// USER RELATED REPORTS 
////////////////////////////////////////////////////////////////////
$title = "roleassignments";
$sectionhtml = '';
if ($displayforms) { 
    $sectionhtml .= supportconsole_simple_form($title);
} else if ($consolecommand == "$title") {     

    $sql = "SELECT  ra.id,
                    r.name,                    
                    c.contextlevel,
                    ra.component,
                    si.type,
                    COUNT(*) AS count
            FROM    {role_assignments} ra 
            JOIN    {context} c ON c.id = ra.contextid
            JOIN    {role} r ON (ra.roleid = r.id) 
            LEFT JOIN   {ucla_siteindicator} si ON 
                        (c.instanceid=si.courseid AND
                         c.contextlevel=50)
            GROUP BY contextlevel, ra.component, r.id, si.type
            ORDER BY c.contextlevel ASC, r.sortorder ASC";
    $results = $DB->get_records_sql($sql);
    
    foreach ($results as $result) {
        // If exporting to Excel, then don't create form.
        if (empty($exportoption)) {
            // Link to view results.
            $result->count = supportconsole_simple_form("userswithrole",
                html_writer::empty_tag('input', array(
                    'type' => 'hidden',
                    'name' => 'role',
                    'value' => $result->name,
                ))
                .html_writer::empty_tag('input', array(
                    'type' => 'hidden',
                    'name' => 'contextlevel',
                    'value' => $result->contextlevel,
                ))
                .html_writer::empty_tag('input', array(
                    'type' => 'hidden',
                    'name' => 'count',
                    'value' => $result->count,
                ))
                . html_writer::empty_tag('input', array(
                    'type' => 'hidden',
                    'name' => 'component',
                    'value' => $result->component,
                ))
                .html_writer::empty_tag('input', array(
                    'type' => 'hidden',
                    'name' => 'type',
                    'value' => $result->type,
                )), ($result->count==1) ? get_string('viewrole', 'tool_uclasupportconsole') :
            get_string('viewroles', 'tool_uclasupportconsole', $result->count));
        }
    }

    $admin_result = get_config(null, 'siteadmins');
    if (empty($admin_result) && empty($result)) {
        $sectionhtml .= $OUTPUT->error_text(get_string('noenrollments', 'tool_uclasupportconsole'));
    } else {
        // get siteadmins, they are a different breed
        $admin_cnt = count(explode(',', $admin_result));
        $adminrow = new stdClass();
        $adminrow->name = 'Site administrators';
        $adminrow->contextlevel = CONTEXT_SYSTEM;
        $adminrow->component = 'admin';

        // If exporting to Excel, then don't create form.
        if (!empty($exportoption)) {
            $adminrow->count = $admin_cnt;
        } else {
            $adminrow->count = supportconsole_simple_form("userswithrole",
                html_writer::empty_tag('input', array(
                    'type' => 'hidden',
                    'name' => 'role',
                    'value' => get_string('siteadministrators', 'role'),
                ))
                .html_writer::empty_tag('input', array(
                    'type' => 'hidden',
                    'name' => 'contextlevel',
                    'value' => CONTEXT_SYSTEM,
                ))
                .html_writer::empty_tag('input', array(
                    'type' => 'hidden',
                    'name' => 'count',
                    'value' => $admin_cnt,
                ))
                . html_writer::empty_tag('input', array(
                    'type' => 'hidden',
                    'name' => 'component',
                    'value' => 'admin',
                )), ($admin_cnt==1) ? get_string('viewrole', 'tool_uclasupportconsole') :
                get_string('viewroles', 'tool_uclasupportconsole', $admin_cnt));            
        }
        $results[] = $adminrow;

        foreach ($results as $key => $result) {
            if ($result->component == '') {
                $result->component = 'manual';
            }
            $result->contextlevel = context_helper::get_level_name($result->contextlevel);
        }

        $sectionhtml .= supportconsole_render_section_shortcut($title, $results);
    }
}

$consoles->push_console_html('users', $title, $sectionhtml);

////////////////////////////////////////////////////////////////////
$title = "userswithrole";
$sectionhtml = '';
define("RESULTS_PER_PAGE", 30);
if ($consolecommand == "$title") {
    $roleparam = (optional_param('role', null, PARAM_RAW_TRIMMED));
    $componentparam = optional_param('component', null, PARAM_ALPHAEXT);
    $contextlevelparam = optional_param('contextlevel', null, PARAM_INT);
    $countparam = optional_param('count', null, PARAM_INT);
    $typeparam = optional_param('type', null, PARAM_ALPHAEXT);
    $pageparam = optional_param('page', null, PARAM_INT);
    $seeallparam = optional_param('seeall',null, PARAM_INT);

    // Default is only showing the first page.
    if (!isset ($seeallparam)){
        $seeallparam = 0;
    }
    
    // If the page didnt have enough entry, we want to show all from the start.
    if ($countparam <= RESULTS_PER_PAGE) {
        $seeallparam = 1;
    }

    if (!isset($pageparam)) {
        $pageparam = 0;
    }

    $limitstart = $pageparam * RESULTS_PER_PAGE;
    
    if ($componentparam == "manual") {
        $componentparam = "";
    }
    
    $modifiedresults = array();
    
    if ($componentparam == "admin") {
        $adminresult = explode(',', get_config(null, 'siteadmins'));
        foreach ($adminresult as $admin) {
            $sql = "SELECT  id,
                            CONCAT (lastname, ', ', firstname) AS name
                    FROM   {user}
                    WHERE  id = :admin
                    ORDER BY name";
            $params = array('admin' => $admin);
            if (!empty($exportoption) || $seeallparam ) {
                // Do not limit Excel download results.
                $results = $DB->get_records_sql($sql, $params);
            } else {
                $results = $DB->get_records_sql($sql, $params, $limitstart, RESULTS_PER_PAGE);
            }
            $modifiedresults = array_merge($modifiedresults, $results);
        }
        foreach ($modifiedresults as $result) {
            $userurl = new moodle_url("/user/profile.php", array('id' => $result->id));
            $result->name = "<a href=\"".$userurl->out()."\">$result->name</a>";
        }
    } else {
        // Common SQL used by both Course and Category Contexts
        $middlesql = "FROM    {role_assignments} ra 
                      JOIN    {role} r ON 
                              (r.name = :role_param AND 
                               ra.component = :component_param AND 
                               ra.roleid = r.id) 
                      JOIN    {user} u ON (u.id = ra.userid)
                      JOIN    {context} c ON 
                               (c.id = ra.contextid AND
                                c.contextlevel = :contextlevel_param) ";
        if ($typeparam && ($contextlevelparam == CONTEXT_COURSE)) {
            $middlesql .= "JOIN {ucla_siteindicator} si ON
                                   (c.instanceid = si.courseid AND
                                    si.type = :type_param) ";
        }
        if ($contextlevelparam == CONTEXT_COURSECAT) {
            $contexttablename = '{course_categories}';
            $contextnamecolumn = 'name';
            $contextname = "Category_Name";
            $contexturl = "/course/index.php?categoryid=";
            $lookup = "cid";
        } else if ($contextlevelparam == CONTEXT_COURSE) {
            $contexttablename = '{course}';
            $contextnamecolumn = 'shortname';
            $contextname = "Course_Name";
            $contexturl = "/course/view.php?id=";
            $lookup = "cid";
        }
        
        if ($contextlevelparam != CONTEXT_COURSE && $contextlevelparam != CONTEXT_COURSECAT) {
            $sql = "SELECT  ra.id,
                            u.id AS uid,
                            u.lastaccess AS last,
                            CONCAT(u.lastname, ', ', u.firstname) AS name,
                            ra.modifierid,
                            ra.timemodified " . $middlesql .
                   "ORDER BY name";
        } else {
            $sql = "SELECT  ra.id,
                            u.id AS uid,
                            u.lastaccess AS last,
                            CONCAT(u.lastname, ', ', u.firstname) AS name,
                            clevel.id AS cid,
                            clevel.$contextnamecolumn AS cname,
                            ra.modifierid,
                            ra.timemodified " . $middlesql . 
                   "JOIN    $contexttablename clevel ON (clevel.id=c.instanceid)
                    ORDER BY name";
        }
        $params = array('role_param' => $roleparam, 'component_param'=>$componentparam,
                        'contextlevel_param'=>$contextlevelparam, 'type_param'=>$typeparam);
        if (!empty($exportoption) || $seeallparam) {
            // Do not limit Excel download results.
            $results = $DB->get_records_sql($sql, $params);
        } else {
            $results = $DB->get_records_sql($sql, $params, $limitstart, RESULTS_PER_PAGE);
        }

        foreach ($results as $result) {
            $modifiedrow = new stdClass();
            $modifiedrow->id = $result->id;
            $userurl = new moodle_url("/user/profile.php", array('id' => $result->uid));
            $modifiedrow->Name = "<a href=\"".$userurl->out()."\">$result->name</a>";
            if ($contextlevelparam == CONTEXT_COURSE || $contextlevelparam == CONTEXT_COURSECAT) {
                $modifiedrow->$contextname = "<a href=\"$CFG->wwwroot" . $contexturl . $result->$lookup . 
                    '">' . $result->cname . '</a>';
            }
            $modifiedrow->Time_Modified = '<p hidden="hidden">'. $result->timemodified. '</p>'.userdate($result->timemodified);
            
            if ($result->modifierid == 0) {
                $modifiedrow->Assigned_By = 'System';
            } else {
                $sql = "SELECT  CONCAT(u.lastname, ', ', u.firstname) AS name
                        FROM    {user} u 
                        WHERE   u.id = :modifierid";
                $modifierresults = $DB->get_records_sql($sql, array('modifierid' => $result->modifierid));
                $modifier = reset($modifierresults);
                $modifiedrow->Assigned_By = $modifier->name;
            }
            $lastaccess = $result->last;
            if ($lastaccess != 0) {
                $modifiedrow->Last_Access= '<p hidden="hidden">'. $lastaccess. '</p>'.userdate($lastaccess);
            } else {
                $modifiedrow->Last_Access= 'User has not accessed CCLE';
            }
            $modifiedresults[] = $modifiedrow;
        }
    }

    if ($componentparam == "") {
        $componentparam = "manual";
    }

    $inputs = array('role' => $roleparam, 'contextlevel' => $contextlevelparam,
                    'totalcount' => $totalcount,
                    'component' => $componentparam, 'type' => $typeparam);
    $sectionhtml .= supportconsole_render_section_shortcut($title, $modifiedresults, $inputs,
            get_string('usersdescription', 'tool_uclasupportconsole', (object) $inputs));
    $pageurl = new moodle_url( $PAGE->url, array('role' => $roleparam, 'component' => $componentparam, 
        'contextlevel' => $contextlevelparam, 'count' => $countparam, 'type' => $typeparam, 'console' => $title));
    

    // Only show "See All" option when it's not in seeall mode.
    if (!$seeallparam){
        $sectionhtml .= $OUTPUT->paging_bar($countparam, $pageparam, RESULTS_PER_PAGE, $pageurl->out()) ;
        $sectionhtml .= '<div class="see-all"> <a href="'.$pageurl.'&seeall=1">'.get_string('seeall', 'tool_uclasupportconsole').'</a> </div>'; 
    } else if ($countparam > RESULTS_PER_PAGE) {
        $sectionhtml .= '<div class="show-page"> <a href="'.$pageurl.'&seeall=0">'.get_string('showpage', 'tool_uclasupportconsole').'</a> </div>'; 
    }
    $consoles->push_console_html('users', $title, $sectionhtml);
}

////////////////////////////////////////////////////////////////////
$title = "countnewusers";
$sectionhtml = '';
if ($displayforms) { 
    $sectionhtml .= supportconsole_simple_form($title, 
        html_writer::label('Number of users to show', 'count')
            . html_writer::empty_tag('input', array(
                'name' => 'count',
                'type' => 'text',
                'id' => 'count',
                'value' => 20,
                'size' => 3
            )));
// save for later when figure out how sql query should look    <input type="radio" name="radio" value="unique" CHECKED>Unique Logins
} else if ($consolecommand == "$title") { 
    $count = required_param('count', PARAM_INT);
    $distinct = ""; 
    $results = $DB->get_records_sql("
        SELECT 
            id,
            idnumber,
            lastname, 
            firstname,
            IF (timemodified = 0,
                    'Never',
                    FROM_UNIXTIME(timemodified, '%Y-%m-%d')
                ) AS Time_Modified,
            IF (firstaccess = 0,
                    'Never',
                    FROM_UNIXTIME(firstaccess, '%Y-%m-%d')
                ) AS First_Access,
            IF (lastaccess = 0,
                    'Never',
                    FROM_UNIXTIME(lastaccess, '%Y-%m-%d')
                ) AS Last_Access,
            IF (lastlogin = 0,
                    'Never',
                    FROM_UNIXTIME(lastlogin, '%Y-%m-%d')
                ) AS Last_Login
        FROM {user} 
        ORDER BY id DESC 
        LIMIT $count
    ");

    foreach ($results as $k => $result) {
        //$result->delete = html_writer::link(new moodle_url('/admin/user.php',
        //    array('delete' => $result->id)), 'Delete');

        $result->view = html_writer::link(new moodle_url('/user/view.php',
            array('id' => $result->id)), 'View');

        $results[$k] = $result;
    }

    $sectionhtml .= supportconsole_render_section_shortcut($title, $results, array('count' => $count));
}

$consoles->push_console_html('users', $title, $sectionhtml);

////////////////////////////////////////////////////////////////////
$title = "listdupusers";
$sectionhtml = '';

if ($displayforms) {
    $sectionhtml .= supportconsole_simple_form($title);
} else if ($consolecommand == "$title") {
    
    //This SQL query gets all rows that have at least 1 other duplicate email.       
    $dupemails = $DB->get_records_sql("SELECT a.id AS userid, a.username, a.email,
                                              a.idnumber
                                         FROM {user} a
                                         JOIN (SELECT id, email
                                                 FROM {user}
                                             GROUP BY email
                                               HAVING count(*) > 1) b ON a.email = b.email
                                     ORDER BY a.email");
    
    //This gets all rows that have at least 1 other duplicate idnumber.
    $dupids = $DB->get_records_sql("SELECT a.id AS userid, a.username, a.email,
                                           a.idnumber
                                      FROM {user} a
                                      JOIN (SELECT id, idnumber
                                              FROM {user}
                                          GROUP BY idnumber
                                            HAVING count(*) > 1) b ON a.idnumber = b.idnumber
                                  ORDER BY a.idnumber");
    
    //The following blocks of code iterate through the duplicates and populate the
    //result table with row objects, which might have multiple usernames or userids,
    //in which case it will put them all in the same cell.
    $results = array();
 
    $row = new stdClass();
    $row->email = null;
    //Iterate through the duplicate emails and start a new row object whenever the
    //email changes.
    foreach ($dupemails as $k => $dupemail) {
        if(strtolower($row->email) != strtolower($dupemail->email)) {
            if($row->email != null) {
                $results[] = $row;                
            }
            $row = new stdClass();
            //The userid and username must be initialized before email so that the
            //result columns are in the correct order.
            $row->userid = null;
            $row->username = null;
            $row->email = $dupemail->email;
        }
        //If the email is the same, keep appending user information to that row.
        $row->userid .= $dupemail->userid . html_writer::empty_tag('br');
        $row->username .= html_writer::link(new moodle_url('/user/view.php',
                array('id' => $dupemail->userid)), $dupemail->username)
                . html_writer::empty_tag('br');
        if($dupemail->idnumber == null) {
            $row->idnumber .= html_writer::tag('i', 'n/a') . html_writer::empty_tag('br');
        }
        else {
            $row->idnumber .= $dupemail->idnumber . html_writer::empty_tag('br');
        }
    }
    if(isset($row->email)) {
        $results[] = $row;
    }

    $row = new stdClass();
    $row->idnumber = null;
    foreach ($dupids as $k => $dupid) {
        if($row->idnumber != $dupid->idnumber) {
            if($row->idnumber != null) {
                $results[] = $row;                
            }
            $row = new stdClass();
            $row->idnumber = $dupid->idnumber;
        }
        $row->userid .= $dupid->userid . html_writer::empty_tag('br');
        $row->username .= html_writer::link(new moodle_url('/user/view.php',
                array('id' => $dupid->userid)), $dupid->username)
                . html_writer::empty_tag('br');
        $row->email .= $dupid->email . html_writer::empty_tag('br');
    }
    if(isset($row->idnumber)) {
        $results[] = $row;
    }

    $sectionhtml .= supportconsole_render_section_shortcut($title, $results);
}

$consoles->push_console_html('users', $title, $sectionhtml);

////////////////////////////////////////////////////////////////////
$title = "pushgrades";
$sectionhtml = '';
if ($displayforms) {
    $sectionhtml = supportconsole_simple_form($title, 
        html_writer::label('Moodle course.id', 'gradepush-courseid')
            . html_writer::empty_tag('input', array(
                    'type' => 'text',
                    'length' => 10,
                    'id' => 'gradepush-courseid',
                    'name' => 'courseid'
                )));
} else if ($consolecommand == "$title") { 
    $sectionhtml = '';

    $courseid =  required_param('courseid', PARAM_INT);
    $output = null;
    
    exec("php $CFG->dirroot/local/gradebook/cli/grade_push.php $courseid", $output);

    echo html_writer::tag('h1', get_string('pushgrades', 'tool_uclasupportconsole'));
    echo "<pre>";
    echo implode("\n", $output);
    echo "</pre>";
    
    $consoles->no_finish = true;
}

$consoles->push_console_html('users', $title, $sectionhtml);

////////////////////////////////////////////////////////////////////
$title = "tasites";
$sectionhtml = '';

if ($displayforms) {
    $sectionhtml = supportconsole_simple_form($title, get_term_selector($title));
} else if ($consolecommand == "$title") {
    $selectedterm = required_param('term', PARAM_ALPHANUM);
    $sql = "SELECT DISTINCT tasite.id, tasite.shortname, tasite.fullname
                       FROM {course} tasite
                       JOIN {ucla_siteindicator} si ON si.courseid = tasite.id
                       JOIN {enrol} meta ON meta.courseid = tasite.id
                       JOIN {course} c ON c.id = meta.customint1
                       JOIN {ucla_request_classes} urc ON urc.courseid = c.id
                      WHERE si.type = 'tasite'
                            AND urc.term = :selectedterm";
    $results = $DB->get_records_sql($sql, array('selectedterm' => $selectedterm));
    
    $newresults = array();
    foreach ($results as $k => $result) {
        $result->shortname = html_writer::link(new moodle_url('/course/view.php',
                array('id' => $result->id)), $result->shortname,
                array('target' => '_blank'));
        $newresults[$k] = $result;
    }
    
    $sectionhtml .= supportconsole_render_section_shortcut($title, $newresults);
}
$consoles->push_console_html('users', $title, $sectionhtml);

////////////////////////////////////////////////////////////////////
$title = "mediausage";
$sectionhtml = '';
if ($displayforms) {
    $content = html_writer::tag('p',
            get_string('mediausage_help', 'tool_uclasupportconsole')) .
            get_term_selector($title);

    $sectionhtml .= supportconsole_simple_form($title, $content);
} else if ($consolecommand == "$title") {  # tie-in to link from name lookup
    $term = optional_param('term', null, PARAM_ALPHANUM);
    $courseid = optional_param('courseid', null, PARAM_INT);

    $params = array('contextlevel' => CONTEXT_MODULE);
    if (!empty($term)) {
        $params['term'] = $term;

        // Get aggregate video totals.
        $sql = "SELECT  DISTINCT 
                        c.id AS idx,
                        urci.division,
                        c.id AS course,
                        c.shortname,
                        COUNT(f.id) AS numvideos,
                        SUM(f.filesize) AS size
                FROM    {course} c
                JOIN    {ucla_request_classes} urc ON (urc.courseid=c.id AND urc.hostcourse=1)
                JOIN    {ucla_reg_classinfo} urci ON (urci.term=urc.term AND urci.srs=urc.srs)
                JOIN    {course_modules} cm ON (cm.course=c.id)
                JOIN    {context} ct ON (ct.instanceid=cm.id AND ct.contextlevel=:contextlevel)
                JOIN    {files} f ON (f.contextid=ct.id)
                JOIN    {modules} m ON (m.id=cm.module)
                WHERE   urc.term=:term AND
                        f.mimetype LIKE 'video/%'
                GROUP BY c.id
                ORDER BY    urci.division, urci.term, urci.subj_area, urci.crsidx, urci.secidx";
    } else if (!empty($courseid)) {
        $params['courseid'] = $courseid;

        // Get videos for specific course.
        $sql = "SELECT  DISTINCT f.id AS idx,
                        f.filename,
                        m.name,
                        cm.section,
                        f.filesize AS size,
                        cm.id
                FROM    {course} c
                JOIN    {course_modules} cm ON (cm.course=c.id)
                JOIN    {context} ct ON (ct.instanceid=cm.id AND ct.contextlevel=:contextlevel)
                JOIN    {files} f ON (f.contextid=ct.id)
                JOIN    {modules} m ON (m.id=cm.module)
                WHERE   c.id=:courseid AND
                        f.mimetype LIKE 'video/%'
                ORDER BY    f.filename";
    } else {
        print_error('missingparameter');
    }

    $results = $DB->get_records_sql($sql, $params);

    foreach ($results as $key => $result) {
        // Display both display and sort friendly versions of file size.
        if (isset($result->size)) {
            if (isset($result->course)) {
                $result->displaysize = html_writer::link(new moodle_url(
                        '/'.$CFG->admin.'/tool/uclasupportconsole/index.php',
                        array('console' => $consolecommand, 'courseid' => $result->course)),
                        display_size($result->size));
            } else {
                $result->displaysize = display_size($result->size);
            }
            $result->sortablsizeinmb = round($result->size / 1048576 * 10) / 10;
            unset($result->size);
        }

        // Give link to section where module exists.
        if (isset($result->section)) {
            $result->name = html_writer::link(new moodle_url(
                    '/course/view.php', array('id' => $courseid,
                        'sectionid' => $result->section)
                ), $result->name, array('target' => '_blank'));
            unset($result->section);
        }

        // Replace courseid with link to course.
        if (isset($result->course)) {
            $result->course = html_writer::link(new moodle_url(
                    '/course/view.php', array('id' => $result->course)
                ), $result->shortname, array('target' => '_blank'));
            unset($result->shortname);
        }

        // Give link to video.
        if (isset($result->id)) {
            $result->filename = html_writer::link(new moodle_url(
                    sprintf('/mod/%s/view.php?id=%d', $result->name, $result->id)),
                    $result->filename, array('target' => '_blank'));
            unset($result->id);
        }

        unset($result->idx);

        $results[$key] = $result;
    }

    if (!empty($courseid)) {
        // Display course shortname if viewing a particular course.
        $course = $DB->get_record('course', array('id' => $courseid));

        $params['courseid'] = html_writer::link(new moodle_url(
                    '/course/view.php', array('id' => $courseid)),
                $course->shortname,
                array('target' => '_blank'));
    }

    unset($params['contextlevel']);
    $sectionhtml .= supportconsole_render_section_shortcut($title, $results,
        $params, get_string('mediausage_help', 'tool_uclasupportconsole'));
}
$consoles->push_console_html('modules', $title, $sectionhtml);

///////////////////////////////////////////////////////////////
$title = "visiblecontentlist";
$sectionhtml = '';
if ($displayforms) {
    $content = get_term_selector($title);
    $sectionhtml .= supportconsole_simple_form($title, $content);
} else if ($consolecommand == "$title") {
    $term = required_param('term', PARAM_ALPHANUM);
    $params = array('contextlevel' => CONTEXT_MODULE);
    if(!empty($term)) {
        $params['term'] = $term;
        // Table of relevant courses and their srs.
        $classsql = "SELECT m.id, rc.term, regd.fullname AS division, c.shortname,
                            count(m.course) AS count, c.id as instructors
                       FROM {course_modules} m
                       JOIN {course_sections} s ON m.section = s.id AND m.course = s.course
                       JOIN {course} c ON s.course = c.id                       
                       JOIN {ucla_request_classes} rc ON (c.id = rc.courseid AND
                                                          rc.hostcourse=1)
                       JOIN {ucla_reg_classinfo} regc ON (rc.srs = regc.srs AND
                                                          rc.term = regc.term)
                       JOIN {ucla_reg_division} regd ON regc.division = regd.code
                      WHERE m.visible = 1 AND s.visible = 0 AND rc.term = :term
                   GROUP BY m.course";

        // Table of instructors and contact info.
        $instrsql = "SELECT ra.id, c.id AS courseid, 
                            u.firstname,
                            u.lastname,
                            u.email,
                            u.firstnamephonetic,
                            u.lastnamephonetic,
                            u.middlename,
                            u.alternatename
                       FROM {role_assignments} ra
                       JOIN {user} u ON ra.userid = u.id
                       JOIN {role} r ON ra.roleid = r.id
                       JOIN {context} co ON ra.contextid = co.id
                       JOIN {course} c ON co.instanceid = c.id
                      WHERE r.shortname = :shortname";
    }

    $results = $DB->get_records_sql($classsql, $params);
    $instrs = $DB->get_records_sql($instrsql, array('shortname' => 'editinginstructor'));

    foreach($results as $key => $result) {
        $result->shortname = html_writer::link(new moodle_url('/course/view.php',
                array('id' => $result->instructors)), $result->shortname);

        // For each of the instructor rows, if the srs matches that of $result,
        // append to instr string with their contact info. The srs of $result is 
        // stored in the instructors variable so that instructor info can 
        // replace the srs once it is used.
        $instrstr = "";
        foreach($instrs as $ikey => $instr) {
            if($instr->courseid == $result->instructors) {
                // Reach here if $instr teaches this course.
                $instrstr .= html_writer::link("mailto:" . $instr->email,
                        fullname($instr)) . html_writer::empty_tag('br');
            }
        }
        $result->instructors = $instrstr;
        $results[$key] = $result;
    }
    unset($params['contextlevel']);
    $sectionhtml .= supportconsole_render_section_shortcut($title, $results);
}
$consoles->push_console_html('modules', $title, $sectionhtml);
///////////////////////////////////////////////////////////////
$title = "unhiddencourseslist";
$sectionhtml = '';
if ($displayforms) {
    $content = html_writer::tag('p',
            get_string('unhiddencourseslist_help', 'tool_uclasupportconsole')) .
            get_term_selector($title);
    $sectionhtml .= supportconsole_simple_form($title, $content);
} else if ($consolecommand == "$title") {
    $term = required_param('term', PARAM_ALPHANUM);
    $params['term'] = $term;
    if (term_cmp_fn($term, $CFG->currentterm) < 0) {
        // Show visible courses for past terms.
        $params['visible'] = 1;
    } else {
        // Show hidden courses for current and future terms.
        $params['visible'] = 0;
    }

    $sql = "SELECT DISTINCT c.id, c.shortname
                   FROM {course} c
                   JOIN {ucla_request_classes} urc
                     ON (c.id = urc.courseid AND
                         urc.term = :term)
                   JOIN {ucla_reg_classinfo} urci
                     ON (urci.term = urc.term AND
                         urci.srs = urc.srs AND
                         urci.enrolstat <> 'X')
                  WHERE c.visible = :visible";
    $results = $DB->get_records_sql($sql, $params);

    foreach($results as $key => $result) {
        $result->shortname = html_writer::link(new moodle_url('/course/view.php',
                array('id' => $result->id)), $result->shortname,
                array('target' => '_blank'));
    }
    unset($params['visible']);
    $sectionhtml .= supportconsole_render_section_shortcut($title, $results,
            $params, get_string('unhiddencourseslist_help', 'tool_uclasupportconsole'));
}
$consoles->push_console_html('modules', $title, $sectionhtml);

///////////////////////////////////////////////////////////////
$title = "coursedownloadhistory";
$sectionhtml = '';
if ($displayforms) {
    $content = get_term_selector($title);
    $sectionhtml .= supportconsole_simple_form($title, $content);
} else if ($consolecommand == "$title") {
    $params = array('term' => required_param('term', PARAM_ALPHANUM));

    // Get list of courses with requests, ordered by subject area. Join the
    // "ucla_archives" table with the "course" and "ucla_reg_classinfo" tables
    // to display courses and subject area.
    //
    // Use the 'ucla_request_classes' table to map Moodle course IDs to UCLA
    // course terms and SRSs. Create a SQL query which selects information from
    // 'ucla_reg_classinfo', where the terms and SRS are equal to those of a
    // subquery. The subqeury will select terms and SRSs from the
    // 'ucla_request_classes' where the course ID is equal to those in
    // 'ucla_archives'.
    //
    // Run a SQL query to get all the course IDs, user IDs, timestamps, and activeness
    // of each request in the 'ucla_archives' table.
    $sql = "SELECT ua.id,
                   urci.division,
                   c.id AS courseid,
                   c.shortname AS course,
                   COUNT(ua.courseid) AS '" . get_string('coursedownloadhistorytotalrequests', 'tool_uclasupportconsole') . "',
                   SUM(ua.numdownloaded) AS '" . get_string('coursedownloadhistorynumdownloaded', 'tool_uclasupportconsole') . "',
                   SUM(IF(ua.fileid IS NULL, 0, f.filesize)) AS filesize,
                   SUM(IF(ua.numdownloaded, 0, 1)) AS '" . get_string('coursedownloadhistorynumnotdownloaded', 'tool_uclasupportconsole') . "',
                   COUNT(DISTINCT ua.contexthash) AS '" . get_string('coursedownloadhistoryuniquezipfile', 'tool_uclasupportconsole') . "'
              FROM {ucla_archives} ua
              JOIN {ucla_request_classes} urc ON ua.courseid = urc.courseid
              JOIN {ucla_reg_classinfo} urci ON (urc.term = urci.term AND urc.srs = urci.srs)
              JOIN {course} c ON (urc.courseid = c.id)
         LEFT JOIN {files} f ON ua.fileid = f.id
             WHERE urc.term=:term AND
                   urci.enrolstat!='X'
          GROUP BY ua.courseid
          ORDER BY urci.division,urci.subj_area, crsidx, secidx";
    $results = $DB->get_records_sql($sql, $params);

    foreach ($results as &$result) {
        $result->course = html_writer::link(new moodle_url('/course/view.php',
                array('id' => $result->courseid)), $result->course);
        unset($result->courseid);

        // Give user friendly sizes.
        $result->storagesize = display_size($result->filesize);
        $result->sortablestoragesize = round($result->filesize / 1048576 * 10) / 10;
        unset($result->filesize);
    }

    $sectionhtml .= supportconsole_render_section_shortcut($title, $results, $params);
}

$consoles->push_console_html('modules', $title, $sectionhtml);

//
//
///////////////////////////////////////////////////////////////

// see if user came from a specific page, if so, then direct them back there
$gobackurl = $PAGE->url;
if (!empty($_SERVER['HTTP_REFERER'])) {
    // make sure link came from same server
     if (strpos($_SERVER['HTTP_REFERER'], $CFG->wwwroot) !== false) {
         $gobackurl = $_SERVER['HTTP_REFERER'];
     }
}

if (isset($consoles->no_finish)) {
    echo html_writer::link(new moodle_url($gobackurl),
            get_string('goback', 'tool_uclasupportconsole'));
    die();
}

echo $OUTPUT->header();

// Heading

// Add 'top' id to header as anchor link to return to top
echo $OUTPUT->heading(get_string('pluginname', 'tool_uclasupportconsole'), 2, 'headingblock', 'top');

if (!$displayforms) {
    echo html_writer::link(new moodle_url($gobackurl), 
            get_string('goback', 'tool_uclasupportconsole'));
    echo $consoles->render_results();
} else {
    // Put srdb stuff first
    $consoles->sort(
        array(
            'srdb' => array(
                'ccle_getclasses' => ''
            )
        )
    );

    echo $consoles->render_forms();
}
echo $OUTPUT->footer();
