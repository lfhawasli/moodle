<?php
/**
 * Course Download alert file.
 * 
 * Responds to course content download alert form. Handles setting
 * of user preferences and redirecting.
 * 
 * @package     block
 * @subpackage  block_ucla_course_download
 * @copyright   2014 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot . '/blocks/ucla_course_download/alert_form.php');

// Use the ID of the course to retrieve course records.
$id = required_param('id', PARAM_INT);
$course = get_course($id);

require_course_login($course);

$cdalertform = new course_download_alert_form();
$data = $cdalertform->get_data();

if (!empty($data) && confirm_sesskey()) {
    
    if(isset($data->yesbutton)) {
        // Set the user preference not to be prompted again.
        set_user_preference('ucla_course_download_noprompt_' . $id, 0);

        // Redirect user to the course content download page.
        $params = array('courseid' => $id);
        redirect(new moodle_url('/blocks/ucla_course_download/view.php', $params));

    } else if (isset($data->nobutton)) {
        // Set user preference not to be prompted again.
        set_user_preference('ucla_course_download_noprompt_' . $id, 0);
        $message = get_string('alert_dismiss_message', 'block_ucla_course_download');

        // Redirect user to the course landing page and display dismiss message.
        $section = 0;    
        $format_options = course_get_format($course->id)->get_format_options();
        if (isset($format_options['landing_page'])) {
            $landing_page = $format_options['landing_page'];
        }
        if (!empty($landing_page)) {
            $section = $landing_page;
        } 
        flash_redirect(new moodle_url('/course/view.php',
                array('id' => $id, 'section' => $section)), $message);
        }
}