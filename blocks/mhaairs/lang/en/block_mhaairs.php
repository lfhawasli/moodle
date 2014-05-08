<?php
defined('MOODLE_INTERNAL') || die();

// Language Strings - English.

$string['pluginname'] = 'McGraw-Hill AAIRS';
$string['mhaairs'   ] = 'MH AAIRS';

$string['mhaairs:viewadmindoc'  ] = 'View Administrator Documentation';
$string['mhaairs:viewteacherdoc'] = 'View Teacher Documentation';
$string['mhaairs:addinstance'   ] = 'Add a new McGraw-Hill AAIRS block';

// Strings for the edit_form file.
$string['linktype'         ] = 'Link Type';
$string['blocksettings'    ] = 'Block Settings';
$string['availableservices'] = 'Available Services';
$string['noservicesmsg'] = 'Available Services have not yet been configured for this site. Please contact your site admin.';
$string['edit_prelabel'    ] = 'Links to Display';

// Strings for the settings file.
$string['secretlabel'] = 'Shared Secret';
$string['customernumberlabel'] = 'Customer Number';
$string['sslonlylabel'] = 'SSL Only';
$string['conskeylabel'] = 'Consumer Key';
$string['baseaddresslabel'] = 'Service Address';
$string['service_down_msg'] = 'One or more of the web services is currently down or your client access data are not correctly configured. Please contact McGraw-Hill help for further action.';
$string['services_displaylabel'] = 'Available Services';
$string['services_desc'] = 'The services above will display as links in the MH AAIRS block. Select which system(s) should be available ';
$string['services_desc'] .= 'to courses on your site. The service(s) selected will appear as options in the block\'s instance settings.';
$string['connected_displaylabel'] = ' ConnectEd';
$string['tegrity_displaylabel'] = ' Tegrity Campus';
$string['mhaairs_displaylabel'] = ' McGraw-Hill Campus';
$string['mhaairs_syncgradebook'] = 'Gradebook Sync';
$string['mhaairs_syncgradebookdesc'] = 'Gradebook Sync provides the ability to push scores from MH Campus directly to the Moodle gradebook. ';
$string['mhaairs_syncgradebookdesc'] .= 'Note: If only Tegrity is enabled on your site, this is irrelevant.';
$string['mhaairs_displayhelp'] = 'Help links';
$string['mhaairs_displayhelpdesc'] = 'Select this option if you wish help links to appear in the block appropriate to admin and teacher roles.';

// Strings for the main block display file.
$string['linktypelabel'] = ' Link type: ';
$string['adminhelplabel'] = 'Admin documentation';
$string['instrhelplabel'] = 'Instructor documentation';
$string['nolinktext'] = 'No link';
$string['blocknotconfig'] = 'Block requires further configuration. Please contact your site admin. ';
$string['coursenotconfig'] = 'Course requires further configuration. Please contact your site admin. ';
$string['nolinkdefined'] = ' No link type defined. ';

// Strings for the Utilities file.
$string['error_tokeninvalid'] = 'Error: Token is invalid.';
$string['error_notsecuressl'] = 'Error: Connection must be secured with SSL.';

// Strings that may be needed by the gradebook test client
$string['get_completion_course_desc'] = 'Course for which to get the completion status';
$string['get_completion_user_desc'] = 'User for which to get the completion status';
$string['get_completion_completion_desc'] = 'Course Completion status';
$string['get_completion_result_desc'] = 'get_completion resultset';
$string['get_completion_activity_desc'] = 'Activity title';
$string['get_completion_activity_type_desc'] = 'Activity type';
$string['get_completion_activity_compl_desc'] = 'Activity completion status';
$string['get_completions_result_desc'] = 'get_completions resultset';
$string['block_mhaairs_get_completion'] = 'Returns course completion';
$string['block_mhaairs_get_completions'] = 'Returns course activities completions';
$string['error_no_course'] = 'Course does not exist!';
$string['error_no_user'] = 'User does not exist!';
$string['error_no_course_completion'] = 'Completion is not enabled for this course!';
$string['error_no_system_completion'] = 'Completion is not enabled on this instance!';
$string['error_invalid_course_param'] = 'Invalid course parameter passed!';
