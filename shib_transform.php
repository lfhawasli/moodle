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

$pnaction = '';
$tempdispname = '';
$displayname = array();
if (isset($_SERVER['SHIBDISPLAYNAME'])) {
    $displayname = $this->get_first_string($_SERVER['SHIBDISPLAYNAME']);
}

if ($result['alternatename']) {
    // Handle suffix by appending it to last name.
    if (isset($_SERVER['SHIBUCLAPERSONNAMESUFFIX'])) {
        $suffix = $this->get_first_string(
                $_SERVER['SHIBUCLAPERSONNAMESUFFIX']
        );
        $result['lastname'] .= ' ' . $suffix;
    }
    $pnaction = "set alternateName to preferredName";
} else if (!empty($displayname)) {
    // Handle user info when display name is chosen.
    $tempdispname = trim($result['lastname'] . ", " . $result['firstname'] . " " . $result['middlename']);
    if (core_text::strtoupper($displayname) !== core_text::strtoupper($tempdispname)) {
        $formattedname = format_displayname($displayname);
        $result['firstname'] = $formattedname['firstname'];
        $result['lastname'] = $formattedname['lastname'];
        $result['middlename'] = '';
        $pnaction = "set fn, ln to displayName and cleared mn";
    } else {
        $pnaction = "displayName equals ln, fn mn. Name unchanged.";
    }
}

if (!empty($CFG->namingdebugging)) {
    $filename = $CFG->dataroot . "/namingdebugging.log";
    $time = @date('[d/M/Y:H:i:s]');

    file_put_contents($filename, "\n " . $time . " User info: "
            . "\n fn     = " . (isset($_SERVER['SHIBGIVENNAME']) ? $_SERVER['SHIBGIVENNAME'] : 'N/A')
            . "\n mn     = " . (isset($_SERVER['SHIBUCLAPERSONMIDDLENAME']) ? $_SERVER['SHIBUCLAPERSONMIDDLENAME'] : 'N/A')
            . "\n ln     = " . (isset($_SERVER['SHIBSN']) ? $_SERVER['SHIBSN'] : 'N/A')
            . "\n suffix = " . (!empty($suffix) ? $suffix : 'N/A')
            . "\n pn     = " . (isset($_SERVER['SHIBEDUPERSONNICKNAME']) ? $_SERVER['SHIBEDUPERSONNICKNAME'] : 'N/A')
            . "\n dn     = " . (!empty($displayname) ? $displayname : 'N/A')
            . "\n tempdn = " . (!empty($tempdispname) ? $tempdispname : 'N/A')
            . "\n Action = " . $pnaction
            . "\n", FILE_APPEND);
}

$result['institution'] = str_replace("urn:mace:incommon:", "", $result['institution']);
