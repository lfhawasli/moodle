<?php
// Respondus LockDown Browser Extension for Moodle
// Copyright (c) 2011-2018 Respondus, Inc.  All Rights Reserved.
// Date: March 13, 2018.

// ----- never edit these
// START UCLA MOD: CCLE-4027 - Install and evaluate Respondus
// Define these in config_private.php.
define('LDB_SERVERNAME', '');
define('LDB_SERVERSECRET', '');
// END UCLA MOD: CCLE-4027
define('LDB_SERVERID', '228644488');
define('LDB_SERVERTYPE', '0');
define('LDB_DOWNLOAD', 'https://www.respondus.com/lockdown/download.php?id=228644488');
// to remove link: define('LDB_DOWNLOAD', '');

// ----- edit these only if your server is nonstandard
// editing these will break the module!
define('LDB_TSERVER_1', 'https://moots1.respondus2.com');
define('LDB_TSERVER_2', 'https://moots2.respondus2.com');
define('LDB_TSERVER_ENDPOINT', '/SMServer/moodlews/token.html');
define('LDB_TSERVER_AKEY', 'myn3r41z');
define('LDB_TSERVER_BKEY', '1uti3n1yc0p3n3');
define('LDB_TSERVER_FORM1', 'v=a&i=%s&t=');
define('LDB_TSERVER_FORM2', '&a=%s&myn=%s&s=%s');
define('LDB_TSERVER_SET', 500);
define('LDB_TSERVER_REC', 84);
define('LDB_TSERVER_T1L', 48);
define('LDB_TSERVER_T2L', 32);
define('LDB_TSERVER_T2P', 51);
define('LDB_TOKEN1_COOKIE', 'mldbt1c');
define('LDB_TOKEN2_COOKIE', 'mldbc2t');
define('LDB_EPASS_COOKIE', 'mldbxc');
define('LDB_ID_COOKIE', 'mldbz');
define('LDB_SESSION_COOKIE', 'MoodleSession');
// if your server needs proxy credentials to make HTTP POST requests
define('LDB_PROXY_DEFINED', '0'); // '1'=yes

if (!isset($CFG->block_lockdownbrowser_ldb_tserver_1)) {
    $CFG->block_lockdownbrowser_ldb_tserver_1 = LDB_TSERVER_1;
}
if (!isset($CFG->block_lockdownbrowser_ldb_tserver_2)) {
    $CFG->block_lockdownbrowser_ldb_tserver_2 = LDB_TSERVER_2;
}
if (!isset($CFG->block_lockdownbrowser_ldb_tserver_endpoint)) {
    $CFG->block_lockdownbrowser_ldb_tserver_endpoint = LDB_TSERVER_ENDPOINT;
}
if (!isset($CFG->block_lockdownbrowser_ldb_tserver_akey)) {
    $CFG->block_lockdownbrowser_ldb_tserver_akey = LDB_TSERVER_AKEY;
}
if (!isset($CFG->block_lockdownbrowser_ldb_tserver_bkey)) {
    $CFG->block_lockdownbrowser_ldb_tserver_bkey = LDB_TSERVER_BKEY;
}
if (!isset($CFG->block_lockdownbrowser_ldb_tserver_form1)) {
    $CFG->block_lockdownbrowser_ldb_tserver_form1 = LDB_TSERVER_FORM1;
}
if (!isset($CFG->block_lockdownbrowser_ldb_tserver_form2)) {
    $CFG->block_lockdownbrowser_ldb_tserver_form2 = LDB_TSERVER_FORM2;
}
if (!isset($CFG->block_lockdownbrowser_ldb_tserver_set)) {
    $CFG->block_lockdownbrowser_ldb_tserver_set = LDB_TSERVER_SET;
}
if (!isset($CFG->block_lockdownbrowser_ldb_tserver_rec)) {
    $CFG->block_lockdownbrowser_ldb_tserver_rec = LDB_TSERVER_REC;
}
if (!isset($CFG->block_lockdownbrowser_ldb_tserver_t1l)) {
    $CFG->block_lockdownbrowser_ldb_tserver_t1l = LDB_TSERVER_T1L;
}
if (!isset($CFG->block_lockdownbrowser_ldb_tserver_t2l)) {
    $CFG->block_lockdownbrowser_ldb_tserver_t2l = LDB_TSERVER_T2L;
}
if (!isset($CFG->block_lockdownbrowser_ldb_tserver_t2p)) {
    $CFG->block_lockdownbrowser_ldb_tserver_t2p = LDB_TSERVER_T2P;
}
if (!isset($CFG->block_lockdownbrowser_ldb_token1_cookie)) {
    $CFG->block_lockdownbrowser_ldb_token1_cookie = LDB_TOKEN1_COOKIE;
}
if (!isset($CFG->block_lockdownbrowser_ldb_token2_cookie)) {
    $CFG->block_lockdownbrowser_ldb_token2_cookie = LDB_TOKEN2_COOKIE;
}
if (!isset($CFG->block_lockdownbrowser_ldb_epass_cookie)) {
    $CFG->block_lockdownbrowser_ldb_epass_cookie = LDB_EPASS_COOKIE;
}
if (!isset($CFG->block_lockdownbrowser_ldb_id_cookie)) {
    $CFG->block_lockdownbrowser_ldb_id_cookie = LDB_ID_COOKIE;
}
if (!isset($CFG->block_lockdownbrowser_ldb_session_cookie)) {
    $CFG->block_lockdownbrowser_ldb_session_cookie = LDB_SESSION_COOKIE;
}
if (!isset($CFG->block_lockdownbrowser_ldb_proxy_defined)) {
    $CFG->block_lockdownbrowser_ldb_proxy_defined = LDB_PROXY_DEFINED;
}
if (!isset($CFG->block_lockdownbrowser_ldb_servername)) {
    if (isset($CFG->block_lockdownbrowser_LDB_SERVERNAME)) {
        $CFG->block_lockdownbrowser_ldb_servername = $CFG->block_lockdownbrowser_LDB_SERVERNAME;
    } else {
        $CFG->block_lockdownbrowser_ldb_servername = LDB_SERVERNAME;
    }
}
if (!isset($CFG->block_lockdownbrowser_ldb_serverid)) {
    if (isset($CFG->block_lockdownbrowser_LDB_SERVERID)) {
        $CFG->block_lockdownbrowser_ldb_serverid = $CFG->block_lockdownbrowser_LDB_SERVERID;
    } else {
        $CFG->block_lockdownbrowser_ldb_serverid = LDB_SERVERID;
    }
}
if (!isset($CFG->block_lockdownbrowser_ldb_serversecret)) {
    if (isset($CFG->block_lockdownbrowser_LDB_SERVERSECRET)) {
        $CFG->block_lockdownbrowser_ldb_serversecret = $CFG->block_lockdownbrowser_LDB_SERVERSECRET;
    } else {
        $CFG->block_lockdownbrowser_ldb_serversecret = LDB_SERVERSECRET;
    }
}
if (!isset($CFG->block_lockdownbrowser_ldb_servertype)) {
    if (isset($CFG->block_lockdownbrowser_LDB_SERVERTYPE)) {
        $CFG->block_lockdownbrowser_ldb_servertype = $CFG->block_lockdownbrowser_LDB_SERVERTYPE;
    } else {
        $CFG->block_lockdownbrowser_ldb_servertype = LDB_SERVERTYPE;
    }
}
if (!isset($CFG->block_lockdownbrowser_ldb_download)) {
    if (isset($CFG->block_lockdownbrowser_LDB_DOWNLOAD)) {
        $CFG->block_lockdownbrowser_ldb_download = $CFG->block_lockdownbrowser_LDB_DOWNLOAD;
    } else {
        $CFG->block_lockdownbrowser_ldb_download = LDB_DOWNLOAD;
    }
}
if (!isset($CFG->block_lockdownbrowser_monitor_username)) {
    if (isset($CFG->block_lockdownbrowser_MONITOR_USERNAME)) {
        $CFG->block_lockdownbrowser_monitor_username = $CFG->block_lockdownbrowser_MONITOR_USERNAME;
    } else {
        $CFG->block_lockdownbrowser_monitor_username = '';
    }
}
if (!isset($CFG->block_lockdownbrowser_monitor_password)) {
    if (isset($CFG->block_lockdownbrowser_MONITOR_PASSWORD)) {
        $CFG->block_lockdownbrowser_monitor_password = $CFG->block_lockdownbrowser_MONITOR_PASSWORD;
    } else {
        $CFG->block_lockdownbrowser_monitor_password = '';
    }
}

