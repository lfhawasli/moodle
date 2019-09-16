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
require_once($CFG->dirroot . '/blocks/ucla_media/locallib.php');

/**
 * Class file.
 *
 * @package    block_ucla_media
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class update_bcast extends \core\task\scheduled_task {
    /**
     * Value from block_ucla_media | bruincast_url.
     * @var string
     */
    private $bruincasturl;

    /**
     * Authentication cookie to query web service.
     * @var string
     */
    private $cookie;

    /**
     * Value from block_ucla_media | bruincast_http_user.
     * @var string
     */
    private $htaccessusername;

    /**
     * Value from block_ucla_media | bruincast_http_pass.
     * @var string
     */
    private $htaccesspassword;

    /**
     * Update Bruincast database.
     *
     * @param array $terms  Optional. Used if task is called via command line.
     * @return boolean
     */
    public function execute($terms = null) {
        global $DB;

        mtrace(get_string('bcstartnoti', 'tool_ucladatasourcesync'));

        $this->curl_login();

        // Index existing videos by term, srs, date, and title (same as unique key).
        $existingmedia = array();

        // Wrap everything in a transaction, because we don't want to lose data
        // if there is a data issue.
        $numdeleted = $numinserted = $numupdated = $numprocessed = 0;
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

            $cleanedresult = $this->curl_get_courses($correctedterm);

            // Only processing next part if the result was non-empty.
            if (array_key_exists('item', $cleanedresult)) {
                // To get session data for the term.
                $sessiondata = $this->registrar_get_term_session($term);

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

                    ++$numprocessed;

                    $contents = $this->curl_get_media($correctedterm, $srs);

                    // CCLE-7002 - Leading zeros dropped for BruinCast.
                    $srs = validate_field('srs', $srs, 7, 9);

                    // Match content to a course, if any.
                    $courseid = $this->match_course($term, $srs);

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
                        $entry->comments = null;
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
                        $entry->week = $sessiondata->get_week(new \DateTime(date('Y-m-d H:i:s', $date)));

                        // See if we need to update or add.
                        if (isset($existingmedia[$term][$srs][$date][$entry->title])) {
                            $existing = $existingmedia[$term][$srs][$date][$entry->title];
                            // Exists, so update.

                            // Bruincast data sometimes has duplicate entries
                            // due to the same file being uploaded multiple
                            // times due to user error. We handle this by
                            // not unsetting entry in $existingmedia, instead
                            // we set it to false meaning we already updated it.
                            if (!empty($existing)) {
                                $entry->id = $existing['id'];

                                // Mark existing media as being processed.
                                $existingmedia[$term][$srs][$date][$entry->title] = false;

                                // Update only if something changed.
                                $updateneeded = false;
                                foreach (array_keys($existing) as $key) {
                                    if ($existing[$key] != $entry->$key) {
                                        $updateneeded = true;
                                    }
                                }

                                if ($updateneeded) {
                                    $DB->update_record('ucla_bruincast', $entry);
                                    ++$numupdated;
                                }
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
            } else if (!empty($existingmedia[$term])) {
                // Data sync is empty for an active term while our current DB has content, no update.
                throw new \moodle_exception('bcnoentries', 'tool_ucladatasourcesync');
            }
        }

        // Don't delete or process anything else if found no Bruincast content.
        if ($numprocessed == 0) {
            throw new \moodle_exception('bcnoentries', 'tool_ucladatasourcesync');
        }

        // Finished processing, so delete entries that no longer exists.
        foreach ($existingmedia as $srses) {
            foreach ($srses as $dates) {
                foreach ($dates as $titles) {
                    foreach ($titles as $entry) {
                        if (empty($entry)) {
                            continue;
                        }
                        // Delete timestamps from the database if the Bruincast entry is deleted.
                        $vidarray = $audarray = [];
                        if (!empty($entry->video_files)) {
                            $vidarray = array_map('trim', explode(',', $entry->video_files));
                        }
                        if (!empty($entry->audio_files)) {
                            $audarray = array_map('trim', explode(',', $entry->audio_files));
                        }
                        $media = array_merge($vidarray, $audarray);

                        // Check if there are any crosslisted courses using this content.
                        $allcourseids = $DB->get_fieldset_select('ucla_bruincast_crosslist',
                                "CONCAT('jwtimestamp_', courseid)", 'contentid = :contentid',
                                array('contentid' => $entry['id']));
                        $allcourseids[] = 'jwtimestamp_'.$entry['courseid'];

                        delete_timestamps($allcourseids, $media);

                        // Found record that was not updated, so delete.
                        $DB->delete_records('ucla_bruincast',
                                array('id' => $entry['id']));
                        ++$numdeleted;
                    }
                }
            }
        }

        // Crosslist courses.
        list($countinserted, $countupdated, $countdeleted) = $this->perform_crosslisting();
        $numinserted += $countinserted;
        $numupdated += $countupdated;
        $numdeleted += $countdeleted;

        $counts = new \stdClass();
        $counts->deleted    = $numdeleted;
        $counts->inserted   = $numinserted;
        $counts->updated    = $numupdated;
        mtrace(get_string('successnotice', 'tool_ucladatasourcesync', $counts));
    }

    /**
     * Converts term from the format of 17F to fall-2017, etc.
     *
     * @param string $term Term in the YYQ format where YY is year and Q is quarter
     * @return string term in with spelled out term and full 4 digit year
     */
    protected function convert_term($term) {
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
     * @param array $existingcrosslists  Passed by reference.
     *
     * @return array  Number of records added, updated, and deleted.
     */
    private function copy_entries($course1, $course2, &$existingcrosslists) {
        global $DB;

        $numinserted = $numupdated = $numdeleted = 0;
        $records = $DB->get_records('ucla_bruincast',
                array('courseid' => $course1['courseid']));

        if (!empty($records)) {
            $a = new \stdClass();
            $a->course1 = $course1['shortname'];
            $a->course2 = $course2['shortname'];
            mtrace(get_string('bccrosslistentries', 'tool_ucladatasourcesync', $a));
            foreach ($records as $record) {
                $newrecord = new \stdClass();
                $newrecord->contentid = $record->id;
                $newrecord->courseid = $course2['courseid'];

                // Make sure that entry does not already exist for same file.
                // Sometimes BruinCast already crossposted data.
                if (!empty($existingcrosslists[$newrecord->courseid][$newrecord->contentid])) {
                    // Make existing entry false so it is not deleted.
                    $existingcrosslists[$newrecord->courseid][$newrecord->contentid] = false;
                } else {
                    // Add new entry.
                    try {
                        $DB->insert_record('ucla_bruincast_crosslist', $newrecord);
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
            }
        }

        return array($numinserted, $numupdated, $numdeleted);
    }

    /**
     * Returns courses with BruinCast content for given term.
     *
     * @param string $term
     * @return array
     */
    protected function curl_get_courses($term) {
        // Setting parameters for our request.
        $params = array(
            'display_id' => 'ccle_api_courses',
            'args[0]' => $term
        );

        // Doing a Curl to retrive bruincasted courses for a particular term.
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_POST, 0); // Do a regular HTTP POST.
        curl_setopt($curl, CURLOPT_MAXREDIRS, 5);
        curl_setopt($curl, CURLOPT_TIMEOUT, 5);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($curl, CURLOPT_COOKIE, "$this->cookie"); // Use the previously saved session.
        curl_setopt($curl, CURLOPT_USERPWD, "$this->htaccessusername:$this->htaccesspassword");
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);  // Essential for SSL.

        $query = http_build_query($params, '', '&');
        /* Please note http_build_query is different in moodle and outside it, and it's usage is different */

        curl_setopt($curl, CURLOPT_URL, $this->bruincasturl.'?'.$query);
        $output = curl_exec($curl);

        $xml = simplexml_load_string($output, "SimpleXMLElement", LIBXML_NOCDATA);
        $json = json_encode($xml);
        $cleanedresult = json_decode($json, true);
        curl_close($curl);

        return $cleanedresult;
    }

    /**
     * Returns Bruincast content for given course.
     *
     * @param string $term
     * @param string $srs
     * @return array
     */
    protected function curl_get_media($term, $srs) {
        $params = array(
            'display_id' => 'ccle_api_media',
            'args[0]' => $term,
            'args[1]' => $srs
        );

        // Retrieving information about a specific course in a specific term.
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_MAXREDIRS, 5);
        curl_setopt($curl, CURLOPT_TIMEOUT, 5);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($curl, CURLOPT_COOKIE, "$this->cookie"); // Use the previously saved session.
        curl_setopt($curl, CURLOPT_USERPWD, "$this->htaccessusername:$this->htaccesspassword");
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);  // Essential for SSL.

        $query = http_build_query($params, '', '&');
        curl_setopt($curl, CURLOPT_URL, "$this->bruincasturl?$query");
        $output = curl_exec($curl);
        $xml = simplexml_load_string($output, "SimpleXMLElement", LIBXML_NOCDATA);
        $json = json_encode($xml);

        $cleanedresult = json_decode($json, true);

        curl_close($curl);

        if (!is_array($cleanedresult['item'])) {
            mtrace("ERROR: Invalid result for $term $srs");
            throw new \moodle_exception('eventbcparsing', 'tool_ucladatasourcesync');
        }

        // If there is more than one resource, then 'item' is an
        // array of arrays. But if there is only one, then it is
        // by itself. Make it an array of arrays.
        $contents = $cleanedresult['item'];
        if (!array_key_exists(0, $cleanedresult['item'])) {
            $contents = array($cleanedresult['item']);
        }

        return $contents;
    }

    /**
     * Login to Bruincast server and stores authentication cookie.
     */
    protected function curl_login() {
        // URL to get relevant courses accoring to term.
        $this->bruincasturl = get_config('block_ucla_media', 'bruincast_url');

        $this->htaccessusername = get_config('block_ucla_media', 'bruincast_http_user');
        $this->htaccesspassword = get_config('block_ucla_media', 'bruincast_http_pass');

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
        curl_setopt($curl, CURLOPT_USERPWD, "$this->htaccessusername:$this->htaccesspassword");
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);  // Essential for SSL.

        $response = curl_exec($curl);
        $xml = new \SimpleXMLElement($response);
        $this->cookie = $xml->session_name .'='. $xml->sessid;

        curl_close($curl);

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
     *  [term][srs][date][title] = {
     *      ucla_bruincast.*
     * }
     *
     * @param string $term
     * @return array
     */
    public function get_existingmedia($term) {
        global $DB;
        $retval = array();

        $records = $DB->get_records('ucla_bruincast', array('term' => $term),
                null, '*');
        if (empty($records)) {
            return $retval;
        }

        foreach ($records as $record) {
            $retval[$record->term][$record->srs][$record->date][$record->title]
                    = (array) $record;
        }
        return $retval;
    }

    /**
     * Returns existing crosslists in following format:
     *  [courseid][contentid] = crosslist id
     *
     * @return array
     */
    public function get_existingcrosslists() {
        global $DB;
        $retval = array();

        $records = $DB->get_records('ucla_bruincast_crosslist');
        if (empty($records)) {
            return $retval;
        }
        foreach ($records as $record) {
            $retval[$record->courseid][$record->contentid] = $record->id;
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
     * Wrapper function for tool_ucladatasourcesync/lib.php function so it can
     * be mocked in unit tests.
     *
     * @param string $term
     * @param string $srs
     *
     * return int   Course id, if any, otherwise null.
     */
    public function match_course($term, $srs) {
        return match_course($term, $srs);
    }

    /**
     * Finds courses that should have their media cross-listed and copies data
     * both ways (if there is any).
     *
     * @return array  Number of records added, updated, and deleted.
     */
    public function perform_crosslisting() {
        global $DB;
        mtrace(get_string('bccrosslistmedia', 'tool_ucladatasourcesync'));

        // Expecting crosslists to be in following format:
        // 17F-CHEM153A-2=17F-CHEM153A-3
        // Each on a new line.
        $crosslistsconfig = get_config('block_ucla_media', 'bruincast_crosslists');
        if (empty($crosslistsconfig)) {
            return;
        }

        $existingcrosslists = $this->get_existingcrosslists();
        $crosslists = explode("\n", $crosslistsconfig);
        $crosslists = array_map('trim', $crosslists);

        $numinserted = $numupdated = $numdeleted = 0;
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

            // Everything is good to go, so let's copy data in ucla_bruincast_crosslist.
            list($countinserted, $countupdated, $countdeleted) = $this->copy_entries($courses[0],
                    $courses[1], $existingcrosslists);
            $numinserted += $countinserted;
            $numupdated += $countupdated;
            $numdeleted += $countdeleted;
            list($countinserted, $countupdated, $countdeleted) = $this->copy_entries($courses[1],
                    $courses[0], $existingcrosslists);
            $numinserted += $countinserted;
            $numupdated += $countupdated;
            $numdeleted += $countdeleted;
        }

        // Delete the non-existing entries.
        foreach ($existingcrosslists as $keycourseid => $course) {
            foreach ($course as $keydeleteid => $deleteid) {
                if (empty($deleteid)) {
                    continue;
                }

                // Delete timestamps from the database if the crosslist entry is deleted.
                $deletecourse = $DB->get_record('ucla_bruincast', array('id' => $keydeleteid));

                // We only need to delete timestamps if the crosslist is deleted
                // but the content is still in the Bruincast table.
                if (!empty($deletecourse)) {
                    $vidarray = array_map('trim', explode(',', $deletecourse->video_files));
                    $audarray = array_map('trim', explode(',', $deletecourse->audio_files));
                    $media = array_merge($vidarray, $audarray);

                    delete_timestamps(array('jwtimestamp_'.$keycourseid), $media);
                }

                $DB->delete_records('ucla_bruincast_crosslist', array('id' => $deleteid));
                ++$numdeleted;
            }
        }

        return array($numinserted, $numupdated, $numdeleted);
    }

    /**
     * Get session data for the term.
     *
     * @param string $term
     */
    protected function registrar_get_term_session($term) {
        $query = \registrar_query::run_registrar_query('ucla_getterms', array($term), true);
        return \block_ucla_weeksdisplay_session::create($query);
    }
}
