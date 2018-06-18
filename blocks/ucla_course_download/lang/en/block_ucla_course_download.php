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

$string['request_available'] = 'Request a zip file containing all available course materials. Files are subject to change without notice.';
$string['request_submitted'] = 'You have successfully submitted a request to create a zip file of course materials. You will be notified via email when it is available.';
$string['request_in_progress'] = 'A zip file of all available course materials has been requested. You will be notified via email when it is available.';
$string['request_completed'] = 'This zip file was last updated on <strong>{$a->timeupdated}</strong>.';
$string['request_completed_changed'] = 'You downloaded this file on <strong>{$a}</strong>. The available course materials have since changed.';
$string['request_completed_post'] = 'To be certain that you have all available course materials, you should check back periodically.' .
        '<p>This zip file will be deleted on <strong>{$a->timedelete}</strong>.</p>';
$string['copyrightagreement'] = 'This zip file may contain copyrighted material. '
        . 'Such material is meant for your personal educational use, and should not '
        . 'be shared with others outside this course, posted online, or otherwise '
        . 'distributed without permission from the copyright owner.'
        . '<p>Click to agree.</p>';
$string['request_unavailable'] = 'There are no <strong>{$a}</strong> available for download.';

$string['noaccess'] = 'You do not have access to view this page';

/* alert notice */
$string['alert_msg'] = 'You can click "Download course materials" below to request a zip file of your course materials. Files are subject to change without notice.';
$string['alert_download'] = 'Download course materials';
$string['alert_dismiss'] = 'Dismiss';
$string['alert_dismiss_message'] = 'You will no longer be prompted to download course material. ' .
        'Use the "Download course materials" link in the Admin panel to request a zip file later.';

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
        'students a link to "Download course materials" page in their Admin ' .
        'panel. They will get access as soon as the site is available. ' .
        'Students will continue to have access to the "Download course ' .
        'materials" page until the course is hidden or they lose access to ' .
        'the course site.';
$string['ziplifetime'] = 'Keep zips for';
$string['ziplifetime_desc'] = 'This specifies how long to keep zip files ' . 
        'after they have been requested. After the specified number of days, ' .
        'the zip file and request will be deleted.';
$string['maxfilesize'] = 'Maximum file size in MB';
$string['maxfilesize_desc'] = 'Files over this size will be excluded from the zip file.';
$string['studentaccessdisabled'] = 'Students do not have access to this feature. To enable this feature for students please enable the "Download course materials" option in the course settings.';

// Instructor view of files
$string['instructorfilewarning'] = 'Files included are ones that are already visible ' .
        'to students on your site.  Files that are part of conditional activities or ' .
        'groupings will only be available to students that match that criteria. ' .
        'When you update the availability of a file, it will be reflected in an ' .
        'updated zip file.';
$string['filewillbeexcluded'] = 'File will be excluded for students.';
$string['filemaybeincluded'] = 'File may be included.';
$string['fileoversizeexclusion'] = 'Files over <strong>{$a}</strong> will be excluded.';

// Events.
$string['eventrequestcreated'] = 'Course download request created';
$string['eventzipdownloaded'] = 'Course content zip downloaded';
$string['eventfilelistviewed'] = 'Course download status page viewed';

// Capability descriptions.
$string['ucla_course_download:addinstance'] = 'Add a new UCLA course download block';
$string['ucla_course_download:myaddinstance'] = 'Add a new UCLA course download block to My home';
$string['ucla_course_download:requestzip'] = 'Request/download a course download zip file';

// Admin panel strings.
$string['coursedownload'] = 'Download course material';
