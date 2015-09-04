<?php
// This file is part of the local UCLA plugin for Moodle - http://moodle.org/
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
 * Handles Shibboleth login errors.
 *
 * @package    local_ucla
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../../config.php");

// If user is coming to this page, they most likely were attempted to be auto
// logged in.
// If the errorType is "opensaml::FatalProfileException", then isPassive login
// failed, so we just redirect user back to where they were.
// 
// See https://wiki.shibboleth.net/confluence/display/SHIB2/isPassive for info.

$errortype = optional_param('errorType', null, PARAM_TEXT);
$requesturl = optional_param('requestURL', null, PARAM_LOCALURL);

// Set cookie to failed autologin attempt.
local_ucla_autologin::clear();

if (!empty($CFG->debugautologin)) {
    print_object($_GET);
}

if (isset($errortype) && strpos($errortype, 'FatalProfileException') !== false) {
    // Autologin failed, so redirect user to the page they were trying to login.
    $redirecturl = null;
    if (!empty($SESSION->wantsurl)) {
        // Maybe URL is in their session.
        $redirecturl = $SESSION->wantsurl;
    } else {
        // Just direct user to homepage.
        $redirecturl = $CFG->wwwroot;
    }

    $message = '';
    if (!empty($CFG->debugautologin)) {
        $message = 'Failed isPassive login, redirecting';
    }

    redirect($redirecturl, $message);

} else {
    // User got here because of some other error!
    error_log('errshiberror: ' . $FULLME);
    // This string should have a friendly message (see CCLE-653 - Better Shibboleth error message).
    print_error('shib_no_attributes_error', 'auth_shibboleth');
}
