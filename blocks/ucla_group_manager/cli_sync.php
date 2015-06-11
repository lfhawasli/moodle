<?php

define('CLI_SCRIPT', true);

require(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/blocks/ucla_group_manager/lib.php');
require_once($CFG->libdir . '/clilib.php');

list($options, $unrecog) = cli_get_params(
        array(
            'help' => false,
            'course-id' => false,
            'current-term' => false,
            'term' => false,
            'verbose' => false
        ),
        array(
            'h' => 'help',
            'c' => 'current-term',
            'v' => 'verbose'
        )
);

if ($options['help']) {
    $help = "Syncs courses with the registrar so that their groups and groupings correspond
to their course sections. With its default options, this script will sync all courses in the 
'mdl_courses' table.

Options:
-h, --help              Print this help text.
--course-id             Limit the group/groupings sync to only courses with ids
                        listed (comma-separated values).
--term                  Limit the group/groupings sync to only courses with
                        terms listed (comma-separated values). Using this will
                        nullify use of the --current-term option.
-c, --current-term      Perform the sync only on courses in the current term.
                        The --term option has precedence over --current-term.
-v, --verbose           Include output from syncing function, reports errors,
                        warnings, and status of group and groupings syncing
                        
Examples:
php blocks/ucla_group_manager/cli_sync.php                  Syncs all courses in any term
php blocks/ucla_group_manager/cli_sync.php --course-id=812  Syncs course with id 812 regardless of term
php blocks/ucla_group_manager/cli_sync.php --current-term   Syncs all courses in the current term
php blocks/ucla_group_manager/cli_sync.php --term=15W,15S   Sync all courses in terms 15W and 15S
";

    echo $help;
    die;
}

$where = '';
$join = '';
$courseparams = [];
$termparams = [];

// Process course id.
$courseslist = preg_split('/\s*,\s*/', $options['course-id'], -1, PREG_SPLIT_NO_EMPTY);
if (!empty($courseslist)) {
    list($coursesql, $courseparams) = $DB->get_in_or_equal($courseslist, SQL_PARAMS_NAMED, 'id');
    $where = 'WHERE c.id ' . $coursesql;
}

// Process term.
// If no term specified, do all and skip joining the ucla_request_classes table.
if ($options['term'] || $options['current-term'] && !empty($CFG->currentterm)) {
    $termslist = preg_split('/\s*,\s*/', $options['term'], -1, PREG_SPLIT_NO_EMPTY);
    // If no term options, then current-term must be true.
    if (empty($termslist)) {
        $termslist = array($CFG->currentterm);
    }
    list($termsql, $termparams) = $DB->get_in_or_equal($termslist, SQL_PARAMS_NAMED, 'term');
    $join = 'JOIN {ucla_request_classes} AS urc ON c.id = urc.courseid ';
    if (empty($where)) {
        $where = 'WHERE ';
    } else {
        $where .= 'AND ';
    }
    $where .= 'urc.term ' . $termsql . ' AND urc.hostcourse = 1';
}

// Make DB call.
$params = array_merge($courseparams, $termparams);
$courses = $DB->get_fieldset_sql('SELECT c.id FROM {course} AS c ' . $join . $where, $params);
if (!$courses) {
    cli_error('No courses found');
}

// Begin sync.
$count = count($courses);
echo "Syncing $count courses...\n";
$groupmanager = new ucla_group_manager();
foreach ($courses as $courseid) {
    // The sync_course method generates a lot of text. Allow the user to
    // ignore this.
    if (empty($options['verbose'])) {
        ob_start();
    }
    $groupmanager->sync_course($courseid);
    if (empty($options['verbose'])) {
        ob_end_clean();
    }
    echo "Course [$courseid] was synced\n";
}