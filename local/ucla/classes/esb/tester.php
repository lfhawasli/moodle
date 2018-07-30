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
class tester extends base {
    /**
     * Calls the Infrastructure/VerifyConnectivity web service.
     *
     * @return array    Returns array with results.
     */
    public function run() {
        $message = $this->query('Infrastructure/VerifyConnectivity', ['anyText' => date('r')]);
        // Also append message about the token used.
        $message .= '<br/>' . get_string('esbtoken', 'local_ucla', $this->get_token());
        return $message;
    }
}
