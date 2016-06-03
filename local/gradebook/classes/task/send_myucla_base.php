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
 * Ad-hoc task base class for sending grade information to MyUCLA.
 *
 * @package    local_gradebook
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_gradebook\task;
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../../ucla/lib.php');

/**
 * Ad-hoc task base class for sending grade information to MyUCLA.
 *
 * @package    local_gradebook
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class send_myucla_base extends \core\task\adhoc_task {

    /**
     * @var array Stores decoded json object from $this->customdata.
     */
    private $gradeinfo = null;

    /**
     * @var SoapClient
     */
    private static $webserviceclient = null;

    /**
     * @var int Maximum length that a comment/feedback will be sent to MyUCLA.
     */
    const MAXTEXTLENGTH = 7900;

    /**
     * @var string  Used in send_to_myucla() to determine which webservice call
     *              to make.
     */
    const WEBSERVICECALL = null;

    /**
     * Makes sure that sub-classes define certain class constants.
     *
     * Need to implement constant abstract variables. The idea for this method
     * is from: http://stackoverflow.com/a/7635076/6001
     *
     * @throws Exception
     */
    public final function __construct() {
        // NOTE: Need to use static instead of self, because of this:
        // http://stackoverflow.com/a/4404561/6001.
        if (is_null(static::WEBSERVICECALL)) {
            throw new \Exception(get_class($this).' must have WEBSERVICECALL defined.');
        }
    }

    /**
     * Executes task and makes webservice call to MyUCLA.
     *
     * @throws Exception    On any errors.
     */
    public function execute() {
        global $CFG;

        $gradeinfo = $this->get_custom_data();
        if (!$this->should_send_to_myucla($gradeinfo->courseid, $gradeinfo->itemtype)) {
            // We didn't need to process this request.
            // TODO: Add logging, because task shouldn't have gotten this far.
            return;
        }

        $courses = $this->get_courses_info();
        if (empty($courses)) {
            // We couldn't find course to associate this grade with.
            // TODO: Add logging.
            return;
        }

        // May be processing multiple course info for cross-listed courses.
        $client = $this->get_webservice_client();
        foreach ($courses as $courseinfo) {
            $parameters = $this->format_myucla_parameters($courseinfo);
            try {
                $result = $client->{static::WEBSERVICECALL}($parameters);
                // Return status is returned as WEBSERVICECALL . 'Result', for
                // example moodleItemModify and moodleItemModifyResult.
                if (!$result->{static::WEBSERVICECALL.'Result'}->status) {
                    throw new \Exception($result->{static::WEBSERVICECALL.'Result'}->message);
                }

                // Success is logged conditionally.
                if (!empty($CFG->gradebook_log_success)) {
                    // Include only selective information from $parameters.
                    $message = $this->myucla_parameters_to_string($parameters);
                    error_log(sprintf('SUCCESS: Send data to %s webservice: %s',
                            static::WEBSERVICECALL, $message));
                }
            } catch (\SoapFault $e) {
                error_log(sprintf('ERROR: SoapFault sending data to %s webservice: [%s] %s',
                        static::WEBSERVICECALL, $e->faultcode, $e->getMessage()));
                throw $e;
            } catch (\Exception $e) {
                error_log(sprintf('ERROR: Exception sending data to %s webservice: %s',
                        static::WEBSERVICECALL, $e->getMessage()));
                throw $e;
            }
        }
    }

    /**
     * Create the array that will be sent to the MyUCLA webservice.
     *
     * @param stdClass $courseinfo   Necessary course information to generate
     *                             parameters for MyUCLA webservice call.
     * @return array    Returns an array used to create the SOAP message that
     *                  will be sent to MyUCLA.
     */
    public abstract function format_myucla_parameters($courseinfo);

    /**
     * Should return the necessary information for courses to be used in
     * creating the MyUCLA parameters.
     *
     * @return array    Returns array of database objects containing courseinfo
     *                  needed to produce parameters for the MyUCLA webservice.
     */
    public abstract function get_courses_info();

    /**
     * Overriding parent's method to provide some caching, since this is called
     * more than once per session.
     * @return mixed (anything that can be handled by json_decode).
     */
    public function get_custom_data() {
        if (empty($this->gradeinfo)) {
            $this->gradeinfo = parent::get_custom_data();
        }
        return $this->gradeinfo;
    }

    /**
     * Return the ID of the last record in the *_history table.
     * Also sends back the userid of the user to last modify the grade
     *
     * @param grade_object $gradeobject
     * @return object   Returning transactionid from the appropiate grade
     *                  history table and user info on who made the transaction.
     */
    public function get_transactioninfo($gradeobject) {
        global $DB, $USER;

        if (get_parent_class($gradeobject) != 'grade_object') {
            throw new \Exception(get_class($gradeobject).' must a grade_object.');
        }

        // Assumption: There should always be a record returned.
        $transactionrecs = $DB->get_records($gradeobject->table . '_history',
                array('oldid' => $gradeobject->id), 'id DESC', 'id, loggeduser', 0, 1);
        $transactionrec = array_shift($transactionrecs);

        // Get user record for whoever made grade change.
        $userrec = null;
        if ($transactionrec->loggeduser == $USER->id) {
            // Try to save a database query by seeing if the user who made the
            // transaction is the logged in user. It should usually be the case.
            $userrec = $USER;
        } else if (!empty($transactionrec->loggeduser)) {
            // Going to fetch the user record from the DB.
            $userrec = $DB->get_record('user',
                    array('id' => $transactionrec->loggeduser));
        }

        if (empty($userrec)) {
            // No user found, so just use admin user.
            $userrec = get_admin();
        }

        // We are only interested in certain fields from the user table.
        $transactionuser = new \stdClass();
        $transactionuser->name      = fullname($userrec);
        $transactionuser->idnumber  = empty($userrec->idnumber) ?
                '000000000' : $userrec->idnumber;
        $transactionuser->lastip    = empty($userrec->lastip) ?
                '0.0.0.0' : $userrec->lastip;

        // Store history table id.
        $transactionuser->transactionid = $transactionrec->id;

        return $transactionuser;
    }

    /**
     * Gets the singleton instance of a SOAP connection to MyUCLA.
     *
     * @return SoapClient   The connection to MyUCLA.
     */
    public function get_webservice_client() {
        if (self::$webserviceclient === null) {
            global $CFG;
            // Fixing coding issues between UTF8 and Windows encoding.
            // See http://stackoverflow.com/a/12551101/6001 for more info.
            $settings = array('exceptions' => true, 'encoding' => 'ISO-8859-1');

            // Careful - can raise exceptions.
            self::$webserviceclient = new \SoapClient($CFG->gradebook_webservice, $settings);
        }
        return self::$webserviceclient;
    }

    /**
     * Convert MyUCLA parameters array to a single line string for logging
     * purposes.
     *
     * @param array $parameters
     */
    public function myucla_parameters_to_string($parameters) {
        $retval = '';

        // Remove unnecessary data.
        unset($parameters['mInstance']);
        unset($parameters['mTransaction']);

        // Next, squash array into a string.
        foreach ($parameters as $key => $array) {
            if (isset($array[0]) && is_array($array[0])) {
                // Might be a sub-array.
                $array = reset($array);
            }
            $retval .= $key . '::' . implode(':', $array);
        }

        return $retval;
    }

    /**
     * Given a grade object (grade_item or grade_grade) get the necessary
     * data to later sent the information to MyUCLA and store it.
     *
     * @param stdClass $gradeobject   Should be a class derived from grade_object.
     * @return boolean  Returns false if given grade object shouldn't be sent.
     */
    public abstract function set_gradeinfo($gradeobject);

    /**
     * Checks if we should send given grade information to MyUCLA.
     *
     * We don't send grades if:
     *  1) Server isn't configured properly.
     *  2) Grade object belongs to a course or category.
     *  3) Course isn't listed at the Registrar.
     *
     * @param int $courseid
     * @param string $itemtype
     * @return boolean
     */
    public function should_send_to_myucla($courseid, $itemtype) {
        global $CFG;
        // Find reasons not to process grade object.
        if (empty($CFG->gradebook_send_updates) || empty($CFG->gradebook_id) ||
                empty($CFG->gradebook_password)) {
            // This Moodle instance isn't setup to send grades.
            return false;
        } else if ($itemtype === 'course' || $itemtype === 'category') {
            // Do not process course/category items.
            return false;
        } else if (is_collab_site($courseid)) {
            // We only send data for courses at the Registrar.
            return false;
        }
        return true;
    }

    /**
     * Trims long text fields and removes invalid XML characters.
     *
     * ASP.NET webservices use XML 1.0 which restricts the character set allowed
     * to the following chars:
     *  #x9 | #xA | #xD | x20-#xD7FF | xE000-#xFFFD | x10000-#x10FFFF
     *  (Source: http://www.w3.org/TR/REC-xml/#charsets)
     *
     * Function is from: http://stackoverflow.com/a/3466049/6001
     *
     * @param string $value
     * @return string
     */
    public function trim_and_strip($value) {
        $ret = '';
        $current = null;
        if (empty($value)) {
            return $ret;
        }

        // Shorten super long text with comment saying that complete text is
        // on website.
        $value = trim($value);  // Strip whitespace.
        $value = clean_param($value, PARAM_NOTAGS); // Also remove HTML tags.
        if (\core_text::strlen($value) > self::MAXTEXTLENGTH) {
            $value = \core_text::substr($value, 0,
                                    self::MAXTEXTLENGTH) .
                    get_string('continuecomments', 'local_gradebook');
        }

        // Strip out invalid characters that can break XML parsing for ASP.NET
        // webservices used by MyUCLA.
        $length = \core_text::strlen($value);
        for ($i = 0; $i < $length; $i++) {
            $current = ord($value{$i});
            if (($current == 0x9) ||
                    ($current == 0xA) ||
                    ($current == 0xD) ||
                    (($current >= 0x20) && ($current <= 0xD7FF)) ||
                    (($current >= 0xE000) && ($current <= 0xFFFD)) ||
                    (($current >= 0x10000) && ($current <= 0x10FFFF))) {
                $ret .= chr($current);
            } else {
                $ret .= ' ';
            }
        }
        return $ret;
    }
}
