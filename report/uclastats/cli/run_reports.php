<?php
// This file is part of the UCLA stats report for Moodle - http://moodle.org/
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

/*
 * Command line script to bulk run and export reports for the end of quarter
 * activity report.
 *
 * @package    report_uclastats
 * @copyright  2014 UCLA regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once("$CFG->libdir/clilib.php");
require_once("$CFG->dirroot/local/ucla/lib.php");

// Now get cli options.
list($options, $unrecognized) = cli_get_params(array('help' => false),
        array('h' => 'help'));

if ($options['help']) {
    $help = "Command line script to bulk run and export reports for the end of quarter activity report.

Options:
-h, --help            Print out this help

Example:
\$sudo -u www-data /usr/bin/php report/uclastats/cli/run_reports.php [TERM]
";

    echo $help;
    die;
}

// Get the term to process.
$term = null;
if ($unrecognized) {
    foreach ($unrecognized as $index => $param) {
        // Maybe someone is passing us a term to run.
        if (ucla_validator('term', $param)) {
            $term = $param;
            unset($unrecognized[$index]);
        }
    }
    if (!empty($unrecognized)) {
        $unrecognized = implode("\n  ", $unrecognized);
        cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
    }
}

if (empty($term)) {
    cli_error('No term passed');
}

// Object to output process of report generation.
$trace = new text_progress_trace();

// Ensure location of where to generate report is ready and clear any unfinished
// reports.
$reportoutputdir = $CFG->dataroot . '/uclastats';
$reportoutputcachedir = $reportoutputdir . '/cache';
$reportoutputfile = $reportoutputdir . '/' . $term . '_uclastats.zip';

// Make sure that both report and cache directories exist and cache directory
// is purged.
if (!is_dir($reportoutputdir)) {
    mkdir($reportoutputdir, 0777);
}
rrmdir($reportoutputcachedir);
mkdir($reportoutputcachedir, 0777);

// List of reports to run.
$reports = array('collab_modules_used', 'course_modules_used',
                 'collab_block_sites', 'course_block_sites',
                 'collab_type',
                 'active_instructor_focused', 'active_student_focused',
                 'collab_forum_usage', 'course_forum_usage',
                 'large_collab_sites', 'large_courses',
                 'most_active_collab_sites', 'most_active_course_sites',
                 'collab_num_sites', 'course_num_sites',
                 'repository_usage',
                 'role_count',
                 'sites_per_term',
                 'system_size',
                 'total_downloads',
                 'unique_logins_per_term',
                 'users_by_division');

// Run each report and save the results in $reportoutputcachedir.
$admin = get_admin();   // User running report.
$reportfiles = array();
foreach ($reports as $reportname) {
    // Include report.
    require_once($CFG->dirroot . '/report/uclastats/reports/'.$reportname.'.php');

    // Create report object.
    $report = new $reportname($admin->id);

    // Run report, but see if need to provide term as a parameter.
    $params = array();
    if (in_array('term', $report->get_parameters())) {
        $params['term'] = $term;
    }
    $reportid = $report->run($params);

    // Now export result into Excel file in cache directory.
    ob_start();
    $report->export_result_xls($reportid);
    $content = ob_get_contents();
    ob_end_clean();
    $reportpath = $reportoutputcachedir . '/'.$reportname.'.xls';
    file_put_contents($reportpath, $content);
    $reportfiles[$reportname.'.xls'] = $reportpath;
}

// Zip all generated reports and save it in $reportoutputfile.
$zippacker = get_file_packer('application/zip');
$result = $zippacker->archive_to_pathname($reportfiles, $reportoutputfile);

if ($result) {
    $trace->output("DONE! Report available at:\n" . $reportoutputfile);    
} else {
    $trace->output("ERROR! Cannot generate zip file.");
}

// Make sure that $reportoutputfile doesn't exist, if it does, replace it.


/**
 * Deletes given folder and all files.
 *
 * Taken from: http://stackoverflow.com/a/13490957/6001
 *
 * @param string $dir
 */
function rrmdir($dir) {
    foreach (glob($dir . '/*') as $file) {
        if (is_dir($file)) {
            rrmdir($file);
        } else {
            unlink($file);
        }
    }
    rmdir($dir);
}
