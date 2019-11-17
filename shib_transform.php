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

global $DB;

$pnaction = $tempdispname = '';
$displayname = array();
if (isset($_SERVER['SHIBDISPLAYNAME'])) {
    $displayname = $this->get_first_string($_SERVER['SHIBDISPLAYNAME']);
}

if (!empty($result['alternatename'])) {
    // Store preferred name in first name field and store first name in alternate name field.
    $preferredname = $result['alternatename'];
    $result['alternatename'] = $result['firstname'];
    $result['firstname'] = $preferredname;

    // Handle suffix by appending it to last name.
    if (isset($_SERVER['SHIBUCLAPERSONNAMESUFFIX'])) {
        $suffix = $this->get_first_string($_SERVER['SHIBUCLAPERSONNAMESUFFIX']);
        $suffix = strtoupper($suffix);
        if ($suffix == "JR" || $suffix == "SR") {
            $result['lastname'] .= ', ' . $suffix;
        } else {
            $result['lastname'] .= ' ' . $suffix;
        }
    }
    $pnaction = "set alternatename was set";
} else if (!empty($displayname)) {
    $firstname  = $result['firstname'];
    $middlename = $result['middlename'];
    $lastname   = $result['lastname'];

    // Handle user info when display name is chosen.
    $tempdispname = trim($result['lastname'] . ", " . $result['firstname'] . " " . $result['middlename']);
    if (core_text::strtoupper($displayname) !== core_text::strtoupper($tempdispname)) {
        $formattedname  = format_displayname($displayname);
        $firstname      = $formattedname['firstname'];
        $lastname       = $formattedname['lastname'];
        $middlename     = '';
        $pnaction = "set fn, ln to displayName and cleared mn";
    } else {
        $pnaction = "displayName equals ln, fn mn. Name unchanged.";
    }

    // Check special cases.
    if (isset($result['idnumber']) && !empty($result['idnumber'])) {
        $specialcase = false;
        switch ($result['idnumber']) {
            case '900804617':
                $firstname = 'MI KYUNG';
                $middlename = '';
                $specialcase = true;
                break;
            case '902840840':
                $firstname = 'SHAREE BANTAD';
                $middlename = '';
                $specialcase = true;
                break;
        }

        if ($specialcase) {
            $pnaction = 'Special case found';
        }
    }

    // Set names.
    $result['firstname'] = $firstname;
    $result['middlename'] = $middlename;
    $result['lastname'] = $lastname;
}

// CCLE-8913 - Notify when user changes email to existing email address.
if (!empty($result['email'])) {    
    $currentuser = $DB->get_record('user', ['username' => $result['username']]);
    if ($currentuser && $currentuser->email != $result['email']) {
        $exists = $DB->record_exists('user', ['email' => $result['email']]);
        if ($exists) {
            $emailaction = sprintf('Email %s was duplicated; keeping %s', $result['email'], $currentuser->email);
            // Set alert.
            set_user_preference('show_dupemail_warning', $result['email'], $currentuser);
            // Set email back to current.
            $result['email'] = $currentuser->email;
        } else {
            // Remove any previous warning.
            unset_user_preference('show_dupemail_warning', $currentuser);
            $emailaction = sprintf('Email changing from %s to %s', $currentuser->email, $result['email']);
        }
    } else {
        $emailaction = 'Email unchanged from ' . $result['email'];
    }
} else {
    $emailaction = 'Email was empty';
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
            . "\n result (ln,fn,mn) = " . trim('|' . $result['lastname'] . '|' . $result['firstname'] . '|' . $result['middlename'] . '|')
            . "\n Action = " . $pnaction
            . "\n Email action = " . $emailaction
            . "\n", FILE_APPEND);
}

$result['institution'] = str_replace("urn:mace:incommon:", "", $result['institution']);
