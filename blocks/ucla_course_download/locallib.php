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
 * @return bool
 */
function student_zip_requestable() {

    if ((int)get_config('local_ucla', 'current_week') >= (int)get_config('block_ucla_course_download', 'student_access_begins_week')) {
        return true;                
    } else {
        return false;
    }
}
