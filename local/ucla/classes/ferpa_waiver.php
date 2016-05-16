<?php
// This file is part of the UCLA local plugin for Moodle - http://moodle.org/
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
 * Class to handle checking and signing of the FERPA waiver.
 *
 * @package     local_ucla
 * @copyright   2014 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class file.
 *
 * @package     local_ucla
 * @copyright   2014 UC Regents
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_ucla_ferpa_waiver {
    /**
     * Checks if user needs to sign FERPA waiver.
     *
     * We need this to be very fast with as few database calls as possible,
     * because this method is called for every page load. We will try to do all
     * the checks before we query for waiver information with existing data.
     *
     * @param context $context
     * @param moodle_url $url
     * @param int $userid
     * @return boolean  If true, then user needs to sign waiver.
     */
    static public function check(context $context, moodle_url $url, $userid) {
        global $DB;
        $checkwaiver = false;

        // Don't bother checking if user is not logged in.
        if (!isloggedin() || isguestuser()) {
            return false;
        }

        // Check URL if it is a module/block that needs a FERPA waiver.
        $path = $url->get_path();
        $type = $names = null;
        if ($context->contextlevel == CONTEXT_MODULE) {
            $names = array('elluminate', 'kalvidassign', 'kalvidpres', 'lti',
                'mylabmastering', 'turnitintool');
            $type = 'mod';
            $page = 'view.php';
        } else if ($context->contextlevel == CONTEXT_BLOCK) {
            $names = array('mhaairs', 'mylabmastering');
            $type = 'blocks';
            // Need to protect all pages, since there isn't a single access
            // point for blocks.
            $page = '';
        }
        if (!empty($names)) {
            foreach ($names as $name) {
                if (strpos('/'.$path, '/'.$type.'/'.$name.'/'.$page) !== false) {
                    // If page is an LTI resource, ignore resources that are
                    // from UCLA.
                    if ($type == 'mod' && $name == 'lti') {
                        // There isn't a simple way to get the data from the
                        // 'mdl_lti' table using core APIs, so using direct
                        // query.
                        $sql = "SELECT l.toolurl, l.typeid
                                  FROM {context} cxt
                                  JOIN {course_modules} cm ON (cxt.instanceid=cm.id)
                                  JOIN {lti} l ON (cm.instance=l.id)
                                 WHERE cxt.id=?";
                        $ltitool = $DB->get_record_sql($sql, array($context->id));
                        if (strpos($ltitool->toolurl, 'ucla.edu') !== false) {
                            break;
                        }

                        // Do not need to sign waiver for tools configured at site level.
                        if ($ltitool->typeid != 0) {
                            // If tool was configured at site level, then typeid will be nonzero
                            // and the course will be set to SITEID. Also check that the configuration is active (state = 1).
                            // i.e. Check that the tool still has a valid configuration at site level.
                            $ltitype = $DB->get_record('lti_types', array('id' => $ltitool->typeid), 'state, course');
                            if ($ltitype && ($ltitype->state == 1) && ($ltitype->course == SITEID)) {
                                break;
                            }
                        }
                    }
                    $checkwaiver = true;
                    break;
                }
            }
        }

        // Is a page that needs a waiver.
        if (!empty($checkwaiver)) {
            // See if user needs to sign a waiver.
            $coursecontext = $context->get_course_context();
            if (mod_casa_privacy_waiver::check_user($coursecontext, $userid)) {
                // See if user signed the waiver.
                return !mod_casa_privacy_waiver::check_signed($coursecontext->instanceid, $context->id, $userid);
            }
        }

        return false;
    }

    /**
     * Returns link to get to the FERPA waiver.
     *
     * @param context $context
     * @param moodle_url $url   The URL that a user was on, so that we can
     *                          redirect them back
     * return moodle_url
     */
    static public function get_link(context $context, moodle_url $url) {
        return new moodle_url('/local/ucla/ferpawaiver.php',
                array('contextid'   => $context->id,
                      'return'      => $url->out_as_local_url()));
    }
}
