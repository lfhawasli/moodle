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
 * CLI script to fix "broken" assignments 2.2 modules.
 *
 * For the assignment upgrade tool to work (CCLE-3282) we need to fix several
 * problems with the old assignment entries. Such problems include:
 *  - Missing entries in the course_modules table.
 *  - Assignments belonging to non-existent sections.
 *
 * @package    local_ucla
 * @copyright  2013 UCLA regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__.'/../../../config.php');
require_once("$CFG->libdir/clilib.php");
require_once("$CFG->dirroot/course/lib.php");

// Now get cli options.
list($options, $unrecognized) = cli_get_params(array('help' => false),
        array('h' => 'help'));

if ($options['help']) {
    $help = "CLI script to fix \"broken\" assignments 2.2 modules.

For the assignment upgrade tool to work (CCLE-3282) we need to fix several
problems with the old assignment entries. Such problems include:
  - Missing entries in the course_modules table.
  - Assignments belonging to non-existent sections.

Options:
-h, --help            Print out this help

Example:
\$sudo -u www-data /usr/bin/php local/ucla/cli/fix_assignments22.php
";

    echo $help;
    die;
}

$trace = new text_progress_trace();

// Handle assignment modules missing from the course_modules table.
$sql = "SELECT  a.*
        FROM    {assignment} a
        WHERE   a.id NOT IN (
            SELECT  cm.instance
            FROM   {course_modules} cm
            JOIN    {modules} m ON (cm.module=m.id)
            WHERE   m.name='assignment'
        )";
$missingcmods = $DB->get_records_sql($sql);

$trace->output(sprintf('Found %d missing course module for assignment',
        count($missingcmods)));

if (!empty($missingcmods)) {
    $moduleid = $DB->get_field('modules', 'id', array('name' => 'assignment'));
    foreach ($missingcmods as $missingcmod) {
        $trace->output(sprintf('Processing assignmentid ' . $missingcmod->id));

        // Just add to section 0.
        $sectionnum = 0;

        // Create course module.
        $cm = new stdClass();
        $cm->course     = $missingcmod->course;
        $cm->module     = $moduleid;
        $cm->instance   = $missingcmod->id;
        $cm->section    = $sectionnum;
        $cm->added      = $missingcmod->timemodified;

        try {
            $cm->id = $DB->insert_record('course_modules', $cm);

            // Add course module to section.
            course_add_cm_to_section($missingcmod->course, $cm->id, $sectionnum);

            // Create context instance.
            $context = context_module::instance($cm->id);

            $trace->output(sprintf('Created course_modules (%d) and context (%d) entries',
                    $cm->id, $context->id), 1);
        } catch (Exception $e) {
            $trace->output('Cannot create course_modules entry: ' . $e->getMessage(), 1);
        }
    }
}

// Handle assignment modules in nonexistent sections.
$sql = "SELECT  cm.*
        FROM    {assignment} a
        JOIN    {course_modules} cm ON (a.id=cm.instance)
        JOIN    {modules} m ON (m.id=cm.module)
        WHERE   m.name='assignment' AND
                cm.section NOT IN (
            SELECT  id
            FROM   {course_sections} cs
            WHERE   1
        )";
$nonexistentsections = $DB->get_records_sql($sql);

$trace->output(sprintf('Found %d assignments in nonexistent sections',
        count($nonexistentsections)));

// TODO need to fix numsections?
if (!empty($nonexistentsections)) {
    $coursecache = array(); // Indexed by courseid => section number.
    foreach ($nonexistentsections as $nonexistentsection) {
        $sectionnum = null;
        if (isset($coursecache[$nonexistentsection->course])) {
            $sectionnum = $coursecache[$nonexistentsection->course];
        } else {
            // Create a new section called "Upgraded assignments (2.2)".
            $course = $DB->get_record('course',
                    array('id' => $nonexistentsection->course));

            // Clean up sections, since we are manipulating them.
            local_ucla_course_section_fixer::fix_problems($course);
            $numsections = course_get_format($course)->get_course()->numsections;

            // Create new section, but make sure one doesn't already exist.
            $section = new stdClass();
            $section->course = $nonexistentsection->course;
            $section->name = 'Upgraded assignments (2.2)';
            $section->summary = '';
            $section->summaryformat = 1; // FORMAT_HTML, but must be a string.
            $section->visible = 1;
            $section->showavailability = 0;
            $section->availablefrom = 0;
            $section->availableuntil = 0;
            $section->groupingid = 0;

            $movesection = false;
            if (!$DB->record_exists('course_sections', array('section' => $numsections + 1))) {
                // Safe to use the next number in the sequence.
                $section->section = $numsections + 1;
            } else {
                // A section already exists there, just add the next section.
                $maxsection = $DB->get_field('course_sections', 'MAX(section)',
                        array('course' => $course->id));
                $section->section = $maxsection + 1;
                $movesection = true;
            }

            // Insert record and then see if we need to move section.
            $sectionid = $DB->insert_record('course_sections', $section);
            $trace->output(sprintf('Created section %d (%d) for course %d',
                    $section->section, $sectionid, $course->id));

            if ($movesection) {
                // So move_section_to does not allow moving of sections beyond
                // $numsections, so let's temporarily set it to be high.
                course_get_format($course)->update_course_format_options(
                        array('numsections' => $section->section));

                $result = move_section_to($course, $section->section, $numsections + 1);
                if (empty($result)) {
                    $trace->output(sprintf('Call to move_section_to failed with %s|%s|%s',
                            $course->id, $section->section, $numsections + 1), 1);
                    // Reset back numsections.
                    course_get_format($course)->update_course_format_options(
                            array('numsections' => $numsections));
                    die('Exited');
                } else {
                    // No need to reset numsections, it will be changed next.
                    $trace->output(sprintf('Moved section %d to %d for course %d',
                            $section->section, $numsections + 1, $course->id), 1);
                }
            }

            $sectionnum = $numsections + 1;
            $coursecache[$course->id] = $sectionnum; // Cache section.

            // Increase numsection so newly created section will display now.
            course_get_format($course)->update_course_format_options(
                    array('numsections' => $sectionnum));
        }

        $newsectionid = course_add_cm_to_section($nonexistentsection->course,
                $nonexistentsection->id, $sectionnum);

        $trace->output(sprintf('Moved course_modules (%d) to section %d (%d)',
                $nonexistentsection->id, $sectionnum, $newsectionid), 1);
    }

    // Now go through course cache and hide all newly created sections.
    foreach ($coursecache as $courseid => $sectionum) {
        $hiddenitems = set_section_visible($courseid, $sectionum, 0);
        $trace->output(sprintf('Hid section %d for course %d containing %d items',
                $sectionum, $courseid, count($hiddenitems)));
    }
}

$trace->output('DONE!');
$trace->finished();
