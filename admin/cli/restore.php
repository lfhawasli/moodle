<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This script allows to do restore.
 *
 * @package    core
 * @subpackage cli
 * @copyright  2019 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', 1);

require(__DIR__.'/../../config.php');
require_once($CFG->libdir.'/clilib.php');
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

// Now get cli options.
list($options, $unrecognized) = cli_get_params(array(
    'from' => false,
    'categoryid' => false,
    'courseid' => false,
    'type' => '',
    'help' => false,
    ), array('h' => 'help'));

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help'] || empty($options['from']) ||
        (empty($options['categoryid']) && empty($options['courseid'])) ||
        (!empty($options['courseid']) && empty($options['type']))) {
    $help = <<<EOL
Perform restore of the given backup into new or existing course.

Options:
--from=STRING               Path to backup file.
--categoryid=INTEGER        Category ID to restore as new course into.
--courseid=INTEGER          Course ID for restore as existing course. Must define type.
--type=add|delete           Delete existing course content or add.
-h, --help                  Print out this help.

Example:
\$sudo -u www-data /usr/bin/php admin/cli/restore.php --from=/path/to/backup.mbz --[categoryid|courseid]=3 [--type=delete]\n
EOL;

    echo $help;
    die;
}

$admin = get_admin();
if (!$admin) {
    mtrace("Error: No admin account was found");
    die;
}

// Check that the source exists.
if (!is_readable($options['from'])) {
    cli_error('Error: Cannot read ' . $options['from']);
}
if (!is_file($options['from'])) {
    cli_error('Error: From must be a file: ' . $options['from']);
}
// Uncompress file.
$backupdir = "cli_restore_" . uniqid();
$sourcedirectory = $CFG->tempdir . DIRECTORY_SEPARATOR . "backup" . DIRECTORY_SEPARATOR . $backupdir;

cli_writeln("Extracting Moode backup file to: '" . $sourcedirectory);
$fp = get_file_packer('application/vnd.moodle.backup');
$fp->extract_to_pathname($options['from'], $sourcedirectory);

$xmlfile = $sourcedirectory . DIRECTORY_SEPARATOR . "course" . DIRECTORY_SEPARATOR . "course.xml";

// Different XML file in Moodle 1.9 backup.
if (!file_exists($xmlfile)) {
    $xmlfile = $path . DIRECTORY_SEPARATOR . "moodle.xml";
}

$xml = simplexml_load_file($xmlfile);
$fullname = $xml->xpath('/course/fullname');
if (!$fullname) {
    $fullname = $xml->xpath('/MOODLE_BACKUP/COURSE/HEADER/FULLNAME');
}
$shortname = $xml->xpath('/course/shortname');
if (!$shortname) {
    $shortname = $xml->xpath('/MOODLE_BACKUP/COURSE/HEADER/SHORTNAME');
}
$fullname = (string)($fullname[0]);
$shortname = (string)($shortname[0]);
if (!$shortname) {
    cli_error('No shortname in the backup file.');
}

cli_writeln(sprintf('Backup contained course %s: %s', $shortname, $fullname));

// If passed, check that category id exists.
$type = null;                   // Type of restore.
$destinationcourseid = null;    // Course to restore into.
$destinationshortname = null;
if (!empty($options['categoryid'])) {
    $category = $DB->get_record('course_categories', array('id' => $options['categoryid']), '*', MUST_EXIST);

    // Get unique shortname if creating new course.
    if ($DB->get_record('course', array('shortname' => $shortname))) {
        $matches = null;
        preg_match('/(.*)_(\d+)$/', $shortname, $matches);
        if ($matches) {
            $base = $matches[1];
            $number = $matches[2];
        } else {
            $base = $shortname;
            $number = 1;
        }
        $destinationshortname = $base . '_' . $number;
        while ($DB->get_record('course', array('shortname' => $destinationshortname))) {
            $number++;
            $destinationshortname = $base . '_' . $number;
        }
    } else {
        $destinationshortname = $shortname;
    }

    $type = backup::TARGET_NEW_COURSE;
    $destinationcourseid = restore_dbops::create_new_course($fullname, $destinationshortname, $category->id);

    cli_heading(sprintf('Restoring as new course %s in category %s', $destinationshortname,
        $category->name));

} else {
    $course = $DB->get_record('course', array('id' => $options['courseid']), '*', MUST_EXIST);
    $destinationcourseid = $course->id;
    $destinationshortname = $course->shortname;

    // Check that type is valid.
    if ($options['type'] == 'add') {
        $type = backup::TARGET_EXISTING_ADDING;
    } else if ($options['type'] == 'delete') {
        $type = backup::TARGET_EXISTING_DELETING;
    } else {
        cli_error('Invalid type specified.');
    }

    cli_heading(sprintf('Restoring %s into %s. Mode: %s', $shortname,
            $destinationshortname, $options['type']));
}

// Perform restore.
cli_writeln('Performing restore...');
$rc = new restore_controller($backupdir, $destinationcourseid, backup::INTERACTIVE_NO,
        backup::MODE_GENERAL, $admin->id, $type);
if ($rc->get_status() == backup::STATUS_REQUIRE_CONV) {
    $rc->convert();
}
if (!$rc->execute_precheck()) {
    $check = $rc->get_precheck_results();
    cli_problem('Error: Failed restore pre-check.');
    var_dump($check);
    die();
}

if ($type == backup::TARGET_EXISTING_DELETING) {
    cli_writeln('Deleting contents of ' . $destinationshortname);
    $deletingoptions = array();
    $deletingoptions['keep_roles_and_enrolments'] = 0;
    $deletingoptions['keep_groups_and_groupings'] = 0;
    restore_dbops::delete_course_content($destinationcourseid);
}

$rc->execute_plan();
rebuild_course_cache($destinationcourseid);
$rc->destroy();

cli_writeln('Finished restore.');
exit(0);
