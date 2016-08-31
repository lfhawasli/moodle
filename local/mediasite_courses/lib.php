<?php
defined('MOODLE_INTERNAL') || die;

require_once(dirname(__FILE__) . '/../../config.php');
if (is_mod_mediasite_installed()) {
    require_once("$CFG->dirroot/mod/mediasite/navigation.php");
}

function local_mediasite_courses_extend_navigation(global_navigation $nav) {
    if (!is_mod_mediasite_installed()) {
        return;
    }
    // $nav is the global navigation instance.
    // Here you can add to and manipulate the navigation structure as you like.
    // This callback was introduced in 2.0 as nicehack_extends_navigation(global_navigation $nav)
    // In 2.3 support was added for local_nicehack_extends_navigation(global_navigation $nav).
    // In 2.9 the name was corrected to local_nicehack_extend_navigation() for consistency
    
    // debugging('local_mediasite_courses_extend_navigation');
    mediasite_navigation_extension_mymediasite_placement();
    mediasite_navigation_extension_courses7_course();
}

function local_mediasite_courses_extends_navigation(global_navigation $nav) {
    if (!is_mod_mediasite_installed()) {
        return;
    }
    // this is the callback for pre-2.9
    local_mediasite_courses_extend_navigation($nav);
}

function local_mediasite_courses_extend_settings_navigation($settingsnav, $context) {
    if ($context->contextlevel == CONTEXT_COURSE) {
        // find the course settings menu
        global $PAGE;
        $key = 'courseadmin';
        $coursenode = $settingsnav->get($key);
        if ($coursenode != false) {
            return mediasite_extend_navigation_course_settings($coursenode, $context);
        } else {
            return mediasite_extend_navigation_course_settings($settingsnav, $context);
        }
    }
    return null;
}

function is_mod_mediasite_installed() {
    global $CFG;
    return file_exists($CFG->dirroot."/mod/mediasite/navigation.php");
}

?>