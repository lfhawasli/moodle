<?php
// This file is part of the UCLA Help plugin for Moodle - http://moodle.org/
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
 * Form for users to send messages to support contacts.
 *
 * @package    block_ucla_help
 * @copyright  2011 UC Regents
 * @author     Rex Lorenzo <rex@seas.ucla.edu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');

defined('MOODLE_INTERNAL') || die();

/**
 * Form for users to send messages to support contacts.
 *
 * @copyright  2011 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class help_form extends moodleform {

    /**
     * Defines the help form itself
     */
    public function definition() {
        global $CFG, $COURSE, $SITE, $USER;

        // If on a real course, be sure to include courseid as GET variable.
        if ($COURSE->id > $SITE->id) {
            $this->_form->_attributes['action'] .= '?course='. $COURSE->id;
        }

        $this->_form->_attributes['id'] = 'help_form';

        $courses = $this->_customdata['courses'];

        $mform =& $this->_form;

        // Css should be used to define widths of input/textarea fields.
        $mform->addElement('text', 'ucla_help_name',
                get_string('name_field', 'block_ucla_help'));
        $mform->addElement('text', 'ucla_help_email',
                get_string('email_field', 'block_ucla_help'));

        // Set and freeze defaults for name/email.
        if (isloggedin() && !isguestuser()) {
            $mform->setDefault('ucla_help_name', "$USER->firstname $USER->lastname");
            $mform->setDefault('ucla_help_email', $USER->email);
            $mform->hardFreeze('ucla_help_name');
            $mform->hardFreeze('ucla_help_email');

            $docswikiurl = get_config('block_ucla_help', 'docs_wiki_url');
            $changeemail = new stdClass;
            $changeemail->students = html_writer::link(($docswikiurl . 'Changing_your_email_address#Students'),
                    'Students');
            $changeemail->facultystaff = html_writer::link(($docswikiurl . 'Changing_your_email_address#Faculty.2FStaff'),
                    'faculty/staff');
            $mform->addElement('static', '', '', get_string('helpform_alternative', 'block_ucla_help',
                    $changeemail));
        } else {
            // Make name field a required field.
            $mform->addRule('ucla_help_name', get_string('requiredelement', 'form'), 'required');

            // Make email field a required field.
            $mform->addRule('ucla_help_email', get_string('requiredelement', 'form'), 'required');
            // If email is present, make sure it is a valid email address.
            $mform->addRule('ucla_help_email', get_string('err_email', 'form'), 'email');
        }

        $mform->addElement('select', 'ucla_help_course',
                get_string('course_field', 'block_ucla_help'), $courses);

        // Set and freeze default for course.
        $mform->setDefault('ucla_help_course', $COURSE->id);
        if ($COURSE->id > $SITE->id) {
            $mform->hardFreeze('ucla_help_course');
        }

        $mform->addElement('textarea', 'ucla_help_description',
                get_string("description_field", "block_ucla_help"),
                'wrap="virtual" rows="6"');

        // Only allow logged in users to upload attachments.
        if (isloggedin() && !isguestuser()) {
            // Temporarily hides the file picker for CCLE in all cases.
            if (get_config('block_ucla_help', 'enablefileuploads')) {
                $mform->addElement('filepicker', 'ucla_help_attachment',
                        get_string('helpform_upload', 'block_ucla_help'), null,
                        array('maxbytes' => get_config('block_ucla_help', 'maxfilesize'),
                        'accepted_types' => '*'));
            }
        }

        // Display reCAPTCHA for non-logged in users.
        if (!isloggedin() || isguestuser()) {
            if (!empty($CFG->recaptchapublickey) && !empty($CFG->recaptchaprivatekey) &&
                    get_config('block_ucla_help', 'enablerecaptcha')) {
                $mform->addElement('recaptcha', 'recaptcha_element');
            }
        }

        // No point in having a cancel option.
        $this->add_action_buttons(false, get_string('submit_button', 'block_ucla_help'));

        // Set proper types for each element.
        $mform->setType('ucla_help_name', PARAM_TEXT);
        $mform->setType('ucla_help_email', PARAM_EMAIL);
        $mform->setType('ucla_help_description', PARAM_TEXT);

        // Trim all input.
        $mform->applyFilter('ucla_help_name', 'trim');
        $mform->applyFilter('ucla_help_email', 'trim');
        $mform->applyFilter('ucla_help_description', 'trim');

        // Make description field a required field.
        $mform->addRule('ucla_help_description', get_string('requiredelement', 'form'), 'required');
    }

    /**
     * Validate user supplied data on the help form.
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK (true allowed for backwards compatibility too).
     */
    public function validation($data, $files) {
        global $CFG;
        $errors = parent::validation($data, $files);

        if (!isloggedin() || isguestuser()) {
            if (!empty($CFG->recaptchapublickey) && !empty($CFG->recaptchaprivatekey) &&
                    get_config('block_ucla_help', 'enablerecaptcha')) {
                $recaptchaelement = $this->_form->getElement('recaptcha_element');
                if (!empty($this->_form->_submitValues['g-recaptcha-response'])) {
                    $response = $this->_form->_submitValues['g-recaptcha-response'];
                    if (!$recaptchaelement->verify($response)) {
                        $errors['recaptcha_element'] = get_string('incorrectpleasetryagain', 'auth');
                    }
                } else {
                    $errors['recaptcha_element'] = get_string('missingrecaptchachallengefield');
                }
            }
        }

        return $errors;
    }
}
