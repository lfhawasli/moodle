<?php
// This file is part of the UCLA course creator plugin for Moodle - http://moodle.org/
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
 * English language strings for uclacourserequestor.
 *
 * @package    tool_uclacourserequestor
 * @copyright  2011 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = "UCLA course requestor";
$string['uclacourserequestor'] = $string['pluginname'];
$string['courserequestor:view'] = "View " . $string['pluginname'];

$string['srslookup'] = "SRS number lookup (Registrar)";

// Fetch from Registrar.
$string['registrarunavailable'] = 'The Registrar is unavailable, please try again later.';
$string['fetch'] = 'Get courses from Registrar';
$string['buildcourse'] = "Get course";
$string['builddept'] = "Get subject area courses";

$string['views'] = 'View existing requests';
$string['viewcourses'] = "View/Edit existing requests";
$string['viewrequest'] = "Edit this request";
$string['buildcoursenow'] = "Build courses now";
$string['alreadybuild'] = "Course build in progress";
$string['queuebuild'] = "Course build queued";

// Status readable.
$string['build'] = "To be built";
$string['failed'] = "Failed";
$string['live'] = "Live";

$string['delete'] = 'Delete';

// This string should be rarely used.
$string['noviewcourses'] = "There are no existing requests.";

$string['crosslistnotice'] = "You can add crosslists while these couses are waiting in queue to be built.";

$string['error'] = 'Some of the courses that you have requested have problems with them. Please look over them and if needed, submit your requests again.';
$string['warning'] = 'Some of the courses that you have requested have different values than default. Be sure to look over them if this message is unexpected.';
$string['pasttermalert'] = 'The courses that you have requested are courses from a past term.';

$string['all_department'] = 'All subject areas';
$string['all_action'] = 'All statuses';
$string['all_srs'] = '';

$string['noinst'] = 'Not Assigned';

$string['newrequestid'] = 'New entry';
$string['newrequestcourseid'] = 'Not built yet';

$string['checkchanges'] = 'Validate requests without saving changes';
$string['submitfetch'] = 'Submit requests';
$string['submitviews'] = 'Save changes';

$string['norequestsfound'] = 'No course(s) found at the Registrar.';
// Note - this is UCLA_REQUESTOR_VIEW constant's value.
$string['norequestsfound-views'] = 'No requests found.';

$string['optionsforall'] = 'Options that can affect all requests';
$string['requestorglobal'] = 'Email to contact when these courses are built: ';
$string['mailinsttoggle'] = 'Toggle email instructors';
$string['buildfilters'] = 'Build filters:';

// Table headers for the requests.
$string['id'] = 'Request ID';
$string['courseid'] = 'Associated Course ID';
$string['term'] = 'Term';
$string['srs'] = 'SRS';
$string['course'] = 'Course';
$string['department'] = 'Department';
$string['instructor'] = 'Instructors';
$string['requestoremail'] = 'Requestor email';
$string['crosslist'] = 'Crosslist?';
$string['timerequested'] = 'Time requested';
$string['action'] = 'Status';
$string['status'] = 'Condition';
$string['mailinst'] = 'Email instructors';
$string['hidden'] = 'Course built hidden from students';
$string['nourlupdate'] = 'Do NOT send URL to MyUCLA';
$string['crosslists'] = 'Crosslisted SRSes';

$string['deletefetch'] = 'Ignore';
$string['deleteviews'] = 'Remove request';

$string['addmorecrosslist'] = 'Add additional SRS';

$string['clchange_removed'] = 'Removed crosslist: ';
$string['clchange_added'] = 'Added crosslist: ';

$string['nochanges'] = 'Nothing was changed.';

// Crosslisting errors.
$string['illegalcrosslist'] = 'This SRS has already been requested to be built.';
$string['hostandchild'] = 'This course or one of its crosslists has already been built, and is preventing this request from proceeding.';
$string['srserror'] = 'The SRS number must be exactly 9 digits long';
$string['cancelledcourse'] = 'This course is marked as cancelled by the Registrar.';
$string['nosrsfound'] = 'Could not find course with this SRS.';
$string['builtbyanothersystem'] = 'This course is marked as already existing at {$a}.';

$string['queuetobebuilt'] = "Courses in queue to be built";
$string['queueempty'] = "The queue is empty. All courses have been built as of now.";

$string['alreadysubmitted'] = "This SRS number has already been submitted as a request.";
$string['checktermsrs'] = "Cannot find course. Please check the term and SRS again.";
$string['childcourse'] = " has either been submitted for course creation or is a child course";
$string['duplicatekeys'] = "Duplicate entry. The alias is already inserted.";
$string['checksrsdigits'] = "Please check your SRS input. It has to be a 9 digit numeric value.";
$string['submittedforcrosslist'] = "Submitted for crosslisting";
$string['newaliascrosslist'] = "New aliases submitted for crosslisting with host: ";
$string['crosslistingwith'] = " - submitted for crosslisting with ";
$string['individualorchildcourse'] = " is already submitted individually or as a child course. ";
$string['submittedtobebuilt'] = " submitted to be built ";

$string['deletesuccess'] = "Deleted request entry: {\$a}";
$string['deletecoursesuccess'] = "Delete request entry: {\$a}, along with course.";
$string['savesuccess'] = "Updated request entry: {\$a}";
$string['insertsuccess'] = "Inserted request entry: {\$a}";
$string['deletecoursefailed'] = "Cannot delete request. To delete request please delete the actual course.";
$string['savefailed'] = 'Request entry could not be saved: {$a}';

$string['changedto'] = ' to ';

$string['uclacourserequestor:edit'] = 'Ability to view/use UCLA course requestor';