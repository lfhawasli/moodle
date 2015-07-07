<?php
/**
 * @package    local_publicprivate
 * @copyright  2013 UCLA regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__.'/../../../config.php');
require_once($CFG->libdir.'/clilib.php');
require_once($CFG->dirroot.'/local/publicprivate/lib/course.class.php');
require_once($CFG->dirroot.'/lib/accesslib.php');

// Now get cli options.
list($options, $unrecognized) = cli_get_params(
    array(
        'courseid' => false,
        'days' => false,
        'help' => false,
        'verbose' => false
    ),
    array(
        'd' => 'days',
        'c' => 'courseid',
        'h' => 'help',
        'v' => 'verbose'
    )
);

if ($unrecognized) {
    echo "Unrecognized arguments found!\n";

    if (!empty($unrecognized)) {
        $unrecognized = implode("\n  ", $unrecognized);
        cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
    }
}

if ($options['help']) {
    $help =
"
Description:
Combs through courses and cleans up group membership based on roles and enrolment plugins.
By default, checks all courses with publicprivate enabled.

Options:
-c, --courseid=COURSEID Cleans specified course
-d, --days=DAYS         Cleans only publicprivate enabled courses whose enrolment methods 
                        that have been modified in the last 'DAYS' days.
-v, --verbose           Print verbose progress information
-h, --help              Print out this help

Examples:
\$cleanup.php -c=[COURSEID]
    cleans the specified course.
\$cleanup.php -d=[DAYS] -v
    only cleans courses whose enrolment methods have been modified within the last 'DAYS'
    days and prints progress information.
";

    echo $help;
    die;
}

$verbose = !empty($options['verbose']);
$idgiven = !empty($options['courseid']) && $options['courseid'] !== true;
$daysgiven = !empty($options['days']) && $options['days'] !== true;

if ($idgiven) {
    // Get a single course.
    $c = $DB->get_record('course', array('id' => intval($options['courseid'])), 
                                 'id,enablepublicprivate,grouppublicprivate,groupingpublicprivate');
    if (empty($c)) {
        cli_error('Failed to get course, check course id.');
    } else {
        $courses[] = $c;
    }
    if ($courses[0]->enablepublicprivate != 1) {
        cli_error('Publicprivate not enabled on specified course');
    }
} else if ($daysgiven) {
    $t = time() - ($options['days'] * 86400); // There are 86400 seconds in a day.
    $sql = "SELECT c.*
              FROM {course} c
              JOIN {enrol} e ON (e.courseid=c.id)
             WHERE e.timemodified >= ? AND c.enablepublicprivate=1";
    $courses = $DB->get_recordset_sql($sql, array($t));
} else {
    // Get all courses.
    // We only need id, grouppublicprivate, enablepublicprivate, groupingpublicprivate.
    $courses = $DB->get_recordset('course', array('enablepublicprivate' => 1), '',
                                  'id,enablepublicprivate,grouppublicprivate,groupingpublicprivate');
}

foreach ($courses as $course) {
    if ($verbose ) {
        echo "checking course with id $course->id\n";
    }
    $context = context_course::instance($course->id);
    $ppc = new PublicPrivate_Course($course);
    $users = get_enrolled_users($context);
    $instances = enrol_get_instances($course->id, true);
    
    foreach ($users as $user) {
        if ($verbose ) {
            echo "checking user with id $user->id\n";
        }
        $roles = get_user_roles($context, $user->id);
        if (empty($roles)) {
            // If a user has no roles, remove them from all groups
            $groups = groups_get_all_groups($course->id, $user->id);
            foreach ($groups as $group) {
                groups_remove_member($group->id, $user->id);
            }
        } else {
            $ppc->check_enrolments($user->id, $instances);
        }
    }
}

if (!$idgiven && !$daysgiven) {
    $courses->close();
}

exit(0);
