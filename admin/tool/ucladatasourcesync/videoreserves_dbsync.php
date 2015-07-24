<?php
// This file is part of the UCLA data source sync tool for Moodle - http://moodle.org/
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
 * Command line script to parse, verify, and update video reserves entries.
 *
 * $CFG->videoreservesurl is defined in the plugin configuration at
 * Site administration->Plugins->Blocks->Video reserves.
 *
 * See CCLE-5185 for details.
 *
 * @package    tool_ucladatasourcesync
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/' . $CFG->admin . '/tool/ucladatasourcesync/lib.php');
require_once($CFG->libdir. '/clilib.php');

// Get video reserves. Dies if there are any problems.
$videoreserves = get_videoreserves();

// Begin database update.
update_videoreserves_db($videoreserves);

/**
 * Returns array detailing the different expected fields.
 *
 * @return array
 */
function define_data_source() {
    $retval = array();

    $retval[] = array('name' => 'term',
                      'type' => 'term',
                      'min_size' => '3',
                      'max_size' => '3');
    $retval[] = array('name' => 'srs',
                      'type' => 'srs',
                      'min_size' => '7',
                      'max_size' => '9');
    $retval[] = array('name' => 'start_date',
                      'type' => 'date_slashed',
                      'min_size' => '6',
                      'max_size' => '10');
    $retval[] = array('name' => 'stop_date',
                      'type' => 'date_slashed',
                      'min_size' => '6',
                      'max_size' => '10');
    $retval[] = array('name' => 'class',
                      'type' => 'string',
                      'min_size' => '0',
                      'max_size' => '150');
    $retval[] = array('name' => 'instructor',
                      'type' => 'string',
                      'min_size' => '0',
                      'max_size' => '30');
    $retval[] = array('name' => 'video_title',
                      'type' => 'string',
                      'min_size' => '0',
                      'max_size' => '200');
    $retval[] = array('name' => 'video_url',
                      'type' => 'url',
                      'min_size' => '0',
                      'max_size' => '255');
    $retval[] = array('name' => 'filename',
                      'type' => 'string',
                      'min_size' => '0',
                      'max_size' => '30');
    $retval[] = array('name' => 'subtitle',
                      'type' => 'string',
                      'min_size' => '0',
                      'max_size' => '30');
    $retval[] = array('name' => 'height',
                      'type' => 'int',
                      'min_size' => '0',
                      'max_size' => '4');
    $retval[] = array('name' => 'width',
                      'type' => 'int',
                      'min_size' => '0',
                      'max_size' => '4');
    return $retval;
}
/**
 * Main function for updating the video reserves db.
 *
 * @param array $incomingdata
 */
function update_videoreserves_db($incomingdata) {
    global $DB;

    echo get_string('vrstartnoti', 'tool_ucladatasourcesync');

    $fields = define_data_source();

    // Delete everything and refresh table.
    $transaction = $DB->start_delegated_transaction();
    $DB->delete_records('ucla_video_reserves');

    $insertcount = 0;
    $row = 2;   // Entries start on line 2 in file.
    foreach ($incomingdata as $rowdata) {
        ++$row;

        // Check if the row has the correct number of columns.
        if (count($rowdata) != count($fields)) {
            log_ucla_data('video reserves', 'read', 'Extracting data from data source',
                    get_string('errvrinvalidrowlen', 'tool_ucladatasourcesync', $row));
            echo get_string('errvrinvalidrowlen', 'tool_ucladatasourcesync', $row) . "\n";
            continue;
        }

        // Validate/clean data.
        $invalidfields = array();
        foreach ($fields as $fieldnum => $fielddef) {
            $data = validate_field($fielddef['type'], $rowdata[$fieldnum],
                    $fielddef['min_size'], $fielddef['max_size']);
            if ($data === false) {
                $invalidfields[] = $fielddef['name'];
            }
        }

        // Give warning about errors.
        if (!empty($invalidfields)) {
            $error = new stdClass();
            $error->fields = implode(', ', $invalidfields);
            $error->line_num = $row;
            $error->data = print_r($rowdata, true);
            echo(get_string('warninvalidfields', 'tool_ucladatasourcesync',
                    $error) . "\n");
        }

        fix_data_format($rowdata);

        $id = null;
        try {
            $id = $DB->insert_record('ucla_video_reserves', $rowdata);
        } catch (Exception $e) {
            // Handle CCLE-3378 - Video reserves crashing on video title "ClŽo de 5 ˆ 7".
            if (strpos($e->error, 'video_title')) {
                // Try to recover by converting titles to ASCII.
                $rowdata['video_title'] = textlib::specialtoascii($rowdata['video_title']);
                try {
                    $id = $DB->insert_record('ucla_video_reserves', $rowdata);
                } catch (Exception $e) {
                    // Cannot fix invalid title.
                    $error = new stdClass();
                    $error->fields = implode(', ', $invalidfields);
                    $error->line_num = $row;
                    $error->data = print_r($rowdata, true);
                    log_ucla_data('video reserves', 'write', 'Invalid title',
                            get_string('errinvalidtitle', 'tool_ucladatasourcesync', $error));
                    echo get_string('errinvalidtitle', 'tool_ucladatasourcesync', $error);
                }
            } else {
                // Error, log this and print out error.
                log_ucla_data('video reserves', 'write', 'Inserting video reserves data',
                        get_string('errcannotinsert', 'tool_ucladatasourcesync', $e->error));
                echo get_string('errcannotinsert', 'tool_ucladatasourcesync', $e->error);
            }
        }

        if (!empty($id)) {
            $insertcount++;
            echo '.';   // Give process bar.
        }
    }

    $transaction->allow_commit();
    log_ucla_data('video reserves', 'update', get_string('vrsuccessnoti', 'tool_ucladatasourcesync'));
    echo "\n" . $insertcount . " " . get_string('vrsuccessnoti', 'tool_ucladatasourcesync') . "\n";

    // Save current timestamp so we don't do unncessary updates.
    set_config('lastruntime', time(), 'block_ucla_video_reserves');
}

/**
 * Function to format dates so that they look like standard MySQL dates.
 *
 * @param string $date   Date in format of 6/20/12.
 * @return int      UNIX timestamp.
 */
function fix_date_format($date) {
    $tempdate = explode('/', $date);
    $date = mktime(0, 0, 0, $tempdate[0], $tempdate[1], $tempdate[2]);
    return $date;
}

/*
 * Formats the raw data stored in row, and replaces row with an object
 * containing the formatted data and a new courseid field based on the raw data.
 *
 * @param $row Array containing the raw TSV data obtained from the datasource.
 */

function fix_data_format(&$row) {

    // If the term is less than 3 characters, it's probably from
    // missing leading zeroes. We solve this by prepending zeroes.
    $row[0] = str_pad($row[0], 3, STR_PAD_LEFT);

    // Fix all formatting issues with incoming data add leading zeroes to the
    // srs is its under 9 chars.
    $row[1] = str_pad($row[1], 9, STR_PAD_LEFT);

    // Format the start and end dates yyyy-mm-dd.
    $row[2] = fix_date_format($row[2]);
    $row[3] = fix_date_format($row[3]);

    // Remove quotes surrounding class names and movie titles.
    $row[4] = preg_replace('/^"(.*)"$/', '$1', $row[4]);
    $row[6] = preg_replace('/^"(.*)"$/', '$1', $row[6]);

    // Remove newlines from urls.
    $row[7] = trim($row[7]);

    $dataobject = array();
    $dataobject['term'] = $row[0];
    $dataobject['srs'] = $row[1];
    $dataobject['start_date'] = $row[2];
    $dataobject['stop_date'] = $row[3];
    $dataobject['class'] = $row[4];
    $dataobject['instructor'] = $row[5];
    $dataobject['video_title'] = $row[6];
    $dataobject['video_url'] = $row[7];

    // Find related course.
    $courseid = match_course($dataobject['term'], $dataobject['srs']);
    if (!empty($courseid)) {
        // Course was found on system!
        $dataobject['courseid'] = $courseid;
    }

    $row = $dataobject;
}

/**
 * Get datasource, check it is valid, and returns array to be processed.
 *
 * @return array   File entries.
 */
function get_videoreserves() {
    $entries = array();

    // See if user wants help via command-line.
    list($options, $unrecognized) = cli_get_params(
        array(
            'force' => false,
            'help' => false
        ),
        array(
            'f' => 'force',
            'h' => 'help'
        )
    );

    if ($options['help']) {
        $help = "Updates the video reserves table.

If sourcefile for video reserves hasn't been updated since last run time,
update process will be skipped unless a force update is made.

Options:
-f, --force     Force update of video reserves table
-h, --help      Print out this help";
        die($help);
    }

    // Check to see that datasource is initialized.
    $datasourceurl = get_config('block_ucla_video_reserves', 'sourceurl');
    if (empty($datasourceurl)) {
        log_ucla_data('video reserves', 'read', 'Getting datasource',
                get_string('errvrsourceurl', 'tool_ucladatasourcesync'));
        cli_error(get_string('errvrsourceurl', 'tool_ucladatasourcesync'));
    }

    // Allows \r characters to be read as \n's. The config file has \r's instead
    // of \n's.
    ini_set('auto_detect_line_endings', true);

    // Open file and make sure it is valid.
    $datasource = fopen($datasourceurl, 'r');
    if ($datasource === false) {
        cli_error(get_string('errvrfileopen', 'tool_ucladatasourcesync'));
    }

    // Check file timestamp so we only refresh the table if changes were made.
    // First line in file is formated as: "Updated:        6/11/15 4:01pm".
    $lastupdatedline = fgets($datasource);
    $lastupdated = str_replace('Updated:', '', $lastupdatedline);
    $lasttimestamp = strtotime($lastupdated);

    // Do we need to run the update?
    $lastruntime = get_config('block_ucla_video_reserves', 'lastruntime');
    if (empty($lastruntime) || $options['force']) {
        // Didn't run last time or being forced.
        echo sprintf("Running update\n");
    } else if ($lasttimestamp > $lastruntime) {
        // File is updated.
        echo sprintf('Last run time: %s and last updated: %s, ' .
                "running update because of new changes\n",
                userdate($lastruntime, get_string('strftimedatetime')),
                userdate($lasttimestamp, get_string('strftimedatetime')));
    } else {
        cli_error(sprintf('Last run time: %s and last updated: %s, ' .
                "skipping update because no new changes\n",
                userdate($lastruntime, get_string('strftimedatetime')),
                userdate($lasttimestamp, get_string('strftimedatetime'))));
    }

    // Process file line by line.
    while (!feof($datasource)) {
        // Use tabs as a delimiter instead of commas.
        $entries[] = fgetcsv($datasource, 0, "\t", "\n");
    }

    // Remove first row of file, since it is a header line.
    array_shift($entries);

    if (empty($entries)) {
        cli_error('No entries found');
    }

    return $entries;
}

