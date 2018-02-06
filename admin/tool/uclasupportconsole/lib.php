<?php

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/' . $CFG->admin . '/tool/uclasupportconsole/manager.class.php');

/**
 * Generates HTML output for display of export options for given support console
 * output.
 *
 * @param array $params Params for to generate report. Will append 'export=xls'.
 * @return string
 */
function display_uclasupportconsole_export_options($params) {
    global $CFG, $OUTPUT;
    $exportoptions = html_writer::start_tag('div',
            array('class' => 'export-options'));
    $exportoptions .= get_string('exportoptions', 'tool_uclasupportconsole');

    // Right now, only supporting xls.
    $xlsstring = get_string('application/vnd.ms-excel', 'mimetypes');
    $icon = html_writer::img($OUTPUT->image_url('f/spreadsheet'), $xlsstring, array('title' => $xlsstring));
    $params['export'] = 'xls';
    $exportoptions .= html_writer::link(
            new moodle_url('/'.$CFG->admin.'/tool/uclasupportconsole/index.php',
                    $params), $icon);

    $exportoptions .= html_writer::end_tag('div');
    return $exportoptions;
}

/**
 * Returns all available registrar queries
 * 
 * @return array        Array of registrar queries avialble 
 */
function get_all_available_registrar_queries() {
    global $CFG;
    
    $dirname = $CFG->dirroot .'/local/ucla/registrar';
    $qfs = glob($dirname . '/*.class.php');

    $queries = array();
    foreach ($qfs as $query) {
        if ($query == __FILE__) {
            continue;
        }

        $query = str_replace($dirname . '/registrar_', '', $query);
        $query = str_replace('.class.php', '', $query);
        $queries[] = $query;
    }

    return $queries;    
}

/**
 * Generates appropiate browseby link
 * 
 * @param string $type      Type of browseby link to generate: subjarea or
 *                          division
 * @param array $params     Needed params to generate link. Different depending
 *                          on type of link
 * 
 * @return string           Returns html link text
 */
function get_browseby_link($type, $params) {
    $ret_val = '';
    if ($type == 'subjarea') {
        /* Expects params to have: subjarea, term, display_string
         */
        $ret_val = html_writer::link(new moodle_url('/blocks/ucla_browseby/view.php',
                array('subjarea' => $params['subjarea'],
                      'term' => $params['term'],
                      'type' => 'course')), $params['display_string'],
                array('target' => '_blank'));
    } else if ($type == 'division') {
        /* Expects params to have: division, term, display_string
         */
        $ret_val = html_writer::link(new moodle_url('/blocks/ucla_browseby/view.php',
                array('division' => $params['division'],
                      'term' => $params['term'],
                      'type' => 'subjarea')), $params['display_string'],
                array('target' => '_blank'));
    }
    return $ret_val;
}

/**
 * Generates input field for generic text type.
 *
 * @param string $id
 * @return string       Returns HTML to render text input.
 */
function get_generic_input($id, $name) {
    $retval = html_writer::label($name, $id.'_'.$name);
    $retval .= html_writer::empty_tag('input',
            array('type' => 'text', 'name' => $name, 'id' => $id.'_'.$name));
    return $retval;
}

/**
 * Generates input field for SRS number
 * 
 * @param string $id        Id to use for label
 * 
 * @return string           Returns HTML to render SRS input 
 */
function get_srs_input($id) {
    $ret_val = html_writer::label(get_string('srs', 
            'tool_uclasupportconsole'), $id.'_srs');
    $ret_val .= html_writer::empty_tag('input', 
            array('type' => 'text', 'name' => 'srs', 'id' => $id.'_srs'));        
    return $ret_val;
}

/**
 * Either creates or returns a subject area selector dropdown.
 * 
 * @param string $id        Id to use for label.
 * @param string $selectedsubjectarea Default subject area selected.
 * 
 * @return string           Returns HTML to render subject area dropdown.
 */
function get_subject_area_selector($id, $selectedsubjectarea = null) {
    global $DB;  
    static $_subjectareaselectorsubjects;  // To store cached copy of db record.
    $retval = '';
  
    if (!isset($_subjectareaselectorsubjects)) {
        // Generate associative array: subject area => subject area.
        $sql = 'SELECT DISTINCT subjarea
                  FROM {reg_subjectarea}
                 WHERE 1
              ORDER BY subjarea';
        $_subjectareaselectorsubjects = $DB->get_records_menu('ucla_reg_subjectarea',
                null, 'subjarea', 'subjarea, subjarea AS subject_area');
        if (empty($_subjectareaselectorsubjects)) {
            return '';
        }        
    }

    $retval .= html_writer::label(get_string('subject_area',
            'tool_uclasupportconsole'), $id.'_subject_area_selector');
    $retval .= html_writer::select($_subjectareaselectorsubjects,
            'subjarea', $selectedsubjectarea,
            get_string('choose_subject_area', 'tool_uclasupportconsole'), 
            array('id' => $id.'_subject_area_selector'));
        
    return $retval;
}

/**
 * Either creates or returns a term selector dropdown.
 * 
 * @global object $DB
 * 
 * @param string $id        Id to use for label
 * @param string $selected_term If passed, will be the default term selected
 * 
 * @return string           Returns HTML to render term dropdown 
 */
function get_term_selector($id, $selected_term = null) {
    global $CFG, $DB;  
    $ret_val = '';
    
    if (!ucla_validator('term', $selected_term)) {
        $selected_term = $CFG->currentterm;
    }

    $ret_val .= html_writer::label(get_string('term', 'tool_uclasupportconsole'), $id.'_term_selector');
    $ret_val .= html_writer::empty_tag('input', 
            array('type' => 'text', 'name' => 'term', 'id' => $id.'_term_selector', 
                'value' => $selected_term, 'maxlength' => 3, 'size' => 3));
    
    return $ret_val;
}

/**
 * Generates input field for UID number
 * 
 * @param string $id        Id to use for label
 * 
 * @return string           Returns HTML to render UID input 
 */
function get_uid_input($id) {
    $ret_val = html_writer::label(get_string('uid', 
            'tool_uclasupportconsole'), $id.'_uid');
    $ret_val .= html_writer::empty_tag('input', 
            array('type' => 'text', 'name' => 'uid', 'id' => $id.'_uid'));        
    return $ret_val;
}

/**
 *  This function auto-strips 'id' from the data.
 **/
function html_table_auto_headers($data) {
    $fields = array();
    foreach ($data as $datum) {
        foreach ($datum as $f => $v) {
            if ($f == 'id') {
                continue;
            }

            $fields[$f] = $f;
        }
    }
    
    $paddeddata = array();
    foreach ($data as $datum) {
        $paddeddatarow = array();
        foreach ($fields as $field) {
            if (is_object($datum)) {
                $datum = get_object_vars($datum);
            }

            $fieldvalue = '';
            if (isset($datum[$field])) {
                $fieldvalue = $datum[$field];
            }

            $paddeddatarow[$field] = $fieldvalue;
        }

        $paddeddata[] = $paddeddatarow;
    }

    $table = new html_table();
    $table->head = $fields;
    $table->data = $paddeddata;

    return $table;
}

/**
 * Displays given data and inputs in a nice table. Also handles if given data
 * array is empty.
 *
 * @param string $title
 * @param array $data
 * @param array $inputs     must be in format of params passed to a moodle_url
 *                              constructor
 * @param string $moreinfo  text to be displayed above the table
 * @return string
 */
function supportconsole_render_section_shortcut($title, $data, 
                                                $inputs=array(), $moreinfo=null) {
    global $OUTPUT;

    // Check if user wanted an Excel download instead.
    $export = optional_param('export', null, PARAM_ALPHA);
    if ($export == 'xls') {
        supportconsole_render_section_xls($title, $data, $inputs, $moreinfo);
    }

    $size = 0;
    if (!empty($data)) {
        $size = count($data);
    }

    // Display number of results.
    if ($size == 0) {
        $pretext = get_string('noresults', 'tool_uclasupportconsole');
    } else if ($size == 1) {
        $pretext = get_string('oneresult', 'tool_uclasupportconsole');
    } else if (isset($inputs['totalcount']) && $inputs['totalcount'] > $size) {
        $pretext = get_string('paginatedxresults', 'tool_uclasupportconsole',
            array('pagecount' => $size, 'totalcount' => $inputs['totalcount']));
    } else {
        $pretext = get_string('xresults', 'tool_uclasupportconsole', $size);
    }

    // Display input.
    if (!empty($inputs)) {
        // Not every support console tool as input.
        if (!is_array($inputs)) {
            $inputs = (array) $inputs;
        }        
        $pretext .= get_string('forinput', 'tool_uclasupportconsole',
                implode(', ', $inputs));
    }

    // Export options.
    $params = $inputs;
    $params['console'] = $title;
    $export = display_uclasupportconsole_export_options($params);

    // Only display table if there is data to display.
    if (empty($data)) {
        return $OUTPUT->box($pretext) . $export;
    } else if ($moreinfo != null) {
        return $OUTPUT->box($moreinfo) . $OUTPUT->box($pretext) .
                supportconsole_render_table_shortcut($data) . $export;
    } else {
        return $OUTPUT->box($pretext) .
                supportconsole_render_table_shortcut($data) . $export;
    }
}

/**
 * Outputs given data and inputs in an Excel file.
 *
 * @param string $title
 * @param array $data
 * @param array $inputs
 * @param string $moreinfo
 * @return string
 */
function supportconsole_render_section_xls($title, $data, $inputs=array(), $moreinfo=null) {
    global $CFG;
    require_once($CFG->dirroot.'/lib/excellib.class.php');

    // Might have HTML.
    $fulltitle = clean_param(get_string($title, 'tool_uclasupportconsole'), PARAM_NOTAGS);
    $filename = clean_filename($title . '.xls');

    // Creating a workbook (use "-" for writing to stdout).
    $workbook = new MoodleExcelWorkbook("-");
    // Sending HTTP headers.
    $workbook->send($filename);
    // Adding the worksheet.
    $worksheet = $workbook->add_worksheet($fulltitle);
    $boldformat = $workbook->add_format();
    $boldformat->set_bold(true);
    $row = $col = 0;

    // Add title.
    $worksheet->write_string($row, $col, $fulltitle, $boldformat);
    ++$row;

    // Check if there is moreinfo needed.
    $moreinfo = clean_param($moreinfo, PARAM_NOTAGS);  // Might have HTML.
    $worksheet->write_string($row, $col, $moreinfo);
    ++$row;

    // Display number of results.
    $size = count($data);
    if ($size == 0) {
        $pretext = get_string('noresults', 'tool_uclasupportconsole');
    } else if ($size == 1) {
        $pretext = get_string('oneresult', 'tool_uclasupportconsole');
    } else {
        $pretext = get_string('xresults', 'tool_uclasupportconsole', $size);
    }

    // Display input.
    if (!empty($inputs)) {
        // Not every support console tool has input.
        if (!is_array($inputs)) {
            $inputs = (array) $inputs;
        }
        $pretext .= get_string('forinput', 'tool_uclasupportconsole',
                implode(', ', $inputs));
    }
    $worksheet->write_string($row, $col, $pretext);
    ++$row;

    $table = html_table_auto_headers($data);

    // Display table header.
    $header = $table->head;
    foreach ($header as $name) {
        $worksheet->write_string($row, $col, $name, $boldformat);
        ++$col;
    }

    // Now go through the data set.
    $results = $table->data;
    foreach ($results as $result) {
        ++$row; $col = 0;
        foreach ($result as $value) {
            // Values might have HTML in them.
            $value = clean_param($value, PARAM_NOTAGS);
            if (is_numeric($value)) {
                $worksheet->write_number($row, $col, $value);
            } else {
                $worksheet->write_string($row, $col, $value);
            }
            ++$col;
        }
    }

    // Close the workbook.
    $workbook->close();

    // If we are in the command line, don't die.
    if (!defined('CLI_SCRIPT') || !CLI_SCRIPT) {
        exit;
    }
}

/**
 * Converts given $data array into a table.
 * 
 * @param array $data
 * @return string
 */
function supportconsole_render_table_shortcut($data) {
    $table = html_table_auto_headers($data);
    $table->id = setup_js_tablesorter();

    return html_writer::table($table);

}

function supportconsole_simple_form($title, $contents='', $buttonvalue='Go') {
    global $PAGE;
    $formhtml = html_writer::start_tag('form', array(
            'method' => 'post',
            'action' => $PAGE->url
        ));

    $formhtml .= $contents;
    $formhtml .= html_writer::empty_tag('input', array(
            'type' => 'hidden',
            'name' => 'console',
            'value' => $title
        ));
    $formhtml .= html_writer::empty_tag('input', array(
            'type' => 'submit',
            'name' => 'submit-button',
            'value' => $buttonvalue,
            'class' => 'btn btn-primary'
        ));

    $formhtml .= html_writer::end_tag('form');

    return $formhtml;
}

/*
 * This function caches the target_action list used by the support console's
 * "Show last 100 log entries" tool at some interval (nightly at the time of
 * this writing). See version.php for cron time.
 */
function tool_uclasupportconsole_cron() {
    global $DB;

    // Query the log for all target_action pairs (the id field must be included
    // to give moodle a unique identifier for the records).
    $logquery = 'SELECT DISTINCT CONCAT(target, \'-\', action), target, action
                 FROM {logstore_standard_log}
                 GROUP BY target, action
                 ORDER BY target, action';
    $records = $DB->get_records_sql($logquery);
    $encoding = json_encode($records);

    // Put the encoding into $CFG
    set_config('moduleactionpairs',$encoding, 'tool_uclasupportconsole');
}
