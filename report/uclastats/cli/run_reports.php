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

/**
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
require_once("$CFG->dirroot/report/uclastats/locallib.php");

// Now get cli options.
list($options, $unrecognized) = cli_get_params(array('help' => false),
        array('h' => 'help'));

if ($options['help']) {
    $help = "Command line script to bulk run and export reports for the end of quarter activity report.

Need to pass in term and, optionally, a comma delinated list of reports.

Options:
-h, --help            Print out this help

Example:
\$sudo -u www-data /usr/bin/php report/uclastats/cli/run_reports.php [TERM] [REPORTS|OPTIONAL]
";

    echo $help;
    die;
}

// Get the term to process.
$term = null;
$reportstorun = array();
if ($unrecognized) {
    foreach ($unrecognized as $index => $param) {
        // Maybe someone is passing us a term to run.
        if (ucla_validator('term', $param)) {
            $term = $param;
        } else {
            // Maybe it is a list of reports to run.
            $param = clean_param($param, PARAM_NOTAGS);
            $reportstorun = explode(',', $param);
        }
    }
}

if (empty($term)) {
    cli_error('No term passed');
}

// This will take a while to run.
set_time_limit(0);

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
if (is_dir($reportoutputcachedir)) {
    rrmdir($reportoutputcachedir);
}
mkdir($reportoutputcachedir, 0777);

// List of reports to run.
$reports = array_keys(get_all_reports());

// Check if we want to only run certain reports.
if (!empty($reportstorun)) {
    $reports = $reportstorun;
}

// We are unable to output progress of the reports in realtime, because
// otherwise the report generation will complain about "headers already been
// sent".
$output = array();

// Run each report and save the results in $reportoutputcachedir.
$admin = get_admin();   // User running report.
$reportfiles = array();
foreach ($reports as $reportname) {
    // Include report.
    $reportpath = $CFG->dirroot . '/report/uclastats/reports/'.$reportname.'.php';
    if (is_file($reportpath)) {
        require_once($CFG->dirroot . '/report/uclastats/reports/'.$reportname.'.php');
    } else {
        $output[] = "Could not find $reportname report";
        continue;
    }

    // Create report object.
    $report = new $reportname($admin->id);

    $reportparams = $report->get_parameters();
    // Run any report that:
    // 1) Does not have any parameters.
    // 2) Does not include any parameters other than term and optionaldatepicker.
    if (!empty($reportparams)) {
        $fields = array_flip($reportparams);
        unset($fields['term']);
        unset($fields['optionaldatepicker']);
        if (!empty($fields)) {
            $output[] = 'Skipping report ' . $reportname;
            continue;
        }
    }

    // Run report, but see if need to provide term as a parameter.
    $params = array();
    if (in_array('term', $reportparams)) {
        $params['term'] = $term;
    }
    $reportid = $report->run($params);

    // Now export result into Excel file in cache directory.
    ob_start();
    try {
        $report->export_result_xls($reportid);
        $output[] = "Generated $reportname report";
    } catch (Exception $e) {
        ob_end_clean();

        // Skip a report that has a problem.
        $output[] = "Problem generating $reportname report";
        $output[] = $e->getMessage();
        $output[] = $e->getTraceAsString();
        continue;
    }
    $content = ob_get_contents();
    ob_end_clean();
    $reportpath = $reportoutputcachedir . '/'.$reportname.'.xls';
    file_put_contents($reportpath, $content);
    $reportfiles[$reportname.'.xls'] = $reportpath;
}

// Now get cumulative file system data, if running all reports.
if (empty($reportstorun)) {
    $cumulativefilesize = shell_exec("du -s --block-size=1 $CFG->dataroot/filedir/");
    $reportpath = $reportoutputcachedir . '/cumulative_file_size.txt';
    file_put_contents($reportpath, display_size((int) $cumulativefilesize));
    $reportfiles['cumulative_file_size.txt'] = $reportpath;
    $output[] = 'Generated cumulative_file_size report';
}

// Zip all generated reports and save it in $reportoutputfile.
$zippacker = get_file_packer('application/zip');
$result = $zippacker->archive_to_pathname($reportfiles, $reportoutputfile);

// Report what happened.
foreach ($output as $line) {
    $trace->output($line);
}

if ($result) {
    $trace->output("DONE! Report available at:\n" . $reportoutputfile);    
} else {
    $trace->output("ERROR! Cannot generate zip file.");
}

// Email a report to the notify list if running all reports.
$notifylist = get_config('report_uclastats', 'notifylist');
if (!empty($notifylist) && empty($reportstorun)) {
    $trace->output('Emailing out notices.');

    $from = get_admin();
    $subject = 'Ran UCLA stats report for ' . $term;
    $output[] = '';
    $output[] = 'Reports attached as zip file and also available in UCLA stats console.';
    $messagetext = implode("\n", $output);

    // Need to copy zip file to $CFG->tempdir.
    $attachmentfile = basename($reportoutputfile);
    $attachmentpath = $CFG->tempdir . DIRECTORY_SEPARATOR . $attachmentfile;
    copy($reportoutputfile, $attachmentpath);

    $notifylistarray = explode(',', $notifylist);
    foreach ($notifylistarray as $email) {
        // There might be multiple users with the same email, but we just pick
        // the first one.
        $user = $DB->get_record('user', array('email' => $email), '*', IGNORE_MULTIPLE);
        if (empty($user)) {
            // Create our own user using guestid as base.
            $user = $DB->get_record('user', array('username' => 'guest',
                'mnethostid' => $CFG->mnet_localhost_id));
            $user->email = $email;
        }
        email_to_user($user, $from, $subject, $messagetext, '', $attachmentpath,
                $attachmentfile);
    }    
}

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
