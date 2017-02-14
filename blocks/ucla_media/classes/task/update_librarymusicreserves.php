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

        // Drop table if we are processing new entries.
        $DB->delete_records('ucla_library_music_reserves');
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
                $entry->metadata = "Note1:" + $work->noteOne + " Note2:" + $work->noteTwo;                
                foreach ($work->items as $item) {                    
                    if (!empty($item->trackTitle) || $item->trackTitle == 'N/A') {
                        // If track doesn't have title, just use work title.
                        $entry->title = $work->title;

                    } else {
                        $entry->title = $item->trackTitle;
                    }                                   
                    $entry->httpurl = $item->httpURL;
                    $entry->rtmpurl =  $item->rtmpURL;
                    $DB->insert_record('ucla_library_music_reserves', $entry);
                }
            }
        }
        return true;
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
