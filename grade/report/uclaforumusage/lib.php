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
 * Forum lib file.
 *
 * @package     gradereport_uclaforumusage
 * @copyright   2014 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Return the list of enrolled user
 * @global object $CFG, $PAGE, $DB
 * @param int $courseid
 */
function gradereport_uclaforumusage_get_enrolled_user($courseid) {
    global $CFG, $DB, $PAGE;
    require_once("$CFG->dirroot/enrol/locallib.php");
    $course = $DB->get_record('course', array('id' => $courseid), '*',
            MUST_EXIST);
    $manager = new course_enrolment_manager($PAGE, $course);
    $result = array();
    foreach ($list = $manager->get_users('lastname', 'ASC', 0, null) as $k => $record) {
        $result[$record->id] = $record->lastname . ", " . $record->firstname;
    }
    return $result;
}

/**
 * Return forum list
 * @global object $CFG, $DB
 * @param int $courseid
 */
function gradereport_uclaforumusage_get_forum($courseid) {
    global $CFG, $DB;
    require_once("$CFG->libdir/modinfolib.php");

    if ($courseid) {
        if (!$course = $DB->get_record('course', array('id' => $courseid))) {
            print_error('invalidcourseid');
        }
    } else {
        $course = get_site();
    }

    $forums = $DB->get_records('forum', array('course' => $courseid));
    $generalforums = array();
    $learningforums = array();
    $modinfo = get_fast_modinfo($course);

    if (!isset($modinfo->instances['forum'])) {
        $modinfo->instances['forum'] = array();
    }
    foreach ($modinfo->instances['forum'] as $forumid => $cm) {
        if (!$cm->uservisible or ! isset($forums[$forumid])) {
            continue;
        }
        $forum = $forums[$forumid];

        if (!$context = context_module::instance($cm->id, IGNORE_MISSING)) {
            continue;   // Shouldn't happen.
        }

        if (!has_capability('mod/forum:viewdiscussion', $context)) {
            continue;
        }

        if ($forum->type == 'news' or $forum->type == 'social') {
            $generalforums[$forum->id] = $forum;
        } else if ($course->id == SITEID or empty($cm->sectionnum)) {
            $generalforums[$forum->id] = $forum;
        } else {
            $learningforums[$forum->id] = $forum;
        }
    }
    $result = array();
    foreach ($generalforums as $k => $record) {
        $result[$k] = $record->name;
    }
    foreach ($learningforums as $k => $record) {
        $result[$k] = $record->name;
    }
    return $result;
}

/**
 * Return the statistics for the forum usage.
 *
 * @param array $posts  Format: posts[userid][forum][parent][]=postid
 * @param array $users  Format: users[postid] = userid
 * @param array $tainstr
 * @return array
 */
function get_stats($posts = null, $users = null, $tainstr = null) {
    if ($posts == null || $tainstr == null) {
        return null;
    } else {
        $result = array();
        $tainstrresp = array();
        // Find out TA or Instructor's posting.
        foreach ($tainstr as $k => $v) {
            // If instructor or TA has postings.
            if (!empty($posts[$v])) {
                foreach ($posts[$v] as $forum => $recordbyforum) {
                    foreach ($recordbyforum as $parent => $record) {
                        // TA or Instructor response to this user $users[$parent] by forum.
                        // It is the sum of TA and instructors.
                        if (isset($users[$parent]) && isset($forum) && !empty($record)) {
                            if (isset($tainstrresp[$forum][$users[$parent]])) {
                                $tainstrresp[$forum][$users[$parent]] += count($record);
                            } else {
                                $tainstrresp[$forum][$users[$parent]] = count($record);
                            }
                        }
                    }
                }// End foreach.
            } else {
                $tainstrresp[0] = 1; // TA or instructor has no posting.
            }
            // Unset TA or Instructor in the original post array.
            unset($posts[$v]);
        }
        foreach ($posts as $userid => $recordbyuser) {
            foreach ($recordbyuser as $forum => $record) {
                // Initial posts.
                $initialposts = isset($record[0]) ? count($record[0]) : 0;
                $result[$userid][$forum]['initial_posts'] = $initialposts; // Parent is 0.
                // Responses.
                $responses = 0;
                foreach ($record as $k => $v) {
                    if ($k > 0) {
                        $responses += count($v);
                    }
                }
                $result[$userid][$forum]['responses'] = $responses; // All other posts.
                // TA responses.
                if (isset($tainstrresp[$forum][$userid]) && isset($result[$userid][$forum])) {
                    $result[$userid][$forum]['ta_instr_resp'] = $tainstrresp[$forum][$userid];
                } else {
                    $result[$userid][$forum]['ta_instr_resp'] = 0;
                }
            }
        }
        return $result;
    }
}

function display_uclaforumusage_export_options($params) {
    global $CFG, $OUTPUT;
    $exportoptions = html_writer::start_tag('div',
            array('class' => 'export-options'));
    $exportoptions .= get_string('exportoptions', 'gradereport_uclaforumusage');

    // Right now, only supporting xls.
    $xlsstring = get_string('application/vnd.ms-excel', 'mimetypes');
    $icon = html_writer::img($OUTPUT->image_url('f/spreadsheet'), $xlsstring, array('title' => $xlsstring));
    $params['export'] = 'xls';
    $exportoptions .= html_writer::link(
            new moodle_url('/grade/report/uclaforumusage/index.php',
                    $params), $icon);

    $exportoptions .= html_writer::end_tag('div');
    return $exportoptions;
}


/**
 * Outputs given data and inputs in an Excel file.
 *
 * @param string $title
 * @param array $data
 * @param int $forumtype
 */
function forumusage_export_to_xls($title, $data, $forumtype=1) {
    global $CFG;
    require_once($CFG->dirroot.'/lib/excellib.class.php');

    // Might have HTML.
    $fulltitle = clean_param($title, PARAM_NOTAGS);
    $filename = clean_filename($title . '.xls');
    // Creating a workbook (use "-" for writing to stdout).
    $workbook = new MoodleExcelWorkbook("-");
    // Sending HTTP headers.
    $workbook->send($filename);
    // Adding the worksheet.
    $worksheet = $workbook->add_worksheet($fulltitle);

    $boldformat = $workbook->add_format();
    $boldformat->set_bold(true);
    $rownum = $colnum = 0; 

    // Add title.
    $worksheet->write_string($rownum, $colnum, $fulltitle, $boldformat);
    ++$rownum;

    // Now go through the data set.
    // Start row number 2.
    
    foreach ($data as $row) {
        ++$rownum; $colnum = 0;
        $row = get_object_vars($row);
        $row = $row['cells'];
        // If not simple forum, output row 3 differently
        if (!$forumtype && $rownum == 3) {
            ++$colnum;  // Skip the first column
        }
        foreach ($row as $col) {
            $cell = get_object_vars($col);
            $outval = clean_param($cell['text'], PARAM_NOTAGS);
            if (is_numeric($outval)) {
                $worksheet->write_number($rownum, $colnum, $outval);
            } else {
                $worksheet->write_string($rownum, $colnum, $outval);
            }
            // If not simple forum, output row 2 differently
            if (!$forumtype && $rownum == 2 && $colnum > 0){
                $colnum += 3;
            } else {
                ++$colnum;
            }
        }
    }
    // Close the workbook.
    $workbook->close();

    // If we are in the command line, don't die.
    if (!defined('CLI_SCRIPT') || !CLI_SCRIPT) {
        exit;
    }
}