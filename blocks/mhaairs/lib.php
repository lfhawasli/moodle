<?php
/**
 * ZEND Web Services Plugin for block MHAAIRS
 *
 * @package    block
 * @subpackage mhaairs
 * @copyright  2013 Moodlerooms inc.
 * @author     Teresa Hardy <thardy@moodlerooms.com>
 */

defined('MOODLE_INTERNAL') || die();

/**
 *
 * @param string $linktype
 * @return bool|array|string
 */
function block_mhaairs_getlinks($linktype) {
    global $COURSE, $PAGE, $USER, $CFG;

    if (empty($CFG->block_mhaairs_customer_number)) { // Do we have number?
        return false;
    }

    require($CFG->dirroot.'/local/mr/bootstrap.php');

    mr_bootstrap::zend();

    require_once('Zend/Json.php');
    require_once('Zend/Oauth/Consumer.php');
    require_once('Zend/Oauth/Client.php');

    $endpoint = 'GetHelpLinks';
    if ($linktype == 'services') {
        $endpoint = 'GetCustomerAvailableTools';
    }

    $baseurl = 'http://mhaairs.tegrity.com/v1/Config/';
    $url = $baseurl.$CFG->block_mhaairs_customer_number.'/'.$endpoint;

    $aconfig = array(
            'requestScheme'   => Zend_Oauth::REQUEST_SCHEME_QUERYSTRING,
            'requestMethod'   => Zend_Oauth::GET,
            'signatureMethod' => 'HMAC-SHA1',
            'consumerKey'     => 'SSOConfig',
            'consumerSecret'  => '3DC9C384'
    );

    $result_data = false;
    try {
        $tacc = new Zend_Oauth_Token_Access();
        $client = $tacc->getHttpClient($aconfig);
        $client->setUri($url);
        $client->setMethod(Zend_Http_Client::GET);

        $response    = $client->request();
        $result_data = $response->getBody();

        // Get content type.
        $result_type = $response->getHeader('Content-Type');

        // Is this Json encoded data?
        if (stripos($result_type, 'application/json') !== false) {
            $result_data = Zend_Json::decode($result_data);
        }

        // By default set the status to the HTTP response status.
        $status      = $response->getStatus();
        $description = $response->getMessage();
        if ($status != 200) {
            $result_data = false;
        }
    } catch (Exception $e) {
        $status      = (string)$e->getCode();
        $description = $e->getMessage();
    }

    $logmsg = $status . ": " . $description;
    add_to_log(SITEID, 'mhaairs', 'block mhaairs_getlinks', '', $logmsg);

    return $result_data;
}

