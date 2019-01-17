<?php
// This file is part of the local UCLA plugin for Moodle - http://moodle.org/
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
 * CLI script to generate CSV file with UID field appended.
 *
 * @package    local_ucla
 * @copyright  2018 UCLA regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__.'/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->libdir . '/csvlib.class.php');

// Now get cli options.
list($options, $unrecognized) = cli_get_params(array('help' => false),
        array('h' => 'help'));

// First parameter is input file and second is output file.
$inputfile = $outputfile = null;
if (!empty($unrecognized[0])) {
    $inputfile = validate_param($unrecognized[0], PARAM_PATH);
    // Make sure file exists.
    if (!file_exists($inputfile)) {
        cli_error('Input file does not exist: ' . $inputfile);
    }
}
if (!empty($unrecognized[1])) {
    $outputfile = validate_param($unrecognized[1], PARAM_PATH);
}

if ($options['help'] || empty($inputfile) || empty($outputfile)) {
    $help = "CLI script to generate CSV file with UID field appended.

It finds a user by their email field in the imported CSV file.

Options:
-h, --help            Print out this help

Example:
\$sudo -u www-data /usr/bin/php local/ucla/cli/get_uids.php <CSV to import> <CSV to export>
";
    cli_error($help);
    die;
}

$trace = new text_progress_trace();

// Import CSV file.
$importid = csv_import_reader::get_new_iid('getuids');
$cir = new csv_import_reader($importid, 'getuids');
$inputfilecontent = file_get_contents($inputfile);
if (!$cir->load_csv_content($inputfilecontent, 'utf-8', 'comma')) {
    $cir->cleanup();
    cli_error('Cannot parse input file:' . $inputfile);
}

// Verify that email is a column in the file.
$columns = $cir->get_columns();
$emailcolumindex = false;
foreach ($columns as $index => $column) {
    if (core_text::strtolower($column) == 'email') {
        $emailcolumindex = $index;
        break;
    }
}
if ($emailcolumindex === false) {
    cli_error('Cannot find email column in input file:' . $inputfile);
}

// Open export file so that we can write the new CSV with UID added.
$writer = new csv_export_writer();
$writer->set_filename($outputfile);
$columns[] = 'UID';
$writer->add_data($columns);

// Read file and output row with UID added.
$cir->init();
$rownum = 1;
while ($row = $cir->next()) {
    $rownum++;
    // Get email and find UID.
    try {
        $email = validate_param($row[$emailcolumindex], PARAM_EMAIL);
    } catch (invalid_parameter_exception $ex) {
        cli_writeln(sprintf('Line %d: Invalid email %s', $rownum, $row[$emailcolumindex]));
    }

    $uid = find_uid($email, $rownum);
    if (empty($uid)) {
        cli_writeln(sprintf('Line %d: Cannot find UID for email %s', $rownum, $email));
    }

    // Write row with UID added.
    $row[] = $uid;
    $writer->add_data($row);
}

if (file_put_contents($outputfile, $writer->print_csv_data(true)) === false) {
    $cir->cleanup();
    cli_error('Cannot write to output file: ' . $outputfile);
}

$cir->cleanup();
cli_writeln(sprintf('DONE! Wrote %d lines to %s', $rownum, $outputfile));

/**
 * Given an email, try to find user idnumber to get UID.
 *
 * @param string $email
 * @param int $rownum
 * @return string
 */
function find_uid($email, $rownum) {
    global $DB;
    $uid = '';

    $where = "email='$email' AND deleted=0 AND suspended=0 AND (idnumber!='' OR idnumber!=NULL)";
    $records = $DB->get_records_select('user', $where, null, '', 'idnumber');

    // If there are multiple records returned, report it.
    if (count($records) > 1) {
        $uidlist = array();
        foreach ($records as $record) {
            $uidlist[] = $record->idnumber;
        }
        cli_writeln(sprintf('Line %d: Found multiple UIDs (%s) for email %s; using first one',
                $rownum, implode(',', $uidlist), $email));
    }

    if (!empty($records)) {
        $uid = array_pop($records)->idnumber;
    }

    return $uid;
}