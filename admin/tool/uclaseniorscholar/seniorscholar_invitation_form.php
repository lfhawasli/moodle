<?php
// This file is part of the UCLA Site Invitation Plugin for Moodle - http://moodle.org/
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
 * Form to display invitation.
 *
 * @package    tool_uclaseniorscholar
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    //  It must be included from a Moodle page.
}

require_once($CFG->dirroot . '/enrol/invitation/invitation_form.php');

/**
 * Class for sending invitation to enrol senior scholar in a course.
 *
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class seniorscholar_invitation_form extends invitation_form {

    public function definition() {
        global $CFG, $DB, $USER;
        $mform = & $this->_form;

        // Add some hidden fields.
        $course = $this->_customdata['course'];
        $prefilled = $this->_customdata['prefilled'];
        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);
        $mform->setDefault('courseid', $course->id);

        // Set roles.
        $mform->addElement('header', 'header_role', get_string('header_role', 'tool_uclaseniorscholar'));

        $siteroles = $this->get_roles();
        $label = get_string('assignrole', 'tool_uclaseniorscholar');
        $rolegroup = array();
        $defaultrole = 0;
        foreach ($siteroles as $roletype => $roles) {
            $roletypestring = html_writer::tag('div',
                get_string($roletype, 'tool_uclaroles'),
                array('class' => 'label-bstp label-primary'));
            $rolegroup[] = &$mform->createElement('static', 'role_type_header',
                '', $roletypestring);

            foreach ($roles as $role) {
                $rolestring = parent::format_role_string($role);
                $rolegroup[] = &$mform->createElement('radio', 'roleid', '',
                    $rolestring, $role->id);

                // Set default role as Participant.
                if (!$defaultrole) {
                    $defaultrole = $role->id;
                }
            }
        }
        $mform->addGroup($rolegroup, 'role_group', $label);

        // Set default role.
        $mform->setDefaults(
            array(
                'role_group' => array(
                    'roleid' => $defaultrole
                )
            )
        );

        $mform->addRule('role_group',
                get_string('norole', 'tool_uclaseniorscholar'), 'required');

        // Email address field.
        $mform->addElement('header', 'header_email', get_string('header_email', 'tool_uclaseniorscholar'));
        $mform->addElement('textarea', 'email', get_string('emailaddressnumber', 'tool_uclaseniorscholar'),
                array('maxlength' => 1000, 'class' => 'form-invite-email'));
        $mform->addRule('email', null, 'required', null, 'client');
        $mform->setType('email', PARAM_TEXT);
        // Check for correct email formating later in validation() function.
        $mform->addElement('static', 'email_clarification', '', get_string('email_clarification', 'tool_uclaseniorscholar'));

        // Ssubject field.
        $mform->addElement('text', 'subject', get_string('subject', 'tool_uclaseniorscholar'),
                array('class' => 'form-invite-subject'));
        $mform->setType('subject', PARAM_TEXT);
        $mform->addRule('subject', get_string('required'), 'required');
        // Default subject is "Site invitation for <course title>".
        $defaultsubject = get_string('default_subject', 'tool_uclaseniorscholar',
                sprintf('%s: %s', $course->shortname, $course->fullname));
        $mform->setDefault('subject', $defaultsubject);

        // Message field.
        $mform->addElement('textarea', 'message', get_string('message', 'tool_uclaseniorscholar'),
                array('class' => 'form-invite-message'));
        // Put help text to show what default message invitee gets.
        $mform->addHelpButton('message', 'message', 'tool_uclaseniorscholar',
                get_string('message_help_link', 'tool_uclaseniorscholar'));

        // Email options.
        // Prepare string variables.
        $temp = new stdClass();
        $temp->seniorscholarsupportemail = get_config('tool_uclaseniorscholar', 'seniorscholarsupportemail');
        $mform->addElement('checkbox', 'notify_inviter', '',
                get_string('notify_inviter', 'tool_uclaseniorscholar', $temp));
        $mform->setDefault('notify_inviter', 0);

        // Set defaults if the user is resending an invite that expired.
        if ( !empty($prefilled) ) {
            $mform->setDefault('role_group[roleid]', $prefilled['roleid']);
            $mform->setDefault('email', $prefilled['email']);
            $mform->setDefault('subject', $prefilled['subject']);
            $mform->setDefault('message', $prefilled['message']);
            $mform->setDefault('notify_inviter', $prefilled['notify_inviter']);
        }

        $this->add_action_buttons(false, get_string('inviteusers', 'tool_uclaseniorscholar'));
    }

    private function get_roles() {
        global $DB;
        $shortnams = array('participant', 'visitor');
        $roles = $DB->get_records_list('role', 'shortname', $shortnams, 'sortorder');
        return uclaroles_manager::orderby_role_type($roles);
    }
}