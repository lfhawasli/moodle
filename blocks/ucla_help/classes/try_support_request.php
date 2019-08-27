<?php
// This file is part of the UCLA Help plugin for Moodle - http://moodle.org/
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
 * Adhoc task block_ucla_help_try_support_request.
 *
 * @package    block_ucla_help
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/../ucla_help_lib.php');

/**
 * Adhoc task block_ucla_help_try_support_request.
 *
 * @package    block_ucla_help
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_ucla_help_try_support_request extends \core\task\adhoc_task {

    /**
     * Creates a JIRA issue for a support request.
     *
     * @throws Exception    if issue creation is unsuccessful
     */
    public function execute() {
        $cd = $this->get_custom_data();
        $jiraendpoint = get_config('block_ucla_help', 'jira_endpoint');

        // Try to create the issue.
        $jiraresult = send_jira_request($jiraendpoint, true, array('Content-type: application/json'),
                json_encode($cd->params));
        $decodedresult = json_decode($jiraresult, true);
        if (!empty($decodedresult)) {
            // Issue is successfully created only when the issue key is returned. Otherwise, error.
            if (!empty($decodedresult['key'])) {
                $issueid = $decodedresult['key'];
            } else {
                throw new Exception('JIRA issue creation error - ' . implode(', ', $decodedresult['errors']));
            }
        } else {
            throw new Exception('JIRA request failed - issue creation unsuccessful');
        }

        // If there is an attachment, then attach it to the newly created issue.
        if ($cd->attachmentfile != null) {
            $url = $jiraendpoint . "/$issueid/attachments";
            $headers = array('Content-Type: multipart/form-data', 'X-Atlassian-Token: nocheck');
            $data = array(                 
                'file'=> curl_file_create($cd->attachmentfile),                 
                'filename'=> $cd->attachmentname             
            );
            $jiraresult = send_jira_request($url, true, $headers, $data);
            if ($jiraresult === false) {
                // If attachment fails, comment a note on the JIRA ticket for reference.
                $params = array('body'  => "The reporter attempted to attach a file, but that JIRA request failed.");
                send_jira_request($jiraendpoint . "/{$issueid}/comment", true,
                        array('Content-type: application/json'), json_encode($params));
            }
        }
    }
}
