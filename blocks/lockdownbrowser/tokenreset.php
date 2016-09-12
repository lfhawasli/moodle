<?php
// Respondus LockDown Browser Extension for Moodle
// Copyright (c) 2011-2016 Respondus, Inc.  All Rights Reserved.
// Date: May 13, 2016.

if (ini_get('zlib.output_compression')) {
    @ini_set('zlib.output_compression', 'Off');
}
require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();

if (bccomp($CFG->version, 2013111800, 2) >= 0) {
    // Moodle 2.6.0+.
    $lockdownbrowser_context = context_system::instance();
} else {
    // Prior to Moodle 2.6.0.
    $lockdownbrowser_context = get_context_instance(CONTEXT_SYSTEM);
}
require_capability('moodle/site:config', $lockdownbrowser_context);
require_once($CFG->dirroot . '/blocks/lockdownbrowser/locklib.php');

echo '<p>' . get_string('reset_attempt', 'block_lockdownbrowser') . '</p>';
flush();

try {
    // Trac #2544
    $DB->delete_records("block_lockdownbrowser_sess");
    $DB->delete_records("block_lockdownbrowser_toke");

    echo '<p>' . get_string('resetok', 'block_lockdownbrowser') . '</p>';

} catch (Exception $ex) {

    abort_all_db_transactions();
    $info = get_exception_info($ex);
    echo "<p>-- Exception occurred --</p>";
    echo "<p>message: $info->message</p>";
    echo "<p>errorcode: $info->errorcode</p>";
    echo "<p>file: " . $ex->getFile() . "</p>";
    echo "<p>line: " . $ex->getLine() . "</p>";
    echo "<p>link: $info->link</p>";
    echo "<p>moreinfourl: $info->moreinfourl</p>";
    echo "<p>a: $info->a</p>";
    echo "<p>debuginfo: $info->debuginfo</p>";
    echo "<p>stacktrace: " . $ex->getTraceAsString() . "</p>";
}
