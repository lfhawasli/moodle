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
