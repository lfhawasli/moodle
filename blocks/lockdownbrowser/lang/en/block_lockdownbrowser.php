<?php
// Respondus LockDown Browser Extension for Moodle
// Copyright (c) 2011-2016 Respondus, Inc.  All Rights Reserved.
// Date: May 13, 2016.

$string['pluginname']      = 'Respondus LockDown Browser';
$string['lockdownbrowser'] = 'Respondus LockDown Browser';

// admin settings page
$string["blockdescheader"]     = "Description";
$string["blockdescription"]    = "Respondus LockDown Browser Extension for Moodle";
$string["blockversionheader"]  = "Current version";
$string["adminsettingsheader"] = "Admin settings";

$string['adminsettingsheaderinfo'] =
    'The values for these settings are provided by Respondus. If you download the
    block from the Respondus Campus Portal, the settings are already included in the
    block. Please review the Administrator Guide for additional details.';

$string["adminstatus"] = "Block Status";

$string["authenticationsettingsheader"]     = "Authentication Settings";

$string["authenticationsettingsheaderinfo"] =
    "These are the credentials for the user under which the Respondus LockDown Browser
    and Respondus Monitor web services run. The user entered must be a Moodle site
    administrator listed in Site administration->Users->Permissions->Site administrators.
    This information is never transmitted outside of this Moodle server and all Respondus
    Monitor web service    requests are authenticated using Hash-based Message Authentication
    Codes. If the option \"Use HTTPS for logins\" in the Security->HTTP Security settings
    is selected, all Respondus Monitor web service requests enforce the use of HTTPS.";

$string["password"]                         = "Password";
$string["passwordinfo"]                     = "Password for the Respondus Monitor user.";
$string["username"]                         = "User name";
$string["usernameinfo"]                     = "Respondus Monitor user name.";

$string['servername'] = 'Server Name';

$string['servernameinfo'] =
    'This setting must match the name entered in the Respondus Campus Portal profile for this Moodle Server.';

$string['serverid']     = 'Server Id';
$string['serveridinfo'] = 'Institution ID for this Moodle server. Assigned by Respondus.';
$string['serversecret'] = 'Shared Secret';

$string['serversecretinfo'] =
    'This setting must match the secret entered in the Respondus Campus Portal profile for this Moodle Server.';

$string['servertype']     = 'License Type';
$string['servertypeinfo'] = 'Campus-wide = 0, Lab Pack = 1.';
$string['downloadurl']    = 'Download URL';

$string['downloadinfo'] =
    'Link for students to download browser client.  Leave blank to not display a link on attempts page.';

$string['sessioncookie']     = 'Moodle Session Cookie';
$string['sessioncookieinfo'] = 'Cookie name used by the Moodle server for user sessions.';

$string['dashboard']         = 'Dashboard';
$string['quizzes']           = 'Quizzes';
$string['lockdown_settings'] = 'LockDown Browser Settings';
$string['quiz']              = 'Quiz';
$string['disable']           = 'Disable';
$string['enable']            = 'Enable';
$string['ldb_required']      = 'Respondus LockDown Browser is required for this quiz.';
$string['click']             = 'Click';
$string['here']              = 'here';
$string['todownload']        = ' to download the installer.';
$string['requires_ldb']      = '- Requires Respondus LockDown Browser';
$string['requires_webcam']   = '- Requires Respondus LockDown Browser + Webcam';
$string['test_server']       = 'Test the server by requesting more tokens';
$string['clear_tokens']      = 'Clear all sessions and tokens for the block'; // Trac #2544
$string['tokens_free']       = 'Authentication tokens free';
$string['count_tokens']      = 'Counting existing tokens';
$string['purge_sessions']    = 'Purging stale sessions';
$string['request_tokens']    = 'Requesting additional tokens from server';
$string['reset_attempt']     = 'Attempting to truncate session and token tables for the block...';
$string['added']             = 'Added';
$string['tokensok']          = 'tokens, token server working';
$string['resetok']           = 'Token and session tables successfully cleared.';
$string['curlerror']         = 'extension_loaded claims curl is not loaded.  Giving up.';
$string['mcrypterror']       = 'extension_loaded claims mcrypt is not loaded.  Giving up.';
$string['token_limit_error'] = '10,000 or more free tokens already exist. No more can be requested. Please try again later.';
$string['zero_tokens_free']  = '0 (is mcrypt enabled?)';

$string['block_status_unknown'] = 'Block status is not currently available.';

$string['module_installed_error']  =
    "Error: /mod/lockdown module has not been uninstalled. Please see the Administrator Guide for LockDown Browser - Moodle.";

$string['tokenerror'] =
    "No tokens added, possible causes are: locklibcfg.php settings incorrect,
    lib mcrypt not enabled, database problem, proxy/firewall blocking access to token server";

$string['customscripts_not_set'] = 'Warning: $CFG->customscripts is not set.';

$string['customscripts_not_found']
    = 'Warning: $CFG->customscripts is set, ({$a}), but the lockdownbrowser scripts were not found.';

$string['session_cookie_not_set'] = 'Warning: Moodle session cookie check failed.';

$string['ldb_quiz_count'] =
    'Warning: {$a} rows in quiz settings table. This may result in slower performance for the LDB dashboard.';

$string['ldb_download_disabled'] = 'The LockDown Browser download is not enabled on this site.';
$string['iframe_error']          = 'This page requires iframes support';

$string["errtokendb"]   = "- token db empty, please have server admin check status.";
$string["errsessiondb"] = "- session db error, please have server admin check status.";
$string["errdblook"]    = "- db lookup error, please have server admin check status.";
$string["errdbupdate"]  = "- db update error, please have server admin check status.";
$string["errdbgetlock"]  = "- db locking error, please have server admin check status.";
$string["errdblocksupport"]  = "- db locking not supported, please have server admin check status.";

$string['errcmid'] = 'There is no coursemodule with id {$a}';
$string['errcourse'] = 'Course is misconfigured';
$string['errquizid'] = 'There is no quiz with id {$a}';
$string['errnocourse'] = 'The course with id {$a->course} that the quiz with id {$a->id} belongs to is missing';
$string['errnocm'] = 'The course module for the quiz with id {$a} is missing';
$string['errattempt'] = 'No such attempt ID exists';
$string['errnoquiz1'] = 'The quiz with id {$a->instance} corresponding to this coursemodule {$a->id} is missing';
$string['errnoquiz2'] = 'The quiz with id {$a->quiz} belonging to attempt {$a->id} is missing';

