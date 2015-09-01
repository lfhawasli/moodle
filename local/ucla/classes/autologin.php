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
 * Class to handle autologin.
 *
 * @package local_ucla
 * @copyright 2015 UC Regents
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class file.
 *
 * @package local_ucla
 * @copyright 2015 UC Regents
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_ucla_autologin {

    /**
     * Constant used to indicate that we are trying to attempt autologin
     */
    const ATTEMPT = 'attempt autologin';

    /**
     * Constant used to indicate that user is returning from unsuccessful
     * isPassive login attempt or coming back from a login attempt.
     */
    const FAILED = 'failed autologin';

    /**
     * Set autologin to ignore/failed.
     */
    public static function clear() {
        global $CFG;
        // Do not activate for CLI_SCRIPT or unit tests.
        if ((defined('CLI_SCRIPT') && CLI_SCRIPT) || PHPUNIT_TEST) {
            return;
        }
        // When user logs out, they don't want to be autologged back in.
        self::setcookie(self::FAILED);

        if (!empty($CFG->debugautologin)) {
            echo 'cleared cookie';
        }
    }

    /**
     * Detects if we need to redirect user to Shibboleth to be automatically
     * logged in.
     */
    public static function detect() {
        global $CFG, $FULLME, $SESSION;

        // Have kill switch in case we have problems with autologin.
        if (!empty($CFG->disableautologin)) {
            return;
        }

        // Do not activate for CLI_SCRIPT or unit tests.
        if ((defined('CLI_SCRIPT') && CLI_SCRIPT) || PHPUNIT_TEST) {
            return;
        }

        // Check if they are not logged in.
        if (isloggedin() && !isguestuser()) {
            return;
        }

        // Check if Shibboleth is enabled.
        if (!is_enabled_auth('shibboleth')) {
            return;
        }

        // If there is no cookie, then let's set one and try to autologin.
        if (!isset($_COOKIE["_autologin_check"])) {
            self::setcookie(self::ATTEMPT);
            $message = '';
            if (!empty($CFG->debugautologin)) {
                $message = 'Attempting passive login with target = ' . $FULLME;
            }

            // Redirect to Shibboleth with isPassive.
            $SESSION->wantsurl = $FULLME;
            redirect('/Shibboleth.sso/Login?isPassive=true&target=' . urlencode($FULLME), $message);
        } else {
            // Check if we are returning from a non-failed isPassive login.
            if ($_COOKIE["_autologin_check"] == self::ATTEMPT) {
                // Don't keep on trying to attempt autologin.
                self::setcookie(self::FAILED);

                $message = '';
                if (!empty($CFG->debugautologin)) {
                    $message = 'Detected shib session';
                }

                // Complete their login using the Shibboleth plugin.
                redirect(new moodle_url('/auth/shibboleth/index.php'), $message);
            }
            // Else user does not have active shib login.
        }
    }

    /**
     * Sets autologin cookie.
     *
     * @param string $status
     */
    public static function setcookie($status) {
        global $CFG;

        // Set cookie to expire when browser is closed.
        setcookie('_autologin_check', $status, 0,
                $CFG->sessioncookiepath, $CFG->sessioncookiedomain,
                $CFG->cookiesecure, $CFG->cookiehttponly);

        if (!empty($CFG->debugautologin)) {
            echo 'Set cookie to ' . $status;
        }
    }
}
