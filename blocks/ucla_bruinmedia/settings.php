<?php

/*
 * Generates the settings form for the Bruinmedia Block
 */

defined('MOODLE_INTERNAL') || die;

if($ADMIN->fulltree) {

    $settings->add(new admin_setting_configcheckbox(
                'block_ucla_bruinmedia/quiet_mode',
                get_string('quiet_mode_header','block_ucla_bruinmedia'),
                get_string('quiet_mode_desc','block_ucla_bruinmedia'),
                0
            ));

    $settings->add(new admin_setting_configtext(
               'block_ucla_bruinmedia/source_url',
                get_string('source_url_header','block_ucla_bruinmedia'),
                get_string('source_url_desc','block_ucla_bruinmedia'),
                'http://www.oid.ucla.edu/help/info/bmedialinks/',
                PARAM_URL 
            ));
    $settings->add(new admin_setting_configtext(
                'block_ucla_bruinmedia/errornotify_email',
                get_string('errornotify_email_header','block_ucla_bruinmedia'),
                get_string('errornotify_email_desc','block_ucla_bruinmedia'),
                '',
                PARAM_EMAIL
            ));

}
