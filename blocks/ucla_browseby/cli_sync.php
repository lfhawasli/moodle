<?php

define('CLI_SCRIPT', true);

require_once(dirname(__FILE__) . '/../../config.php');

require_once($CFG->dirroot . '/blocks/moodleblock.class.php');
require_once($CFG->dirroot . '/blocks/ucla_browseby/eventlib.php');

global $CFG;
require($CFG->libdir . '/clilib.php');

list($ext_argv, $unrecog) = cli_get_params(
    array(
        'all' => false,
        'current-term' => false,
        'subjarea' => false,
        'quiet' => false,
        'help' => false
    ),
    array(
        'h' => 'help',
        'q' => 'quiet'
    )
);

$reg_argv = array();
foreach ($argv as $arg) {
    if (strpos($arg, '-') !== false) {
        continue;
    }

    if (strlen($arg) == 3) {
        // If we have processed up to another TERM argument,
        // and we have no SRS requested within that TERM
        $reg_argv[] = $arg;
    }
}

// This may screw up...
ini_set('display_errors', '1');

// Figure out which terms to run for
$termlist = NULL;
if (!empty($reg_argv)) {
    $termlist = $reg_argv;
} 

if ($ext_argv['current-term']) {
    if (!empty($CFG->currentterm)) {
        $termlist = array($CFG->currentterm);
    } else {
        echo "No currentterm.\n";
    }
}

// Figure out which subject areas to run for.
$subjareas = null;
if ($ext_argv['subjarea']) {
    $subjareas = explode(',', $ext_argv['subjarea']);
}

$q = $ext_argv['quiet'];
if ($q) {
    ob_start();
}

if ($ext_argv['all']) {
    run_browseby_sync(null, $subjareas, true);
} else if (empty($termlist)) {
    echo "No terms specified!\n";
    $ext_argv['help'] = true;
}

if ($ext_argv['help']) {
    die (
"Usage: " . exec("which php") . ' ' . $argv[0] . " TERM [ TERM ... ]

Options:
    --all           Run BrowseBy for all terms.
    --current-term  Automatically use current term.
    --subjarea      Pass in a comma delinated list of subject areas to sync.
    -h, --help      Display this message.
    -q, --quiet     Make script say nothing. All output will be suppressed. 

");
}

run_browseby_sync($termlist, $subjareas);

if ($q) {
    ob_end_clean();
}

