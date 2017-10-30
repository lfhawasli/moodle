<?php
// This file is part of the UCLA local plugin for Moodle - http://moodle.org/
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
 * Takes in a list of faculty and their MFA status outputs those faculty who are
 * teaching for the given term.
 *
 * @copyright  2017 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define('CLI_SCRIPT', true);

require(__DIR__.'/../../../config.php');
require_once("$CFG->libdir/clilib.php");
require_once($CFG->libdir . '/csvlib.class.php');
require_once($CFG->dirroot . '/local/ucla/lib.php');

// Get cli parameters.
list($options, $unrecognized) = cli_get_params(
    array('help' => false), array('h' => 'help'));

// Display help if parameter list doesn't match what we expect.
if ($options['help'] || empty($unrecognized) || count($unrecognized) != 2) {
    $help = "Outputs faculty who have not enrolled into MFA in tab deliminated
        format.

Example:
\$php local/ucla/script/mfa_faculty.php <term> <path to MFA CSV>
";
    die($help);
}

ucla_require_registrar();

$term = clean_param($unrecognized[0], PARAM_ALPHANUMEXT);
$filename = clean_param($unrecognized[1], PARAM_PATH);

$content = file_get_contents($filename);
$importid = csv_import_reader::get_new_iid('uploadcourse');
$cir = new csv_import_reader($importid, 'uploadcourse');
$cir->load_csv_content($content, 'utf-8', ',');
$cir->init();

// Loop over the CSV lines.
while ($facultymember = $cir->next()) {
    // We only want faculty who are not enrolled in MFA.
    if ($facultymember[8] == 'Yes') {
        continue;
    }

    // Get classes.
    $courses = registrar_query::run_registrar_query(
            'ucla_get_user_classes', array('uid' => $facultymember[0])
        );

    foreach ($courses as $course) {
        if (strpos($course['termsrs'], $term) !== false) {
            if ($course['role'] != 'student') {
                echo $facultymember[1] . "\t" . $facultymember[3] . "\n";
                break;
            }
        }
    }    
}