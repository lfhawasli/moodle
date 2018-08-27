<?php
$string['pluginname'] = 'UCLA support console';

$string['notitledesc'] = '(no description)';

// Titles
$string['logs'] = 'Log tools';
$string['users'] = 'User tools';
$string['srdb'] = 'Registrar tools';
$string['modules'] = 'Module tools';

// System logs
$string['syslogs'] = 'Last 1000 lines of Moodle cron, Course creator, or Pre-pop logs';
$string['syslogs_info'] = 'If a selection is disabled, then the corresponding log file was not found.';
$string['syslogs_select'] = 'Select a log file';
$string['syslogs_choose'] = 'Choose log...';
$string['log_apache_error'] = 'Apache error';
$string['log_apache_access'] = 'Apache access';
$string['log_apache_ssl_access'] = 'Apache SSL access';
$string['log_apache_ssl_error'] = 'Apache SSL error';
$string['log_apache_ssl_request'] = 'Apache SSL request';
$string['log_shibboleth_shibd'] = 'Shibboleth daemon';
$string['log_shibboleth_trans'] = 'Shibboleth transaction';
$string['log_moodle_cron'] = 'Moodle cron';
$string['log_course_creator'] = 'Course creator';
$string['log_prepop'] = 'Pre-pop';

// Other logs
$string['prepopfiles'] = 'Show pre-pop files';
$string['prepopview'] = 'Show latest pre-pop output';
$string['prepoprun'] = 'Run prepop for one course';
$string['moodlelog'] = 'Last Moodle 100 log entries';
$string['moodlelog_select'] = 'Select which types of log entries to view';
$string['moodlelog_filter'] = 'Filter log by action types';
$string['moodlelogins'] = 'Logins during the last 24 hours';
$string['moodlelogbyday'] = 'Moodle logs by day';
$string['moodlelogbydaycourse'] = 'Moodle logs by day and course (past 7 days, limited to top 100 results)';
$string['moodlelogbydaycourseuser'] = 'Moodle logs by day, course, and user (past 7 days, limited to top 100 results)';
$string['moodlevideoreserveslist'] = 'Courses using Video reserves';
$string['moodlelibraryreserveslist'] = 'Courses using Library reserves';
$string['moodledigitalmediareserveslist'] = 'Courses using Digital media reserves';
$string['moodlebruincastlist'] = 'Courses using Bruincast';
$string['sourcefile'] = 'Data source: {$a}';
$string['recentlysentgrades'] = 'Show 100 most recent MyUCLA grade log entries';

// Users
$string['moodleusernamesearch'] = 'Users with firstname and/or lastname';
$string['roleassignments'] = 'Role assignments (summary and list) by name, context, and site type';
$string['userswithrole'] = 'Users with the given role assignment';
$string['viewrole'] = 'View 1 role assignment';
$string['viewroles'] = 'View {$a} role assignments';
$string['exportroles'] = 'Export';
$string['countnewusers'] = 'Most recently created users';
$string['pushgrades'] = 'Manually push grades to MyUCLA';
$string['noenrollments'] = 'There are no enrollments';
$string['usersdescription'] ='Users with role: {$a->role}, Context: {$a->contextlevel}, Component: {$a->component}, and Type: {$a->type}';
$string['listdupusers'] = "Users with multiple ccle accounts";
// The SRDB
$string['enrollview'] = 'Enrollment history per UID (<a target="_blank" href="https://ccle.ucla.edu/mod/page/view.php?id=3318">enroll2</a>)';

// For each stored procedure, the name is dynamically generated.
// The item itself will be there when the SP-object is coded, but there
// will be no explanation unless the code here is changed (or the SRDB
// layer is altered to include descriptions within the object).
$string['ccle_coursegetall'] = 'Get all courses in a subject area for BrowseBy (CCLE <a target="_blank" href="https://ccle.ucla.edu/mod/page/view.php?id=3305">ccle_coursegetall</a>)';
$string['ccle_courseinstructorsget'] = 'Instructor list by course per term (<a target="_blank" href="https://ccle.ucla.edu/mod/page/view.php?id=3306">ccle_courseinstructorsget</a>)';
$string['ccle_getclasses'] = 'Course info by srs# (description, type, enroll status) (<a target="_blank" href="https://ccle.ucla.edu/mod/page/view.php?id=3308">ccle_getclasses</a>)';
$string['ccle_getinstrinfo'] = 'Instructors by subject area per term (<a target="_blank" href="https://ccle.ucla.edu/mod/page/view.php?id=3309">ccle_getinstrinfo</a>)';
$string['ccle_roster_class'] = 'Class roster by srs# per term (<a target="_blank" href="https://ccle.ucla.edu/mod/page/view.php?id=3310">ccle_roster_class</a>)';
$string['cis_coursegetall'] = 'Courses by subject area per term (CIS <a target="_blank" href="https://ccle.ucla.edu/mod/page/view.php?id=3311">cis_coursegetall</a>)';
$string['cis_subjectareagetall'] = 'Subject area codes and full names (<a target="_blank" href="https://ccle.ucla.edu/mod/page/view.php?id=3313">cis_subjectareagetall</a>)';
$string['ucla_getterms'] = 'UCLA term types (<a target="_blank" href="https://ccle.ucla.edu/mod/page/view.php?id=3315">ucla_getterms</a>)';
$string['ucla_get_user_classes'] = 'Get courses for My sites (<a target="_blank" href="https://ccle.ucla.edu/mod/page/view.php?id=16788">ucla_get_user_classes</a>)';
$string['ccle_class_sections'] = 'Course sections detail by primary srs# (<a target="_blank" href="https://ccle.ucla.edu/mod/page/view.php?id=3304">ccle_class_sections</a>)';
$string['ccle_get_primary_srs'] = 'Primary course srs# by discussion section srs# (<a target="_blank" href="https://ccle.ucla.edu/mod/page/view.php?id=37526">ccle_get_primary_srs</a>)';
$string['ccle_classcalendar'] = 'Course meeting dates/times (<a target="_blank" href="https://ccle.ucla.edu/mod/page/view.php?id=3303">ccle_classcalendar</a>)';
$string['ucla_get_course_srs'] = 'Get srs by term, subject area, unformatted catalog number, and unformatted section number (<a target="_blank" href="https://ccle.ucla.edu/mod/page/view.php?id=395287">ucla_get_course_srs</a>)';
$string['ccle_ta_sections'] = 'Get list of sections and TAs (<a target="_blank" href="https://ccle.ucla.edu/mod/page/view.php?id=1008388">ccle_ta_sections</a>)';

$string['unknownstoredprocparam'] = 'This stored procedure has a unknown parameter type. This needs to be changed in code.';

$string['courseregistrardifferences'] = 'Courses with changed descriptions';
$string['showreopenedclasses'] = "Reopened classes per term";

// Module
$string['assignmentquizzesduesoon'] = 'Show courses with assignments or quizzes due soon';
$string['assignmentquizzesduesoonmoreinfo'] = 'From {$a->start} to {$a->end} ({$a->days} days)';
$string['modulespercourse'] = 'Count module totals and module types per course';
$string['modulespertacourse'] = 'Count module totals and module types per TA site';
$string['syllabusreoport'] = 'Syllabus report by subject area';
$string['syllabus_header_course'] = '{$a->term} Course ({$a->num_courses})';
$string['syllabus_header_instructor'] = 'Instructors';
$string['syllabus_header_public'] = 'Public ({$a})';
$string['syllabus_header_private'] = 'Private ({$a})';
$string['syllabus_header_manual'] = 'Manual ({$a})';
$string['syllabusoverview'] = 'Syllabus overview by dvision/subject area';
$string['syllabus_browseby'] = 'Browse by';
$string['syllabus_iei'] = 'Only display courses billed with an IEI fee';
$string['syllabus_division'] = 'Division';
$string['syllabus_subjarea'] = 'Subject area';
$string['syllabus_count'] = 'Syllabus/Courses<br />{$a}';
$string['syllabus_ugrad_table'] = 'Undergraduate courses';
$string['syllabus_grad_table'] = 'Graduate courses';
$string['public_syllabus_count'] = 'Public<br />{$a}';
$string['loggedin_syllabus_count'] = 'UCLA community<br />{$a}';
$string['preview_syllabus_count'] = 'Preview<br />{$a}';
$string['private_syllabus_count'] = 'Private<br />{$a}';
$string['manual_syllabus_count'] = 'Manual<br />{$a}';
$string['syllabustimerange'] = 'Displaying uploaded syllabi';
$string['nocourses'] = 'No courses found.';
$string['syllabusnotesnoniei'] = 'Does not include cancelled or tutorial courses.';
$string['syllabusnotesiei'] = 'Only includes courses billed an IEI fee.';
$string['syllabusoverviewnotes'] = 'The preview syllabus percentage is counted against the total number of "Public" and "UCLA community" syllabi. ' .
        'A manual syllabus is always counted, but only increments the total number of syllabi if no other syllabus type is found.';
$string['syllabusreoportnotes'] = 'The manual syllabus column counts the number of manual syllabi in a course.';
$string['syllabusieiwarning'] = 'Your query returned no results. '
        . 'Please note that this may be because IEI data for the quarter you requested has not yet been uploaded.';
$string['tasites'] = 'TA sites by term';
$string['mediausage'] = 'Media usage';
$string['mediausage_help'] = 'Lists course with video content for a given term.';
$string['syllabusrecentlinks'] = 'Recently updated syllabus links at Registrar';
$string['syllabuslinkslimit'] = 'Get last {$a} results';
$string['visiblecontentlist'] = 'List courses with visible content in hidden sections';
$string['unhiddencourseslist'] = 'List unexpected visiblity of courses';
$string['unhiddencourseslist_help'] = 'For courses before current term, will display visible courses. For current and future terms, will show courses that are hidden.';
$string['coursedownloadhistory'] = 'Course download requests history';
$string['coursedownloadhistorytotalrequests'] = '# of requests';
$string['coursedownloadhistorynumdownloaded'] = '# of downloads';
$string['coursedownloadhistorynumnotdownloaded'] = '# of requests not downloaded';
$string['coursedownloadhistoryuniquezipfile'] = '# of unique ZIP files';

// Course
$string['collablist'] = 'Show collaboration sites';

// Capability string
$string['tool/uclasupportconsole:view'] = 'Access UCLA support console';

// Form input strings
$string['choose_term'] = 'Choose term...';
$string['term'] = 'Term';
$string['leave_term_blank'] = '(Leave empty to show content from all terms)';
$string['srs'] = 'SRS';
$string['subject_area'] = 'Subject area';
$string['choose_subject_area'] = 'Choose subject area...';
$string['all_subject_areas'] = 'All subject areas';
$string['uid'] = 'UID';
$string['srslookup'] = "SRS number lookup (Registrar)";
$string['goback'] = 'Go back';
$string['noresults'] = 'There are no results';
$string['oneresult'] = 'There is 1 result';
$string['xresults'] = 'There are {$a} results';
$string['paginatedxresults'] = 'Showing {$a->pagecount} entries from {$a->totalcount} total results';
$string['forinput'] = ' for input [{$a}]';
$string['filterfor'] = 'Results filtered for: ';
$string['exportoptions'] = 'Export: ';
$string['totop'] = 'To top';
$string['seeall'] = 'See all';
$string['showpage'] = 'Show pages';
// capability strings
$string['uclasupportconsole:view'] = 'Use UCLA support console';
