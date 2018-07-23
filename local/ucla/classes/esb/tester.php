<?php
// This file is part of the UCLA local_ucla plugin for Moodle - http://moodle.org/
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
 * Tests the connection to the Enterprise Service Bus.
 *
 * @package    local_ucla
 * @copyright  2018 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_ucla\esb;

defined('MOODLE_INTERNAL') || die();

/**
 * Class file
 *
 * @package    local_ucla
 * @copyright  2018 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tester {
    private $baseurl;
    private $cert;
    public $lasthttpcode;
    public $lastmessage;
    private $password;
    private $privatekey;
    private $username;

    /**
     * Sets up class variables.
     */
    public function __construct() {
        $configs = get_config('local_ucla');
        $this->baseurl      = $configs->esburl;
        $this->username     = $configs->esbusername;
        $this->password     = $configs->esbpassword;
        $this->cert         = $configs->esbcert;
        $this->privatekey   = $configs->esbprivatekey;
    }

    /**
     * Formats parameters to be used in query string.
     *
     * @param array $params Expecting name => value.
     * @return string
     */
    private function format_parameters($params) {
        // If empty, then do nothing.
        if (empty($params) || !is_array($params)) {
            return '';
        }
        return http_build_query($params);
    }

    /**
     * Makes cURL call, parses JSON, and checks for error.
     *
     * Closes the cURL handler, unless specified.
     *
     * @param resource $ch          Passed by reference. cURL handle.
     * @param boolean $keepalive    If true, then wouldn't close connection.
     *
     * @return array
     */
    private function get_response(&$ch, $keepalive = false) {
        $response = curl_exec($ch);
        if (!$response) {
            $this->lastmessage = curl_error($ch);
            $this->lasthttpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            throw new \Exception('ESB Error: ' . $this->lastmessage);
        }

        $result = json_decode($response);

        // Check if there was an error processing JSON.
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->lastmessage = $response;
            $this->lasthttpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            throw new \Exception('ESB Error: ' . $this->lastmessage);
        }

        if (!$keepalive) {
            curl_close($ch);
        }

        return $result;
    }

    /**
     * Returns token to be used in web service calls.
     *
     * Either gets the cached token if still valid or gets a new token.
     *
     * @return string
     */
    private function get_token() {
        $ch = curl_init($this->baseurl . '/oauth2/token');
        $options = [
            CURLOPT_PORT => 4443,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_POSTFIELDS => "grant_type=client_credentials",
            CURLOPT_SSLCERT => $this->cert,
            CURLOPT_SSLKEY => $this->privatekey,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERPWD => $this->username.":".$this->password,
            CURLOPT_HTTPHEADER => [
                "Cache-control: no-cache",
                "Content-type: application/x-www-form-urlencoded"
            ]
        ];
        curl_setopt_array($ch, $options);
        $response = $this->get_response($ch);
        return $response->access_token;
    }

    /**
     * Performs the web service call.
     *
     * @param string $restapi
     * @param array $params
     */
    private function query($restapi, $params = null) {
        $parameters = $this->format_parameters(['anyText' => date('r')]);
        $ch = curl_init($this->baseurl . '/sis/api/v1/' . $restapi . '?' . $parameters);
        curl_setopt_array($ch, [
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'esmAuthnClientToken: ' . $this->get_token()
            ]
        ]);
        return $this->get_response($ch);
    }

    /**
     * Calls the Infrastructure/VerifyConnectivity web service.
     *
     * @return array    Returns array with results.
     */
    public function run() {
        return $this->query('Infrastructure/VerifyConnectivity');
    }
}
