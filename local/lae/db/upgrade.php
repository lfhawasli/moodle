<?php

defined('MOODLE_INTERNAL') || die();

function xmldb_local_lae_upgrade($oldversion) {
    global $CFG, $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2014010900) {
        // Add mnethostid and email address to Anonymous User.
        $user = $DB->get_record('user', array('id' => $CFG->anonymous_userid));
        if (empty($user->email)) {
            $user->email = get_string('auser_email', 'local_lae');
        }
        $user->mnethostid = $CFG->mnet_localhost_id;
        $DB->update_record('user', $user);
        upgrade_plugin_savepoint(true, 2014010900, 'local', 'lae');
    }

    if ($oldversion < 2014041600) {
        // Set context for Anonymous User.
        $user = $DB->get_record('user', array('id' => $CFG->anonymous_userid));
        context_user::instance($user->id);
        upgrade_plugin_savepoint(true, 2014041600, 'local', 'lae');
    }

    return true;
}
