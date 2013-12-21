<?php
/*
 * Command line script to force auto-generated forums to be private after a 
 * given term.
 */

define('CLI_SCRIPT', true);

$moodleroot = dirname(dirname(dirname(dirname(__FILE__))));
require($moodleroot . '/config.php');
require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/lib/clilib.php');

list($options, $unrecognized) = cli_get_params(
    array(
        'help' => false,
        'term' => false,
    ),
    array(
        'h' => 'help',
        't' => 'term'
    )
);

if ($options['help']) {
    $help =
"Command line script to force auto-generated forums to be private after a given
 term.

Options:
-h, --help            Print out this help
-t, --term            A valid UCLA term, ex: 13F

Example:
\$sudo -u www-data /usr/bin/php local/ucla/scripts/pp_fixforums -t 13F
";
    echo $help;
    die;
} else if ($options['term']) {
    $term = $unrecognized[0];
} else {
    $term = '13F';
}

global $DB, $CFG;

if (!ucla_validator('term', $term)) {
    echo "Invalid term: " . $term . "\n";
    die();
}

switch($term) {
    case '13F':
        $timemodified = strtotime('September 23, 2013');
        break;
    case '14W':
        $timemodified = strtotime('January 2, 2014');
        break;
}

$params = array(
    'time' => $timemodified,
    'discussion' => 'Discussion forum',
    'announcement' => 'Announcements',
);

// NOTE: name field is NOT indexed.
$sql = "SELECT id, course, name, type, timemodified
        FROM    {forum} 
        WHERE   timemodified > :time
        AND     ((type='general' AND name LIKE :discussion) 
                OR (type='news' AND name LIKE :announcement))";

$records = $DB->get_records_sql($sql, $params);

if ($records) {
    
    echo "Going to make " . count($records) . " forums private.\n";
    
    $publicprivatelib = $CFG->dirroot . '/local/publicprivate/lib/module.class.php';
    require_once($publicprivatelib);
    
    foreach($records as $record) {
        // Makes a module private (if pp is enabled) and rebuilds course cache.
        if (PublicPrivate_Course::build($record->course)->is_activated()) {
            PublicPrivate_Module::build($record->id)->enable();
            echo "Updated '" . $record->name . "' for course: " . $record->course . "\n";
        }
    }
} else {
    echo "No records found.  Nothing was modified.\n";
}

echo "Finished.\n";