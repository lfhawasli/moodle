<?php
// Respondus LockDown Browser Extension for Moodle
// Copyright (c) 2011-2018 Respondus, Inc.  All Rights Reserved.
// Date: March 13, 2018.

// Set true to require per-session locking for access to unused token records;
// requires Moodle 2.7+.
define("LOCKDOWNBROWSER_REQUIRE_DB_LOCKING", false);
define("LOCKDOWNBROWSER_MAX_DB_LOCK_TIME", 10); // seconds

if (!isset($CFG)) {
    require_once(dirname(dirname(dirname(__FILE__))) . "/config.php");
}
require_once(dirname(__FILE__) . "/locklibcfg.php");
require_once(dirname(__FILE__) . "/blowfish.php"); // Trac #3884

function lockdownbrowser_set_settings($quizid, $reqquiz, $reqreview, $exitpass, $monitor) {

    $ldbopt           = new stdClass;
    $ldbopt->attempts = $reqquiz;
    $ldbopt->reviews  = $reqreview;
    $ldbopt->password = $exitpass;
    $ldbopt->monitor  = $monitor;
    return lockdownbrowser_set_quiz_options($quizid, $ldbopt);
}

function lockdownbrowser_get_quiz_options($quizid) {

    global $DB;
    $ldbopt = $DB->get_record('block_lockdownbrowser_sett', array('quizid' => $quizid));
    if ($ldbopt) {
        $quiz = $DB->get_record("quiz", array("id" => $quizid));
        if ($quiz === false || $quiz->course != $ldbopt->course) {
            // this will catch some orphans (deleted quizzes) and some false
            // positives (orphans that are false matches due to backup/restore);
            // also see quizaccess_lockdownbrowser::delete_settings
            try {
                lockdownbrowser_delete_options($quizid);
            } catch (Exception $ex) {
                // ignore possible multi-session conflicts
            }
            $ldbopt = false;
        }
    }
    return $ldbopt;
}

function lockdownbrowser_set_quiz_options($quizid, $ldbopt = null) {

    global $DB;

    if ($ldbopt == null) {
        $ldbopt           = new stdClass;
        $ldbopt->attempts = 0;
        $ldbopt->reviews  = 0;
        $ldbopt->password = "";
        $ldbopt->monitor  = "";
    }
    $quiz = $DB->get_record("quiz", array("id" => $quizid));
    if ($quiz === false) {
        return false;
    }
    $ok          = true;
    $newsettings = false;
    if (is_null($ldbopt->attempts)) {
        $ldbopt->attempts = 0;
    }
    if (is_null($ldbopt->reviews)) {
        $ldbopt->reviews = 0;
    }
    if (is_null($ldbopt->password)) {
        $ldbopt->password = "";
    }
    if (is_null($ldbopt->monitor)) {
        $ldbopt->monitor = "";
    }
    $existing = lockdownbrowser_get_quiz_options($quizid);
    if ($existing) {
        $existing->attempts = $ldbopt->attempts;
        $existing->reviews  = $ldbopt->reviews;
        $existing->password = $ldbopt->password;
        $existing->monitor  = $ldbopt->monitor;
        $existing->course    = $quiz->course;
        $ok    = $DB->update_record('block_lockdownbrowser_sett', $existing);
    } else {
        $newsettings    = true;
        $ldbopt->course = $quiz->course;
        $ldbopt->quizid = $quizid;
        $ok = $DB->insert_record('block_lockdownbrowser_sett', $ldbopt);
    }
    if ($ok) {
        $ok = lockdownbrowser_generate_tokens_settings($newsettings);
    }
    return $ok;
}

function lockdownbrowser_delete_options($quizid) {

    global $DB;
    $DB->delete_records('block_lockdownbrowser_sett', array('quizid' => $quizid));
}

function lockdownbrowser_purge_sessions() { // Trac #2315

    global $DB;

    // purge stale records from tokens and sessions tables
    $all_token_count = $DB->count_records('block_lockdownbrowser_toke');
    if ($all_token_count < 50000) {
        return;
    }
    $max_session_life = 3600 * 24 * 2; // 2 days
    $min_session_start =  time() - $max_session_life;
    $stale_session_count = $DB->count_records_select("block_lockdownbrowser_sess",
      "timeused < ?", array($min_session_start));
    if ($stale_session_count < 2000) {
        return;
    }
    try {
        $DB->delete_records_select("block_lockdownbrowser_sess",
          "timeused < ?", array($min_session_start));
        $DB->delete_records_select("block_lockdownbrowser_toke",
          "timeused > 0 AND timeused < ?", array($min_session_start));
    } catch (Exception $ex) {
        // ignore possible multi-session conflicts
    }
}

function lockdownbrowser_purge_settings() {

    global $DB;

    // remove any orphaned records from our settings table;
    // orphans occur whenever a quiz is deleted without first removing the LDB
    // requirement in our dashboard;
    // also see quizaccess_lockdownbrowser::delete_settings
    $records = $DB->get_records('block_lockdownbrowser_sett');
    if (count($records) > 0) {
        foreach ($records as $settings) {
            if ($DB->record_exists('quiz', array('id' => $settings->quizid)) === false) {
                try {
                    lockdownbrowser_delete_options($settings->quizid);
                } catch (Exception $ex) {
                    // ignore possible multi-session conflicts
                }
            }
        }
    }
}

function lockdownbrowser_get_settings($quizid) {

    $existing = lockdownbrowser_get_quiz_options($quizid);
    if ($existing) {
        return $existing;
    } else {
        $ldbopt           = new stdClass;
        $ldbopt->attempts = 0;
        $ldbopt->reviews  = 0;
        $ldbopt->password = "";
        $ldbopt->monitor  = "";
        return $ldbopt;
    }
}

function lockdownbrowser_browser_detected() {

    global $CFG;
    $detected = false;
    if (isset($_COOKIE[$CFG->block_lockdownbrowser_ldb_id_cookie])) {
        if (strcmp($_COOKIE[$CFG->block_lockdownbrowser_ldb_id_cookie], 'y') == 0) {
            $detected = true;
        }
    }
    return $detected;
}

function lockdownbrowser_tokens_free() {

    global $DB;
    $rf = $DB->count_records('block_lockdownbrowser_toke') - $DB->count_records('block_lockdownbrowser_sess');
    return $rf;
}

function lockdownbrowser_allocate_token1($sesskey, $url) {

    global $CFG, $DB;

    lockdownbrowser_generate_tokens_student(); // only adds more if needed
    $rf = $DB->count_records('block_lockdownbrowser_toke') - $DB->count_records('block_lockdownbrowser_sess');
    if ($rf < 1) {
        return get_string('errtokendb', 'block_lockdownbrowser');
    }
    $locking_required = lockdownbrowser_db_locking_required();
    $timenow      = time();
    $use_existing = false;
    $existing     = $DB->get_record('block_lockdownbrowser_sess', array('sesskey' => $sesskey));
    if ($existing && strcmp($existing->sesskey, $sesskey) == 0) {
        if ($locking_required) {
            $existobj1 = $DB->get_record('block_lockdownbrowser_toke', array('sesskey' => $sesskey));
        } else {
            $existobj1 = $DB->get_record('block_lockdownbrowser_toke', array('id' => $existing->id));
        }
        if ($existobj1 && strcmp($existobj1->sesskey, $sesskey) == 0) {
            $obj1         = $existobj1;
            $use_existing = true;
        }
    }

    if (!$use_existing) {
        $obj2           = new stdClass;
        $obj2->sesskey  = $sesskey;
        $obj2->timeused = $timenow;
        $ix2            = $DB->insert_record('block_lockdownbrowser_sess', $obj2);
        if (!$ix2) {
            return get_string('errsessiondb', 'block_lockdownbrowser');
        }
        if ($locking_required) {
            $obj1 = lockdownbrowser_update_unused_token_record_with_locking($sesskey, $timenow);
        } else {
            $obj1 = $DB->get_record('block_lockdownbrowser_toke', array('id' => $ix2));
        }
        if (!$obj1) {
            return get_string('errdblook', 'block_lockdownbrowser');
        }
        if (is_string($obj1)) {
            return $obj1;
        }
        if (!$locking_required) {
            $obj1->sesskey  = $sesskey;
            $obj1->timeused = $timenow;
            $ok             = $DB->update_record('block_lockdownbrowser_toke', $obj1);
            if (!$ok) {
                return get_string('errdbupdate', 'block_lockdownbrowser');
            }
        }
    }
    $msg = '<script> document.location = \'' . $url . '&'
      . $CFG->block_lockdownbrowser_ldb_token1_cookie . '=' . $obj1->token1 . '\'</script>';
    echo $msg;
    return 0;
}

function lockdownbrowser_validate_token2() {

    global $CFG, $DB;
    $valid = false;
    if (isset($_COOKIE[$CFG->block_lockdownbrowser_ldb_token2_cookie])
      && isset($_COOKIE[$CFG->block_lockdownbrowser_ldb_session_cookie . $CFG->sessioncookie])) {
        $tcookie2 = $_COOKIE[$CFG->block_lockdownbrowser_ldb_token2_cookie];
        $sesskey  = $_COOKIE[$CFG->block_lockdownbrowser_ldb_session_cookie . $CFG->sessioncookie];
        $obj1     = $DB->get_record('block_lockdownbrowser_toke', array('sesskey' => $sesskey));
        if ($obj1) {
            if (strcmp($obj1->token2, $tcookie2) == 0) {
                $valid = true;
            }
        }
    }
    return $valid;
}

function lockdownbrowser_generate_tokens_settings($news) {

    global $DB;
    $rf  = $DB->count_records('block_lockdownbrowser_toke')
      - $DB->count_records('block_lockdownbrowser_sess');
    if ($news) {
        $mkt = ($rf < 10000); // Trac #2315
    } else {
        $mkt = ($rf < 2000); // Trac #2315
    }
    if ($mkt) {
        return lockdownbrowser_generate_tokens(true);
    }
    return true;
}

function lockdownbrowser_generate_tokens_student() {
    global $DB;
    $mkt = false;
    $rf  = $DB->count_records('block_lockdownbrowser_toke')
      - $DB->count_records('block_lockdownbrowser_sess');
     // Trac #2315
    if (($rf < 10) || ($rf < 100 && ($rf % 5 == 0))
      || ($rf < 250 && ($rf % 20 == 0))) {
        $mkt = true;
    }
    if ($mkt) {
        return lockdownbrowser_generate_tokens(false);
    }
    return true;
}

function lockdownbrowser_generate_tokens_instructor() {
    global $DB;
    $rf = $DB->count_records('block_lockdownbrowser_toke')
      - $DB->count_records('block_lockdownbrowser_sess');
    if ($rf < 5000) { // Trac #2315
        return lockdownbrowser_generate_tokens(true);
    }
    return true;
}

function lockdownbrowser_ldblog($msg) {

    return;

    /*
        $file = fopen('c:\\temp\moolog.txt',"a");
        fwrite($file, date("h:i:s") . " - " . $msg . "\r\n");
        fclose($file);
     */
}

function lockdownbrowser_generate_tokens_debug($purge_sessions) {

    global $CFG, $DB;

    echo "<p>Moodle release: $CFG->release</p>";
    echo "<p>Moodle version: $CFG->version</p>";

    $plugin = new stdClass;

    $lockdownbrowser_version_file = "$CFG->dirroot/blocks/lockdownbrowser/version.php";
    if (is_readable($lockdownbrowser_version_file)) {
        include($lockdownbrowser_version_file);
    }
    if (isset($plugin->version)) {
        echo "<p>Block version: $plugin->version</p>";
    } else {
        echo "<p>Could not find block version file</p>";
    }

    echo "<p>LDB server name: $CFG->block_lockdownbrowser_ldb_servername</p>";

    if ($purge_sessions) {
        echo "<p>Purging stale sessions...</p>";
        flush();
        lockdownbrowser_purge_sessions(); // Trac #2315
    }

    $ok       = true;
    $timenow  = time();

    $plain    = $timenow . $CFG->block_lockdownbrowser_ldb_serverid
      . $CFG->block_lockdownbrowser_ldb_tserver_akey
      . $CFG->block_lockdownbrowser_ldb_serversecret;

    $auth     = md5($plain);

    $f1       = sprintf($CFG->block_lockdownbrowser_ldb_tserver_form1,
      $CFG->block_lockdownbrowser_ldb_serverid);

    $f2       = sprintf($CFG->block_lockdownbrowser_ldb_tserver_form2, $auth,
      $CFG->block_lockdownbrowser_ldb_servername,
      $CFG->block_lockdownbrowser_ldb_servertype);

    $formdata = $f1 . $timenow . $f2;
    $url      = $CFG->block_lockdownbrowser_ldb_tserver_1
      . $CFG->block_lockdownbrowser_ldb_tserver_endpoint;

    echo "<p>Token server url: $url</p>";
    echo "<p>Token server data: $formdata</p>";
    echo "<p>Initializing cURL...</p>";
    flush();

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $formdata);

    // support 302 redirects with re-POST for proxies
    if ($CFG->block_lockdownbrowser_ldb_proxy_defined == 1) {
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_POSTREDIR, 2);
        echo "<p>Proxy support enabled</p>";
    }

    echo "<p>Contacting token server...</p>";
    flush();

    $resp  = curl_exec($ch);
    $info  = curl_getinfo($ch);
    $error = curl_error($ch);
    curl_close($ch);

    echo "<p>cURL return code: " . $info['http_code'] . "</p>";

    if ($resp === false) {
        echo "<p>cURL returned an error response, giving up</p>";
        echo "<p>cURL error: $error</p>";
        return false;
    }
    if ($info['http_code'] != 200) {
        echo "<p>Unexpected return code, giving up</p>";
        return false;
    }
    if (strlen($resp) < 1000) {
        echo "<p>cURL response length was less than 1000 characters, giving up</p>";
        echo "<p>cURL response: $resp</p>";
        return false;
    }

    echo "<p>Decrypting token server response...</p>";
    flush();

    $fk2          = $CFG->block_lockdownbrowser_ldb_serversecret . $CFG->block_lockdownbrowser_ldb_tserver_bkey;
    $fk2          = substr($fk2, 0, 16);
    $resp_b64     = base64_decode($resp);

    $decoderesp = Blowfish::decrypt($resp_b64, $fk2, Blowfish::BLOWFISH_MODE_ECB, Blowfish::BLOWFISH_PADDING_ZERO, "01234567");

    $resp_len     = strlen($decoderesp);
    $expected_len = ($CFG->block_lockdownbrowser_ldb_tserver_set * $CFG->block_lockdownbrowser_ldb_tserver_rec);

    if ($resp_len != $expected_len) {
        echo "<p>Unexpected response length from token server, giving up</p>";
        echo "<p>Response length: $resp_len</p>";
        echo "<p>Expected length: $expected_len</p>";
        return false;
    }

    echo "<p>Response length from token server OK</p>";
    echo "<p>Updating token table in database...</p>";
    flush();

    $rpos = 0;
    for ($ix = 0; $ix < $CFG->block_lockdownbrowser_ldb_tserver_set; $ix++) {
        $tc          = new stdClass;
        $tc->token1  = "a" . substr($decoderesp, $rpos, $CFG->block_lockdownbrowser_ldb_tserver_t1l);

        $tc->token2  = substr($decoderesp, $rpos + $CFG->block_lockdownbrowser_ldb_tserver_t2p,
          $CFG->block_lockdownbrowser_ldb_tserver_t2l);

        $tc->sesskey = 0;
        $id          = $DB->insert_record('block_lockdownbrowser_toke', $tc);
        if (!$id) {
            echo "<p>Database insert failure row $ix, giving up</p>";
            return false;
        }
        $rpos += $CFG->block_lockdownbrowser_ldb_tserver_rec;
    }

    echo "<p>All database inserts succeeded, done</p>";

    return $ok;
}

function lockdownbrowser_generate_tokens($purge_sessions) {

    global $CFG, $DB;

    if ($purge_sessions)
        lockdownbrowser_purge_sessions(); // Trac #2315

    $ok       = true;
    $timenow  = time();

    $plain    = $timenow . $CFG->block_lockdownbrowser_ldb_serverid
      . $CFG->block_lockdownbrowser_ldb_tserver_akey
      . $CFG->block_lockdownbrowser_ldb_serversecret;

    $auth     = md5($plain);

    $f1       = sprintf($CFG->block_lockdownbrowser_ldb_tserver_form1,
      $CFG->block_lockdownbrowser_ldb_serverid);

    $f2       = sprintf($CFG->block_lockdownbrowser_ldb_tserver_form2, $auth,
      $CFG->block_lockdownbrowser_ldb_servername,
      $CFG->block_lockdownbrowser_ldb_servertype);

    $formdata = $f1 . $timenow . $f2;

    $url      = $CFG->block_lockdownbrowser_ldb_tserver_1
      . $CFG->block_lockdownbrowser_ldb_tserver_endpoint;

    $ch       = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $formdata);

    // support 302 redirects with re-POST for proxies
    if ($CFG->block_lockdownbrowser_ldb_proxy_defined == 1) {
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_POSTREDIR, 2);
    }

    $resp = curl_exec($ch);
    $info = curl_getinfo($ch);
    if ($resp == false || ($info['http_code'] != 200 || strlen($resp) < 1000)) {
        lockdownbrowser_ldblog("cURL returned: " . $info['http_code']);
        return false;
    }
    curl_close($ch);

    $fk2        = $CFG->block_lockdownbrowser_ldb_serversecret
      . $CFG->block_lockdownbrowser_ldb_tserver_bkey;

    $fk2        = substr($fk2, 0, 16);
    $resp_b64   = base64_decode($resp);

    $decoderesp = Blowfish::decrypt($resp_b64, $fk2, Blowfish::BLOWFISH_MODE_ECB, Blowfish::BLOWFISH_PADDING_ZERO, "01234567");

    if (strlen($decoderesp) !=
      ($CFG->block_lockdownbrowser_ldb_tserver_set * $CFG->block_lockdownbrowser_ldb_tserver_rec)) {
        lockdownbrowser_ldblog("bad response len strlen = " . strlen($decoderesp)); // DEBUG
        return false;
    }
    $rpos = 0;
    for ($ix = 0; $ix < $CFG->block_lockdownbrowser_ldb_tserver_set; $ix++) {
        $tc          = new stdClass;
        $tc->token1  = "a" . substr($decoderesp, $rpos, $CFG->block_lockdownbrowser_ldb_tserver_t1l);

        $tc->token2  = substr($decoderesp, $rpos + $CFG->block_lockdownbrowser_ldb_tserver_t2p,
          $CFG->block_lockdownbrowser_ldb_tserver_t2l);

        $tc->sesskey = 0;
        $id          = $DB->insert_record('block_lockdownbrowser_toke', $tc);
        if (!$id) {
            lockdownbrowser_ldblog("insert failure row $ix");
            return false;
        }
        $rpos += $CFG->block_lockdownbrowser_ldb_tserver_rec;
    }
    return $ok;
}

function lockdownbrowser_check_for_lock($quizobj) {

    // returns false if access should be allowed;
    // returns a message string if access should be prevented;
    // also see quizaccess_lockdownbrowser::prevent_access
    global $CFG, $DB;

    $id      = optional_param('id', 0, PARAM_INT); // Course Module ID
    $q       = optional_param('q', 0, PARAM_INT); // or quiz ID
    $attempt = optional_param('attempt', 0, PARAM_INT); // A particular attempt ID for review
    $cmid      = optional_param('cmid', 0, PARAM_INT); // Some pages use this for course Module ID

    $script = substr(strrchr($_SERVER['SCRIPT_NAME'], "/"), 1);
    $discriminator = "";
    if ($attempt) {
        $discriminator = $attempt;
    } else if ($q) {
        $discriminator = $q;
    } else if ($id) {
        $discriminator = $id;
    } else if ($cmid) {
        $discriminator = $cmid;
    }

    if (isset($_SESSION['LOCKDOWNBROWSER_CONTEXT'])) {
        if ($script . "NONE" . $discriminator == $_SESSION['LOCKDOWNBROWSER_CONTEXT']
          || $script . "VALID" . $discriminator == $_SESSION['LOCKDOWNBROWSER_CONTEXT']
          ) {
            return false;
        }
    }

    // debug
    //echo " DOING LDB CHECK";

    if ($id) {

        if (!$cm = get_coursemodule_from_id('quiz', $id)) {
            print_error("errcmid", "block_lockdownbrowser", "", $id);
        }
        if (!$course = $DB->get_record("course", array("id" => $cm->course))) {
            print_error("errcourse", "block_lockdownbrowser");
        }
        if (!$quiz = $DB->get_record("quiz", array("id" => $cm->instance))) {
            print_error("errnoquiz1", "block_lockdownbrowser", "", $cm);
        }
    } else if ($cmid) {

        if (!$cm = get_coursemodule_from_id('quiz', $cmid)) {
            print_error("errcmid", "block_lockdownbrowser", "", $cmid);
        }
        if (!$course = $DB->get_record("course", array("id" => $cm->course))) {
            print_error("errcourse", "block_lockdownbrowser");
        }
        if (!$quiz = $DB->get_record("quiz", array("id" => $cm->instance))) {
            print_error("errnoquiz1", "block_lockdownbrowser", "", $cm);
        }
    } else if ($q) {

        if (!$quiz = $DB->get_record("quiz", array("id" => $q))) {
            print_error("errquizid", "block_lockdownbrowser", "", $q);
        }
        if (!$course = $DB->get_record("course", array("id" => $quiz->course))) {
            print_error("errnocourse", "block_lockdownbrowser", "", $quiz);
        }
        if (!$cm = get_coursemodule_from_instance("quiz", $quiz->id, $course->id)) {
            print_error("errnocm", "block_lockdownbrowser", "", $q);
        }
    } else if ($attempt) {

        if (!$attempt = $DB->get_record("quiz_attempts", array("id" => $attempt))) {
            print_error("errattempt", "block_lockdownbrowser");
        }
        if (!$quiz = $DB->get_record("quiz", array("id" => $attempt->quiz))) {
            print_error("errnoquiz2", "block_lockdownbrowser", "", $attempt);
        }
        if (!$course = $DB->get_record("course", array("id" => $quiz->course))) {
            print_error("errnocourse", "block_lockdownbrowser", "", $quiz);
        }
        if (!$cm = get_coursemodule_from_instance("quiz", $quiz->id, $course->id)) {
            print_error("errnocm", "block_lockdownbrowser", "", $quiz->id);
        }
    } else {

        echo "<div style='font-size: 150%; color: red'>Cannot get quiz from: " . me() . "</div>";
        die;
    }

    $ldbopt = lockdownbrowser_get_quiz_options($quiz->id);

    if (!$ldbopt) {

        $_SESSION['LOCKDOWNBROWSER_CONTEXT'] = $script . "NONE" . $discriminator;
        return false;

    } else {

        if (bccomp($CFG->version, 2013111800, 2) >= 0) {
            // Moodle 2.6.0+.
            $context = context_module::instance($cm->id);
        } else {
            // Prior to Moodle 2.6.0.
            $context = get_context_instance(CONTEXT_MODULE, $cm->id);
        }

        if (has_capability('mod/quiz:manage', $context)
          || has_capability('mod/quiz:grade', $context) // Trac #3595
          || !has_capability('mod/quiz:view', $context)
          || !has_capability('mod/quiz:attempt', $context)
          ) {
            // May want to change this - see Trac #2341
            $_SESSION['LOCKDOWNBROWSER_CONTEXT'] = $script . "NONE" . $discriminator;
            return false;

        } else {

            $ok      = true;
            $myerror = "Unknown";

            if (!isset($_COOKIE[$CFG->block_lockdownbrowser_ldb_session_cookie . $CFG->sessioncookie])) {

                $myerror = "<div style='font-size: 150%; color:red; text-align: center; padding: 30px'>Session</div>";
                $ok      = false;
            } else {
                $sesskey = $_COOKIE[$CFG->block_lockdownbrowser_ldb_session_cookie . $CFG->sessioncookie];
            }

            if ($ok) {

                $ldb_detected = lockdownbrowser_browser_detected();
                if (!$ldb_detected) {
                    $myerror = "<div style='font-size: 150%; color:red; text-align: center; padding: 30px'>" .
                        get_string('ldb_required', 'block_lockdownbrowser') . "</div>";
                    //if (strlen($CFG->block_lockdownbrowser_ldb_download) > 0) {
                    //    $myerror .= "<div style='font-size: 125%; color:black; text-align: center;'>".
                    //           get_string('click','block_lockdownbrowser')." <a href='"
                    //           .$CFG->block_lockdownbrowser_ldb_download."' target='_blank'>"
                    //           .get_string('here','block_lockdownbrowser')."</a>"
                    //           .get_string('todownload','block_lockdownbrowser')."</div>";
                    //}
                    if (!empty($CFG->block_lockdownbrowser_ldb_download)) {
                        $myerror .= "<div style='font-size: 125%; color:black; text-align: center;'>"
                          . get_string('click', 'block_lockdownbrowser')
                          . " <a href='" . $CFG->block_lockdownbrowser_ldb_download
                          . "' target='_blank'>" . get_string('here', 'block_lockdownbrowser')
                          . "</a>" . get_string('todownload', 'block_lockdownbrowser')
                          . "</div>";
                    } else {
                        $myerror .= "<div style='font-size: 125%; color:black; text-align: center;'>"
                          . get_string('ldb_download_disabled', 'block_lockdownbrowser')
                          . "</div>";
                    }
                    $ok = false;
                }
            }

            if ($ok) {

                $ldbs = optional_param('ldbs', 0, PARAM_TEXT);

                if (!$ldbs) {

                    $use_existing = false;
                    $existing     = $DB->get_record('block_lockdownbrowser_sess', array('sesskey' => $sesskey));

                    if ($existing && strcmp($existing->sesskey, $sesskey) == 0) {
                        if (lockdownbrowser_db_locking_required()) {
                            $existobj1 = $DB->get_record('block_lockdownbrowser_toke', array('sesskey' => $sesskey));
                        } else {
                            $existobj1 = $DB->get_record('block_lockdownbrowser_toke', array('id' => $existing->id));
                        }
                        if ($existobj1 && strcmp($existobj1->sesskey, $sesskey) == 0) {
                            $obj1         = $existobj1;
                            $use_existing = true;
                        }
                    }

                    if (!$use_existing) {

                        $errmsg = lockdownbrowser_allocate_token1($sesskey, me() . "&ldbs=" . $sesskey);
                        if (is_string($errmsg)) {

                            $myerror = "Database error: " . $errmsg;
                            $ok      = false;
                        }
                    }
                } else if ($ldbs != $sesskey || !lockdownbrowser_validate_token2()) {

                    $myerror = "<div style='font-size: 150%; color:red; text-align: center; padding: 30px'>Session</div>";
                    $ok      = false;
                }
            }

            if ($ok) {

                $_SESSION['LOCKDOWNBROWSER_CONTEXT'] = $script . "VALID" . $discriminator;
                return false;

            } else {

                $_SESSION['LOCKDOWNBROWSER_CONTEXT'] = $script . "INVALID" . $discriminator;
                return $myerror;
            }
        }
    }
}

function lockdownbrowser_db_locking_required() {

    global $CFG;

    if ($CFG->version < 2014051200) {
        // Prior to Moodle 2.7.0
        return false;
    }
    return LOCKDOWNBROWSER_REQUIRE_DB_LOCKING;
}

function lockdownbrowser_update_unused_token_record_with_locking($sesskey, $timeused) {

    global $DB;

    if (!lockdownbrowser_db_locking_required()) {
        return get_string('errdblocksupport', 'block_lockdownbrowser');
    }
    $lockclass = "\\core\\lock\\db_record_lock_factory";
    if (!class_exists($lockclass)) {
        throw new \coding_exception('Lock factory class does not exist: ' . $lockclass);
    }
    $locktype = "block_lockdownbrowser_db";
    $resource = "unused_token_records";
    $lockfactory = new $lockclass($locktype);
    $lock = $lockfactory->get_lock($resource, LOCKDOWNBROWSER_MAX_DB_LOCK_TIME);
    if ($lock === false) {
        return get_string('errdbgetlock', 'block_lockdownbrowser');
    }
    try {
        $obj1 = $DB->get_records('block_lockdownbrowser_toke', array('timeused' => 0), '', '*', 0, 1);
    } catch (Exception $ex) {
        $obj1 = false;
    }
    if (count($obj1) > 0) {
        $obj1 = array_pop($obj1);
    }
    if ($obj1) {
        $obj1->sesskey  = $sesskey;
        $obj1->timeused = $timeused;
        try {
            $ok = $DB->update_record('block_lockdownbrowser_toke', $obj1);
        } catch (Exception $ex) {
            $ok = false;
        }
        if (!$ok) {
            $obj1 = get_string('errdbupdate', 'block_lockdownbrowser');
        }
    } else {
        $obj1 = get_string('errdblook', 'block_lockdownbrowser');
    }
    $lock->release();
    return $obj1;
}

function lockdownbrowser_check_plugin_dependencies($resultstyle) {
    // return false if dependencies are valid, or:
    // resultstyle = 0, return an error string
    // resultstyle = 1, return an error object
    // resultstyle = 2, throw an exception
    global $CFG;
    $blockversion = lockdownbrowser_get_block_version();
    if ($blockversion !== false) {
        $ruleversion = lockdownbrowser_get_rule_version();
        if ($ruleversion !== false) {
            if (lockdownbrowser_compare_plugin_versions($ruleversion, $blockversion) === true) {
                return false;
            } else {
                $identifier = "invalidversion";
            }
        } else {
            $identifier = "noruleversion";
        }
    } else {
        $identifier = "noblockversion";
    }
    $component = "block_lockdownbrowser";
    if ($resultstyle == 1
      && $CFG->version >= 2012062500 // Moodle 2.3.0+.
      ) {
        return new lang_string($identifier, $component);
    } else if ($resultstyle == 2) {
        print_error($identifier, $component);
    } else {
        return get_string($identifier, $component);
    }
}

function lockdownbrowser_get_rule_version() {
    global $CFG;
    $plugin = new stdClass;
    $version_file = $CFG->dirroot . '/mod/quiz/accessrule/lockdownbrowser/version.php';
    if (is_readable($version_file)) {
        include($version_file);
    }
    $version = false;
    if (isset($plugin)) {
        if (!empty($plugin->version)) {
            $version = $plugin->version;
        }
    }
    return $version;
}

function lockdownbrowser_get_block_version() {
    global $CFG;
    $plugin = new stdClass;
    $version_file = $CFG->dirroot . '/blocks/lockdownbrowser/version.php';
    if (is_readable($version_file)) {
        include($version_file);
    }
    $version = false;
    if (isset($plugin)) {
        if (!empty($plugin->version)) {
            $version = $plugin->version;
        }
    }
    return $version;
}

function lockdownbrowser_compare_plugin_versions($ruleversion, $blockversion) {
    // return true if the specified plugin versions are considered equal for
    // the purposes of dependency checking, else return false
    $comparelength = 8; // yyyymmddxx, but only consider first 8 digits
    if (strlen($ruleversion) < $comparelength
      || strlen($blockversion) < $comparelength ) {
        return false;
    }
    if(substr($ruleversion, 0, $comparelength)
      == substr($blockversion, 0, $comparelength)) {
        return true;
    } else {
        return false;
    }
}

// START UCLA MOD: CCLE-4027 - Install and evaluate Respondus
/**
 * Checks if the currently logged in user is the Respondus Monitor user.
 *
 * @return boolean
 */
function lockdownbrowser_is_monitor_user() {
    global $CFG, $USER;
    return $CFG->block_lockdownbrowser_monitor_username == $USER->username;
}
// END UCLA MOD: CCLE-4027