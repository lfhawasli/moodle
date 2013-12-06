<?php

defined('MOODLE_INTERNAL') || die();

// @todo When automatic class loading is available via Moodle 2.6, we no longer
// need to include the local_ucla_regsender class, so delete it.
require_once($CFG->dirroot . '/local/ucla/classes/local_ucla_regsender.php');
require_once($CFG->dirroot . '/local/ucla/lib.php');

function course_restored_enrol_check($data) {
    global $DB;

   // only respond to course restores
   if ($data->type != backup::TYPE_1COURSE) {
       return true;
   }

    $record = $DB->get_record('enrol', array('enrol' => 'database',
        'courseid' => $data->courseid, 'status' => ENROL_INSTANCE_DISABLED));

    if(!empty($record)) {
        ucla_reg_enrolment_plugin_cron::update_plugin($courseid, $record->id);
    }
}

/**
 * Determines if, given current week, whether or not to hide the past term's 
 * courses. Uses local_ucla|student_access_ends_week to determine if courses
 * should be hidden.
 *
 * If local_ucla|student_access_ends_week is 0, not set, or not equal to
 * $weeknum then will do nothing.
 *
 * If local_ucla|student_access_ends_week is equal to $weeknum, then will hide
 * the previous term's courses.
 *
 * Responds to the ucla_weeksdisplay_changed event.
 *
 * @param int $weeknum
 */
function hide_past_courses($weeknum) {
    global $CFG, $DB;
    $config_week = get_config('local_ucla', 'student_access_ends_week');

    // If local_ucla|student_access_ends_week is 0, not set, or not equal to
    // $weeknum then will do nothing.
    if (empty($config_week) || $config_week != $weeknum) {
        return true;
    }

    // If local_ucla|student_access_ends_week is equal to $weeknum, then will 
    // hide the previous term's courses.
    if (empty($CFG->currentterm)) {
        // For some reason, currentterm is empty, just exit.
        return true;
    }

    $past_term = term_get_prev($CFG->currentterm);
    if (!ucla_validator('term', $past_term)) {
        // Strange, cannot figure out past_term, just exit.
        return true;
    }

    list($num_hidden_courses, $num_hidden_tasites, $num_problem_courses,
            $error_messages) = hide_courses($past_term);

    // Finished hiding courses, notify admins.
    $to = get_config('local_ucla', 'admin_email');
    if (empty($to)) {
        // Did not have admin contact setup, just exit.
        return true;
    }

    $subj = 'Hiding courses for ' . $past_term;
    $body = sprintf("Hid %d courses.\n\n", $num_hidden_courses);
    $body .= sprintf("Hid %d TA sites.\n\n", $num_hidden_tasites);
    $body .= sprintf("Had %d problem courses.\n\n", $num_problem_courses);
    $body .= $error_messages;
    ucla_send_mail($to, $subj, $body);
    
    return true;
}

/**
 * Handles the updating "ucla_syllabus" table at SRDB by getting the course
 * that triggered the event and pushing out links for all the different syllabus
 * types.
 *
 * NOTE: This only responds to the ucla_syllabus_added and ucla_syllabus_deleted
 * events, because the link stays the same if a syllabus is updated.
 * 
 * @param mixed $data   Either syllabus record id or syllabus record object.
 * @return bool         Returns false on problems, otherwise true.
 */
function update_srdb_ucla_syllabus($data) {
    global $DB;
    $courseid = null;
    if (is_object($data)) {
        $courseid = $data->courseid;
    } else {
        $courseid = $DB->get_field('ucla_syllabus', 'courseid', array('id' => $data));
    }
    if (is_null($courseid)) {
        throw new Exception(get_string('invalidcourseid', 'error'));
    }

    // Get all syllabi for course and then send links.
    $regsender = new local_ucla_regsender();
    return $regsender->push_course_links($courseid);
}

/**
 * After courses are built, run prepop on them.
 *
 * @param array $edata
 * @return boolean
 */
function ucla_sync_built_courses($edata) {
    global $CFG;
    
    // This hopefully means that this plugin IS enabled
    $enrol = enrol_get_plugin('database');
    if (empty($enrol)) {
        debugging('Database enrolment plugin is not installed');
        return false;
    }

    $trace = null;
    if (debugging()) {
        $trace = new text_progress_trace();
    } else {
        $trace = new null_progress_trace();
    }
    $courseids = array();
    foreach ($edata->completed_requests as $key => $request) {
        if (empty($request->courseid)) {
            continue;
        }
        $courseids[] = $request->courseid;
    }

    foreach ($courseids as $courseid) {
        // This will handle auto-groups via events api.
        $enrol->sync_enrolments($trace, $courseid);
    }
    
    return true;
}
