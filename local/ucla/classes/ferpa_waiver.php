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
     * Checks if user needs to sign FERPA waiver for the given LTI tool.
     *
     * @param context $context
     * @param int $userid
     * @return boolean  If true, then user needs to sign waiver.
     */
    static public function check(context $context, $userid) {
        global $DB;
        $checkwaiver = false;
        // Don't bother checking if user is not logged in.
        if (!isloggedin() || isguestuser()) {
            return false;
        }

        // There isn't a simple way to get the data from the 'mdl_lti' table
        // using core APIs, so using direct query.
        $sql = "SELECT l.toolurl, l.typeid, lt.id AS ispreconfigured
                  FROM {context} cxt
                  JOIN {course_modules} cm ON (cxt.instanceid=cm.id AND cxt.contextlevel=?)
                  JOIN {lti} l ON (cm.instance=l.id)
             LEFT JOIN {lti_types} lt ON (l.typeid=lt.id AND lt.course=?)
                 WHERE cxt.id=?";
        $ltitool = $DB->get_record_sql($sql, array(CONTEXT_MODULE, SITEID, $context->id));

        // Ignore resources that are from UCLA or from Pearson MyLabMastering.
        if (strpos($ltitool->toolurl, 'ucla.edu') === false &&
                strpos($ltitool->toolurl, 'pearsoncmg.com') === false) {
            // Do not need to sign waiver for tools configured at site level.
            
            // See if LTI tool is setup as a preconfigured tool.
            if (empty($ltitool->ispreconfigured)) {
                // Try to find tool by URL.
                $tool = lti_get_tool_by_url_match($ltitool->toolurl, SITEID);
                if (empty($tool)) {
                    $checkwaiver = true;
                }                
            }            
        }

        // This is a resource that needs a waiver.
        if (!empty($checkwaiver)) {
            // See if user needs to sign a waiver.
            $coursecontext = $context->get_course_context();
            if (mod_casa_privacy_waiver::check_user($coursecontext, $userid)) {
                // See if user signed the waiver.
                return !mod_casa_privacy_waiver::check_signed($coursecontext->instanceid,
                        $context->id, $userid);
            }
        }

        return false;
    }
}
