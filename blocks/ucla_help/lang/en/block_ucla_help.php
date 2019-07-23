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
 *
 * Strings for ucla_help
 *
 * @package    block_ucla_help
 * @author     Rex Lorenzo <rex@seas.ucla.edu>
 * @copyright  2011 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Help & Feedback';

// Help form text.
$string['name_field'] = 'Name';
$string['email_field'] = 'Email';
$string['course_field'] = 'Course';
$string['description_field'] = 'Details';
$string['type_field'] = 'Type';

$string['submit_button'] = 'Submit';

$string['no_course'] = 'Other, please describe below';

$string['error_empty_description'] = 'A description is required to send a report';

$string['helpbox_header'] = 'Help/Feedback';
$string['helpbox_text_default'] = 'Please use the settings option of the help block to set what displays in the Help Box';

$string['helpform_header'] = 'Report a problem/feedback';
$string['helpform_text'] = 'Use the form below to report a problem, error, feedback, or suggestions.';
$string['helpform_alternative'] = 'CCLE uses your official email address. {$a->students} can change it at MyUCLA and {$a->facultystaff} can change it through their directory coordinator.';

$string['helpform_upload'] = 'Upload a file';

// Report type.
$string['type_bug'] = 'Problem/error';
$string['type_feature'] = 'Feedback/suggestion';

// Used by message being sent.
$string['message_header'] = 'CCLE Help: {$a}';

// Settings page text.
$string['settings_set_in_config'] = 'WARNING: Value set in config.php. Any value set here will be ignored by the system. Check block\'s config.php to edit or view true value.';

$string['settings_boxtext'] = 'Help Box Text';
$string['settings_boxtext_description'] = 'Text that will appear next to the feedback form.';

$string['settings_email_header'] = 'Email settings';
$string['settings_fromemail'] = 'Force from email';
$string['settings_fromemail_description'] = 'If set, then will force any email sent by the Help block to come from this email instead of user.';

$string['settings_jira_header'] = 'JIRA settings';
$string['settings_jira_description'] = 'If set, then all completed feedback forms will automatically create a ticket in JIRA if given support contact is not an email address.';
$string['settings_jira_endpoint'] = 'JIRA endpoint';
$string['settings_jira_user'] = 'JIRA user';
$string['settings_jira_password'] = 'JIRA password';
$string['settings_jira_pid'] = 'JIRA PID';

$string['settings_enablefileuploads'] = 'Enable file uploads';
$string['settings_enablefileuploads_description'] = 'Enable the file attachement uploads for help ticket.';
$string['settings_upload_header'] = 'File Upload settings';

$string['settings_recaptcha_header'] = 'reCAPTCHA setting';
$string['settings_recaptcha_description'] = 'Must have reCAPTCHA key/secret setup in "Site administration > Plugins > Authentication > Manage authentication" in order for setting to show up.';
$string['settings_enablerecaptcha'] = 'Enable reCAPTCHA';
$string['settings_enablerecaptcha_description'] = 'If enabled, displays reCAPTCHA form element for help requests from non-logged in users.';

$string['settings_support_contacts_header'] = 'Support contacts';
$string['settings_support_contacts_description'] = '<p>If a user clicks on a "Help & Feedback" link while in a course, admins ' .
        'can define a support contact based on context levels.</p><p>For example, if a user is in English 1 (shortname=eng1, category=English) ' .
        'and submits a feedback form, then if an admin setup a support contact for the context "eng1", then that person will ' .
        'be contacted. Else if an admin setup a support contact for the context "English", then that person will be contacted. ' .
        'Else the contact specified at the "System" context will be contacted' .
        '<p><strong>Must have someone listed for "System" context.</strong></p>' .
        '</p><p>A point of contact can be either an email address or JIRA user. ' .
        'You can enter multiple email addresses by separating them with a comma. ' .
        'A context can be a category or shortname.</p>';
$string['settings_support_contacts'] = 'Support contacts';
$string['settings_support_contacts_table_context'] = 'Context';
$string['settings_support_contacts_table_contact'] = 'Contact';

// Error messages.
$string['error_emptysystemcontext'] = 'Please enter a value for the "System" support contact.';
$string['error_sending_message'] = 'Sorry, there was a problem with sending your report. Please try again later.';

// Success messages.
$string['success_message_localsupport'] = 'Thank you for contacting us. Your issue has been forwarded to your local support.';
$string['success_message_cclehome'] = 'Thank you for contacting us. Your issue has been forwarded to CCLE support.';

// Side block strings.
$string['hidehelp'] = 'Hide help/legend';