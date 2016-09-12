<?php
// Respondus LockDown Browser Extension for Moodle
// Copyright (c) 2011-2016 Respondus, Inc.  All Rights Reserved.
// Date: May 13, 2016.

if (!isset($CFG)) {
    require_once("../../config.php");
}

require_once("$CFG->dirroot/blocks/lockdownbrowser/locklib.php");

// existence check necessary due to Moodle handling of this file
if (!function_exists("lockdownbrowser_getsettingsstring")) {
    function lockdownbrowser_getsettingsstring($identifier, $a = null) {
        global $CFG;
        $component = "block_lockdownbrowser";
        if (isset($CFG) && $CFG->version >= 2012062500) {
            // Moodle 2.3.0+.
            return new lang_string($identifier, $component, $a);
        } else {
            // Prior to Moodle 2.3.0.
            return get_string($identifier, $component, $a);
        }
    }
}

$settings->add(
    new admin_setting_heading(
        "lockdown_blockdescheader",
        lockdownbrowser_getsettingsstring("blockdescheader"),
        lockdownbrowser_getsettingsstring("blockdescription")
    )
);

$lockdownbrowser_version_file = "$CFG->dirroot/blocks/lockdownbrowser/version.php";
$lockdownbrowser_version      = "(error: version not found)";
if (is_readable($lockdownbrowser_version_file)) {
    $lockdownbrowser_contents = file_get_contents($lockdownbrowser_version_file);
    if ($lockdownbrowser_contents !== false) {
        $lockdownbrowser_parts = explode("=", $lockdownbrowser_contents);
        if (count($lockdownbrowser_parts) > 0) {
            $lockdownbrowser_parts   = explode(";", $lockdownbrowser_parts[1]);
            $lockdownbrowser_version = trim($lockdownbrowser_parts[0]);
        }
    }
}
$settings->add(
    new admin_setting_heading(
        "lockdown_blockversionheader",
        lockdownbrowser_getsettingsstring("blockversionheader"),
        $lockdownbrowser_version //. " (internal release for Q/A)"
    )
);

$settings->add(
    new admin_setting_heading(
        "lockdown_adminsettingsheader",
        lockdownbrowser_getsettingsstring("adminsettingsheader"),
        lockdownbrowser_getsettingsstring("adminsettingsheaderinfo")
    )
);

$settings->add(
    new admin_setting_configtext(
        "block_lockdownbrowser_ldb_servername",
        lockdownbrowser_getsettingsstring("servername"),
        lockdownbrowser_getsettingsstring("servernameinfo"),
        $CFG->block_lockdownbrowser_ldb_servername,
        PARAM_TEXT
    )
);
$settings->add(
    new admin_setting_configtext(
        "block_lockdownbrowser_ldb_serverid",
        lockdownbrowser_getsettingsstring("serverid"),
        lockdownbrowser_getsettingsstring("serveridinfo"),
        $CFG->block_lockdownbrowser_ldb_serverid,
        PARAM_TEXT
    )
);
$settings->add(
    new admin_setting_configtext(
        "block_lockdownbrowser_ldb_serversecret",
        lockdownbrowser_getsettingsstring("serversecret"),
        lockdownbrowser_getsettingsstring("serversecretinfo"),
        $CFG->block_lockdownbrowser_ldb_serversecret,
        PARAM_TEXT
    )
);
$settings->add(
    new admin_setting_configtext(
        "block_lockdownbrowser_ldb_servertype",
        lockdownbrowser_getsettingsstring("servertype"),
        lockdownbrowser_getsettingsstring("servertypeinfo"),
        $CFG->block_lockdownbrowser_ldb_servertype,
        PARAM_TEXT
    )
);
$settings->add(
    new admin_setting_configtext(
        "block_lockdownbrowser_ldb_download",
        lockdownbrowser_getsettingsstring("downloadurl"),
        lockdownbrowser_getsettingsstring("downloadinfo"),
        $CFG->block_lockdownbrowser_ldb_download,
        PARAM_TEXT
    )
);

$settings->add(
    new admin_setting_heading(
        "lockdown_authenticationsettingsheader",
        lockdownbrowser_getsettingsstring("authenticationsettingsheader", "block_lockdownbrowser"),
        lockdownbrowser_getsettingsstring("authenticationsettingsheaderinfo", "block_lockdownbrowser")
    )
);
$settings->add(
    new admin_setting_configtext(
        "block_lockdownbrowser_monitor_username",
        lockdownbrowser_getsettingsstring("username", "block_lockdownbrowser"),
        lockdownbrowser_getsettingsstring("usernameinfo", "block_lockdownbrowser"),
        $CFG->block_lockdownbrowser_monitor_username,
        PARAM_TEXT
    )
);
$settings->add(
    new admin_setting_configpasswordunmask(
        "block_lockdownbrowser_monitor_password",
        lockdownbrowser_getsettingsstring("password", "block_lockdownbrowser"),
        lockdownbrowser_getsettingsstring("passwordinfo", "block_lockdownbrowser"),
        $CFG->block_lockdownbrowser_monitor_password,
        PARAM_TEXT
    )
);

// status string
$lockdownbrowser_ist = "";
$lockdownbrowser_quiz_count = $DB->count_records("block_lockdownbrowser_sett");
if ($lockdownbrowser_quiz_count >= 50000) { // Trac #2315
    $lockdownbrowser_ist .= "<div style='font-size: 125%; color:red; text-align: center; padding: 30px'>";
    $lockdownbrowser_ist .= lockdownbrowser_getsettingsstring('ldb_quiz_count', $lockdownbrowser_quiz_count);
    $lockdownbrowser_ist .= "</div>";
}
if (!isset($_COOKIE[$CFG->block_lockdownbrowser_ldb_session_cookie . $CFG->sessioncookie])) {
    $lockdownbrowser_ist .= "<div style='font-size: 125%; color:red; text-align: center; padding: 30px'>";
    $lockdownbrowser_ist .= lockdownbrowser_getsettingsstring('session_cookie_not_set');
    $lockdownbrowser_ist .= "</div>";
}
if (!isset($CFG->customscripts)) {
    $lockdownbrowser_ist .= "<div style='font-size: 125%; color:red; text-align: center; padding: 30px'>";
    $lockdownbrowser_ist .= lockdownbrowser_getsettingsstring('customscripts_not_set');
    $lockdownbrowser_ist .= "</div>";
} else if (!file_exists("$CFG->customscripts/mod/quiz/attempt.php")
  || !file_exists("$CFG->customscripts/mod/quiz/view.php")
  || !file_exists("$CFG->customscripts/mod/quiz/review.php")
) {
    $lockdownbrowser_ist .= "<div style='font-size: 125%; color:red; text-align: center; padding: 30px'>";
    $lockdownbrowser_ist .= lockdownbrowser_getsettingsstring('customscripts_not_found', $CFG->customscripts);
    $lockdownbrowser_ist .= "</div>";
}
if (!during_initial_install() && empty($CFG->upgraderunning)) {
    $lockdownbrowser_mod_installed = $DB->get_manager()->table_exists(new xmldb_table("lockdown_settings"));
    if (!$lockdownbrowser_mod_installed) {
        clearstatcache();
        $lockdownbrowser_mod_folder = "$CFG->dirroot/mod/lockdown";
        $lockdownbrowser_mod_installed = (file_exists($lockdownbrowser_mod_folder)
          && is_dir($lockdownbrowser_mod_folder));
    }
    if ($lockdownbrowser_mod_installed) {
        $lockdownbrowser_ist .= "<div style='font-size: 125%; color:red; text-align: center; padding: 30px'>";
        $lockdownbrowser_ist .= lockdownbrowser_getsettingsstring('module_installed_error');
        $lockdownbrowser_ist .= "</div>";
    }
}
if (!during_initial_install() && empty($CFG->upgraderunning)) {
    $lockdownbrowser_dbman = $DB->get_manager();
    $lockdownbrowser_toke_ok = $lockdownbrowser_dbman->table_exists(new xmldb_table("block_lockdownbrowser_toke"));
    $lockdownbrowser_sess_ok = $lockdownbrowser_dbman->table_exists(new xmldb_table("block_lockdownbrowser_sess"));
    if ($lockdownbrowser_toke_ok && $lockdownbrowser_sess_ok) {
        $lockdownbrowser_tf = lockdownbrowser_tokens_free();
    }
}
if (isset($lockdownbrowser_tf)) {
    $lockdownbrowser_ist .= "<div style='text-align: center'>" . lockdownbrowser_getsettingsstring('tokens_free') . ": ";
    if ($lockdownbrowser_tf > 0) {
        $lockdownbrowser_ist .= "$lockdownbrowser_tf";
    } else {
        $lockdownbrowser_ist .= lockdownbrowser_getsettingsstring('zero_tokens_free');
    }
    if ($lockdownbrowser_tf < 10000) { // Trac #2315
        $lockdownbrowser_ist .= "<br>" . lockdownbrowser_getsettingsstring('test_server')
          . ": <a href='$CFG->wwwroot/blocks/lockdownbrowser/tokentest.php' target='_blank'>"
          . "/blocks/lockdownbrowser/tokentest.php</a>";
    }
    if ($lockdownbrowser_tf > 0) { // Trac #2544
        $lockdownbrowser_ist .= "<br>" . lockdownbrowser_getsettingsstring('clear_tokens')
          . ": <a href='$CFG->wwwroot/blocks/lockdownbrowser/tokenreset.php' target='_blank'>"
          . "/blocks/lockdownbrowser/tokenreset.php</a>";
    }
    $lockdownbrowser_ist .= "</div>";
}
if (strlen($lockdownbrowser_ist) == 0) {
    $lockdownbrowser_ist .= "<div style='font-size: 125%; color:red; text-align: center; padding: 30px'>";
    $lockdownbrowser_ist .= lockdownbrowser_getsettingsstring('block_status_unknown');
    $lockdownbrowser_ist .= "</div>";
}
$settings->add(
    new admin_setting_heading(
        "lockdown_adminstatus",
        lockdownbrowser_getsettingsstring("adminstatus"),
        $lockdownbrowser_ist
    )
);

