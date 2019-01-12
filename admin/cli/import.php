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
 * This script allows to do import.
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
    'to' => false,
    'type' => '',
    'help' => false,
    ), array('h' => 'help'));

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help'] || empty($options['from']) || empty($options['to']) || empty($options['type'])) {
    $help = <<<EOL
Perform import of one course into another.

Options:
--from=INTEGER              Course ID for import source.
--to=INTEGER                Course ID for target.
--type=add|delete           Delete existing course content or add.
-h, --help                  Print out this help.

Example:
\$sudo -u www-data /usr/bin/php admin/cli/import.php --from=2 --to=3 --type=delete\n
EOL;

    echo $help;
    die;
}

$admin = get_admin();
if (!$admin) {
    mtrace("Error: No admin account was found");
    die;
}

// Check that the courses exists.
$source = $DB->get_record('course', array('id' => $options['from']), '*', MUST_EXIST);
$destination = $DB->get_record('course', array('id' => $options['to']), '*', MUST_EXIST);

// Check that type is valid.
$type = null;
if ($options['type'] == 'add') {
    $type = backup::TARGET_EXISTING_ADDING;
} else if ($options['type'] == 'delete') {
    $type = backup::TARGET_EXISTING_DELETING;
} else {
    cli_error('Invalid type specified.');
}

cli_heading(sprintf('Importing %s into %s. Mode: %s', $source->shortname,
        $destination->shortname, $options['type']));

// First create backup.
cli_writeln('Performing backup...');
$bc = new backup_controller(backup::TYPE_1COURSE, $source->id, backup::FORMAT_MOODLE,
        backup::INTERACTIVE_NO, backup::MODE_IMPORT, $admin->id);

$bc->set_status(backup::STATUS_AWAITING);
$bc->execute_plan();
$backupdir = basename($bc->get_plan()->get_basepath());
$bc->destroy();
unset($bc);

// Then perform import.
cli_writeln('Importing from backup...');
if (!file_exists($CFG->dataroot.'/temp/backup/'.$backupdir.'/course/course.xml')) {
    cli_error('Error: No backup found.');
}

$rc = new restore_controller($backupdir, $destination->id, backup::INTERACTIVE_NO,
        backup::MODE_IMPORT, $admin->id, $type);
if (!$rc->execute_precheck()) {
    $check = $rc->get_precheck_results();
    cli_problem('Error: Failed restore pre-check.');
    var_dump($check);
    die();
}

if ($type == backup::TARGET_EXISTING_DELETING) {
    cli_writeln('Deleting contents of ' . $destination->shortname);
    // If existing course shall be overwritten, delete current content.
    $deletingoptions = array();
    $deletingoptions['keep_roles_and_enrolments'] = 0;
    $deletingoptions['keep_groups_and_groupings'] = 0;
    restore_dbops::delete_course_content($destination->id, $deletingoptions);
}

$rc->execute_plan();
rebuild_course_cache($destination->id);
$rc->destroy();

cli_writeln('Finished import.');
exit(0);
