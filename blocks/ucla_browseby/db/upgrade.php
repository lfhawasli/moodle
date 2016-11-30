<?php

require_once($CFG->dirroot . '/blocks/ucla_browseby/db/install.php');
function xmldb_block_ucla_browseby_upgrade($oldversion = 0) {
    global $CFG, $PAGE, $SITE, $DB;

    $dbman = $DB->get_manager();

    $result = true;

    if ($result && $oldversion < 2012032703) {
        xmldb_block_ucla_browseby_install();
    }

    if ($oldversion < 2016111800) {

        // Define index uid (unique) to be added to ucla_browseall_instrinfo.
        $table = new xmldb_table('ucla_browseall_instrinfo');
        $index = new xmldb_index('uid', XMLDB_INDEX_NOTUNIQUE, array('uid'));

        // Conditionally launch add index uid.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Ucla_browseby savepoint reached.
        upgrade_block_savepoint(true, 2016111800, 'ucla_browseby');
    }

    // This adds an instance of this block to the site page if it
    // doesn't already exist
    block_ucla_browseby::add_to_frontpage();

    return $result;
}
