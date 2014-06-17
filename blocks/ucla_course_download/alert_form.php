<?php
/**
 * Alert form class.
 * 
 * Used to create a form for alerting user about course content download.
 * 
 * @package     block
 * @subpackage  block_ucla_course_download
 * @copyright   2014 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');

class course_download_alert_form extends moodleform {

    /**
     * Generates course content download alert form 
     */
    public function definition() {
        $mform = $this->_form;

        // Display course content download alert.
        $mform->addElement('html', html_writer::tag('div',
                get_string('alert_msg', 'block_ucla_course_download')));

        $alertbuttons = array();
        $alertbuttons[] = $mform->createElement('submit', 'yesbutton',
                get_string('alert_download', 'block_ucla_course_download'));
        $alertbuttons[] = $mform->createElement('submit', 'nobutton',
                get_string('alert_dismiss', 'block_ucla_course_download'));

        $mform->addGroup($alertbuttons, 'alertbuttons', '', array(' '), false);
        $mform->closeHeaderBefore('alertbuttons');
    }
}