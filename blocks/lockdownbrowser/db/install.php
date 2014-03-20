<?php
// Respondus LockDown Browser Extension for Moodle
// Copyright (c) 2011-2013 Respondus, Inc.  All Rights Reserved.
// Date: November 22, 2013.

function xmldb_block_lockdownbrowser_install() {

    global $DB;
    global $CFG;

    $enable_log  = false; // set TRUE to enable logging to temp file
    $install_log = "ldb_install.log";

    $warning_settings =
      "<p>lockdownbrowser block install warning: could not migrate quiz settings from legacy lockdown module</p>";
    $warning_files =
      "<p>lockdownbrowser block install warning: could not undo changes to quiz module made by legacy lockdown module</p>";

    $block_settings_table  = "block_lockdownbrowser_sett";
    $module_settings_table = "lockdown_settings";

    $quiz_table          = "quiz";
    $quiz_file           = "$CFG->dirroot/mod/quiz/attempt.php";
    $quiz_module_folder  = "$CFG->dirroot/mod/quiz";
    $quiz_backup_folders = "$CFG->dataroot/lockdownquiz-[0-1][0-9][0-3][0-9]-[0-1][0-9][0-5][0-9]/backup";

    if ($CFG->version >= 2011120500.00) { // Moodle 2.2
        $quiz_backup_files = array("attempt.php", "lib.php", "mod_form.php", "review.php", "view.php", "accessmanager.php");
    } else {
        $quiz_backup_files = array("accessrules.php", "attempt.php", "lib.php", "mod_form.php", "review.php");
    }

    $debug_info        = "* start: " . date("m-d-Y H:i:s") . "\r\n";
    $exception_msg     = "";
    $migrate_settings  = false;
    $undo_quiz_changes = false;

    try {
        $debug_info .= "* migrating settings from legacy ldb module to ldb block\r\n";
        $ok               = true;
        $dbman            = $DB->get_manager();
        $table            = new xmldb_table($module_settings_table);
        $migrate_settings = $dbman->table_exists($table);

        if (!$migrate_settings) {
            $debug_info .= "* no need to migrate settings: table $module_settings_table does not exist\r\n";
        }
        if ($migrate_settings) {
            $table = new xmldb_table($block_settings_table);
            $ok    = $dbman->table_exists($table);
            if (!$ok) {
                $debug_info .= "* error: table $block_settings_table does not exist\r\n";
            }
        }
        if ($migrate_settings && $ok) {
            $records = $DB->get_records($module_settings_table);
            $ok      = ($records !== false || count($records) != 0);
            if (!$ok) {
                $debug_info .= "* error: table $module_settings_table has no records\r\n";
            }
        }
        if ($migrate_settings && $ok) {
            foreach ($records as $modset) {
                if ($modset->attempts == 0 && $modset->reviews == 0) {
                    continue; // not LDB quiz, nothing to migrate
                }
                $quiz = $DB->get_record($quiz_table, array("id" => $modset->quizid));
                if ($quiz === false) {
                    continue; // quiz gone, no need to migrate
                }
                $blkset = $DB->get_record($block_settings_table, array("quizid" => $quiz->id));
                if ($blkset !== false) {
                    continue; // quiz settings already migrated
                }
                $blkset           = new stdClass;
                $blkset->course   = $quiz->course;
                $blkset->quizid   = $quiz->id;
                $blkset->attempts = 0;
                $blkset->reviews  = 0;
                $blkset->password = $modset->password;
                $blkset->monitor  = "";
                $ok               = $DB->insert_record($block_settings_table, $blkset);
                if (!$ok) {
                    $debug_info .= "* error: could not insert record into table $block_settings_table\r\n";
                    break;
                }
            }
        }
        if ($migrate_settings && $ok) {
            $debug_info .= "* settings migration succeeded\r\n";
        }
    } catch (Exception $e) {

        $info = get_exception_info($e);
        $msg  = "\r\nmessage: $info->message"
            . "\r\nerrorcode: $info->errorcode"
            . "\r\nbacktrace: $info->backtrace"
            . "\r\nlink: $info->link"
            . "\r\nmoreinfourl: $info->moreinfourl"
            . "\r\na: $info->a"
            . "\r\ndebuginfo: $info->debuginfo\r\n"
            . "\r\nstacktrace: "
            . $e->getTraceAsString();
        $debug_info .= "* exception occurred: $msg\r\n";
        $ok = false;
    }

    if ($migrate_settings && !$ok) {
        $exception_msg .= $warning_settings;
    }

    try {
        $debug_info .= "* restoring file changes made to quiz module by legacy ldb module\r\n";

        if ($migrate_settings) {
            $undo_quiz_changes = true;
        } else if (is_readable($quiz_file)) {
            $contents = file_get_contents($quiz_file);
            if ($contents !== false) {
                $pos               = strpos($contents, "## LockDown");
                $undo_quiz_changes = ($pos !== false);
            }
        }
        if (!$undo_quiz_changes) {
            $debug_info .= "* no need to restore file changes: could not find edit marker string\r\n";
        }
        if ($undo_quiz_changes) {
            $folders = glob($quiz_backup_folders, GLOB_ONLYDIR);
            $ok      = (is_array($folders) && count($folders) > 0 && strlen($folders[0]) > 0);
            if (!$ok) {
                $debug_info .= "* error: could not find quiz backup folder\r\n";
            }
        }
        if ($undo_quiz_changes && $ok) {
            if (count($folders) == 1) {
                $src_folder = $folders[0];
            } else { // find most recent folder
                $latest_time = 0;
                $latest_path = "";
                foreach ($folders as $path) {
                    $time = filectime($path);
                    $ok   = ($time !== false);
                    if (!$ok) {
                        $debug_info .= "* error: could not determine which quiz backup folder to use\r\n";
                        break;
                    }
                    if ($time > $latest_time) {
                        $latest_time = $time;
                        $latest_path = $path;
                    }
                }
                if ($ok) {
                    $src_folder = $latest_path;
                    $debug_info .= "* restoring from quiz backup folder $src_folder\r\n";
                }
            }
        }
        if ($undo_quiz_changes && $ok) {
            $dest_folder = $quiz_module_folder;
            $ok          = is_dir($dest_folder);
            if (!$ok) {
                $debug_info .= "* error: could not find quiz module folder $dest_folder\r\n";
            }
        }
        if ($undo_quiz_changes && $ok) {
            foreach ($quiz_backup_files as $file) {
                $ok2 = copy("$src_folder/$file", "$dest_folder/$file");
                if (!$ok2) {
                    $ok = false;
                    $debug_info .= "* error: could not copy $file from $src_folder to $dest_folder\r\n";
                }
            }
        }
        if ($undo_quiz_changes && $ok) {
            $debug_info .= "* restoring file changes succeeded\r\n";
        }
    } catch (Exception $e) {

        $info = get_exception_info($e);
        $msg  = "\r\nmessage: $info->message"
            . "\r\nerrorcode: $info->errorcode"
            . "\r\nbacktrace: $info->backtrace"
            . "\r\nlink: $info->link"
            . "\r\nmoreinfourl: $info->moreinfourl"
            . "\r\na: $info->a"
            . "\r\ndebuginfo: $info->debuginfo\r\n"
            . "\r\nstacktrace: "
            . $e->getTraceAsString();
        $debug_info .= "* exception occurred: $msg\r\n";
        $ok = false;
    }

    if ($undo_quiz_changes && !$ok) {
        $exception_msg .= $warning_files;
    }

    // write debug file
    $debug_info .= "* end\r\n";
    if ($enable_log) {
        if (isset($CFG->tempdir)) {
            $path = "$CFG->tempdir/$install_log";
        } else {
            $path = "$CFG->dataroot/temp/$install_log";
        }
        $handle = fopen($path, "wb");
        if ($handle !== false) {
            fwrite($handle, $debug_info, strlen($debug_info));
            fclose($handle);
        }
    }

    // inform user if migration failed
    if (strlen($exception_msg) > 0) {
        throw new moodle_exception($exception_msg, "block_lockdownbrowser");
    }
}

