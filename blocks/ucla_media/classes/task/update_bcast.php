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
 * Upgrades database for Bruincast.
 *
 * @package    block_ucla_media
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_ucla_media\task;
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/' . $CFG->admin . '/tool/ucladatasourcesync/lib.php');
require_once($CFG->dirroot . '/enrol/externallib.php');

/**
 * Class file.
 *
 * @package    block_ucla_media
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class update_bcast extends \core\task\scheduled_task {

    /**
     * Update Bruincast database.
     *
     * @param array $terms  Optional. Used if task is called via command line.
     * @return boolean
     */
    public function execute($terms = null) {
        global $DB;

        mtrace(get_string('bcstartnoti', 'tool_ucladatasourcesync'));

        $htaccessusername = get_config('block_ucla_media', 'bruincast_http_user');
        $htaccesspassword = get_config('block_ucla_media', 'bruincast_http_pass');

        // REST Server URL.
        $requesturl = get_config('block_ucla_media', 'bruincast_login_url');

        // User data.
        $userdata = array(
            'username' => get_config('block_ucla_media', 'bruincast_user'),
            'password' => get_config('block_ucla_media', 'bruincast_pass')
        );

        // Doing the CURL for Login.
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $requesturl);
        curl_setopt($curl, CURLOPT_POST, 1); // Do a regular HTTP POST.
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($userdata)); // Set POST data.
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERPWD, "$htaccessusername:$htaccesspassword");
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);  // Essential for SSL.

        $response = curl_exec($curl);
        $xml = new \SimpleXMLElement($response);
        $cookie = $xml->session_name .'='. $xml->sessid;

        curl_close($curl);

        // URL to get relevant courses accoring to term.
        $url = get_config('block_ucla_media', 'bruincast_url');

        // Index existing videos by term, srs, date, and title (same as unique key).
        $existingmedia = array();

        // Wrap everything in a transaction, because we don't want to lose data
        // if there is a data issue.
        $numdeleted = $numinserted = $numupdated  = 0;
        if (empty($terms)) {
            $terms = get_active_terms();
        }
        // Iterating through all active terms and retrieving data for them.
        foreach ($terms as $term) {
            mtrace("Processing $term");
            // Get existing entries for term.
            $existingmedia += $this->get_existingmedia($term);

            // Converting term to API format.
            $correctedterm = self::convert_term($term);

            // Setting parameters for our request.
            $params = array(
                'display_id' => 'ccle_api_courses',
                'args[0]' => $correctedterm
            );

            // Doing a Curl to retrive bruincasted courses for a particular term.
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($curl, CURLOPT_POST, 0); // Do a regular HTTP POST.
            curl_setopt($curl, CURLOPT_MAXREDIRS, 5);
            curl_setopt($curl, CURLOPT_TIMEOUT, 5);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            curl_setopt($curl, CURLOPT_COOKIE, "$cookie"); // Use the previously saved session.
            curl_setopt($curl, CURLOPT_USERPWD, "$htaccessusername:$htaccesspassword");
            curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);  // Essential for SSL.

            $query = http_build_query($params, '', '&');
            /* Please note http_build_query is different in moodle and outside it, and it's usage is different */

            curl_setopt($curl, CURLOPT_URL, $url.'?'.$query);
            $output = curl_exec($curl);

            $xml = simplexml_load_string($output, "SimpleXMLElement", LIBXML_NOCDATA);
            $json = json_encode($xml);
            $cleanedresult = json_decode($json, true);
            curl_close($curl);

            // Only processing next part if the result was non-empty.
            if (array_key_exists('item', $cleanedresult)) {

                // The below if statement is a workaround for an XML parsing
                // problem. When only one item is retrieved in a query the array
                // $cleanedresult[item] contains information about that one
                // item, however, when there are multiple results the array is
                // an array of arrays that contain information about these results.
                if (array_key_exists('srs__', $cleanedresult['item'])) {
                    $array = $cleanedresult;
                } else {
                    $array = $cleanedresult['item'];
                }

                foreach ($array as $item) {
                    // The below if statement is due to the structure of the parsed XML, as sometimes $item contains
                    // non relevant data.
                    if (array_key_exists('srs__', $item)) {
                        $srs = $item['srs__'];
                    } else {
                        continue;
                    }
                    $params = array(
                        'display_id' => 'ccle_api_media',
                        'args[0]' => $correctedterm,
                        'args[1]' => $srs
                    );

                    // Retrieving information about a specific course in a specific term.
                    $curl = curl_init();
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($curl, CURLOPT_MAXREDIRS, 5);
                    curl_setopt($curl, CURLOPT_TIMEOUT, 5);
                    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                    curl_setopt($curl, CURLOPT_COOKIE, "$cookie"); // Use the previously saved session.
                    curl_setopt($curl, CURLOPT_USERPWD, "$htaccessusername:$htaccesspassword");
                    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);  // Essential for SSL.

                    $query = http_build_query($params, '', '&');
                    curl_setopt($curl, CURLOPT_URL, "$url?$query");
                    $output = curl_exec($curl);
                    $xml = simplexml_load_string($output, "SimpleXMLElement", LIBXML_NOCDATA);
                    $json = json_encode($xml);
                    $cleanedresult = json_decode($json, true);
                    curl_close($curl);

                    if (!is_array($cleanedresult['item'])) {
                        mtrace("ERROR: Invalid result for $term $srs");
                        continue;
                    }

                    // If there is more than one resource, then 'item' is an
                    // array of arrays. But if there is only one, then it is
                    // by itself. Make it an array of arrays.
                    $contents = $cleanedresult['item'];
                    if (!array_key_exists(0, $cleanedresult['item'])) {
                        $contents = array($cleanedresult['item']);
                    }

                    // CCLE-7002 - Leading zeros dropped for BruinCast.
                    $srs = validate_field('srs', $srs, 7, 9);

                    // Match content to a course, if any.
                    $courseid = match_course($term, $srs);

                    // Entering each media item for a particular course in a particular term into the DB.
                    foreach ($contents as $content) {
                        $entry = new \stdClass();
                        $entry->courseid = $courseid;
                        $entry->term = $term;
                        $entry->srs = $srs;
                        // This if statement is used as $content['video'] is a blank array if there is no video link.
                        if (!is_array($content['video'])) {
                            $entry->video_files = $content['video'];
                        } else {
                            $entry->video_files = null;
                        }
                        // Similar to above.
                        if (!is_array($content['audio'])) {
                            $entry->audio_files = $content['audio'];
                        } else {
                            $entry->audio_files = null;
                        }
                        $entry->title = $content['title'];
                        if (!empty($content['comments'])) {
                            $entry->comments = $content['comments'];
                        }
                        $temp = $content['date_for_recording_s_'];
                        $tempdate = explode('/', $temp);
                        $date = mktime(0, 0, 0, $tempdate[0], $tempdate[1], $tempdate[2]);

                        if (empty($date)) {
                            // We sometimes get timestamp of 0, ignore it.
                            mtrace("ERROR: Invalid timestamp $term $srs: $temp; skipping");
                            continue;
                        }

                        $entry->date = $date;

                        // See if we need to update or add.
                        if (isset($existingmedia[$term][$srs][$date][$entry->title][$entry->courseid][$entry->video_files][$entry->audio_files])) {
                            // Exists, so update.

                            // Bruincast data sometimes has duplicate entries
                            // due to the same file being uploaded multiple
                            // times due to user error. We handle this by
                            // not unsetting entry in $existingmedia, instead
                            // we set it to false meaning we already updated it.
                            if (!empty($existingmedia[$term][$srs][$date][$entry->title][$entry->courseid][$entry->video_files][$entry->audio_files])) {
                                $entry->id = $existingmedia[$term][$srs][$date][$entry->title][$entry->courseid][$entry->video_files][$entry->audio_files];
                                $DB->update_record('ucla_bruincast', $entry);
                                $existingmedia[$term][$srs][$date][$entry->title][$entry->courseid][$entry->video_files][$entry->audio_files] = false;
                                ++$numupdated;
                            } else {
                                // Ignore other entries, because newest entry is
                                // first.
                                mtrace(get_string('bcfoundupdatedentry',
                                        'tool_ucladatasourcesync',
                                        "$term $srs " .
                                        $this->format_date($date) .
                                        " $entry->title"));
                            }

                        } else {
                            // Add new entry.
                            try {
                                $DB->insert_record('ucla_bruincast', $entry);
                                ++$numinserted;
                            } catch (\dml_write_exception $ex) {
                                // It is a duplicate entry, so ignore it.
                                mtrace(get_string('founddupentry',
                                        'tool_ucladatasourcesync',
                                        "$term $srs " .
                                        $this->format_date($date) .
                                        " $entry->title"));
                            }
                        }
                    }
                }
            }
        }

        // Crosslist courses.
        list($countinserted, $countupdated) = $this->perform_crosslisting($existingmedia);
        $numinserted += $countinserted;
        $numupdated += $countupdated;

        // Finished processing, so delete entries that no longer exists.
        foreach ($existingmedia as $srses) {
            foreach ($srses as $dates) {
                foreach ($dates as $titles) {
                    foreach ($titles as $courseids) {
                        foreach ($courseids as $videofiles) {
                            foreach ($videofiles as $audiofiles) {
                                foreach ($audiofiles as $deleteid) {
                                    if (!empty($deleteid)) {
                                        // Delete timestamps from the database if the Bruincast entry is deleted.
                                        $videos = explode(',', key($videofiles));
                                        $videos = array_map('trim', $videos);
                                        $audio = explode(',', key($audiofiles));
                                        $audio = array_map('trim', $audio);
                                        $media = array_merge($videos, $audio);
                                        foreach ($media as $filename) {
                                            if (empty($filename)) {
                                                continue;
                                            }
                                            $records = $DB->get_records('user_preferences', 
                                                    array('name' => 'jwtimestamp_'.key($courseids).'_'.$filename), '', 'userid');
                                            foreach ($records as $record) {
                                                unset_user_preference('jwtimestamp_'.key($courseids).'_'.$filename, $record->userid);
                                            }
                                        }

                                        // Found record that was not updated, so delete.
                                        $DB->delete_records('ucla_bruincast',
                                                array('id' => $deleteid));
                                        ++$numdeleted;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        if ($numinserted == 0 && $numupdated == 0) {
            throw new \moodle_exception('bcnoentries', 'tool_ucladatasourcesync');
        }

        $counts = new \stdClass();
        $counts->deleted    = $numdeleted;
        $counts->inserted   = $numinserted;
        $counts->updated    = $numupdated;
        mtrace(get_string('successnotice', 'tool_ucladatasourcesync', $counts));
    }

    /**
     * Converts term from the format of 17F to fall-2017, etc.
     *
     * @param $term is a given term in the YYQ format where YY is year and Q is quarter
     * @return string
     */
    private function convert_term($term) {
        $correctedterm = '20'.$term[0].$term[1];
        if ($term[2] == 'F') {
            $correctedterm = 'fall-'.$correctedterm;
        } else if ($term[2] == 'S') {
            $correctedterm = 'spring-'.$correctedterm;
        } else if ($term[2] == 'W') {
            $correctedterm = 'winter-'.$correctedterm;
        } else {
            $correctedterm = 'summer-'.$correctedterm;
        }
        return $correctedterm;
    }

    /**
     * Copies the Bruincast from course1 to course 2.
     *
     * @param array $course1    Array with shortname, courseid and srs.
     * @param array $course2    Array with shortname, courseid and srs.
     * @param array $existingmedia  Passed by reference.
     *
     * @return array  Number of records added and updated.
     */
    private function copy_entries($course1, $course2, &$existingmedia) {
        global $DB;
        $numinserted = $numupdated = 0;
        $records = $DB->get_records('ucla_bruincast',
                array('courseid' => $course1['courseid']));
        if (!empty($records)) {
            $a = new \stdClass();
            $a->course1 = $course1['shortname'];
            $a->course2 = $course2['shortname'];
            mtrace(get_string('bccrosslistentries', 'tool_ucladatasourcesync', $a));
            foreach ($records as $record) {
                unset($record->id);   // Want to create new entry.
                // Change courseid and srs to course2.
                $record->courseid = $course2['courseid'];
                $record->srs = $course2['srs'];

                // Don't copy any content from course1 that course2 already had.
                if (strpos($record->title, $course2['shortname']) !== false) {
                    continue;
                }

                // Skip content already crosslisted from another course.
                // Looking if text similar to shortname appears like 
                // 181A-COMSCI32-1 and 17F-COMSCI31-1 at the start of the title.
                if (preg_match("/^[0-9]{2}[FWS1][AC]?-.+-.{1,3}\s/U", $record->title) == 1) {
                    continue;
                }

                // Make sure that we haven't already prepended the course
                // shortname before from a previous crosslisting.
                if (strpos($record->title, $course1['shortname']) === false) {
                    $record->title = $course1['shortname'] . ' ' . $record->title;
                }

                // Make sure that entry does not already exist for same file.
                // Sometimes BruinCast already crossposted data.
                if (!empty($existingmedia[$record->term][$record->srs][$record->date][$record->title])) {
                    // Exists, so update.
                    $record->id = $existingmedia[$record->term][$record->srs][$record->date][$record->title];
                    try {
                        $DB->update_record('ucla_bruincast', $record);
                        ++$numupdated;
                    } catch (\dml_write_exception $ex) {
                        // It is a duplicate entry, so ignore it.
                        mtrace(get_string('bcfoundupdatedentry',
                                'tool_ucladatasourcesync',
                                "$record->term $record->srs " .
                                $this->format_date($record->date) .
                                " $record->title"));
                    }
                } else {
                    // Add new entry.
                    try {
                        $DB->insert_record('ucla_bruincast', $record);
                        ++$numinserted;
                    } catch (\dml_write_exception $ex) {
                        // It is a duplicate entry, so ignore it.
                        mtrace(get_string('founddupentry',
                                'tool_ucladatasourcesync',
                                "$record->term $record->srs " .
                                $this->format_date($record->date) .
                                " $record->title"));
                    }
                }

                // Make existing entry false so it is not deleted.
                if (isset($existingmedia[$record->term][$record->srs][$record->date][$record->title])) {
                    $existingmedia[$record->term][$record->srs][$record->date][$record->title] = false;
                }
            }
        }
        return array($numinserted, $numupdated);
    }

    /**
     * Utility function to format timestamp to humand readable format.
     * 
     * @param int $timestamp
     * @return string
     */
    private function format_date($timestamp) {
        return date('m/d/y', $timestamp);
    }

    /**
     * Returns existing media for given term in following format:
     *  [term][srs][date][title] = record id
     *
     * @param string $term
     * @return array
     */
    public function get_existingmedia($term) {
        global $DB;
        $retval = array();

        $records = $DB->get_records('ucla_bruincast', array('term' => $term),
                null, 'id,term,srs,date,title,courseid,video_files,audio_files');
        if (empty($records)) {
            return $retval;
        }

        foreach ($records as $record) {
            $retval[$record->term][$record->srs][$record->date][$record->title][$record->courseid][$record->video_files][$record->audio_files]
                    = $record->id;
        }
        return $retval;
    }

    /**
     * Returns task name.
     *
     * @return string
     */
    public function get_name() {
        return get_string('taskupdatebcast', 'block_ucla_media');
    }

    /**
     * Finds courses that should have their media cross-listed and copies data
     * both ways (if there is any).
     *
     * @param array $existingmedia  Passed by reference.
     * @return array  Number of records added and updated.
     */
    public function perform_crosslisting(&$existingmedia) {
        global $DB;
        mtrace(get_string('bccrosslistmedia', 'tool_ucladatasourcesync'));

        // Expecting crosslists to be in following format:
        // 17F-CHEM153A-2=17F-CHEM153A-3
        // Each on a new line.
        $crosslistsconfig = get_config('block_ucla_media', 'bruincast_crosslists');
        if (empty($crosslistsconfig)) {
            return;
        }

        $crosslists = explode("\n", $crosslistsconfig);
        $crosslists = array_map('trim', $crosslists);

        $numinserted = $numupdated = 0;
        foreach ($crosslists as $crosslist) {
            // Split by "=".
            $shortnames = explode('=', $crosslist);
            // Must be only two elements.
            if (count($shortnames) != 2) {
                mtrace(get_string('bcinvalidcrosslists', 'tool_ucladatasourcesync',
                        $crosslist));
                continue;
            }
            // Verify that course shortnames exists and store course data.
            $courses = array();
            $validatedcourses = true;
            foreach ($shortnames as $index => $shortname) {
                if ($courseid = $DB->get_field('course', 'id',
                        array('shortname' => $shortname))) {
                    $courses[$index] = array();
                    $courses[$index]['shortname'] = $shortname;
                    $courses[$index]['courseid'] = $courseid;
                    // Found courseid, need to also find srs.
                    $srs = $DB->get_field('ucla_request_classes', 'srs',
                            array('courseid' => $courseid, 'hostcourse' => 1),
                            MUST_EXIST);
                    $courses[$index]['srs'] = $srs;
                } else {
                    mtrace(get_string('bcinvalidcrosslists', 'tool_ucladatasourcesync',
                        $shortname));
                    $validatedcourses = false;
                    break;
                }
            }

            if (!$validatedcourses) {
                // Skip invalid record.
                continue;
            }

            // Everything is good to go, so let's copy data in ucla_bruincast.
            list($countinserted, $countupdated) = $this->copy_entries($courses[0],
                    $courses[1], $existingmedia);
            $numinserted += $countinserted;
            $numupdated += $countupdated;
            list($countinserted, $countupdated) = $this->copy_entries($courses[1],
                    $courses[0], $existingmedia);
            $numinserted += $countinserted;
            $numupdated += $countupdated;
        }
        return array($numinserted, $numupdated);
    }
}
