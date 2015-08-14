<?php
/**
 * UCLA Course Content Download
 *
 * @package    blocks
 * @subpackage ucla_course_download
 * @copyright  2014 UC Regents                              
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Checks if students are able to request a course content download.
 *
 * First we check if we are allowing students to download content. Then we see
 * if the current course is in the current term. If it is a past term, we allow
 * access. Finally, if the course is in the current term, then we look at the
 * current week and only allow access if it is 1 week before the end.
 *
 * We take into account the different week lengths for summer sessions.
 *
 * @param object $course    Course object.
 * @return boolean
 */
function student_zip_requestable($course) {
    global $CFG;

    if (!get_config('block_ucla_course_download', 'allowstudentaccess')) {
        return false;
    }

    // See if the instructor has disabled downloading for this specific course.
    $formatoptions = course_get_format($course->id)->get_format_options();
    if ($formatoptions['coursedownload'] != 1) {
        return false;
    }

    // See if course is a UCLA course.
    $courseinfos = ucla_get_course_info($course->id);
    // If not, then this is a collab site, so always allow downloading course (CCLE-4758).
    if (empty($courseinfos)) {
        return true;
    }
    $courseinfo = reset($courseinfos);  // Just care about first item.

    // If this course belongs to the past, then allow access.
    if (is_past_course($course)) {
        return true;
    }

    // Does this course belong to the current term?
    if (empty($CFG->currentterm) || $courseinfo->term != $CFG->currentterm) {
        return false;
    }

    // Find out about where we are in the quarter calendar for this course.
    $currentweek = -1;
    $totalweeks = 10;
    // Handle summer sessions.
    if (is_summer_term($courseinfo->term)) {
        // Get what session the course belongs to.
        if ($courseinfo->session_group == 'A') {
            $currentweek = get_config('local_ucla', 'current_week_summera');

            if ($courseinfo->session_group == '6A') {
                $totalweeks = 6;
            } else if ($courseinfo->session_group == '8A') {
                $totalweeks = 8;
            } else if ($courseinfo->session_group == '9A') {
                $totalweeks = 9;
            }

        } else if ($courseinfo->session_group == 'C') {
            $currentweek = get_config('local_ucla', 'current_week_summerc');
            $totalweeks = 6;    // There is only 6C.
        }
    } else {
        $currentweek = get_config('local_ucla', 'current_week');
    }

    // Now see if we are 1 week before the quarter ends.
    if ($currentweek >= ($totalweeks - 1) ||
            $currentweek === \block_ucla_weeksdisplay_session::WEEK_FINALS ||
            $currentweek === \block_ucla_weeksdisplay_session::WEEK_BETWEEN_SESSION) {
        return true;
    } else {
        return false;
    }
}
