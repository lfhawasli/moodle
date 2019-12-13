<?php
// This file is part of the MyUCLA url updater for Moodle - http://moodle.org/
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
 * Handles communication with MyUCLA class links server.
 *
 * @package    tool_myucla_url
 * @copyright  2019 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Class file.
 *
 * @copyright  2019 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group tool_myucla_url
 */
class myucla_urlupdater {
    /** @var string Never update the url. */
    const NEVERFLAG = 'neversend';
    /** @var string Do not overwrite the URL (standard). */
    const NOOVERWRITEFLAG = 'nooverwrite';
    /** @var string Always overwrite the URL. */
    const ALWAYSFLAG = 'alwayssend';

    // Possible MyUCLA server responses.
    /** @var string Success message. */
    const EXPECTED_SUCCESS_MESSAGE  = 'Update Successful';
    /** @var string Connection error. */
    const ERROR_CONNECTION          = 'Unable to Connect to SQL Servers!';
    /** @var string Access denied error. */
    const ERROR_DENIED              = 'Unauthorized Access!';
    /** @var string SQL error. */
    const ERROR_FAILED              = 'Update Unsuccessful. SQL Update Failed.';
    /** @var string Invalid course. */
    const ERROR_INVALID             = 'Update Unsuccessful. Invalid Course.';

    // Responses for set_url_if_same_server.
    /** @var int If url was set at MyUCLA. */
    const URL_SET = 1;
    /** @var int If url at MyUCLA didn't belong to current server. */
    const URL_NOTSET = 0;
    /** @var int If problem with web service. */
    const URL_ERROR = -1;

    /** @var string URL to MyUCLA class links with user/email set. */
    private $myuclalogin = null;

    /** @var array Contains success messages. */
    public $successful = array();
    /** @var array Contains failed messages. */
    public $failed = array();
    /** @var array Contains skipped messages. */
    public $skipped = array();

    /**
     * Builds the MyUCLA URL update webservice URL.
     *
     * @param string $term The term to upload.
     * @param string $srs  The SRS of the course to upload.
     * @param string $url  Default to false. Otherwise is the url to update to.
     *                     If parameter is null or an empty string it will
     *                     clear the URL at MyUCLA.
     * @return string      The URL to be used in the MyUCLA update.
     */
    protected function get_myucla_service($term, $srs, $url=false) {
        if ($this->myuclalogin == null) {
            $ccurl = get_config('tool_myucla_url', 'url_service');

            $ccname = get_config('tool_myucla_url', 'user_name');
            $ccemail = get_config('tool_myucla_url', 'user_email');

            $muurl = $ccurl . '?name=' . urlencode($ccname)
                . '&email=' . $ccemail;

            $this->myuclalogin = $muurl;
        }

        $returner = $this->myuclalogin . '&term=' . $term . '&srs=' . $srs;

        if ($url !== false) {
            // If URL is null or empty it will clear the url on MyUCLA.
            $returner .= '&url=' . urlencode($url);
        }

        return $returner;
    }

    /**
     * Sends the URLs of the courses to MyUCLA. Either updates those urls or
     * gets the current valies depending on the parameter.
     *
     * @param array $sendingurls  Expects array in following format:
     *     Array (
     *         make_idnumber() => Array (
     *             'term' => term,
     *             'srs' => srs,
     *             'url' => url
     *         )
     *     )
     * @param boolean $push     Default false. If true will update urls for
     *                         given set of courses
     *
     * @return array    Returns array in following format:
     *                 [term-srs] => [response message from server]
     */
    public function send_myucla_urls($sendingurls, $push=false) {
        // Figure out what to build as the URL of the course.
        $retrievedinfo = array();

        // For each requested course, figure out the URL.
        foreach ($sendingurls as $idnumber => $sendings) {
            $sender = false;
            if ($push) {
                $sender = $sendings['url'];
            }

            // Figure out the URL.
            $urlupdate = $this->get_myucla_service(
                $sendings['term'], $sendings['srs'], $sender
            );

            if ($this->is_debugging()) {
                // Debugging is on, so just assume success.
                $myuclacurl = self::EXPECTED_SUCCESS_MESSAGE;
            } else {
                $myuclacurl = $this->contact_myucla($urlupdate);
            }

            $retrievedinfo[$idnumber] = $myuclacurl;
        }

        return $retrievedinfo;
    }

    /**
     * Syncs a set of courses with MyUCLA URLs.
     * @param array $courses Array(
     *     make_idnumber() => Array(
     *         'term' => term,
     *         'srs' => srs,
     *         'url' => url
     *     )
     * )
     *
     * Sets successful and failed arrays with the appropiate courses indexed by
     * the same course index as given in the $course paramter.
     */
    public function sync_myucla_urls($courses) {
        // First get the urls for the given courses.
        $fetchresults = $this->send_myucla_urls($courses);

        foreach ($fetchresults as $idnumber => $result) {
            // The hardcoded default.
            $flag = self::NOOVERWRITEFLAG;

            if (isset($courses[$idnumber]['flag'])) {
                $flag = $courses[$idnumber]['flag'];
            }

            if ($flag == self::NEVERFLAG) {
                // This is done.
                $this->successful[$idnumber] = $result;
            }

            // We got a result but we're not supposed to overwrite it.
            if (!empty($result)) {
                // Check if result is an error, like access denied or db error.
                if ((strpos($result, self::ERROR_CONNECTION) !== false) ||
                        (strpos($result, self::ERROR_DENIED) !== false)) {
                    $this->failed[$idnumber] = $result;
                } else if ($flag == self::NOOVERWRITEFLAG) {
                    $this->successful[$idnumber] = $result;
                }
            }

            // We don't need to push urls that are done or had errors.
            if (isset($this->successful[$idnumber]) ||
                    isset($this->failed[$idnumber])) {
                $this->skipped[$idnumber] = true;
                unset($courses[$idnumber]);
            }
        }

        // Now update those urls that need to be processed.
        $results = $this->send_myucla_urls($courses, true);

        foreach ($results as $rid => $result) {
            if (strpos($result, self::EXPECTED_SUCCESS_MESSAGE) === false) {
                $this->failed[$rid] = $result;
            } else {
                $this->successful[$rid] = $result;
            }
        }

        return true;
    }

    /**
     * Convenience function to get access the webservice for MyUCLA.
     *
     * @param string $url
     * @return string
     */
    protected function contact_myucla($url) {
        $content = $this->trim_strip_tags(file_get_contents($url));
        return $content;
    }

    /**
     * Returns if we should send the actual message or not.
     *
     * @return boolean
     */
    private function is_debugging() {
        if (get_config('tool_myucla_url', 'override_debugging')) {
            return false;
        }

        return debugging();
    }

    /**
     * Quick wrapper for strip_tags and trim.
     *
     * @param string $string The string to trim and strip_tags.
     * @return string The string, without HTML tags and with leading and
     *     trailing spaces removed.
     */
    private function trim_strip_tags($string) {
        return trim(strip_tags($string), " \r\n\t");
    }

    /**
     * Sets the url at MyUCLA only if it belongs to the same server that is
     * sending the url out.
     *
     * @param string $term
     * @param string $srs
     * @param string $url   If blank, will clear url, else will be
     *
     * @return boolean      Returns myucla_urlupdater::url_set if url set.
     *                     Returns myucla_urlupdater::url_notset if existing
     *                     url belongs to another server
     *                     Returns myucla_urlupdater::url_error if problem with
     *                     web service
     */
    public function set_url_if_same_server($term, $srs, $url) {
        global $CFG;

        $course = array(0 => array('term' => $term, 'srs' => $srs));

        $results = $this->send_myucla_urls($course, false);
        $responseurl = array_pop($results);

        if ((strpos($responseurl, $CFG->wwwroot) !== false) || empty($responseurl)) {
            $course[0]['url'] = $url;
            $results = $this->send_myucla_urls($course, true);

            $responseback = array_pop($results);
            if (strpos($responseback, self::EXPECTED_SUCCESS_MESSAGE) === false) {
                return self::URL_ERROR;
            } else {
                return self::URL_SET;
            }
        }

        return self::URL_NOTSET;
    }
}
