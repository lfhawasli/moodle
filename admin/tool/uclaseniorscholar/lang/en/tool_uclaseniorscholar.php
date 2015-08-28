<?php
// This file is part of the UCLA senior scholar site invitation plugin for Moodle - http://moodle.org/
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
 * Strings for senior scholar site invitation tool.
 *
 * @package    tool_uclaseniorscholar
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['seniorscholaradministratoraccount'] = 'Senior scholar adminstrator UID';
$string['seniorscholaradministratoraccount_instruction'] = 'Enter senior scholar adminstrators\' UIDs.  Seperate them with semicolon.';
$string['seniorscholarsupportemail'] = 'Senior scholar support email';
$string['seniorscholarsupportemail_instruction'] = 'Enter support email for senior scholar';

$string['pluginname'] = 'UCLA Senior Scholar';
$string['pluginname_desc'] = 'Manage senior scholars';
$string['course'] = 'Course';
$string['coursecanlled'] = 'Cancelled';
$string['instructor'] = 'Instructor';
$string['email'] = 'Senior scholar email';
$string['fromlastname'] = 'Senior scholar coordinator';
$string['mainmenu_course'] = 'You can search by term/instructor and term/subject area';
$string['mainmenu_history'] = 'You can search user invite history by term';
$string['all_by_course_current_term'] = 'All items for current term listed by course';
$string['all_by_instructor_current_term'] = 'All items for current term listed by instructor (across all their courses)';
$string['all_by_course_subj_current_term'] = 'All items for current term listed by subject area';
$string['all_by_course_ccle_current_term'] = 'All items for current term within CCLE';

$string['all_by_course'] = 'All items listed by course';
$string['all_by_instructor'] = 'All items listed by instructor (across all their courses)';
$string['all_by_division'] = 'All items listed by course group by division (D) and subject area (S) expressed in aggregate numbers for each status';
$string['all_by_course_subj'] = 'All items listed by course group by subject area (which can be expressed in aggregate numbers for each status)';
$string['all_ccle'] = 'Details of all items within CCLE (for all terms)';
$string['all_by_quarter_year'] = 'Details of all items by quarter and year';
$string['all_filter'] = 'Customize reports';
$string['no_all_by_course'] = 'No course listed';
$string['no_all_by_instructor'] = 'No item listed under this instructor';
$string['no_file'] = 'No file listed for this course';
$string['detail_copyright'] = 'Detail copyright status for the course files';
$string['list_by_course_term'] = '{$a->term}';
$string['ccle'] = 'CCLE';
$string['allterm'] = 'all terms';
$string['list_course_by_term'] = 'List course by';
$string['submit_button'] = 'Submit';
$string['print_button'] = 'Printer friendly';
$string['export_button'] = 'Export to Excel';

$string['no_result'] = 'Your search does not return any result';
$string['class_name'] = 'Course';
$string['invite_link'] = 'Invite user';
$string['history_link'] = 'Invite history';

$string['header_role'] = 'What role do you want to assign to the invitee?';
$string['assignrole'] = 'Assign role';
$string['norole'] = 'Please choose a role.';
$string['header_email'] = 'Who do you want to invite?';
$string['emailaddressnumber'] = 'Email address';
$string['email_clarification'] = 'You may specify multiple email addresses by separating
    them with semi-colons, commas, spaces, or new lines';
$string['subject'] = 'Subject';
$string['default_subject'] = 'Site invitation for {$a}';
$string['message'] = 'Message';
$string['message_help_link'] = 'see what instructions invitees are sent';
$string['message_help'] =
    '<strong>CLASS WEBSITE INSTRUCTIONS:</strong>'.
    '<hr />'.
    'You have been invited to access the class web site: [site name]. ' .
    'After clicking the ACCESS LINK below, you will need to log in ' .
    'using your UCLA logon ID and password, to confirm your access ' .
    'to the site. If you do not have a UCLA logon ID, please follow ' .
    'the UCLALOGON instructions below to create one.<br />' .
    'Be advised that by clicking on the site access link provided in this ' .
    'email you are acknowledging that:<br />' .
    ' --you are the person to whom this email was addressed and for whom this ' .
    '   invitation is intended;<br />' .
    ' --the link below can be used only one time, and will expire on ([expiration date]).<br /><br />' .
    '<strong>ACCESS LINK:</strong>'.
    '<hr />'.
    '[invite url]<br /><br />'.
    '<strong>UCLA LOGON:</strong>'.
    '<hr />'.
    'If you currently do not have a UCLA Logon ID, you can obtain one here: ' .
    'https://logon.ucla.edu/activate.php<br />' .
    'You do not need to be an enrolled student at UCLA or have a 9 digit UID ' .
    'to create a UCLA Logon. When you are asked to identify your role in the ' .
    'UCLA system, select the following option: <br />' .
    '"I do not have a UCLA Identification Number and I am NONE OF THE ABOVE."<br /><br />' .
    'If you think you already have a UCLA Logon ID, but cannot remember it, look it up here: ' .
    'https://logon-asm1.logon.ucla.edu/userlookup.php<br /><br />' .
    '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;--Select the option <strong>"I do not have a UCLA Identification Number"</strong><br /><br />' .
    '<strong>CONTACT INFORMATION:</strong>'.
    '<hr />'.
    'If you believe that you have received this message in error or are in need ' .
    'of assistance, please contact: [senior scholar support email].';

$string['emailmsghtml'] =
    '<strong>CLASS WEBSITE INSTRUCTIONS:</strong>' . "<br />" .
    '------------------------------------------------------------' . "<br /><br />" .
    'You have been invited to access the class web site: {$a->fullname}. ' .
    'After clicking the ACCESS LINK below, you will need to log in ' .
    'using your UCLA logon ID and password, to confirm your access ' .
    'to the site. If you do not have a UCLA logon ID, please follow ' .
    'the UCLALOGON instructions below to create one.' . "<br /><br />" .
    'Be advised that by clicking on the site access link provided in this ' .
    'email you are acknowledging that:' . "<br />" .
    ' --you are the person to whom this email was addressed and for whom this ' .
    '   invitation is intended;' . "<br />" .
    ' --the link below can be used only one time, and will expire on ({$a->expiration}).' . "<br /><br />" .
    '<strong>ACCESS LINK:</strong>' . "<br />" .
    '------------------------------------------------------------' . "<br />" .
    '{$a->inviteurl}' . "<br /><br />" .
    '<strong>UCLA LOGON:</strong>' . "<br />" .
    '------------------------------------------------------------' . "<br />" .
    'If you currently do not have a UCLA Logon ID, you can obtain one here:' . "<br />" .
    'https://logon.ucla.edu/activate.php' . "<br />" .
    'You do not need to be an enrolled student at UCLA or have a 9 digit UID ' .
    'to create a UCLA Logon. When you are asked to identify your role in ' .
    'the UCLA system, select the following option:' . "<br />" .
    '"I do not have a UCLA Identification Number and I am NONE OF THE ABOVE."' . "<br /><br />" .
    'If you think you already have a UCLA Logon ID, but cannot remember it, look it up here: ' .
    'https://logon-asm1.logon.ucla.edu/userlookup.php' . "<br /><br />" .
    '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' .
    '--Select the option <strong>"I do not have a UCLA Identification Number"</strong>' . "<br /><br />" .
    '<strong>CONTACT INFORMATION:</strong>' . "<br />" .
    '------------------------------------------------------------' . "<br />" .
    'If you believe that you have received this message in error or are in need ' .
    'of assistance, please contact: {$a->seniorscholarsupportemail}.';

$string['administratormsghtml'] =
    'MESSAGE FROM PROGRAM ADMINISTATOR:' . "<br />" .
    '------------------------------------------------------------' . "<br />" .
    '{$a}' . "<br /><br />";

$string['emailmsgtxt'] =
    'CLASS WEBSITE INSTRUCTIONS:' . "\n" .
    '------------------------------------------------------------' . "\n\n" .
    'You have been invited to access the class web site: {$a->fullname}. ' .
    'After clicking the ACCESS LINK below, you will need to log in ' .
    'using your UCLA logon ID and password, to confirm your access ' .
    'to the site. If you do not have a UCLA logon ID, please follow ' .
    'the UCLALOGON instructions below to create one.' . "\n\n" .
    'Be advised that by clicking on the site access link provided in this ' .
    'email you are acknowledging that:' . "\n" .
    ' --you are the person to whom this email was addressed and for whom this ' .
    '   invitation is intended;' . "\n" .
    ' --the link below can be used only one time, and will expire on ({$a->expiration}).' . "\n\n" .
    'ACCESS LINK:' . "\n" .
    '------------------------------------------------------------' . "\n" .
    '{$a->inviteurl}' . "\n\n" .
    'UCLA LOGON:' . "\n" .
    '------------------------------------------------------------' . "\n" .
    'If you currently do not have a UCLA Logon ID, you can obtain one here:' . "\n" .
    'https://logon.ucla.edu/activate.php' . "\n" .
    'You do not need to be an enrolled student at UCLA or have a 9 digit UID ' .
    'to create a UCLA Logon. When you are asked to identify your role in ' .
    'the UCLA system, select the following option:' . "\n" .
    '"I do not have a UCLA Identification Number and I am NONE OF THE ABOVE."' . "\n\n" .
    'If you think you already have a UCLA Logon ID, but cannot remember it, look it up here: ' .
    'https://logon-asm1.logon.ucla.edu/userlookup.php' . "\n\n" .
    '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' .
    '--Select the option "I do not have a UCLA Identification Number"' . "\n\n" .
    'CONTACT INFORMATION:' . "\n" .
    '------------------------------------------------------------' . "\n" .
    'If you believe that you have received this message in error or are in need ' .
    'of assistance, please contact: {$a->seniorscholarsupportemail}.';

$string['administratormsgtxt'] =
    'MESSAGE FROM PROGRAM ADMINISTATOR:' . "\n" .
    '------------------------------------------------------------' . "\n" .
    '{$a}' . "\n\n";

$string['inviteusers'] = 'Invite users';
$string['show_from_email'] = 'Allow invited user to contact me at {$a->seniorscholarsupportemail} (your address will be on the "FROM" field. If not selected, the "FROM" field will be {$a->seniorscholarsupportemail})';
$string['notify_inviter'] = 'Notify me at {$a->seniorscholarsupportemail} when invited users accept this invitation';
$string['invitationsuccess'] = 'Invitation successfully sent';
$string['returntoinvite'] = 'Send another invite';
$string['returntosearchcourse'] = 'Return to search course';

$string['nopermissiontosendinvitation'] = 'No permission to send invitation';

// Invite history strings.
$string['invitehistory'] = 'Invite history';
$string['noinvitehistory'] = 'No invites sent out yet';
$string['historytofrom'] = 'To - From';
$string['historyrole'] = 'Role';
$string['historystatus'] = 'Status';
$string['historydatesent'] = 'Date sent';
$string['historydateexpiration'] = 'Expiration date';
$string['historyactions'] = 'Actions';
$string['historyundefinedrole'] = 'Unable to find role. Please resent invite and choose another role.';
$string['historyexpires_in'] = 'expires in';
$string['used_by'] = ' by {$a->username} ({$a->roles}, {$a->useremail}) on {$a->timeused}';

// After invite sent strings.
$string['invitationsuccess'] = 'Invitation successfully sent';
$string['revoke_invite_sucess'] = 'Invitation sucessfully revoked';
$string['extend_invite_sucess'] = 'Invitation sucessfully extended';
$string['resend_invite_sucess'] = 'Invitation sucessfully resent';
$string['returntocourse'] = 'Return to course';
$string['returntoinvite'] = 'Send another invite';

// Invite status strings.
$string['status_invite_invalid'] = 'Invalid';
$string['status_invite_expired'] = 'Expired';
$string['status_invite_used'] = 'Accepted';
$string['status_invite_used_noaccess'] = '(no longer has access)';
$string['status_invite_used_expiration'] = '(access ends on {$a})';
$string['status_invite_revoked'] = 'Revoked';
$string['status_invite_resent'] = 'Resent';
$string['status_invite_active'] = 'Active';

// Invite action strings.
$string['action_revoke_invite'] = 'Revoke invite';
$string['action_extend_invite'] = 'Extend invite';
$string['action_resend_invite'] = 'Resend invite';
$string['action_unenroll'] = 'Unenroll';

// Bulk upload invitation.
$string['importfile'] = 'Import invitation file';
$string['separator'] = 'Separator';
$string['sepcolon'] = 'Colon';
$string['sepcomma'] = 'Comma';
$string['sepsemicolon'] = 'Semicolon';
$string['septab'] = 'Tab';
$string['uploadseniorscholar'] = 'Upload senior scholars';
$string['sendinvites'] = 'Send invites';
$string['status'] = 'Upload status';
$string['notavailableforimport'] = 'Not available for import';
$string['alreadyinvite'] = 'There is already invite for this email for this course';
$string['readyforimport'] = 'Ready for import';
$string['bulkupload_button'] = 'Bulk upload senior scholars';
$string['bulkupload_byterm'] = 'You can bulk upload senior scholar for invites';
$string['encoding'] = 'Encoding';
$string['coursenotexists'] = 'Course not exists';
$string['nobulkinvite'] = 'No course available for bulk invite';
$string['continue'] = 'Continue';

// Unenrol user.
$string['unenrol'] = 'Unenroll user';
$string['unenroluser'] = 'Do you really want to unenroll "{$a->user}" from course "{$a->course}"?';