<?php
// Respondus LockDown Browser Extension for Moodle
// Copyright (c) 2011-2016 Respondus, Inc.  All Rights Reserved.
// Date: May 13, 2016.

if (file_exists("$CFG->dirroot/blocks/lockdownbrowser/locklib.php")) {

    require_once("$CFG->dirroot/blocks/lockdownbrowser/locklib.php");
    lockdownbrowser_check_for_lock();
}

