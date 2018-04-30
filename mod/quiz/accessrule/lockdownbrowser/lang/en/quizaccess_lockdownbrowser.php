<?php
// Respondus LockDown Browser Extension for Moodle
// Copyright (c) 2011-2018 Respondus, Inc.  All Rights Reserved.
// Date: March 13, 2018.

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Respondus LockDown Browser quiz access rule';
// Trac #3521
//$string['requirelockdownbrowser'] = 'Require the use of Respondus LockDown Browser';
$string['requirelockdownbrowser'] = 'Browser security should be set through the LockDown Browser Dashboard';
$string['lockdownbrowsernotice'] = 'This quiz has been configured so that students may only attempt it using the Respondus LockDown Browser.';
$string['noblockversion'] = 'The Respondus LockDown Browser Extension for Moodle is not properly installed. The block plugin is either missing or the version cannot be determined.';
$string['noruleversion'] = 'The Respondus LockDown Browser Extension for Moodle is not properly installed. The quiz access rule plugin is either missing or the version cannot be determined.';
$string['invalidversion'] = 'The Respondus LockDown Browser Extension for Moodle is not properly installed. The block plugin version does not match the quiz access rule plugin version.';
$string['errnofunc'] = 'Missing function {$a}';
$string['errdepend'] = 'Dependency error: {$a}';

// don't translate anything below this line

// quiz browser security setting option key; must match same string in LDB block
$string['browsersecuritychoicekey'] = 'lockdownbrowser';
