<?php

define('CLI_SCRIPT', true);

require(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/blocks/ucla_group_manager/lib.php');
require_once($CFG->libdir . '/clilib.php');

list($options, $unrecog) = cli_get_params(
    array(
        'all' => false,
        'future' => false,
        'help' => false,
        'course-id' => false,
        'current-term' => true,
        'verbose' => false
    ),
    array(
        'h' => 'help',
        'c' => 'current-term',
        'f' => 'future',
        'v' => 'verbose'
    )
);

if ($options['help'] || empty($options['course-id']) && empty($options['all'])) {
    $help = 
"Syncs courses with the registrar so that their groups and groupings correspond
to their course sections.

Options:
-h, --help              Print this help
--course-id             Perform group/groupings sync on all courses with ids
                        listed (comma-separated values or * for all). Required
                        if option '--all' is not used.
--all                   Perform group/groupings sync on all courses. Equivalent
                        to using option '--course-id=*'. Required if option
                        '--course-id' is not used. Using '--all' will override
                        the course-id options
-c, --current-term      Perform the sync only on courses in the current term.
                        Value is by default set to true . Add
                        '--current-term=0' or '--current-term='' ' to perform
                        course sync regardless of term.
-v, --verbose           Include output from syncing function, reports errors,
                        warnings, and status of group and groupings syncing
                        
Examples:
php blocks/ucla_group_manager/cli_sync.php --all                Syncs all courses with the current term
php blocks/ucla_group_manager/cli_sync.php --course-id=812 -c=0 Syncs course with id 812 regardless of term
";
    
    echo $help;
    die;
}

// Process 'course-id' or 'all' options.
$courseslist = preg_split('/\s*,\s*/', $options['course-id'], -1, PREG_SPLIT_NO_EMPTY);
if (in_array('*', $courseslist) || $options['all']) {
    $where = '';
    $params = array();
} else {
    list($sql, $params) = $DB->get_in_or_equal($courseslist, SQL_PARAMS_NAMED, 'id');
    $where = 'WHERE c.id '. $sql;
}

// Process 'current-term' option.
$join = '';
if ($options['current-term']) {
    if (!empty($where)) {
        $where .= ' AND ';
    } else {
        $where .= 'WHERE ';
    }
    $join = 'JOIN {ucla_request_classes} AS urc ON urc.courseid = c.id ';
    $where .= 'urc.term = \'' . $CFG->currentterm . '\'';
}

$coursescount = $DB->get_field_sql('SELECT count(c.id) FROM {course} AS c '. $join . $where, $params);
if (!$coursescount) {
    cli_error('No courses found');
}

// Begin sync.
echo "Syncing $coursescount courses...\n\n";
$courses = $DB->get_fieldset_sql('SELECT c.id FROM {course} AS c '. $join . $where, $params);
$groupmanager = new ucla_group_manager();
foreach ($courses as $courseid) {
    // The sync_course method generates a lot of text and warnings. Allow the user to
    // ignore this.
    if (empty($options['verbose'])) {
        ob_start();
        $prevlevel = error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED & ~E_WARNING);
    }
    $groupmanager->sync_course($courseid);
    if (empty($options['verbose'])) {
        ob_end_clean();
        error_reporting($prevlevel);
    }
    echo "Course [$courseid] was synced\n";
}

    // TODO work for selected terms
    // TODO implement future terms