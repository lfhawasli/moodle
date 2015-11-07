<?php
// This file is part of Moodle - http://moodle.org/
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
 * Does extra processing for Shibboleth variables.
 *
 * NOTE: This file is being included by auth/shibboleth/auth.php: get_userinfo
 * so there already exists an $result array.
 *
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/config.php');
require_once($CFG->dirroot . '/local/ucla/lib.php');

// Handle suffix by appending it to last name.
if (isset($_SERVER['HTTP_SHIB_UCLAPERSONNAMESUFFIX'])) {
    $suffix = $this->get_first_string(
        $_SERVER['HTTP_SHIB_UCLAPERSONNAMESUFFIX']
    );
    $result['lastname'] .= ' ' . $suffix;
}

$result['institution'] = str_replace("urn:mace:incommon:","", $result['institution']);
