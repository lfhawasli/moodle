<?php
// Respondus LockDown Browser Extension for Moodle
// Copyright (c) 2011-2018 Respondus, Inc.  All Rights Reserved.
// Date: September 12, 2018.

namespace block_lockdownbrowser\privacy;

defined('MOODLE_INTERNAL') || die();

// Trac #4402
// Privacy Subsystem for block_lockdownbrowser implementing null_provider.
// This plugin does not store any personal user data.
class provider implements \core_privacy\local\metadata\null_provider {

    /**
     * Get the language string identifier with the component's language
     * file to explain why this plugin stores no data.
     *
     * @return  string
     */
    public static function get_reason() : string {
        return 'privacy:metadata';
    }
}
