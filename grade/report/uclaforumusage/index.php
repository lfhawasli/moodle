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
 *
 * Display forum usage report
 *
 * @package     gradereport_uclaforumusage
 * @copyright   2014 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->dirroot . '/grade/lib.php');
require_once($CFG->dirroot . '/grade/report/uclaforumusage/lib.php');
require_once($CFG->dirroot . '/grade/report/uclaforumusage/forumusage_form.php');

$courseid = required_param('id', PARAM_INT);
$userid = optional_param('userid', $USER->id, PARAM_INT);
$forumtype = optional_param('forumtype', 1, PARAM_INT);

$formsubmitted = optional_param('submitbutton', 0, PARAM_TEXT);
$PAGE->set_url(new moodle_url('/grade/report/uclaforumusage/index.php', array('id' => $courseid)));

// Basic access checks.
if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('nocourseid');
}
require_login($course);
$PAGE->set_pagelayout('report');

$context = context_course::instance($course->id);
require_capability('gradereport/uclaforumusage:view', $context);

// Return tracking object.
$gpr = new grade_plugin_return(array('type' => 'report', 'plugin' => 'uclaforumusage', 'courseid' => $courseid));

// Last selected report session tracking.
if (!isset($USER->grade_last_report)) {
    $USER->grade_last_report = array();
}
$USER->grade_last_report[$course->id] = 'forumusage';
$reportname = get_string('modulename', 'gradereport_uclaforumusage');

print_grade_page_head($COURSE->id, 'report', 'uclaforumusage', $reportname, false);

echo $OUTPUT->box(get_string('description', 'gradereport_uclaforumusage'));

$enrolledlist = gradereport_uclaforumusage_get_enrolled_user($courseid);

$allowed_roles = array();
while($role = array_shift($CFG->instructor_levels_roles)){
    $allowed_roles[]= implode("', '", $role);
}

// Get instructors and TAs.
$sql = "SELECT u.id
        FROM {user} u
        INNER JOIN {role_assignments} ra ON u.id = ra.userid
        INNER JOIN {role} r ON r.id = ra.roleid
        INNER JOIN {context} ct ON ra.contextid = ct.id
        WHERE ct.contextlevel = " . CONTEXT_COURSE ."
        AND r.shortname IN ('". implode('\', \'', $allowed_roles) . "')
        AND ct.instanceid = :courseid";

$rs = $DB->get_recordset_sql($sql, array('courseid' => $courseid));
$tainstr = array();  // TA and instrutor users.
if ($rs->valid()) {
    foreach ($rs as $instance) {
        array_push($tainstr, $instance->id);
    }
} else {
    print_error('notainstr', 'gradereport_uclaforumusage');
}

$forumlist = gradereport_uclaforumusage_get_forum($courseid);

// Add 'All' to student list.
$enrolledlist[0] = 'All';  // Set as default
// Exclude TA/Instr.
foreach ($tainstr as $k => $v) {
    // If it is not simple forum type means when it is 1.
    if (!$forumtype) {
        unset($enrolledlist[$v]);
    } else {
        $enrolledlist[$v] = $enrolledlist[$v].' (Instructor/TA)';
    }
}

// Form post data.
$forums = optional_param_array('forum', 0, PARAM_RAW);
$student = optional_param('student', 0, PARAM_INT);

$mform = new forumusage_form(null, array('user' => $USER,
                                         'studentlist' => $enrolledlist,
                                         'forumlist' => $forumlist,
                                         'courseid' => $courseid,
                                         'forums' => $forums,
                                         'forumtype' => $forumtype));
$mform->set_data($PAGE->url->params());
$data = $mform->get_data();
$mform->display();

if (!$forums) {
    $forums = array_keys($forumlist);
}
foreach ($forums as $k => $value) {
    if (!$value) {
        unset($forums[$k]); // For query the right data.
    }
}

$sql = "SELECT fp.parent, fp.userid, fp.id as post_id, fp.discussion as discussion_id, fd.forum
        FROM {forum_posts} fp
        INNER JOIN {forum_discussions} fd
        ON fp.discussion = fd.id";

// Default show all forums.
if (!empty($forums)) {
    $condition1 = implode(", ", $forums);
    $sql .= " WHERE fd.forum in ($condition1)";
} else {
    $sql .= " WHERE fd.course = $courseid";
    $forums = array_keys($forumlist);
}
// Exclude TA or instructor's initial posting.
$condition2 = implode(", ", $tainstr);
if ($student) {
    $sql .= " AND ((fp.userid in ($condition2) AND fp.parent <> 0) OR fp.userid = $student)";
} else if (!$forumtype) {
    $sql .= " AND ((fp.userid in ($condition2) AND fp.parent <> 0) OR fp.userid NOT IN ($condition2))";
}
$sql .= " ORDER BY fp.userid, fp.parent";
$rs = $DB->get_recordset_sql($sql, array('courseid' => $courseid));
if ($rs->valid()) {
    // Store result in array for statistics.
    $posts = array();
    $users = array();
    foreach ($rs as $instance) {
        $posts[$instance->userid][$instance->forum][$instance->parent][] = $instance->post_id;
        $users[$instance->post_id] = $instance->userid;
    }

    // Display.
    $studentdisplay = array();
    if ($student) {
        $studentdisplay[$student] = $enrolledlist[$student];
    } else {
        $studentdisplay = $enrolledlist;
        unset($studentdisplay[0]);
    }
    $statdisplay = get_stats($posts, $users, $tainstr);

    $table = new html_table();
    // Rows.

     // Row 1 and Row 2(header).
    $row1 = new html_table_row();
    $cell = new html_table_cell();
    $cell->header = true;
    // If not simple type, then name needs to extend to second row.
    if (!$forumtype) {
        $row2 = new html_table_row();
        $cell->rowspan = 2;
    }
    $cell->text = get_string('labelstudentname', 'gradereport_uclaforumusage');
    $row1->cells[] = $cell;

    foreach ($forums as $v) {
        // Row 1.
        $cell = new html_table_cell();
        $cell->header = true;
        $cell->colspan = (!$forumtype) * 3;
        $cell->text = $forumlist[$v];
        $row1->cells[] = $cell;

        if (!$forumtype) {
            // Row 2.
            $cell = new html_table_cell();
            $cell->header = true;
            $cell->text = get_string('labelinitialpost', 'gradereport_uclaforumusage');
            $row2->cells[] = $cell;
            $cell = new html_table_cell();
            $cell->header = true;
            $cell->text = get_string('labelresp', 'gradereport_uclaforumusage');
            $row2->cells[] = $cell;
            $cell = new html_table_cell();
            $cell->header = true;
            $cell->text = get_string('labelinsttaresp', 'gradereport_uclaforumusage');
            $row2->cells[] = $cell;
        }
    }
    $table->data[] = $row1;
    if (!$forumtype) {
        $table->data[] = $row2;
    }

    // Need to create all the rows.
    foreach ($studentdisplay as $userid => $studentname) {
        $row = new html_table_row();
        $cell = new html_table_cell();
        $cell->text = $studentname;
        $row->cells[] = $cell;
        foreach ($forums as $v) {
            if (!$forumtype) {
                // Initial post.
                $cell = new html_table_cell();
                if (isset($statdisplay[$userid][$v]['initial_posts'])) {
                    $cell->text = $statdisplay[$userid][$v]['initial_posts'];
                } else {
                    $cell->text = 0;
                }
                $row->cells[] = $cell;
            }
            // Response.
            $cell = new html_table_cell();
            if (isset($statdisplay[$userid][$v]['responses'])) {
                $cell->text = $statdisplay[$userid][$v]['responses'];
            } else {
                $cell->text = 0;
            }
            $row->cells[] = $cell;

            if (!$forumtype) {
                // TA response.
                $cell = new html_table_cell();
                if (isset($statdisplay[$userid][$v]['ta_instr_resp'])) {
                    $cell->text = $statdisplay[$userid][$v]['ta_instr_resp'];
                } else {
                    $cell->text = 0;
                }
                $row->cells[] = $cell;
            }
        }// End forum.
        $table->data[] = $row;
    }
    echo html_writer::tag('div', html_writer::table($table), array('class' => 'flexible-wrap'));
} else {
    echo get_string('noforumpost', 'gradereport_uclaforumusage');
}
echo $OUTPUT->footer();
