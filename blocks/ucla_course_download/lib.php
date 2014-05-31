<?php
/**
 * 
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Serves the zipped course content files.
 *
 * @param stdClass $course course object
 * @param stdClass $cm This will be ignored.
 * @param stdClass $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - just send the file
 */
function block_ucla_course_download_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    global $CFG, $DB, $USER;
    require_once($CFG->dirroot . '/blocks/ucla_course_download/classes/base.php');

    // Expecting course context.
    if ($context->contextlevel != CONTEXT_COURSE) {
        return false;
    }

    // Does the user have the ability to get zip files?
    require_course_login($course, true);
    if (!has_capability('block/ucla_course_download:requestzip', $context)) {
        print_error('noaccess', 'block_ucla_course_download');
    }

    // Depending on the file area, it will tell us which class to load.
    $classname = 'block_ucla_course_download_' . $filearea;
    $classfile = $CFG->dirroot . '/blocks/ucla_course_download/classes/' . $filearea . '.php';
    if (!file_exists($classfile)) {
        return false;
    }
    require_once($classfile);
    $coursecontentclass = new $classname($course->id, $USER->id);

    $coursecontentclass->download_zip();
}