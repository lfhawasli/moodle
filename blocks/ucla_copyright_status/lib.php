<?php
/**
 * Library file for UCLA Manage copyright status
 *
 * @package    ucla
 * @subpackage ucla_copyright_status
 * @copyright  2012 UC Regents    
 * @author     Jun Wan <jwan@humnet.ucla.edu>                                  
 */
defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/licenselib.php');

/*
 * Initializes all $PAGE variables.
 */
function init_copyright_page($course, $courseid, $context) {
    global $PAGE;
    $PAGE->set_url('/blocks/ucla_copyright_status/view.php',
            array('courseid' => $courseid));

    $page_title = $course->shortname . ': ' . get_string('pluginname',
                    'block_ucla_copyright_status');

    $PAGE->set_context($context);
    $PAGE->set_title($page_title);

    $PAGE->set_heading($course->fullname);

    $PAGE->set_pagelayout('course');
    $PAGE->set_pagetype('course-view-' . $course->format);
}

/*
 * Retrive copyright information for all the files uploaded through add a
 * resource.
 *
 * @param int $courseid
 * @param string $filter    Defaults to 'all'. Otherwise, should be copyright
 *                          license shortname.
 * @return array            Returns an array of filename, author and copyright
 *                          information.
 */
function get_files_copyright_status_by_course($courseid, $filter = 'all') {
    global $DB, $CFG;

    // Cache results
    static $output = array();

    $params = array(CONTEXT_MODULE, $courseid);
    if (!isset($output[$courseid][$filter])) {
        $sql = "SELECT  MAX(f.id) as id,
                        f.filename,
                        f.author,
                        f.license,
                        f.timemodified,
                        f.contenthash,
                        cm.id AS cmid,
                        r.name AS rname
                FROM    {files} f
                INNER JOIN {context} c ON (c.id = f.contextid AND c.contextlevel = ?)
                INNER JOIN {course_modules} cm ON cm.id = c.instanceid
                INNER JOIN {resource} r ON cm.instance = r.id
                WHERE   r.course = ? AND
                        f.filename NOT LIKE  '.%'
                GROUP BY c.id, cm.id";

        // include files have null value in copyright status as default status
        if ($filter && $filter == $CFG->sitedefaultlicense) {
            $sql .= " HAVING (f.license IS NULL OR f.license = '' OR f.license = ?)";
            $params[] = $filter;
        } else if ($filter && $filter != 'all') {
            $sql .= " HAVING f.license = ?";
            $params[] = $filter;
        }

        $output[$courseid][$filter] = $DB->get_records_sql($sql, $params);
    }

    return $output[$courseid][$filter];
}

/*
 * Process result return from function get_files_copyright_status_by_course to
 * return a data structure for display
 * 
 * @param array $filelist
 * @return array            Returns an array indexed by content hash
 */
function process_files_list($filelist) {
    $result_array = array();
    foreach ($filelist as $result) {
        $result_array[$result->contenthash][$result->id] =
                array('license' => $result->license,
                    'timemodified' => $result->timemodified,
                    'author' => $result->author,
                    'filedisplayname' => !empty($result->rname) ? $result->rname : $result->filename,
                    'cmid' => $result->cmid);
    }
    return $result_array;
}

/*
 * Calculate files copyright status statistics.  Files with same contenthash treated as one file
 * Files have license as null will be included as Copyright status not yet identified.
 * @param $filelist, $licensetypes
 * @return statistics array with file license type as key, and number of the files of that type as value. 
 * @including total file count. File with same contenthash treated as one file.
 */
function calculate_copyright_status_statistics($filelist) {
    global $CFG;
    $sum_array = array(); // array stored license type and its quantity
    $total = 0;
    foreach ($filelist as $record) {
        // include null license as not yet identified statistics
        $license = !empty($record->license) ? $record->license : $CFG->sitedefaultlicense;
        // initialize
        $sum_array[$license] = isset($sum_array[$license]) ? $sum_array[$license] : 0;
        $sum_array[$license]++; // calculate each type total
        $total++;
    }
    $sum_array['total'] = $total;
    return $sum_array;
}

/*
 * Return a group of file ids that have the same content hash
 * @param $fileid
 * @return array of file ids 
 */
function get_file_ids($fileid) {
    global $DB;
    $res_contenthash = $DB->get_records('files', array('id' => $fileid), null,
            'id, contenthash');
    $sql_get_fileids = "SELECT f.id FROM {files} f where f.contenthash = ?";
    return $DB->get_records_sql($sql_get_fileids,
                    array($res_contenthash[$fileid]->contenthash));
}

/*
 * Update copyright status for files
 * @param form post data include string with file id and license the user choose
 * @param $user
 */
function update_copyright_status($data) {
    // loop through submitted data
    global $DB, $USER;
   // $data_array = explode('|', $data);
    foreach ($data as $key => $value) {
        if (!empty($value)&& preg_match('/^file/',$key)) {
            $a = explode('_', $key);
            $id = trim($a[1]);
            if (isset($id)) {
                $id_array_with_same_contenthash = get_file_ids($id);
                // loop through all files with same contenthash
                foreach ($id_array_with_same_contenthash as $fid => $other) {
                    $params = array('id' => $fid, 'license' => $value, 'timemodified' => time());
                    $DB->update_record('files', $params);
                }
            }
        }
    }
}

/*
 * Display file list with copyright status associated with the file for a course
 * 
 * @param int $courseid
 * @param string $filter    Should be copyright license shortname.
 *
 * @return void
 */

function display_copyright_status_contents($courseid, $filter) {
    global $CFG;
    global $OUTPUT;
    global $PAGE;

    $url = '/blocks/ucla_copyright_status/view.php';
    $PAGE->set_url($url, array('courseid' => $courseid));   // get copyright data
    $PAGE->requires->js('/theme/uclashared/javascript/jquery-1.5.2.min.js');
    $PAGE->requires->js('/blocks/ucla_copyright_status/view.js');

    // get license types
    $licensemanager = new license_manager();
    $licenses = $licensemanager->get_licenses(array('enabled' => 1));
    $license_options = array();
    $license_options['all'] = 'All';
    foreach ($licenses as $license) {
        $license_options[$license->shortname] = $license->fullname;
    }

    // display statistics 
    $all_copyrights = get_files_copyright_status_by_course($courseid);
    $stat_array = calculate_copyright_status_statistics($all_copyrights);
    //if no files, do not calculate
    if ($stat_array['total'] > 0) {
        echo html_writer::start_tag('fieldset',
                array('id' => 'block_ucla_copyright_status_stat'));
        echo html_writer::tag('legend',
                get_string('statistics', 'block_ucla_copyright_status'));
        echo html_writer::start_tag('ul');
        foreach ($license_options as $k => $v) {
            if ($k != 'all') {
                // if tbd, shown in red
                $text_style_class = 'block-ucla-copyright-status-stat-num';
                if ($k == $CFG->sitedefaultlicense) {
                    $text_style_class = 'block-ucla-copyright-status-stat-num-red';
                }
                $stat_count = isset($stat_array[$k]) ? $stat_array[$k] : 0;
                echo html_writer::tag('li',
                        $v . ':' . html_writer::start_tag('span',
                                array('class' => $text_style_class)) .
                        '(' . $stat_count . '/' . $stat_array['total'] . ', ' .
                        number_format($stat_count * 100 / $stat_array['total'],
                                0, '', '') . '%)' . html_writer::end_tag('span'));
            }
        }
        echo html_writer::end_tag('ul');
        echo html_writer::end_tag('fieldset');
    }
    // end display statistics

    echo html_writer::start_tag('div',
            array('id' => 'block_ucla_copyright_status_cp'));
    echo html_writer::start_tag('form',
            array('id' => 'block_ucla_copyright_status_form_copyright_status_list',
        'action' => $PAGE->url->out(), 'method' => 'post'));

    // display copyright filter
    echo html_writer::start_tag('div',
            array('id' => 'block_ucla_copyright_status_filter'));
    echo html_writer::tag('span',
            get_string('copyright_status', 'block_ucla_copyright_status'),
            array('id' => 'block_ucla_copyright_status_t1'));
    echo html_writer::select($license_options, 'filter_copyright', $filter,
            false,
            array('id' => 'block_ucla_copyright_status_id_filter_copyright'));
    $PAGE->requires->js_init_call('M.util.init_select_autosubmit',
            array('form_copyright_status_list', 'block_ucla_copyright_status_id_filter_copyright',
        ''));
    echo html_writer::end_tag('div');
    // end display copyright filter
    // display copyright status list
    unset($license_options['all']);
    $t = new html_table();
    $t->id = 'copyright_status_table';
    $helpicon = html_writer::link(new moodle_url('/help.php',
                array('component' => 'local_ucla', 'identifier' => 'license',
                      'lang' => 'en')),
            html_writer::empty_tag('img', array('class' => 'iconhelp',
                'alt' => get_string('copyrightstatushelp', 'block_ucla_copyright_status'),
                'src' => new moodle_url('/theme/image.php', array('theme' => 'uclashared',
                                        'image' => 'help')))),
            array('target' => '_blank'));
    $t->head = array(get_string('copyrightstatus', 'block_ucla_copyright_status')
        . ' ' . $helpicon,
        get_string('updated_dt', 'block_ucla_copyright_status'),
        get_string('author', 'block_ucla_copyright_status'));
    $t->attributes[] = 'generaltable';

    $course_copyright_status_list = get_files_copyright_status_by_course($courseid,
            $filter);
    $files_list = process_files_list($course_copyright_status_list);
    foreach ($files_list as $contenthash_record) {
        $file_names = array();
        $file_dates = array();
        $file_authors = array();
        $select_copyright = null;

        //loop through all the files with the same content hash        
        foreach ($contenthash_record as $id => $record) {
            $select_copyright = html_writer::select($license_options,
                            'filecopyright_' . $id, $record['license']);

            $file_names[] = html_writer::link(new moodle_url('/mod/resource/view.php',
                            array('id' => $record['cmid'])),
                            $record['filedisplayname']);
            $file_dates[] = strftime("%B %d %Y %r", $record['timemodified']);
            $file_authors[] = $record['author'];
        }

        // if there are mutliple records for a given contenthash, then display
        // then in a ordered list
        if (count($contenthash_record) > 1) {
            $file_names = html_writer::alist($file_names, null, 'ol');
            $file_dates = html_writer::alist($file_dates, null, 'ol');
            $file_authors = html_writer::alist($file_authors, null, 'ol');
        } else {
            // only one file, so just show information normally
            $file_names = array_pop($file_names);
            $file_dates = array_pop($file_dates);
            $file_authors = array_pop($file_authors);
        }

        $t->data[] = array($file_names .
            html_writer::tag('div', $select_copyright,
                    array('class' => 'block-ucla-copyright-status-list')),
            $file_dates, $file_authors);
    }
    echo html_writer::start_tag('div',
            array('id' => 'block_ucla_copyright_status_id_cp_list'));
    if (count($course_copyright_status_list) > 0) {
        echo html_writer::table($t);
    } else {
        echo $OUTPUT->notification(get_string('no_files',
                        'block_ucla_copyright_status'));
    }
    echo html_writer::end_tag('div');
    // end display copyright status list
    // display save changes button, hidden field data and submit form
    if (count($course_copyright_status_list) > 0) {
        $savebtn = html_writer::tag('button', get_string('savechanges'),
                        array('class' => 'btn btn-primary',
                    'name' => 'action_edit',
                    'type' => 'submit'));
        $cancelbtn = html_writer::tag('button', get_string('cancel'),
                        array('class' => 'btn-cancel',
                    'name' => 'action_cancel',
                    'type' => 'submit'));
        echo html_writer::tag('div', $savebtn . $cancelbtn,
                array('class' => 'mform'));
    }
    // end display save changes button
    echo html_writer::end_tag('form');
    echo html_writer::end_tag('div');
    echo $OUTPUT->footer();
    // end output screen
}
