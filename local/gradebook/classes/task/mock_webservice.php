<?php
// This file is part of the UCLA gradebook customizations plugin for Moodle - http://moodle.org/
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
 * Fixtures for gradebook task tests.
 *
 * @package    local_gradebook
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_gradebook\task;
defined('MOODLE_INTERNAL') || die();

/**
 * Mocks the webservice call to MyUCLA.
 *
 * @package    local_gradebook
 * @category   phpunit
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mock_webservice {
    /**
     * @var array   Whatever was last sent to this web service.
     */
    public $lastparams = null;

    /**
     * @var stdClass    Should be set to return desired result for given
     *                  webservice call.
     */
    public $returnresult = null;

    /**
     * @var Exception   If set, when web service call is made it will throw
     *                  set exception instead.
     */
    public $thrownexception = null;

    /**
     * Sets up the return results for the different web service calls.
     */
    public function __construct() {
        $result             = new \stdClass();
        $result->status     = 1; // Start with success.
        $result->message    = '';

        $this->returnresult = new \stdClass();
        $this->returnresult->moodleGradeModifyResult    = clone($result);
        $this->returnresult->moodleItemModifyResult     = clone($result);
    }

    /**
     * Mocks the MyUCLA webservice call moodleGradeModify.
     *
     * To set what this call should return set the $moodleGradeModifyResult class
     * variable.
     *
     * To make this call simulate a failure, then set the $thrownException class
     * variable.
     *
     * @param array $params
     * @return stdClass     Object containing a status and message.
     * @throws Exception    May be SoapFault or general Exception.
     */
    public function moodleGradeModify($params) {
        $this->lastparams = $params;
        if (!empty($this->thrownexception)) {
            throw $this->thrownexception;
        }
        return $this->returnresult;
    }

    /**
     * Mocks the MyUCLA webservice call moodleItemModify.
     *
     * To set what this call should return set the $moodleItemModifyResult class
     * variable.
     *
     * To make this call simulate a failure, then set the $thrownException class
     * variable.
     *
     * @param array $params
     * @return stdClass     Object containing a status and message.
     * @throws Exception    May be SoapFault or general Exception.
     */
    public function moodleItemModify($params) {
        $this->lastparams = $params;
        if (!empty($this->thrownexception)) {
            throw $this->thrownexception;
        }
        return $this->returnresult;
    }
}
