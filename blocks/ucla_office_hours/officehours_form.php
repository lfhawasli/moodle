<?php
defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/completionlib.php');


class officehours_form extends moodleform {

    protected function definition() {
        global $CFG, $USER, $DB;

        $editid = $this->_customdata['editid'];
        $editemail = $this->_customdata['editemail'];
        $courseid = $this->_customdata['courseid'];
        $defaults = $this->_customdata['defaults'];
        $website = $this->_customdata['url'];
        $emailsettings = $this->_customdata['emailsettings'];
        $coursenames = $this->_customdata['coursenames'];
        $currentcoursename = $this->_customdata['currentcoursename'];

        $multiplecourses = count($coursenames) > 1;
        $coursenames = implode(', ', $coursenames);

        $mform = $this->_form;
        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT);
        $mform->addElement('hidden', 'editid', $editid);
        $mform->setType('editid', PARAM_INT);

        // Add office info heading.
        $mform->addElement('header', 'header_office_info',
                get_string('header_office_info', 'block_ucla_office_hours'));

        // Add office location field.
        $mform->addElement('static', 'f_office_text', '',
                get_string('f_office_text', 'block_ucla_office_hours'));
        $mform->addElement('text', 'office',
                get_string('f_office', 'block_ucla_office_hours'));

        // Add radio buttons to choose which courses to apply office location.
        if ($multiplecourses) {
            $mform->addElement('advcheckbox', 'officelocationallcourses', '',
                    get_string('office_info_all_courses', 'block_ucla_office_hours', $coursenames),
                    '', array(0, 1));
        }

        // Add office hours field.
        $mform->addElement('static', 'f_officehours_text', '', '</br>' .
                get_string('f_officehours_text', 'block_ucla_office_hours'));
        $mform->addElement('text', 'officehours',
                get_string('f_officehours', 'block_ucla_office_hours'));

        // Add radio buttons to choose which courses to apply office hours.
        if ($multiplecourses) {
            $mform->addElement('advcheckbox', 'officehoursallcourses', '',
                    get_string('office_info_all_courses', 'block_ucla_office_hours', $coursenames),
                    '', array(0, 1));
        }

        // Add contact info field.
        $mform->addElement('header', 'header_contact_info',
                get_string('header_contact_info', 'block_ucla_office_hours'));

        // Add email of record field.
        if (empty($editemail)) {
            $editemail = get_string('f_email_of_record_empty', 'block_ucla_office_hours');
        }
        $mform->addElement('static', 'f_email_of_record',
                get_string('f_email_of_record', 'block_ucla_office_hours'), $editemail);

        // Add email display settings field.
        $displayopt = array(get_string('emaildisplayno', 'moodle'),
                get_string('emaildisplayyes', 'moodle'),
                get_string('emaildisplaycourse', 'moodle'));
        $mform->addElement('select', 'emailsettings',
                get_string('f_email_display', 'block_ucla_office_hours'), $displayopt);
        $mform->setDefault('emailsettings', $emailsettings);

        // Add alternative email field.
        $mform->addElement('static', 'f_email_text', '',
                get_string('f_email_text', 'block_ucla_office_hours', $editemail));
        $mform->addElement('text', 'email',
                get_string('f_email', 'block_ucla_office_hours'));

        // Add phone field.
        $mform->addElement('static', 'f_phone_text', '',
                get_string('f_phone_text', 'block_ucla_office_hours'));
        $mform->addElement('text', 'phone',
                get_string('f_phone', 'block_ucla_office_hours'));

        // Add website field.
        $mform->addElement('text', 'website',
                get_string('f_website', 'block_ucla_office_hours'));

        // Set Rules, Types and Defaults
        // Set character limits for each field from field limits in DB.
        $fields = $DB->get_columns('ucla_officehours');
        $officehourslimit = $fields['officehours']->max_length;
        $officelimit = $fields['officelocation']->max_length;
        $emaillimit = $fields['email']->max_length;
        $phonelimit = $fields['phone']->max_length;

        // Set maxlength rule and type for office hours field.
        $mform->addRule('officehours',
                get_string('maximumchars', '', $officehourslimit).'.'.
                get_string('officehours_format', 'block_ucla_office_hours'), 'maxlength', $officehourslimit);
        $mform->setType('officehours', PARAM_TEXT);

        // Set maxlength rule and type for office location field.
        $mform->addRule('office', get_string('maximumchars', '', $officelimit),
                        'maxlength', $officelimit);
        $mform->setType('office', PARAM_TEXT);

        // Set maxlength and email verification rules and type for alternate email field.
        $mform->addRule('email', get_string('maximumchars', '', $emaillimit),
                        'maxlength', $emaillimit);
        $mform->addRule('email', get_string('err_email', 'form'), 'email');
        $mform->setType('email', PARAM_EMAIL);

        // Set maxlenth rule and type for phone field.
        $mform->addRule('phone', get_string('maximumchars', '', $phonelimit),
                        'maxlength', $phonelimit);
        $mform->setType('phone', PARAM_TEXT);

        // Set default and type for website field.
        $mform->setDefault('website', $website);
        $mform->setType('website', PARAM_URL);

        // Set defaults for other fields supplied by $defaults.
        if (!empty($defaults)) {
            $mform->setDefault('office', $defaults->officelocation);
            $mform->setDefault('officehours', $defaults->officehours);
            $mform->setDefault('phone', $defaults->phone);
            $mform->setDefault('email', $defaults->email);
        }

        $this->add_action_buttons();
    }

    /**
     * Try to make website a valid url with "http://" in front.
     * 
     * @param arrray $data
     * @param arrray $files
     */
    public function validation($data, $files) {
        $err = array();

        if (!empty($data['website'])) {
            if (!validateUrlSyntax($data['website'], 's+')) {
                // See if it failed because is missing the http:// at the beginning.
                if (validateUrlSyntax('http://' . $data['website'], 's+')) {
                    // It was.
                    $data['website'] = 'http://' . $data['website'];
                    $this->_form->updateSubmission($data, $files);
                } else {
                    $err['website'] = get_string('invalidurl');
                }
            }
        }

        return $err;
    }
}

// End of file.
