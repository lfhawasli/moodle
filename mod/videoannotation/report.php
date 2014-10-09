<?php

    require_once("../../config.php");
    require_once("lib.php");
    require_once($CFG->libdir.'/moodlelib.php');
    require_once($CFG->libdir.'/formslib.php');
    require_once($CFG->libdir.'/tablelib.php');

    $id   = optional_param('id', 0, PARAM_INT);          // Course module ID
    $a    = optional_param('a', 0, PARAM_INT);           // Video annotation ID
    $mode = optional_param('mode', 'all', PARAM_ALPHA);  // What mode are we in?
    $groupid = optional_param('group', null, PARAM_INT);
    global $DB, $PAGE;
    if ($id) {
        if (! $cm = get_coursemodule_from_id('videoannotation', $id)) {
            print_error("coursemodidincorrect", "videoannotation");
        }
    } else {
        if (!$videoannotation = $DB->get_record("videoannotation", array("id"=>$a))) {
            print_error("coursemodincorrect", "videoannotation");
        }
    }
    
    if (! $videoannotation = $DB->get_record("videoannotation", array("id"=>$cm->instance))) {
        print_error("vaidincorrect", "videoannotation");
    }
    
    if (! $course = $DB->get_record("course", array("id"=>$videoannotation->course))) {
        print_error("coursemisconfigure", "videoannotation");
    }
    
    if (! $cm = get_coursemodule_from_instance("videoannotation", $videoannotation->id, $course->id)) {
        print_error("coursemodidincorrect", "videoannotation");
    }
    
    if (! $module_context = context_module::instance($cm->id)) {
        print_error("coursemisconfigure", "videoannotation");
    }
    
    require_login($course->id, false, $cm);
    //Print page header
    $PAGE->set_url('/mod/videoannotation/report.php', array('id'=>$cm->id));
    $PAGE->set_title('REPORT');
    $PAGE->set_pagetype('course');
    $PAGE->set_heading('VA');
    echo $OUTPUT->header();
    require_capability('mod/videoannotation:view', context_module::instance($cm->id));
    
    //
    
    add_to_log($course->id, "videoannotation", "report", "report.php?id=$cm->id", "$videoannotation->id");
    
    // Print a link to download the report
    
    echo '<div style="text-align: right"><a href="' . $CFG->wwwroot . '/mod/videoannotation/report_download.php?id=' . (int) $id . '&group=' .(int) $groupid .'">' . get_string('downloadreport', 'videoannotation') . '</a></div>';
    
    //
    
    
    $table = new flexible_table('mod-assignment-submissions');
    $table->collapsible(true);
    $table->set_attribute('cellspacing', '0');
    $table->set_attribute('id', 'attempts');
    $table->set_attribute('class', 'submissions');
    $table->set_attribute('width', '100%');

    $has_grade_capability = has_capability('mod/videoannotation:grade', $module_context);
    $has_submit_capability = has_capability('mod/videoannotation:submit', $module_context);
    
    // If the user has grade capability
    // then the table contains user name and picture
    
    if ($videoannotation->groupmode != NOGROUPS) {
        $columns = array('group_name');
        $headers = array('group_name');
        
        $table->sortable(true);
    } else if ($has_grade_capability) {
        $columns = array('picture', 'fullname');
        $headers = array('', get_string('fullname'));
        
        $table->sortable(true, 'lastname'); //sorted by lastname by default
        $table->column_suppress('picture');
        $table->column_suppress('fullname');
        $table->initialbars(true);
    } else {
        $columns = array();
        $headers = array();
        
        $table->sortable(true);
    }
    
    $columns = array_merge($columns, array('clip_url', 'clip_start', 'clip_end', 'tag_name', 'color', 'event_start', 'event_end', 'event_content'));
    $headers = array_merge($headers, array(
    get_string('clipurl', 'videoannotation'),
    get_string('playabletimestart', 'videoannotation'),
    get_string('playabletimeend', 'videoannotation'),
    get_string('tagname', 'videoannotation'),
    get_string('color', 'videoannotation'),
    get_string('eventstart', 'videoannotation'),
    get_string('eventend', 'videoannotation'),
    get_string('eventcontent', 'videoannotation')
    ));
    
    $table->define_columns($columns);
    $table->define_headers($headers);
    
    // Set up the table
    // Must be done before $table->get_sql_where() and $table->get_sql_sort() are called
   
    $table->define_baseurl($PAGE->url); 
    $table->setup();
    
    // Get report data
    
    switch ($videoannotation->groupmode) {
        case NOGROUPS:
            $sql = "SELECT u.id AS user_id, u.firstname, u.lastname, 
                    c.url AS clip_url, c.playabletimestart AS clip_start, 
                    c.playabletimeend AS clip_end, t.name AS tag_name, 
                    t.color AS color, e.starttime AS event_start, 
                    e.endtime AS event_end, e.content AS event_content
            FROM {$CFG->prefix}videoannotation_clips c
            JOIN {$CFG->prefix}videoannotation_tags t ON c.id = t.clipid
            JOIN {$CFG->prefix}videoannotation_events e ON t.id = e.tagid
            JOIN {$CFG->prefix}user u
            WHERE c.videoannotationid = " . (int) $videoannotation->id . "
            AND u.id = t.userid AND t.userid = e.userid AND t.groupid IS NULL AND e.groupid IS NULL";
            
            if (!$has_grade_capability)
                $sql .= ' AND u.id = ' . (int) $USER->id;
            break;
        
        case VIDEOANNOTATION_GROUPMODE_GROUP_USER:    
        case VIDEOANNOTATION_GROUPMODE_GROUP_GROUP:
            $sql = "SELECT g.id AS group_id, g.name AS group_name, 
                    c.url AS clip_url, c.playabletimestart AS clip_start, 
                    c.playabletimeend AS clip_end, t.name AS tag_name, 
                    t.color AS color, e.starttime AS event_start, 
                    e.endtime AS event_end, e.content AS event_content
            FROM {$CFG->prefix}videoannotation_clips c
            JOIN {$CFG->prefix}videoannotation_tags t ON c.id = t.clipid
            JOIN {$CFG->prefix}videoannotation_events e ON t.id = e.tagid
            JOIN {$CFG->prefix}groups g
            WHERE c.videoannotationid = " . (int) $videoannotation->id;

            global $USER;

//changing to only display information for the current group
            $sql .= ' AND g.id = ' . $groupid . ' AND t.groupid = g.id AND e.groupid = g.id'; 
            break;
        default:
            error('Invalid group mode');
    }
    
    if ($where = $table->get_sql_where() && isset($where)) {
        $sql .= ' AND ' . $where;
    }

    if ($sort = $table->get_sql_sort('mod-videoannotation-report')) {
        $sql .= ' ORDER BY ' . $sort;
    }
    // Add data to the table
    
    if ($rs = $DB->get_recordset_sql($sql,null, $table->get_page_start(), $table->get_page_size())) {
        foreach($rs as $record) {
            if ($videoannotation->groupmode != NOGROUPS) {
                $data = array($record->group_name);
            } else if ($has_grade_capability) {
                $auser = new stdClass();
                $sql = "SELECT u.id AS id, u.firstname, u.lastname, u.email, u.picture, u.imagealt
                        FROM mdl_user u
                        WHERE u.id = " . $record->user_id . " ";
                $auser = $DB->get_record_sql($sql);
                $picture = $OUTPUT->user_picture($auser, array('courseid' => $course->id, 'link'=>true, 'size'=>0));
                $userlink = '<a href="' . $CFG->wwwroot . '/user/view.php?id=' . $record->user_id . '&amp;course=' . $course->id . '">' . fullname($record) . '</a>';
                $data = array($picture, $userlink);
            } else {
                $data = array();
            }
            
            $data = array_merge($data, array($record->clip_url, $record->clip_start, $record->clip_end, $record->tag_name, $record->color, $record->event_start, $record->event_end, $record->event_content));
            $table->add_data($data);
        }
        $rs->close();
    }
    
    $table->print_html();

    /// Finish the page
    echo $OUTPUT->footer();

?>
