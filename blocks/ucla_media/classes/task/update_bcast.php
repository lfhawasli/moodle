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
require_once($CFG->dirroot . '/' . $CFG->admin . '/tool/ucladatasourcesync/lib.php');

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
    * @return boolean
    */
    public function execute() {
        global $CFG, $DB;

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

        $response = curl_exec($curl);
        $xml = new \SimpleXMLElement($response);
        $cookie = $xml->session_name .'='. $xml->sessid;

        curl_close($curl);

        // URL to get relevant courses accoring to term.
        $url = get_config('block_ucla_media', 'bruincast_url');

        // Clearing old records.
        $DB->delete_records('ucla_bruincast');
        $terms = get_active_terms();
        // Iterating through all active terms and retrieving data for them.
        foreach ($terms as $term) {
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

                // The below if statement is a workaround for an XML parsing problem. When only one item is retrieved in a query
                // the array $cleanedresult[item] contains information about that one item, however, when there are multiple results
                // the array is an array of arrays that contain information about these results.
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

                    $query = http_build_query($params, '', '&');
                    curl_setopt($curl, CURLOPT_URL, "$url?$query");
                    $output = curl_exec($curl);
                    $xml = simplexml_load_string($output, "SimpleXMLElement", LIBXML_NOCDATA);
                    $json = json_encode($xml);
                    $cleanedresult = json_decode($json, true);
                    curl_close($curl);

                    // Entering each media item for a particular course in a particular term into the DB.
                    foreach ($cleanedresult['item'] as $content) {
                        $entry = new \stdClass();
                        $entry->courseid = ucla_map_termsrs_to_courseid($term, $srs);
                        $entry->term = $term;
                        $entry->srs = $srs;
                        $entry->restricted = 'Restricted';
                        // This if statement is used as $content['video'] is a blank array if there is no video link.
                        if (!is_array($content['video'])) {
                            $entry->bruincast_url = $content['video'];
                        } else {
                            $entry->bruincast_url = 'null';
                        }
                        // Similar to above.
                        if (!is_array($content['audio'])) {
                            $entry->audio_url = $content['audio'];
                        } else {
                            $entry->audio_url = 'null';
                        }
                        $entry->name = $content['title'];
                        $entry->podcast_url = 'null';
                        $temp = $content['date_for_recording_s_'];
                        $tempdate = explode('/', $temp);
                        $date = mktime(0, 0, 0, $tempdate[0], $tempdate[1], $tempdate[2]);
                        $entry->date = $date;
                        $DB->insert_record('ucla_bruincast', $entry);
                    }
                }
            }
        }
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
    * Returns task name.
    *
    * @return string
    */
    public function get_name() {
        return get_string('taskupdatebcast', 'block_ucla_media');
    }

}