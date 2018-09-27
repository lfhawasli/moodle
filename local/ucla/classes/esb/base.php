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
 * Base class for Enterprise Service Bus (ESB) web service calls.
 *
 * @package    local_ucla
 * @copyright  2018 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_ucla\esb;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/../../lib.php');   // Include local_ucla lib.php.

/**
 * Class file
 *
 * @package    local_ucla
 * @copyright  2018 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class base {
    /**
     * URL to web service endpoint.
     * @var string
     */
    private $baseurl;

    /**
     * Path to SSL certificate.
     * @var string
     */
    private $cert;

    /**
     * The cURL handler.
     * @var mixed
     */
    private $ch;

    /**
     * If set to true, will output debugging messages.
     *
     * @var boolean
     */
    public $debugging = false;

    /**
     * Holds debugging messages as a string.
     *
     * @var string
     */
    public $debugginglog = '';

    /**
     * If there was an error, has the HTTP code of last request stored.
     * @var string
     */
    public $lasthttpcode;

    /**
     * If there was an error, has the error message of last request stored.
     * @var string
     */
    public $lastmessage;

    /**
     * Saves query used so it can be used for debugging.
     * @var string
     */
    public $lastquery;

    /**
     * Endpoint password credential.
     * @var string
     */
    private $password;

    /**
     * Holds profiling data if debugging is enabled.
     * @var array
     */
    private $profiling = [];

    /**
     * Path to SSL private key.
     * @var string
     */
    private $privatekey;

    /**
     * Start time. Used to build profiling data.
     * @var float
     */
    private $starttime;

    /**
     * Cached token.
     * @var string
     */
    private $token;

    /**
     * Endpoint username credential.
     * @var string
     */
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
     * Used to benchmark API calls.
     *
     * Should only be used to benchmark one call at a time.
     *
     * @param string $apicall       Name of API to benchmark.
     * @param string $startstop     Expecting 'start' or 'stop'.
     */
    private function build_profile($apicall, $startstop) {
        if (!$this->debugging) {
            // Only profile if we are debugging.
            return;
        }

        // Initialize.
        if (empty($this->profiling[$apicall])) {
            $this->profiling[$apicall]['count'] = 0;
            $this->profiling[$apicall]['time'] = 0;
        }

        if ($startstop == 'start') {
            ++$this->profiling[$apicall]['count'];
            $this->starttime = microtime(true);
        } else if ($startstop == 'stop') {
            $start = $this->starttime;
            $end = microtime(true);
            $this->profiling[$apicall]['time'] += $end - $start;
        }
    }

    /**
     * Defines what web services to call and what to do with the results.
     *
     * @params array $params
     *
     * @return mixed
     */
    abstract function build_result($params);

    /**
     * Closes curl connection.
     */
    private function close_curl() {
        if (!empty($this->ch)) {
            curl_close($this->ch);
        }
    }

    /**
     * If debugging flag for class is set, then will output message.
     * @param string $message
     */
    protected function debug($message) {
        if ($this->debugging) {
            $eol = "<br />";
            if (CLI_SCRIPT) {
                $eol = "\n";
            }
            $this->debugginglog .= $message . $eol;
        }
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

        // Make sure to encode spaces as %20.
        return http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Returns the array describing the parameters are needed to return data.
     *
     * @return array
     */
    abstract public function get_parameters();

    /**
     * Makes cURL call, parses JSON, and checks for error.
     *
     * Closes the cURL handler, unless specified.
     *
     * @param string $url       Query string.
     * @param resource $ch      Optional custom cURL handler.
     *
     * @return array
     */
    final private function get_response($url, &$ch = null) {
        if (empty($ch)) {
            $ch = $this->open_curl();
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        $response = curl_exec($ch);
        if (!$response) {
            $this->lastmessage = curl_error($ch);
            $this->lasthttpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $this->close_curl();
            throw new \moodle_exception('esberror', 'local_ucla', null, $this);
        }

        $result = json_decode($response, true);

        // Check if there was an error processing JSON.
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->lastmessage = $response;
            $this->lasthttpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $this->close_curl();

            // If error was 404, then there were no results.
            if ($this->lasthttpcode == 404) {
                return null;
            }
            throw new \moodle_exception('esberror', 'local_ucla', null, $this);
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
    final protected function get_token() {
        // Cache token.
        if (!empty($this->token)) {
            return $this->token;
        }

        $cache = \cache::make('local_ucla', 'esbtoken');
        $token = $cache->get('token');

        if (empty($token)) {
            // Use customized cURL handler for obtaining token.
            $url = $this->baseurl . '/oauth2/token';
            $ch = curl_init();
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
            $response = $this->get_response($url, $ch);
            curl_close($ch);
            $token = $response['access_token'];
            $cache->set('token', $token);
        }

        $this->token = $token;

        return $token;
    }

    /**
     * Opens or returns an opened curl connection.
     *
     * @return mixed    cURL handler by reference.
     */
    private function &open_curl() {
        if (!empty($this->ch)) {
            return $this->ch;
        }
        $this->ch = curl_init();
        curl_setopt_array($this->ch, [
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_RETURNTRANSFER => true,
            // ESB timeout is 2 minutes, so allow time for ESB to give timeout error.
            CURLOPT_TIMEOUT => 130,
            CURLOPT_HTTPHEADER => [
                'esmAuthnClientToken: ' . $this->get_token()
            ]
        ]);
        return $this->ch;
    }

    /**
     * Performs the web service call.
     *
     * @param string $restapi
     * @param array $params
     * @param $ignoreextradata  Queries are returned in an array with first item
     *                          is the content followed, sometimes, by junk like
     *                          totalRecords, totalPages, etc. If true, this
     *                          will return just the content.
     *
     * @return array
     */
    protected function query($restapi, $params = null, $ignoreextradata = false) {
        $parameters = $this->format_parameters($params);

        // Always get all the records.
        if (!empty($parameters)) {
            $parameters .= '&';
        }
        $parameters .= 'PageSize=0';

        // Make sure $restapi is urlencoded.
        $parts = explode('/', $restapi);
        $parts = array_map('rawurlencode', $parts);
        $restapi = implode('/', $parts);

        // For benchmarking, use the first part of the API call as the key.
        $this->build_profile($parts[0], 'start');

        $this->lastquery = $this->baseurl . '/sis/api/v1/' . $restapi . '?' . $parameters;
        $response = $this->get_response($this->lastquery);
        if (is_array($response) && !empty($ignoreextradata)) {
            $response = reset($response);
        }

        $this->build_profile($parts[0], 'stop');

        return $response;
    }

    /**
     * Validates parameters and then builds results.
     *
     * @params array $params
     * @param boolean $debug    If true, then will output debugging statements.
     *
     * @return mixed
     */
    public function run($params, $debug = false) {
        $this->debugging = $debug;
        $validatedparams = $this->validate_parameters($params);
        
        $results = $this->build_result($validatedparams);

        // Close any open cURL handlers.
        $this->close_curl();

        // If debugging, display profiling data.
        if ($debug) {
            foreach ($this->profiling as $apicall => $data) {
                $this->debug(sprintf('API call: %s, time it took: %s, ' .
                        'times called: %d, average time per call: %.4f',
                        $apicall, format_time(number_format($data['time'], 2)), $data['count'],
                        $data['time'] / $data['count']));

            }
        }

        return $results;
    }

    /**
     * Validates parameters against expected parameters.
     *
     * Make sure to call this at the start of the run() method.
     *
     * @param array $params
     * @return array
     */
    protected function validate_parameters($params) {
        $expectedparams = $this->get_parameters();
        if (empty($expectedparams)) {
            return null;
        }

        // Validate parameters, expecting every parameter to be checkable via
        // ucla_validator().
        $validparameters = [];
        foreach ($expectedparams as $expectedparam) {
            if (isset($params[$expectedparam])) {
                if (ucla_validator($expectedparam, $params[$expectedparam])) {
                    $validparameters[$expectedparam] = $params[$expectedparam];
                } else {
                    throw new \Exception(sprintf('ESB Error: Invalid param %s = %s',
                            $expectedparam, $params[$expectedparam]));
                }
            } else {
                throw new \Exception('ESB Error: Missing required parameter ' .
                        $expectedparam);
            }
        }
        return $validparameters;
    }
}
