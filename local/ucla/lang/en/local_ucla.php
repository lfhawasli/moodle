<?php
// This file is part of Moodle - http://moodle.org/
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
 * The local_ucla language file.
 *
 * @package    local_ucla
 * @copyright 2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'UCLA customizations';

$string['access_failure'] = 'Your access control systems are not properly set up, configuration files in the "local/ucla/" directory may be web visible!';

$string['curl_failure'] = 'cURL is not installed, your configuration files\' web visibility could not be tested!';

$string['term'] = 'Term';
$string['invalidrolemapping'] = 'Could not find role mapping {$a}';

$string['ucla:viewall_courselisting'] = 'Allows user to see all courses another user is associated with on their profile';

$string['external-link'] = 'External website (opens new window)';

/* strings for datetimehelpers */

// For distance_of_time_in_words.
$string['less_than_x_seconds'] = 'less than {$a} seconds';
$string['half_minute'] = 'half a minute';
$string['less_minute'] = 'less than a minute';
$string['a_minute'] = '1 minute';
$string['x_minutes'] = '{$a} minutes';
$string['about_hour'] = 'about 1 hour';
$string['about_x_hours'] = 'about {$a} hours';
$string['a_day'] = '1 day';
$string['x_days'] = '{$a} days';

// CCLE-3158 - Use UCLA specific lang string for copyright help icon in filepicker.
$string['license_help'] = 'This question requires you to declare the copyright
status of the item you are uploading. Each option is explained in greater detail
below.

<strong>I own the copyright.</strong>
<br />
You are an author of this work and have not transferred the rights to a
publisher or any other person.

<strong>The UC Regents own the copyright.</strong>
<br />
This item’s copyright is owned by the University of California Regents; most
items created by UC staff fall into this category.

<strong>Item is licensed by the UCLA Library.</strong>
<br />
This item is made available in electronic form by the UCLA library. <i> Note:
the UCLA Library would prefer that you provide a link to licensed electronic
resources rather than uploading the file to your CCLE course.</i>

<strong>Item is in the public domain.</strong>
<br />
Generally, an item is in the public domain if one of the following applies:
<ol>
    <li>It was published in the U.S. before 1923.</li>
    <li>It is a product of the federal government.</li>
    <li>The term of copyright, which is generally the life of the author plus
    seventy years, has expired.</li>
</ol>

<strong>Item is available for this use via Creative Commons license.</strong>
<br />
Many items are made available through Creative Commons licenses, which specify
how an item may be reused without asking the copyright holder for permission.
Similar “open source” licenses would also fit under this category. See
<a href="http://creativecommons.org/" target="_blank">creativecommons.org</a>
for more information.

<strong>I have obtained written permission from the copyright holder.</strong>
<br />
This answer applies if you have contacted the copyright holder of the work and
have written permission to use the work in this manner.  Note: You should keep
this written permission on file.

<strong>I am using this item under fair use.</strong><br />
Fair use is a right specifically permitting educational, research, and scholarly
uses of copyrighted works.  However, <u>not every educational use is
automatically a fair use</u>; a
<a href="http://copyright.universityofcalifornia.edu/fairuse.html#2" target="_blank">four-factor analysis</a>
must be applied to each item.

<strong>Copyright status not yet identified.</strong>
<br />
Select <strong>only</strong> if this upload is being performed by <u>someone besides the
instructor of record</u> at the instructor’s behest, but the instructor did not
clarify the copyright status.

Note: if you believe none of these answers apply, you should not upload the item.
For more details  on copyright status and fair use, go to the
<a href="http://copyright.universityofcalifornia.edu/fairuse.html" target="_blank">UC copyright fair use page</a>,
use ARL’s <a href="http://www.knowyourcopyrights.org/bm~doc/kycrbrochurebw.pdf" target="_blank">Know Your Copy Rights</a>
brochure, or read their great <a href="http://www.knowyourcopyrights.org/resourcesfac/faq/online.shtml" target="_blank">FAQ</a>.
If you have questions regarding the above or need assistance in determining
copyright status, please email <a href="mailto:copyright@library.ucla.edu">copyright@library.ucla.edu</a>
for a consultation. <strong>It is the instructor of record’s responsibility to
comply with copyright law in the handling of course materials;</strong> see the
<a href="'.$CFG->wwwroot.'/theme/uclashared/view.php?page=copyright">CCLE copyright information page</a>
    for more details.
';

// SSC-1306 - Let instructors know when if the announcements forum is hidden.
$string['announcementshidden'] = 'The Announcements forum is currently hidden: Emails will NOT be sent out to students.';
$string['unhidelink'] = 'Click here to unhide';
$string['askinstructortounhide'] = 'Please ask the instructor to unhide this forum.';

// Capability strings.
$string['ucla:assign_all'] = 'CCLE-2530: Can see the entire user database when assigning roles';
$string['ucla:editadvancedcoursesettings'] = 'CCLE-3278: Can edit the course settings for category, format, maximum upload size, or language defaults';
$string['ucla:deletecoursecontentsandrestore'] = 'CCLE-3446: Can delete course contents when restoring a course';
$string['ucla:editcoursetheme'] = 'CCLE-2315: Can edit the theme a course uses';
$string['ucla:bulk_users'] = 'CCLE-2970: Can perform bulk user actions';
$string['ucla:browsecourses'] = 'CCLE-3773: Gives users link to "Add/edit courses"';
$string['ucla:vieweventlist'] = 'CCLE-4671: Can view event list page';
$string['ucla:viewscheduledtasks'] = 'CCLE-4999: Can view scheduled tasks page';

// CCLE-3028 - Fix nonlogged users redirect on hidden content.
// If a user who is not logged in tries to access private course information.
$string['login'] = 'Log in';
$string['loginredirect'] = 'Login required';
$string['notloggedin'] = 'Please login to view this content.';

// Strings for notice_course_status.
$string['notice_course_status_hidden'] = 'This site is unavailable.';
$string['notice_course_status_pasthidden'] = 'You are viewing a course that is no longer in session.';
$string['notice_course_status_pastinstructor'] = 'You are viewing a site for a course that is no longer in session. Student access will expire at the end of Week 2 of the subsequent term.';
$string['notice_course_status_paststudent'] = 'You are viewing a site for a course that is no longer in session. Your access will expire at the end of Week 2 of the subsequent term.';
$string['notice_course_status_pasthidden_login'] = 'You are viewing a course that is no longer in session. This is the public display of the course site. If you are enrolled, please log in to view private course materials.';
$string['notice_course_status_pasthidden_nonenrolled'] = 'You are viewing a course that is no longer in session. You need to be associated with the course to view private course materials.';
$string['notice_course_status_pasthidden_tempparticipant'] = 'You are viewing a site for a course that is no longer in session. Student access has expired. Use the <a href="{$a}">Site invitation tool</a>/Temporary Participant role to grant temporary access to this site.';
$string['notice_course_status_temp'] = 'You have temporary access to this site. Your access will expire after {$a}.';
$string['notice_course_status_pasttemp'] = 'You have temporary access to a site for a course that is no longer in session. Your access will expire after {$a}.';
$string['notice_course_status_hiddentemp'] = 'You have temporary access to a site that is currently unavailable. Your access will expire after {$a}.';
$string['notice_course_status_pasthiddentemp'] = 'You have temporary access to a site for a course that is no longer in session. Your access will expire after {$a}.';

$string['lti_warning'] = 'There are risks using external tools. Please read ' .
        'this help document for more information: ' .
        '<a target="_blank" href="https://docs.ccle.ucla.edu/w/LTI">https://docs.ccle.ucla.edu/w/LTI</a>';

// Settings.
$string['student_access_ends_week'] = 'Prevent student access on week';
$string['student_access_ends_week_description'] = 'When the specified week starts, the system will automatically ' .
        'hide all courses for previous term. For example, if "3" is given, then when "Week 3" starts for Spring ' .
        'Quarter, then all courses for Winter will be hidden automatically. Also, if set, will prevent "My sites" ' .
        'from listing the previous term\'s courses for students. If set to "0" no courses will be hidden ' .
        'automatically and "My sites" is not restricted.';
$string['coursehidden'] = '<p>This course is unavailable for students. Students ' .
        'can access their course sites from a prior term during the first ' .
        'two weeks of the subsequent term.</p><p>If additional access is ' .
        'needed, students should contact the course instructor. </p>';
$string['overrideenroldatabase'] = 'Override database enrollment plugin';
$string['overrideenroldatabasedesc'] = 'Override the database enrollment plugin to use UCLA specific customizations.';
$string['minuserupdatewaitdays'] = 'User information update delay';
$string['minuserupdatewaitdays_desc'] = 'Number of days since a user last used the site before updating their first name, last name and/or email from the external database.';
$string['handlepreferredname'] = 'Enable preferred name';
$string['handlepreferrednamedesc'] = 'If enabled, will use alternatename field as preferred name and change fullname display depending on course context for user.';
$string['registrarurlconfig'] = 'Registrar URL';
$string['registrarurlconfighelp'] = 'Set the URL for UCLA Registrar.';

// Form submit login check.
$string['longincheck_login'] = 'Your session has timed out. In order to save
         your work login again and save your changes.';
$string['logincheck_idfail'] = 'Your user ID does not match!  This form has been ' .
        'disabled in order to prevent an erroneous submission. ' .
        'Save your work and reload this page.';
$string['logincheck_networkfail'] = 'There was no response from the server. ' .
        'You might be disconnected from your network. Please reconnect and try again.';
$string['logincheck_success'] = 'You\'re logged in.  You can now submit this form.';

// CCLE-3652 - Students unable to see "Submission Grading" link on Assignment module.
$string['submissionsgrading'] = 'Grading criteria';

// CCLE-3970 - Install and evaluate LSU's Gradebook Improvements.
$string['overridecat'] = 'Allow Grade Override';
$string['overridecat_help'] = 'This option allows users to override the final grade in for category totals. Unchecking this option will make category totals uneditable.';

// CCLE-4293 - Display a message when attempting to override grades.
$string['overridewarning'] = 'If you override a grade
    you will lose the ability to make any additional changes to the grade from
    within the activity itself. To change the grade from within the activity,
    simply click on the item name in the Grader report or click on the activity
    from the course homepage.';

// CCLE-4295 Add groups filter to grader report.
$string['view_grouping'] = 'View grouping';
$string['all_groupings'] = 'All';

// MUC strings.
$string['cachedef_rolemappings'] = 'UCLA role mapping';
$string['cachedef_urcmappings'] = 'UCLA ucla_request_classes to Moodle courses';
$string['cachedef_usermappings'] = 'UCLA user idnumber/username to Moodle users';
$string['cachedef_esbtoken'] = 'Stores the active ESB token';

// CCLE-6512 - Profile Course details doesn't match My page Class sites.
$string['cantfindcourse'] = '* Can\'t find your course or collaboration site? Check {$a->altsystemname} or {$a->myucla}.';

// CCLE-4415 - Prompt deletion warning.
$string['deletecoursewarning'] = 'WARNING<p>You are deleting a course for which content has been added.</p>';
$string['deletecoursesafe'] = 'You are deleting a course for which no content has been added.';

// CCLE-4416 - Prompt overwriting warning.
$string['deletecoursecontenttitle'] = 'Course deletion warning';
$string['deletecoursecontentyes'] = 'Continue';
$string['deletecoursecontentno'] = 'Cancel';

$string['deletecoursecontentwarning'] = '<p>You are about to delete the content of the site:</p>'
        . '<p>{$a->shortname} ({$a->fullname})</p>'
        . '<p>To ensure that your content is saved, first <a href="{$a->backuplink}">create a backup.</a>  If you are sure you want to delete the content, press "Continue".';

// CCLE-7187 - Reimplement-CCLE-4416 Prompt overwriting warning.
$string['deleterestoretitle'] = "Delete the contents of the course and then restore";
$string['deletewarning'] = 'WARNING';
$string['deleterestorewarning'] = 'You are about to delete a course for which content has been added.<br />';
$string['deleterestoresafe'] = 'You are about to delete a course for which no content has been added.<br />';
$string['backuprestore'] = 'If you want back up this course now to ensure your course content is saved, click "Backup."<br />';
$string['deleterestore'] = 'If you are absolutely sure you want to delete this course and all the data it contains, click "Continue."';
$string['deletecurrentcourse'] = 'Delete the contents of the course and then restore';

// CCLE-3843 - Single file recovery.
$string['logfiledeleted'] = 'Deleted file';

// CCLE-3673 - Limit crosslist display using config value.
$string['maxcrosslistshown'] = 'Max crosslists shown';
$string['maxcrosslistshowndesc'] = 'Limit the amount of crosslisted courses shown on a course page.';

$string['crontask'] = 'Cron task for local_ucla';

// CCLE-4424 - Reject attempt on quiz timeout.
$string['confirmstartwarningheader'] = 'Warning: ';
$string['confirmstartwarningmessage'] = 'You must click submit for this attempt to be counted.';
$string['confirmstartwarningattemptlimit'] = 'You have {$a} attempts.';
$string['confirmstartwarningtimelimit'] = 'The time limit is {$a} minutes.';
$string['confirmstartwarningprompt'] = 'Are you sure that you wish to start?';
$string['finishsummary'] = 'Return to Quiz View';
$string['persistentquizwarningmessage'] = 'This quiz is not automatically submitted. Please be sure to submit your quiz attempt before the time runs out or your attempt will not be counted.';
$string['quizattemptabandoned'] = 'You did not submit your attempt before the time ran out. Your attempt was counted as zero.';

// CCLE-5099 - Alert instructors who use TurnItIn Direct and MyUCLA.
$string['turnitinwarning'] = 'Be aware there are two portals for TurnItIn submissions: MyUCLA and '
        . 'CCLE. Only one should be used per course.<br/><br/>For further information please read ';

// CCLE-4568 - Need a better screen for users who log into Moodle without an email address.
$string['errmissingemail'] = 'Because your email address is missing from your CCLE profile, you will not be able to login to CCLE until you update your official UCLA email address.<br/><br/>Instructions vary for faculty/staff and students. For more information go to: <a href="http://directory.ucla.edu/update.php">http://directory.ucla.edu/update.php</a>';

// CCLE-4329 - Handling public forums.
$string['warningpublicforum'] = 'This forum is public and viewable by anyone, not just those enrolled in the site.';

// SSC-966 - Change "Add a new discussion topic" wording.
$string['forumaddannouncement'] = 'Add an announcement';
$string['forumyournewannoucement'] = 'Your new announcement';

// CCLE-5316 - Checkbox to message students.
$string['noselectedusers'] = 'No users selected.';

// CCLE-5486 - Welcome message for registrar enrollment method.
$string['sendcoursewelcomemessage'] = 'Send course welcome message';
$string['sendcoursewelcomemessage_help'] = 'If enabled, users receive a welcome message via email when they enrol in a course.';
$string['welcometocourse'] = 'Welcome to {$a}';
$string['welcometocoursetext'] = 'Welcome to {$a->coursename}!

URL: {$a->courseurl}
';
$string['customwelcomemessage'] = 'Custom welcome message';
$string['customwelcomemessage_help'] = 'A custom welcome message may be added as plain text or Moodle-auto format, including HTML tags and multi-lang tags.

The following placeholders may be included in the message:

* Course name {$a->coursename}
* Link to course page {$a->courseurl}
* Link to user\'s profile page {$a->profileurl}';

// CCLE-5640 - Custom From field of welcome message for registrar enrollment method.
$string['customwelcomemessagesubject'] = 'Subject';
$string['customwelcomemessagefrom'] = 'From';
$string['customwelcomemessagefrom_help'] = 'Please enter the email address you would like student to reply to.';

// CCLE-4863 - Prompt FERPA waiver for LTI/External Plugins.
$string['ferpawaiverdesc'] = 'Before you may continue you must agree to the following statements.
<ul>
<li>I understand that by providing personal and academic information on this web site I am providing this information to third parties not affiliated with UCLA.</li>
<li>By providing personal or academic information I understand that I am publicly acknowledging my status as a student and my association with UCLA.</li>
<li>By providing personal or academic information on this web site I understand that I am waiving my FERPA rights to privacy.</li>
</ol>';
$string['ferpawaivererror'] = 'FERPA waiver already signed.';
$string['ferpawaivermoreinfo'] = '(For more information on FERPA go to {$a})';
$string['ferpawaiverrequired'] = 'FERPA waiver needs to be signed for this course activity/resource.';

$string['eventsyncenrolmentsfinished'] = 'Enrol sync finished';

// CCLE-5989 - Activity availability conditions popup.
$string['availabilityconditions'] = 'Access restrictions';

// SSC-2050 - Include associated courses in subject header of forum emails.
$string['limitcrosslistemailname'] = 'Cross Listed Course Limit';
$string['limitcrosslistemaildesc'] = 'Limit of short course names appearing on email subject.';

// CCLE-6760 - Rename Miscellaneous to Site Activity.
$string['siteactivity'] = 'Site activity';

// CCLE-5910 - Migrate events for local_ucla.
$string['clear_srdb_ucla_syllabus_task'] = 'Clear syllabus links from SRDB.';
$string['update_srdb_ucla_syllabus_task'] = 'Updates syllabus links at SRDB.';

// SSC-3739/CCLE-6785 - Grade import fixes.
$string['duplicateuser'] = 'Please check these id\'s and remove duplicates:{$a}';
$string['incompleteuser'] = 'Please check and fix these incomplete id\'s:{$a}';

// CCLE-6807 - Improve Group/Grouping column on Participants page.
$string['groupsandgroupings'] = 'Group /<br />Grouping';

// SSC-3723/CCLE-6923 - Grades: Import fails on bad UID.
$string['incompleteimportstart'] = 'User mapping error: Could not find users with id numbers:';
$string['incompleteimportstatus'] = 'Incomplete import. All input UID\'s except those above were imported correctly.
        Please fix the entries for only UID\'s displayed above.';

// CCLE-7791 - Add "E-mail Students (via Announcements Forum)" into Admin Panel.
$string['announcementshidden'] = 'The Announcements forum is currently hidden: Emails will NOT be sent out to students.';
$string['unhidelink'] = 'Click here to unhide';
$string['askinstructortounhide'] = 'Please ask the instructor to unhide this forum.';
// CCLE-6644 - Registrar web service connection settings.
$string['localucla'] = 'UCLA';
$string['localsettingesb'] = 'Enterprise service bus (ESB)';
$string['esbstatus'] = 'ESB status';
$string['esbtoken'] = 'ESB token is {$a}';
$string['esburl'] = 'Web service URL';
$string['esburlhelp'] = 'URL to make ESB web service calls to. Can be QA or PROD versions.';
$string['esbusername'] = 'Username';
$string['esbusernamehelp'] = 'Username to connect to the ESB.';
$string['esbpassword'] = 'Password';
$string['esbpasswordhelp'] = 'Password to connect to the ESB.';
$string['esbcert'] = 'CERT file';
$string['esbcerthelp'] = 'Path on the server to the CERT file. Must not be publically accessible.';
$string['esbprivatekey'] = 'Private key file';
$string['esbprivatekeyhelp'] = 'Path on the server to the Private Key file. Must not be publically accessible.';
