<?php
/**
 * Event handlers for non-webservices events.
 * 
 * @package     block
 * @subpackage  block_ucla_course_download
 * @copyright   2014 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/blocks/ucla_course_download/alert_form.php');
require_once($CFG->dirroot . '/blocks/ucla_course_download/locallib.php');

/**
 * Alert students that course content archives are available for download.
 * 
 * @param object $eventdata
 * @return boolean              Returns early if we don't want to show alert
 *                              to user, else modifies the eventdata parameter.
 */
function ucla_course_download_ucla_format_notices($eventdata) {

    // Make sure this is a course site.
    if (is_collab_site($eventdata->course)) {
        return true;
    }

    // Check if this is a student that can download the archive.
    $coursecontext = context_course::instance($eventdata->course->id);
    $isinstructor = has_capability('moodle/course:manageactivities', $coursecontext);
    $canrequest = has_capability('block/ucla_course_download:requestzip', $coursecontext);

    // Don't show alert if user is an course admin, cannot get download archives,
    // or it isn't time to show alert for students.
    if ($isinstructor || !$canrequest || !student_zip_requestable($eventdata->course)) {
        return true;
    }

    // Check if the user chose to dismiss the alert before.
    $noprompt = get_user_preferences('ucla_course_download_noprompt_' .
                $eventdata->course->id, null, $eventdata->userid);

    if(!is_null($noprompt) && (intval($noprompt) === 0)) {
        return true;
    }

    // Render the alert.
    $alertform = new course_download_alert_form(new moodle_url('/blocks/ucla_course_download/alert.php',
                array('id' => $eventdata->course->id)), null, 'post', '',
                array('class' => 'ucla-course-download-alert-form'));

    // Unfortunately, the display function outputs HTML, rather than returning
    // it, so we need to capture it.
    ob_start();
    $alertform->display();
    $eventdata->notices[] = ob_get_clean();

    return true;
}

