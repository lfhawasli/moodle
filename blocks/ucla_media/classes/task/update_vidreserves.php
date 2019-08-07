<?php
// This file is part of the UCLA Media block for Moodle - http://moodle.org/
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
 * Upgrades database for Video reserve.
 *
 * @package    block_ucla_media
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_ucla_media\task;
require_once($CFG->dirroot . '/' . $CFG->admin . '/tool/ucladatasourcesync/lib.php');
require_once($CFG->dirroot . '/blocks/ucla_media/locallib.php');

/**
 * Class file.
 *
 * @package    block_ucla_media
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class update_vidreserves extends \core\task\scheduled_task {

    /**
     * Returns array detailing the different expected fields.
     *
     * @return array
     */
    public function define_data_source() {
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
                          'max_size' => '255');
        $retval[] = array('name' => 'subtitle',
                          'type' => 'string',
                          'min_size' => '0',
                          'max_size' => '255');
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
    * Updates video reserves database.
    */
    public function execute() {
        global $DB;
        
        // Get video reserves. Returns false if there are any problems.
        $incomingdata = $this->get_videoreserves();
        if ($incomingdata === false) {
            echo get_string('vrnoentries', 'tool_ucladatasourcesync');
            return;
        }

        // Begin database update.
        echo get_string('vrstartnoti', 'tool_ucladatasourcesync');

        $fields = $this->define_data_source();

        // Index existing videos by term, srs, and video_title (same as unique key).
        $existingvideos = array();

        $insertcount = $updatecount = 0;
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
                $error = new \stdClass();
                $error->fields = implode(', ', $invalidfields);
                $error->line_num = $row;
                $error->data = implode('|', $rowdata);
                echo(get_string('warninvalidfields', 'tool_ucladatasourcesync',
                        $error) . "\n");
            }

            $this->fix_data_format($rowdata);

            try {
                // Get all videos for course.
                if (!isset($existingvideos[$rowdata['term']][$rowdata['srs']])) {
                    // Get video titles as keys, so that we can check if video exists.
                    $results = $DB->get_records_menu('ucla_video_reserves',
                            array('term' => $rowdata['term'], 'srs' => $rowdata['srs']),
                            '', 'UPPER(video_title), id');
                    $existingvideos[$rowdata['term']][$rowdata['srs']] = $results;
                }

                // Check if video currently exists.
                $uppercasetitle = \core_text::strtoupper($rowdata['video_title']);
                if (isset($existingvideos[$rowdata['term']][$rowdata['srs']][$uppercasetitle])) {
                    $id = $existingvideos[$rowdata['term']][$rowdata['srs']][$uppercasetitle];

                    // Update record.
                    $rowdata['id'] = $id;
                    $rowdata['timemodified'] = time();
                    $DB->update_record('ucla_video_reserves', $rowdata);

                    // Unset it. We will be deleting any remaining videos later.
                    unset($existingvideos[$rowdata['term']][$rowdata['srs']][$uppercasetitle]);

                    $updatecount++;
                    echo '.';   // Give process bar.
                } else {
                    // Video doesn't exist, so add it.
                    $rowdata['timecreated'] = time();
                    $rowdata['timemodified'] = time();
                    $DB->insert_record('ucla_video_reserves', $rowdata);
                    $insertcount++;
                    echo '+';   // Give process bar.
                }

            } catch (Exception $e) {
                // Error, log this and print out error.
                log_ucla_data('video reserves', 'write', 'Inserting video reserves data',
                        get_string('errcannotinsert', 'tool_ucladatasourcesync', $e->error));
                echo get_string('errcannotinsert', 'tool_ucladatasourcesync', $e->error);
            }
        }

        // Now delete any left over existing records.
        $deletedcount = 0;
        foreach ($existingvideos as $term => $termvideos) {
            foreach ($termvideos as $srs => $srsvideos) {
                foreach ($srsvideos as $videotitle => $id) {
                    delete_timestamps(array('jwtimestamp'), array($videotitle));

                    $DB->delete_records('ucla_video_reserves', array('id' => $id));
                    $deletedcount++;
                    echo '-';   // Give process bar.
                }
            }
        }

        $counts = new \stdClass();
        $counts->deleted    = $deletedcount;
        $counts->inserted   = $insertcount;
        $counts->updated    = $updatecount;
        $logstring = get_string('successnotice', 'tool_ucladatasourcesync', $counts);
        log_ucla_data('video reserves', 'update', $logstring);
        echo "\n" . $logstring . "\n";
    }

    /**
     * Formats the raw data stored in row, and replaces row with an object
     * containing the formatted data and a new courseid field based on the raw data.
     *
     * @param array $row    Contains the raw TSV data obtained from the datasource.
     * @return array
     */
    function fix_data_format(&$row) {

        // If the term is less than 3 characters, it's probably from
        // missing leading zeroes. We solve this by prepending zeroes.
        $row[0] = str_pad($row[0], 3, 0, STR_PAD_LEFT);

        // Fix all formatting issues with incoming data add leading zeroes to 
        // the srs is its under 9 chars.
        $row[1] = str_pad($row[1], 9, 0, STR_PAD_LEFT);

        // Format the start and end dates yyyy-mm-dd.
        $row[2] = $this->fix_date_format($row[2]);
        $row[3] = $this->fix_date_format($row[3]);

        // Remove quotes surrounding class names and movie titles.
        $row[4] = preg_replace('/^"(.*)"$/', '$1', $row[4]);
        $row[6] = preg_replace('/^"(.*)"$/', '$1', $row[6]);

        // Remove newlines from urls.
        $row[7] = trim($row[7]);

        $dataobject = array();
        $dataobject['term']         = $row[0];
        $dataobject['srs']          = $row[1];
        $dataobject['start_date']   = $row[2];
        $dataobject['stop_date']    = $row[3];
        $dataobject['class']        = $row[4];
        $dataobject['instructor']   = $row[5];
        // Handle CCLE-3378 - Video reserves crashing on video title "ClŽo de 5 ˆ 7".
        $dataobject['video_title']  = \core_text::specialtoascii($row[6]);
        $dataobject['video_url']    = $row[7];
        $dataobject['filename']     = $row[8];
        $dataobject['subtitle']     = $row[9];
        $dataobject['height']       = $row[10];
        $dataobject['width']        = $row[11];

        // Find related course.
        $courseid = match_course($dataobject['term'], $dataobject['srs']);
        if (!empty($courseid)) {
            // Course was found on system!
            $dataobject['courseid'] = $courseid;
        }

        $row = $dataobject;
    }

    /**
     * Function to format dates so that they look like standard MySQL dates.
     *
     * @param string $date   Date in format of 6/20/12.
     * @return int      UNIX timestamp.
     */
    private function fix_date_format($date) {
        $tempdate = explode('/', $date);
        $date = mktime(0, 0, 0, $tempdate[0], $tempdate[1], $tempdate[2]);
        return $date;
    }

   /**
    * Returns task name.
    *
    * @return string
    */
    public function get_name() {
        return get_string('taskupdatevidreserves', 'block_ucla_media');
    }

    /**
     * Get datasource, check it is valid, and returns array to be processed.
     *
     * @return array   File entries. False on error.
     */
    function get_videoreserves() {
        $entries = array();

        // Check to see that datasource is initialized.
        $datasourceurl = get_config('block_ucla_video_reserves', 'sourceurl');
        if (empty($datasourceurl)) {
            log_ucla_data('video reserves', 'read', 'Getting datasource',
                    get_string('errvrsourceurl', 'tool_ucladatasourcesync'));
            return false;
        }

        // Allows \r characters to be read as \n's. The config file has \r's instead
        // of \n's.
        ini_set('auto_detect_line_endings', true);

        // Open file and make sure it is valid.
        $datasource = fopen($datasourceurl, 'r');
        if ($datasource === false) {
            return false;
        }

        // First line in file is formated as: "Updated:        6/11/15 4:01pm".
        $lastupdatedline = fgets($datasource);  // Ignore it.

        // Process file line by line.
        while (!feof($datasource)) {
            // Use tabs as a delimiter instead of commas.
            $entry = fgetcsv($datasource, 0, "\t", "\n");

            // A blank line in a CSV file will be returned as an array comprising a
            // single null field, and will not be treated as an error.
            if (!empty($entry[0])) {
                $entries[] = $entry;
            }
        }

        // Remove first row of file, since it is a header line.
        array_shift($entries);

        if (empty($entries)) {
            return false;
        }

        return $entries;
    }

}