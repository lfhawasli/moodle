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
 * Exports cohort used to restrict access to courses using Shibboleth.
 *
 * Command line call:
 * php local/ucla/cli/export_shib_cohort.php <cohort idnumber> <path to export to>
 *
 * @package    local_ucla
 * @copyright  2015 UCLA regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__.'/../../../config.php');
require_once("$CFG->libdir/clilib.php");

// Get cli parameters.
list($options, $unrecognized) = cli_get_params(
    array('help' => false), array('h' => 'help'));

// Display help if parameter list doesn't match what we expect.
if ($options['help'] || empty($unrecognized) || count($unrecognized) != 2) {
    $help = "Exports a file that can be used by an Apache server to restrict
        the course folder to only certain UCLA logonIDs that are in a
        given cohort.

Example:
\$php local/ucla/cli/export_shib_cohort.php <cohort idnumber> <path to export to>
\$php local/ucla/cli/export_shib_cohort.php stageaccess /etc/httpd/conf.d/stageaccess.conf
";
    die($help);
}

// Find out the given cohort name and path.
$cohortidnumber = clean_param($unrecognized[0], PARAM_ALPHANUMEXT);
$exportto = clean_param($unrecognized[1], PARAM_PATH);

// Get cohort members UCLA logonIDs.
$sql = "SELECT u.username
          FROM {cohort} c
          JOIN {cohort_members} cm ON (cm.cohortid=c.id)
          JOIN {user} u ON (cm.userid=u.id)
         WHERE c.idnumber=? AND
               u.auth='shibboleth'";
$cohortmembers = $DB->get_records_sql($sql, array($cohortidnumber));
if (empty($cohortmembers)) {
    cli_error("No cohort members found for $cohortidnumber");
}

// Check if export path is something that we can write to.
if (!is_writable(dirname($exportto))) {
    cli_error("Cannot write to $exportto");
}
if (is_dir($exportto)) {
    cli_error("$exportto is a directory and not a file path");
}

/* Write apache config file in following format:
 * <Location /phpMyAdmin>
 *      AuthType shibboleth
 *      ShibRequestSetting requireSession 1
 *      ShibRequireSession On
 *      ShibUseHeaders On
 *      require SHIBUCLALOGONID nshemer sdicks
 * </Location>
 */

$uclalogonids = '';
foreach ($cohortmembers as $cohortmember) {
    // Strip @ucla.edu from UCLA logonid.
    $uclalogonids .= str_replace('@ucla.edu', '', $cohortmember->username) . ' ';
}

$exportstring = "<Location /course>
    AuthType shibboleth
    ShibRequestSetting requireSession 1
    ShibRequireSession On
    ShibUseHeaders On
    require SHIBUCLALOGONID $uclalogonids
</Location>";

// Write export file.
if (file_put_contents($exportto, $exportstring) === false) {
    cli_error("Cannot write apache config to $exportto");
}

echo "Successfully exported members of $cohortidnumber to $exportto\n";
