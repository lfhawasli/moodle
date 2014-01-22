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
        'help' => false
    ),
    array(
        'h' => 'help'
    )
);

if ($options['help']) {
    $help =
"Command line script to force auto-generated forums to be private for a given
 term.

Options:
-h, --help            Print out this help

Example:
\$sudo -u www-data /usr/bin/php local/ucla/scripts/pp_fixforums [TERM]
";
    echo $help;
    die;
}

// Make sure that first parameter is a term.
if (empty($unrecognized) || !ucla_validator('term', $unrecognized[0])) {
    die("Must pass in a valid term.\n");
}
$term = $unrecognized[0];

$params = array(
    'term' => $term,
    'discussion' => 'Discussion forum',
    'announcement' => 'Announcements',
);

// NOTE: name field is NOT indexed.
$sql = "SELECT  cm.id, urc.courseid, c.shortname, f.name
        FROM    {forum} f
        JOIN    {modules} m ON (m.name='forum')
        JOIN    {course_modules} cm ON (cm.module=m.id AND cm.instance=f.id AND f.course=cm.course)
        JOIN    {course} c ON (cm.course=c.id)
        JOIN    {ucla_request_classes} urc ON (c.id=urc.courseid)
        WHERE   urc.term=:term AND
                ((f.type='general' AND f.name LIKE :discussion)
                OR (f.type='news' AND f.name LIKE :announcement))";

$records = $DB->get_recordset_sql($sql, $params);

if ($records->valid()) {
    
    $publicprivatelib = $CFG->dirroot . '/local/publicprivate/lib/module.class.php';
    require_once($publicprivatelib);

    $countforums = 0;
    foreach($records as $record) {
        // Makes a module private (if pp is enabled) and rebuilds course cache.
        if (PublicPrivate_Course::build($record->courseid)->is_activated()) {
            $ppmopdule = PublicPrivate_Module::build($record->id);
            if (!$ppmopdule->is_private()) {
                $ppmopdule->enable();
                echo "Made '" . $record->name . "' for " . $record->shortname . " private\n";
                ++$countforums;
            }
        }
    }

    echo "Made $countforums forums private.\n";
} else {
    echo "No records found.  Nothing was modified.\n";
}

echo "Finished.\n";