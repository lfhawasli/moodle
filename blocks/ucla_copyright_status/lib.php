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

    $pagetitle = $course->shortname . ': ' . get_string('pluginname',
        'block_ucla_copyright_status');

    $PAGE->set_context($context);
    $PAGE->set_title($pagetitle);

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

    // Cache results.
    static $output = array();

    $params = array(CONTEXT_MODULE, $courseid);
    if (!isset($output[$courseid][$filter])) {
        $includedmodules = array('resource', 'folder');
        // Contains, e.g., 'resource.name, folder.name'.
        $modulenamefields = '';
        // Joins for each of the modules' tables, which we need to get the module name/title.
        $modulejoins = '';
        foreach ($includedmodules as $module) {
            $modulenamefields .= "$module.name, ";
            // We're adding data for relevant modules, so we use a left join.
            $modulejoins .= " LEFT JOIN {" . $module . "} $module ON (m.name = '$module' AND cm.instance = $module.id)";
        }
        // Remove last ', '.
        $modulenamefields = substr($modulenamefields, 0, strlen($modulenamefields) - 2);
        $includedmodulesexpression = $DB->get_in_or_equal($includedmodules);
        $params = array_merge($params, $includedmodulesexpression[1]);

        $sql = "SELECT f.id,
                       f.filename,
                       f.author,
                       f.license,
                       f.timemodified,
                       f.contenthash,
                       f.sortorder,
                       cm.id AS cmid,
                       s.section AS sectionid,
                       s.name AS sectionname,
                       m.name AS module,
                       COALESCE($modulenamefields) AS rname
                  FROM {files} f
                  JOIN {context} c ON (c.id = f.contextid AND c.contextlevel = ?)
                  JOIN {course_modules} cm ON cm.id = c.instanceid
                  JOIN {course_sections} s ON s.id = cm.section
                  JOIN {modules} m ON m.id = cm.module
                  $modulejoins
                 WHERE cm.course = ?
                       AND f.filename NOT LIKE '.%'
                       AND m.name $includedmodulesexpression[0]";

        // Include files that have null value in copyright status as default status.
        if ($filter && $filter == $CFG->sitedefaultlicense) {
            $sql .= " AND (f.license IS NULL OR f.license = '' OR f.license = ?)";
            $params[] = $filter;
        } else if ($filter && $filter != 'all') {
            $sql .= " AND f.license = ?";
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
 * @return array
 * Returns an array indexed by content hash
 */
function process_files_list($filelist) {
    $resultarray = array();
    foreach ($filelist as $result) {
        $resultarray[$result->contenthash][$result->id] =
                array('license' => $result->license,
                    'timemodified' => $result->timemodified,
                    'author' => $result->author,
                    'filename' => $result->filename,
                    'ismainfile' => $result->sortorder == 1,
                    'cmid' => $result->cmid,
                    'sectionid' => $result->sectionid,
                    'sectionname' => $result->sectionname,
                    'module' => $result->module,
                    'resourcename' => $result->rname);
    }
    return $resultarray;
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
    $sumarray = array(); // Array stored license type and its quantity.
    $total = 0;
    foreach ($filelist as $record) {
        // Include null license as not yet identified statistics.
        $license = !empty($record->license) ? $record->license : $CFG->sitedefaultlicense;
        // Initialize.
        $sumarray[$license] = isset($sumarray[$license]) ? $sumarray[$license] : 0;
        $sumarray[$license]++; // Calculate each type total.
        $total++;
    }
    $sumarray['total'] = $total;
    return $sumarray;
}

/*
 * Return a group of file ids that have the same content hash
 * @param $fileid
 * @return array of file ids
 */
function get_file_ids($fileid) {
    global $DB;
    $rescontenthash = $DB->get_records('files', array('id' => $fileid), null,
            'id, contenthash');
    $sqlgetfileids = "SELECT f.id FROM {files} f where f.contenthash = ?";
    return $DB->get_records_sql($sqlgetfileids,
                    array($rescontenthash[$fileid]->contenthash));
}

/*
 * Update copyright status for files
 * @param form post data include string with file id and license the user choose
 * @param $user
 */
function update_copyright_status($data) {
    // Loop through submitted data.
    global $DB, $USER;
    foreach ($data as $key => $value) {
        // If bulk assign.
        if (!empty($data->bulkassign) && preg_match('/^checkbox/', $key) && !empty($value)) {
            $license = $data->bulkassign;
        } else if (empty($data->bulkassign) && !empty($value) && preg_match('/^file/', $key)) {
            $license = $value;
        } else {
            continue;
        }
        $a = explode('_', $key);
        $id = trim($a[1]);
        if (isset($id)) {
            $idarraywithsamecontenthash = get_file_ids($id);
            // Loop through all files with same contenthash.
            foreach ($idarraywithsamecontenthash as $fid => $other) {
                $params = array('id' => $fid, 'license' => $license, 'timemodified' => time());
                $DB->update_record('files', $params);
            }
        }
    }
}

/*
 * Display file list with copyright status associated with the file for a course
 *
 * @param int $courseid
 * @param string $filter
 * Should be copyright license shortname.
 *
 * @return void
 */

function display_copyright_status_contents($courseid, $filter) {
    global $CFG, $COURSE, $OUTPUT, $PAGE;

    $url = '/blocks/ucla_copyright_status/view.php';
    $PAGE->set_url($url, array('courseid' => $courseid));   // Get copyright data.
    $PAGE->requires->js('/blocks/ucla_copyright_status/view.js');

    // Get license types.
    $licensemanager = new license_manager();
    $licenses = $licensemanager->get_licenses(array('enabled' => 1));
    $licenseoptions = array();
    $licenseoptions['all'] = 'All';
    foreach ($licenses as $license) {
        $licenseoptions[$license->shortname] = $license->fullname;
    }

    // Display statistics.
    $allcopyrights = get_files_copyright_status_by_course($courseid);
    $statarray = calculate_copyright_status_statistics($allcopyrights);
    // If no files, do not calculate.
    if ($statarray['total'] > 0) {
        echo html_writer::start_tag('fieldset',
                array('id' => 'block_ucla_copyright_status_stat'));
        echo html_writer::tag('legend',
                get_string('statistics', 'block_ucla_copyright_status'));
        echo html_writer::start_tag('ul');
        foreach ($licenseoptions as $k => $v) {
            if ($k != 'all') {
                // If tbd, shown in red.
                $textstyleclass = 'block-ucla-copyright-status-stat-num';
                if ($k == $CFG->sitedefaultlicense) {
                    $textstyleclass = 'block-ucla-copyright-status-stat-num-red';
                }
                $statcount = isset($statarray[$k]) ? $statarray[$k] : 0;
                echo html_writer::tag('li',
                        $v . ':' . html_writer::start_tag('span',
                                array('class' => $textstyleclass)) .
                        '(' . $statcount . '/' . $statarray['total'] . ', ' .
                        number_format($statcount * 100 / $statarray['total'],
                                0, '', '') . '%)' . html_writer::end_tag('span'));
            }
        }
        echo html_writer::end_tag('ul');
        echo html_writer::end_tag('fieldset');
    }

    // Display copyright filter.
    echo html_writer::start_tag('form', array('id' => 'block_ucla_copyright_status_form_copyright_status_filter',
        'action' => $PAGE->url->out(), 'method' => 'post'));
    echo html_writer::start_tag('div',
            array('id' => 'block_ucla_copyright_status_filter'));
    echo html_writer::tag('span',
            get_string('copyright_status', 'block_ucla_copyright_status'),
            array('id' => 'block_ucla_copyright_status_t1'));
    echo html_writer::select($licenseoptions, 'filter_copyright', $filter, false,
            array('id' => 'block_ucla_copyright_status_id_filter_copyright', 'class' => 'autosubmit'));
    $PAGE->requires->yui_module('moodle-core-formautosubmit',
           'M.core.init_formautosubmit',
           array(array('selectid' => 'block_ucla_copyright_status_id_filter_copyright', 'nothing' => false))
           );
    echo html_writer::end_tag('div');
    echo html_writer::end_tag('form');
    // End display copyright filter.

    // End display statistics.
    echo html_writer::start_tag('div',
            array('id' => 'block_ucla_copyright_status_cp'));
    echo html_writer::start_tag('form',
            array('id' => 'block_ucla_copyright_status_form_copyright_status_list',
                  'action' => $PAGE->url->out(), 'method' => 'post'));
    // Display copyright status list.
    unset($licenseoptions['all']);
    $t = new html_table();
    $t->id = 'copyright_status_table';
    $helpicon = html_writer::link(new moodle_url('/help.php',
                array('component' => 'local_ucla', 'identifier' => 'license',
                      'lang' => 'en')),
                html_writer::img(new moodle_url('/theme/image.php', array('theme' => 'uclashared', 'image' => 'help')),
                    get_string('copyrightstatushelp', 'block_ucla_copyright_status'),
                    array('class' => 'iconhelp')),
                    array('target' => '_blank'));
    $t->head = array('', get_string('copyrightstatus', 'block_ucla_copyright_status')
        . ' ' . $helpicon,
        get_string('section'),
        get_string('updated_dt', 'block_ucla_copyright_status'),
        get_string('author', 'block_ucla_copyright_status'));
    $t->attributes[] = 'generaltable';

    $coursecopyrightstatuslist = get_files_copyright_status_by_course($courseid,
            $filter);
    $fileslist = process_files_list($coursecopyrightstatuslist);
    foreach ($fileslist as $contenthashrecord) {
        $filenames = array();
        $filesections = array();
        $filedates = array();
        $fileauthors = array();
        $selectcopyright = null;

        // Loop through all the files with the same content hash,...
        // ...which we assume to have the same copyright status.
        foreach ($contenthashrecord as $id => $record) {
            $filecheckbox = html_writer::checkbox('checkbox_' . $id, $id, false, $label = '', array('class' => 'usercheckbox'));
            $selectcopyright = html_writer::select($licenseoptions,
                            'filecopyright_' . $id, $record['license']);

            $resourceinfo = html_writer::link(
                    new moodle_url("/mod/{$record['module']}/view.php", array('id' => $record['cmid'])),
                    $record['resourcename']);
            if ($record['module'] == 'resource') {
                $resourceinfo .= $record['ismainfile'] ? ': main file' : ': secondary file';
            }
            $name = $record['filename'] . ' (' . $resourceinfo . ')';

            $filenames[] = $name;
            $sectionurl = course_get_url($COURSE->id, $record['sectionid']);
            $filesections[] = html_writer::link($sectionurl, $record['sectionname']);
            // Hack to sort by date properly: insert a hidden timestamp at the beginning.
            $filedates[] = html_writer::tag('span', $record['timemodified'], array('hidden' => 'hidden'))
                    . strftime("%B %d %Y %r", $record['timemodified']);
            $fileauthors[] = $record['author'];
        }

        // If there are multiple records for a given contenthash, then display...
        // ...then in a ordered list.
        if (count($contenthashrecord) > 1) {
            $filenames = html_writer::alist($filenames, null, 'ol');
            $filesections = html_writer::alist($filesections, null, 'ol');
            $filedates = html_writer::alist($filedates, null, 'ol');
            $fileauthors = html_writer::alist($fileauthors, null, 'ol');
        } else {
            // Only one file, so just show information normally.
            $filenames = array_pop($filenames);
            $filesections = array_pop($filesections);
            $filedates = array_pop($filedates);
            $fileauthors = array_pop($fileauthors);
        }

        $t->data[] = array($filecheckbox, $filenames .
            html_writer::tag('div', $selectcopyright,
                    array('class' => 'block-ucla-copyright-status-list')),
            $filesections, $filedates, $fileauthors);
    }
    echo html_writer::start_tag('div',
            array('id' => 'block_ucla_copyright_status_id_cp_list'));
    if (count($coursecopyrightstatuslist) > 0) {
        echo html_writer::table($t);
    } else {
        echo $OUTPUT->notification(get_string('no_files',
                        'block_ucla_copyright_status'));
    }
    echo html_writer::end_tag('div');
    // End display copyright status list.
    // Display save changes button, hidden field data and submit form.
    if (count($coursecopyrightstatuslist) > 0) {
        $selectall = html_writer::tag('button', get_string('selectall'),
                                       array('id' => 'checkall',
                                             'class' => 'btn btn-default',
                                             'type' => 'button'));
        $selectnone = html_writer::tag('button', get_string('deselectall'),
                                        array('id' => 'checknone',
                                              'class' => 'btn btn-default',
                                              'type' => 'button'));
        $label = get_string('withselected', 'block_ucla_copyright_status') . "&nbsp;&nbsp;";
        $bulkassign = html_writer::select($licenseoptions, 'bulkassign');
        $savebtn = html_writer::tag('button', get_string('savechanges'),
                                     array('class' => 'btn btn-primary',
                                           'name' => 'action_edit',
                                           'type' => 'submit'));
        $cancelbtn = html_writer::tag('button', get_string('cancel'),
                                      array('class' => 'btn-cancel',
                                            'name' => 'action_cancel',
                                            'type' => 'submit'));
        echo html_writer::tag('div', $selectall . $selectnone . $label . $bulkassign . $savebtn . $cancelbtn,
                              array('class' => 'mform'));
    }
    // End display save changes button.
    echo html_writer::end_tag('form');
    echo html_writer::end_tag('div');
    echo $OUTPUT->footer();
    // End output screen.
}

/**
 * Alert instructors to assign copyright status for files used in the course they haven't done so already.
 * @param object $course Contains roles, and $courseinfo contains term.
 *                          
 */
function block_ucla_copyright_status_ucla_format_notices($course, $courseinfo) {
    global $CFG, $USER;
    require_once($CFG->dirroot . '/blocks/ucla_copyright_status/alert_form.php');

    // Ignore any old terms or if term is not set (meaning it is a collab site).
    
    if (isset($courseinfo) && (!isset($courseinfo->term) ||
            term_cmp_fn($courseinfo->term, $CFG->currentterm) == -1)) {
        // Important for event handlers to return true, because false indicates...
        // ...error and event will be reprocessed on the next cron run.
        return true;    
    }
     
    // See if current user can manage copyright status for course.
    $context =  context_course::instance($course->id);
    
    if (!has_capability('moodle/course:manageactivities', $context)) {
        return true;
    }

    // Ignore alert if user cannot assign copyright status for the course...
    // ...or if user can assign copyright status, but there is file has not assign...
    // ...copyright status, give alert.
    $a = sizeof(get_files_copyright_status_by_course($course->id,
            $CFG->sitedefaultlicense));

    if ($a==0) {
        return true;
    }

    // But first, see if they turned off the copyright status alert for their account...
    // ...ucla_copyright_status_noprompt_<courseid>.
    $timestamp = get_user_preferences('ucla_copyright_status_noprompt_' .
            $course->id, null, $USER->id);

    // Do not display alert if user turned off copyright status alerts or if remind me...
    // ...time has not passed.
    if (!is_null($timestamp) && (intval($timestamp) === 0 ||
            $timestamp > time())) {
        return true;
    }

    // Now we can display the alert.
    $alert_form = new copyright_alert_form(new moodle_url('/blocks/ucla_copyright_status/alert.php',
            array('id' => $course->id)), $a, 'post', '',
            array('class' => 'alert alert-info'));

    $alert_form->display();    
    return true;
}

/**
 * Adds manage copyright link to admin panel.
 *
 * @param navigation_node $navigation The navigation node to extend.
 * @param stdClass        $course     The course object for the tool.
 * @param context         $context    The context of the course.
 */
function block_ucla_copyright_status_extend_navigation_course($navigation, $course, $context) {
    if (has_capability('moodle/course:manageactivities', $context)) {
        $setting = navigation_node::create(get_string('managecopyright', 'block_ucla_copyright_status'),
                new moodle_url('/blocks/ucla_copyright_status/view.php', array('courseid' => $course->id)),
                navigation_node::TYPE_SETTING, null, 'managecopyright');

        $navigation->add_node($setting);
    }
};
