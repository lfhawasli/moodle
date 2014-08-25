<?php

$string['pluginname'] = 'UCLA gradebook customizations';

$string['continue_comments'] = '... [See full comments on the class website]';

// Errors
$string['nousers'] = 'WARNING: User in course but could not find matching role assignments in child courses: userid - {$a->userid}, courseid - {$a->courseid}';
$string['badenrol'] = 'WARNING: User enrolled in more than one child course of a cross-listed course.';

// Log
$string['eventgradesexported'] = 'Grades exported';
$string['eventgradesexportedmyucla'] = 'Grades exported (myucla)';
$string['eventgradesexportedods'] = 'Grades exported (ods)';
$string['eventgradesexportedtxt'] = 'Grades exported (txt)';
$string['eventgradesexportedxls'] = 'Grades exported (xls)';
$string['eventgradesexportedxml'] = 'Grades exported (xml)';
$string['eventgradesviewed'] = 'Gradebook {$a} report viewed';
$string['gradesuccess'] = 'grade sent to MyUCLA';
$string['gradefail'] = 'failed to send grade to MyUCLA';
$string['gradefailinfo'] = 'General error: GradeID [{$a}]';

$string['connectionfail'] = 'failed to connect with MyUCLA';
$string['gradeconnectionfailinfo'] = 'Connection error: GradeID [{$a}]';
$string['itemconnectionfailinfo'] = 'Connection error: ItemID [{$a}]';

$string['itemsuccess'] = 'item update sent to MyUCLA';
$string['itemfail'] = 'failed to send update to MyUCLA';
$string['itemfailinfo'] = 'General error: ItemID [{$a}]';