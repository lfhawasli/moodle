<?php

$string['pluginname'] = 'UCLA course download';

/* button text */
$string['request'] = 'Request zip';
$string['in_progress'] = 'Request in progress';
$string['download'] = 'Download {$a} zip';
        
/* filenames */
$string['table_of_contents_file'] = "table_of_contents.html";

/* course download page */
$string['files'] = 'Files';
$string['forums'] = 'Forum posts';
$string['submissions'] = 'Assignment submissions';

$string['request_available'] = 'A zip file containing all of your <strong>{$a}</strong> can be requested.';
$string['request_submitted'] = 'You have successfully submitted a request to create a zip file of {$a}. You will be notified via email when it is available.';
$string['request_in_progress'] = 'A zip file of <strong>{$a}</strong> has been requested. You will be notified via email when it is available.';
$string['request_completed'] = 'A zip file of your <strong>{$a->area}</strong> was created on ' .
        '<strong>{$a->timeupdated}</strong>.  New content will be added automatically to the zip ' .
        'file as the site gets updated.  You will receive an email notification when that happens. ' .
        '<p>This zip file will be deleted on <strong>{$a->timedelete}</strong>.</p>';
$string['request_unavailable'] = 'There are no <strong>{$a}</strong> available for download.';
$string['copyrightagreement'] = 'TODO: Agree to copyright to enable download';

$string['noaccess'] = 'You do not have access to view this page';

/* alert notice */
$string['alert_msg'] = 'For copyright compliance you will no longer have access to this course site 2 weeks into the next term. Starting 9th week, you can visit the Control Panel or click "Download course content" below to download your course content.';
$string['alert_download'] = 'Download course content';
$string['alert_dismiss'] = 'Dismiss';
$string['alert_dismiss_message'] = 'You will no longer be prompted to download course material. ' .
        'Use the Download Course Content link in the Control Panel to request content later.';

/* Email strings */
$string['emailsubject'] = 'Your {$a->shortname} {$a->type} are ready';
$string['emailsender'] = 'CCLE';
$string['emailmessage'] = 'CCLE has created a zip file for {$a->shortname}. '
        . 'You can download it here by going to {$a->url}' . "\n\n" .
        'The zip file will only be available for a limited time. Please '
        . 'download it in the next {$a->ziplifetime} days to ensure that you '
        . 'receive it before it is removed.';
$string['emailcopyright'] = 'This zip file may contain copyrighted material. '
        . 'Such material is meant for your personal educational use, and '
        . 'should not be shared with others outside this course, posted '
        . 'online, or otherwise distributed without permission from the '
        . 'copyright owner.';

// Settings.
$string['settingsdisable'] = 'Disable feature for students';
$string['allowstudentaccess'] = 'Allow students';
$string['allowstudentaccess_desc'] = 'If enabled, will display alert and give ' .
        'students a link to "Course content download" page in their Control ' .
        'Panel. They will get access starting the week before the end of the ' . 
        'term. For example, for regular terms, it will start 9th week (since ' .
        'classes end 10th week). For summer sessions, if a course is a 6 week ' .
        'class, it will display on 5th week. Students will continue to have ' .
        'access to the "Course content downloa`d" page until the course is ' .
        'hidden or they lose access to the course site.';
$string['ziplifetime'] = 'Keep zips for';
$string['ziplifetime_desc'] = 'This specifies how long to keep zip files ' . 
        'after they have been requested. After the specified number of days, ' .
        'the zip file and request will be deleted.';

// Instructor view of files
$string['instructorfilewarning'] = 'Files included are ones that are already visible ' .
        'to students on your site.  Files that are part of conditional activities or ' .
        'groupings will only be available to students that match that criteria. ' .
        'When you update the availability of a file, it will be reflected in an ' .
        'updated zip file.';
$string['filewillbeexcluded'] = 'File will be excluded';
$string['filemaybeincluded'] = 'File may be included';
