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
 * Upgrades database for Library Music Reserves.
 *
 * @package    block_ucla_media
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_ucla_media\task;
require_once($CFG->dirroot . '/' . $CFG->admin . '/tool/ucladatasourcesync/lib.php');
/**
 * Class file.
 *
 * @package    block_ucla_media
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class update_librarymusicreserves extends \core\task\scheduled_task {

    /**
     * Update library music reserves database.
     *
     * @return boolean
     */
    public function execute() {
        global $DB;

        $url = get_config('block_ucla_media', 'library_source_url');
        $json = file_get_contents($url);
        $json = json_decode($json);
        if (empty($json)) {
            // Cannot complete update.
            throw new \moodle_exception('Cannot update Digital audio reserves');
        }
        $courses = $json->courses;
        if (empty($courses)) {
            return false;
        }

        // Index existing videos by unique key.
        $existingmedia = $this->get_existingmedia();

        // Wrap everything in a transaction, because we don't want to lose data
        // if there is a data issue.
        $numdeleted = $numinserted = $numupdated = 0;
        $transaction = $DB->start_delegated_transaction();
        try {
            foreach ($courses as $course) {
                $courseid = match_course($course->term, $course->srs);
                foreach ($course->works as $work) {
                    $entry = new \stdClass();
                    $entry->term = $course->term;
                    $entry->srs = $course->srs;
                    if ($courseid) { // Checking if course exists in our system.
                        $entry->courseid = $courseid;
                    } else {
                        $entry->courseid = null;
                    }
                    $entry->albumtitle = $work->title;
                    if ($work->isVideo) {
                        $entry->isvideo = 1;
                    } else {
                        $entry->isvideo = 0;
                    }
                    $entry->composer = $work->composer;
                    $entry->performers = $work->performers;
                    $entry->metadata = "Note1: " . $work->noteOne .
                            "<br />Note2: " . $work->noteTwo;
                    $entry->workid = $work->workID;
                    
                    // Does this entry have an embedURL, if so, then ignore items.
                    if (!empty($work->embedURL)) {
                        $entry->embedurl = $work->embedURL;
                        $entry->title = $work->title;

                        // Unique key to find duplicate entries.
                        $key = $entry->term . '-' . $entry->srs . '-' . $entry->workid;

                        if (isset($existingmedia[$key])) {
                            // Entry exists, so update.
                            $entry->id = $existingmedia[$key];
                            $DB->update_record('ucla_library_music_reserves', $entry);
                            unset($existingmedia[$key]);
                            ++$numupdated;
                        } else {
                            // Does not exist, so add.
                            try {
                                $DB->insert_record('ucla_library_music_reserves', $entry);
                                ++$numinserted;
                            } catch (\Exception $ex) {
                                // It is a duplicate entry, so ignore it.
                                mtrace("\n" . get_string('founddupentry',
                                        'tool_ucladatasourcesync', $key));
                            }
                        }       

                        // Skip over items.
                        continue;
                    }
                    
                    foreach ($work->items as $item) {
                        if (empty($item->trackTitle) || $item->trackTitle == 'N/A') {
                            // If track doesn't have title, just use work title.
                            $entry->title = $work->title;
                        } else {
                            $entry->title = $item->trackTitle;
                        }
                        $entry->httpurl = $item->httpURL;
                        $entry->rtmpurl = $item->rtmpURL;
                        $entry->volume = !empty($item->volume) ? $item->volume : 0;
                        $entry->disc = !empty($item->disc) ? $item->disc : 0;
                        $entry->side = !empty($item->side) ? $item->side : 0;
                        $entry->tracknumber = !empty($item->trackNumber) ? $item->trackNumber : 0;

                        // Unique key to find duplicate entries.
                        $key = $entry->term . '-' . $entry->srs . '-' . $entry->workid
                                 . '-' . $entry->volume . '-' . $entry->disc . '-' .
                                $entry->side . '-' . $entry->tracknumber;

                        if (isset($existingmedia[$key])) {
                            // Entry exists, so update.
                            $entry->id = $existingmedia[$key];
                            $DB->update_record('ucla_library_music_reserves', $entry);
                            unset($existingmedia[$key]);
                            ++$numupdated;
                        } else {
                            // Does not exist, so add.
                            try {
                                $DB->insert_record('ucla_library_music_reserves', $entry);
                                ++$numinserted;
                            } catch (\Exception $ex) {
                                // It is a duplicate entry, so ignore it.
                                mtrace("\n" . get_string('founddupentry',
                                        'tool_ucladatasourcesync', $key));
                            }
                        }
                    }
                }
            }

            // Finished processing, so delete entries that no longer exists.
            foreach ($existingmedia as $deleteid) {
                // Found record that was not updated, so delete.
                $DB->delete_records('ucla_library_music_reserves',
                        array('id' => $deleteid));
                ++$numdeleted;
            }


            if ($numinserted == 0 && $numupdated == 0) {
                throw new \moodle_exception('lrnoentries', 'tool_ucladatasourcesync');
            }

            // Success, so commit changes.
            $transaction->allow_commit();
            $counts = new \stdClass();
            $counts->deleted    = $numdeleted;
            $counts->inserted   = $numinserted;
            $counts->updated    = $numupdated;
            mtrace(get_string('successnotice', 'tool_ucladatasourcesync', $counts));

        } catch (Exception $e) {
            $transaction->rollback($e);
        }

        
        return true;
    }

    /**
     * Returns existing media in following format:
     *  [term-srs-workid-volume-disc-side-tracknumber] = record id
     *
     * @return array
     */
    public function get_existingmedia() {
        global $DB;
        $retval = array();

        $records = $DB->get_records('ucla_library_music_reserves', array(),
                null, 'id,term,srs,workid,volume,disc,side,tracknumber,embedurl');
        if (empty($records)) {
            return $retval;
        }

        foreach ($records as $record) {
            if (!empty($record->embedurl)) {
                $key = $record->term . '-' . $record->srs . '-' . $record->workid;
            } else {
                $key = $record->term . '-' . $record->srs . '-' . $record->workid
                         . '-' . $record->volume . '-' . $record->disc . '-' .
                        $record->side . '-' . $record->tracknumber;
            }
            $retval[$key] = $record->id;
        }
        return $retval;
    }

    /**
     * Returns task name.
     *
     * @return string
     */
    public function get_name() {
        return get_string('taskupdatelibmusic', 'block_ucla_media');
    }

}
