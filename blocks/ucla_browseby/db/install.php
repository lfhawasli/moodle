<?php

require_once($CFG->dirroot . '/blocks/ucla_browseby/classes/observer.php');

function xmldb_block_ucla_browseby_install() {
    global $CFG, $DB;

    return true;
}
