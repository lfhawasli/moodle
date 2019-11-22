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
    if (empty($formatoptions['coursedownload'])) {
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

    // CCLE-5595 - Allow access for entire term (including summer sessions)
    return true;
}
