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
 * The course creator class.
 *
 * This creates courses from the course requestor. It can be configured to
 * build a certain subset of courses.
 * TODO use local configuration vars instead of global
 *
 * @author Yangmun Choi
 * @package tool_uclacoursecreator
 * @copyright 2011 UCLA
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

defined('MOODLE_INTERNAL') || die();
define('RUN_CLI', php_sapi_name() == 'cli');

// Exception... TODO see if the gravity of these are used properly.
require_once($CFG->dirroot  . '/' . $CFG->admin . '/tool/uclacoursecreator/'
    . 'course_creator_exception.class.php');

// Require requestor.
require_once($CFG->dirroot  . '/' . $CFG->admin
    . '/tool/uclacourserequestor/lib.php');

// Require essential stuff...
require_once($CFG->dirroot . '/course/lib.php');

// Required for categories.
require_once($CFG->dirroot . '/course/classes/editcategory_form.php');

require_once($CFG->dirroot . '/local/ucla/lib.php');

/**
 * Course creator.
 * @copyright 2011 UCLA
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/
class uclacoursecreator {
    /** Stuff for Logging **/

    /** @var string $emaillog This is the huge text that will email to admins. */
    public $emaillog = '';

    /** @var file pointer $logfp Contains the log file pointer. */
    private $logfp;

    /**
     * @var string $outputpath The path for the output files as parsed
     * from the configuration, or defaulting to dataroot. Defined in
     * @see check_write().
     */
    public $outputpath;

    /** @var boolean $forcefail Used to force failure at the end. */
    private $forcefail = false;

    /** @var boolean $nosendmails Set to true to hide output? */
    private $nosendmails = false;

    /** @var string $dbid Private identifier for this cron task. */
    private $dbid;

    /** Variants for the cron **/
    /** @var string $cronterm The current term we are working for. */
    private $cronterm;

    /**
     * @var array $crontermcache Contains all the information for a current term
     *
     *  requests - the list of ucla_request_classes that are hosts.
     *  instructors - the set of instructors.
     *  profcodes - an Array in an Array of profcodes in a course.
     *  createdcourses - an Array of created course objects.
     *  activate - courses that we have to activate (all courses)
     *  urlinfo - the information to send to MyUCLA
     *  localemails - the local emails from the mdl_user tables
     *  course_mapper - an Array with SRS => mdl_course.id
     **/
    private $crontermcache = array();

    // Caches.
    /** @var array $categoriescache */
    private $categoriescache = array();
    /** @var array $coursedefaults */
    private $coursedefaults = array();

    /** Non Variants **/
    // These are just simple caches.
    /** @var string $shelldate */
    private $shelldate;
    /** @var string $fulldate */
    private $fulldate;

    // Terms to be used by course creator.
    /** @var array $termslist Array of terms */
    private $termslist = null;

    /** @var array subjtrans */
    private $subjtrans;

    /**
     * Email parsing cache.
     * @var array $parsedparam
     */
    private $parsedparam = array();

    // Email file location cache...
    /** @var string $emailprefix */
    private $emailprefix;
    /** @var string $emailsuffix */
    private $emailsuffix;

    /**
     * Email file default.
     * @var string $defaultemailfile
     */
    private $defaultemailfile;

    /**
     * These are the requests of courses that have been built.
     * @var array $builtrequests
     */
    private $builtrequests;

    /**
     * Cache of MyUCLA url updater.
     * @var stdClass $myuclaurlupdater
     */
    private $myuclaurlupdater;

    /**
     * How many courses to process at once (too many courses can lead to MySQL
     * database timeouts for long running processes).
     * @var int $maxcoursestoprocess
     */
    private $maxcoursestoprocess = 200;

    // Note: There are dynamically generated fields for this class, which
    // contain references to the enrollment object.

    /**
     * This is the course creator cron.
     *
     * @return boolean
     * @throws moodle_exception
     * @throws course_creator_exception
     */
    public function cron() {
        global $CFG;

        if (!$this->get_config('cron_enabled')) {
            // TODO Test if this logic works.
            static $cronuser;

            if (isset($cronuser)) {
                echo get_string('cron_quit_out', 'tool_uclacoursecreator');
                return false;
            }
        }

        $this->println('Running prerequisite checks...');

        // Make sure our email configurations are valid.
        $this->figure_email_vars();

        // Check that we have our registrar wrapper functions.
        ucla_require_registrar();

        // Validate that we have the registrar queries we need all wrapped up.
        $registrars = array('ccle_getclasses', 'ccle_courseinstructorsget');

        foreach ($registrars as $registrarclass) {
            if (!registrar_query::get_registrar_query($registrarclass)) {
                throw new moodle_exception('Missing ' . $regfilename);
            }
        }

        try {
            // This will check if we can write and lock.
            $this->handle_locking(true);
        } catch (course_creator_exception $e) {
            $this->debugln($e->getMessage());

            $this->finish_cron();
            return false;
        }

        // Check if we need to revert any failed requests.
        if ($this->get_config('revert_failed_cron')) {
            $this->revert_failed_requests();
        }

        // Run the course creator
        // Figure out what terms we're running.
        $termlist = $this->get_terms_creating();

        if (empty($termlist)) {
            $this->debugln('No terms to deal with, exiting...');

            $this->handle_locking(false);
            $this->finish_cron();

            return false;
        }

        $this->coursedefaults = get_config('moodlecourse');

        $this->println("---- Course Creator run at {$this->fulldate} "
            . "({$this->shelldate}) -----");

        foreach ($termlist as $workterm) {
            try {
                // Flush the cache.
                $this->start_cron_term($workterm);

                // Get stuff from course requestor.
                $retrieved = $this->retrieve_requests();

                // If we actually have entries to process.
                // Hooray a list.
                if ($retrieved) {
                    // Get official data from Registrar.
                    $this->requests_to_rci();
                    // Prepare the categories.
                    $this->prepare_categories();

                    // Create the courses.
                    $this->create_courses();

                    // Update the URLs for the Registrar.
                    $this->update_myucla_urls();

                    // Send emails to the instructors.
                    $this->send_emails();
                }

                if ($this->forcefail) {
                    throw new course_creator_exception(
                        '** Debugging break **'
                    );
                }

                // This will mark term as finished, insert entries into
                // ucla_reg_classinfo, mark the entries in
                // ucla_request_classes as done.
                $this->mark_cron_term(true);
            } catch (moodle_exception $e) {
                $this->debugln($e->getMessage());
                $this->debugln($e->debuginfo);

                // Since the things were not processed, try to revert the
                // changes in the requestor.
                $this->mark_cron_term(false);

                try {
                    throw $e;
                } catch (course_creator_exception $cce) {
                    // Do nothing, this is safe.
                    continue;
                }
            } catch (Exception $e) {
                // This is a much more serious error.
                $this->debugln($e->getMessage());

                throw $e;
            }
        }

        $this->handle_locking(false);

        $this->finish_cron();
    }

    /* ******************* */
    /* Debugging Functions */
    /* ******************* */

    /**
     * Will print to course creator log.
     * @param string $mesg The message.
     **/
    public function printl($mesg) {
        if (debugging()) {
            echo $mesg;
        }

        if (!isset($this->logfp)) {
            $this->logfp = $this->get_course_creator_log_fp();
        }

        fwrite($this->logfp, $mesg);
    }

    /**
     * Will print to the designated course creator log with newline appended.
     * @param string $mesg The message to print.
     **/
    public function println($mesg='') {
        if (RUN_CLI) {
            $this->printl($mesg . "\n");
        }
    }

    /**
     * Will output to the email log.
     * @param string $mesg The message to output.
     **/
    public function emailln($mesg='') {
        $this->emaillog = $this->emaillog . $mesg . "\n";
    }

    /**
     * Shortcut function to print to both the log and the email.
     * @param string $mesg The message to print to log and email.
     **/
    public function debugln($mesg='') {
        $this->println($mesg);
        $this->emailln($mesg);
    }

    /* ****************** */
    /* Accessor Functions */
    /* ****************** */

    /**
     * This returns the course creator log's file pointer.
     *
     * @see printl
     *
     * @return file pointer The log file pointer.
     * @throws course_creator_exception.
     **/
    public function get_course_creator_log_fp() {
        if (isset($this->logfp)) {
            return $this->logfp;
        }

        // This will set where all our files will be thrown.
        // Throws course_creator_exception.
        if (!isset($this->outputpath)) {
            $this->check_write();
        }

        $this->make_dbid();

        // Do we want to save this?
        $logfile = $this->outputpath . '/course_creator.'
            . $this->shelldate . '.' . $this->dbid . '.log';

        $this->logfp = fopen($logfile, 'a');
        $this->log_file = $logfile;

        return $this->logfp;
    }

    /**
     * Alias for get_registrar_translation().
     * @see get_registrar_translation()
     *
     * @param string $subjarea
     * @return array
     **/
    public function get_subj_area_translation($subjarea) {
        return $this->get_registrar_translation('ucla_reg_subjectarea',
            $subjarea, 'subjarea', 'subj_area_full');
    }

    /**
     * Alias for get_registrar_translation().
     * @see get_registrar_translation()
     *
     * @param string $division
     * @return array
     */
    public function get_division_translation($division) {
        return $this->get_registrar_translation('ucla_reg_division',
            $division, 'code', 'fullname');
    }

    /**
     * Returns the long name of the target if found.
     *
     * This is used for getting the long name for divisions and
     * subject areas.
     *
     * May alter the state of the object.
     *
     * @param string $table        The table to use.
     * @param string $target       The string we are translating.
     * @param string $fromfield   The field that we are using to search if the
     *                              target exists.
     * @param string $tofield     The field that we are going to return if we
     *                              find the target entry.
     * @return An array containing both the short and long name of the target.
     *         If a long name was not found, will return the short name again.
     */
    public function get_registrar_translation($table, $target, $fromfield,
            $tofield) {
        global $DB;

        if (!isset($this->reg_trans[$table]) || $this->reg_trans == null) {
            $this->reg_trans = array();

            $indexedsa = array();

            $translations = $DB->get_records($table);

            foreach ($translations as $translate) {
                $indexedsa[$translate->$fromfield] = $translate->$tofield;
            }

            $this->reg_trans[$table] = $indexedsa;
        }

        if (!isset($this->reg_trans[$table][$target])) {
            return array($target, $target);
        }

        // Format result nicely, not in all caps.
        return array($target,
                ucla_format_name($this->reg_trans[$table][$target], true));
    }

    /**
     * Returns the current term we are working for.
     *
     * @return string The term we are working on or false
     *     if the code has not been properly set.
     **/
    public function get_cron_term() {
        if (!isset($this->cronterm)) {
            return false;
        }

        return $this->cronterm;
    }

    /**
     * Returns an Array of terms to work for. If set_term_list()
     * is used, then it will return whatever has been set already.
     *
     * Wrapper for figure_terms().
     *
     * May change state of object.
     *
     * @return Array of terms
     **/
    public function get_terms_creating() {
        if (!isset($this->termslist)) {
            $this->figure_terms();
        }

        return $this->termslist;
    }

    /**
     * Return if the instructor should be emailed to people.
     *
     * @param mixed $instructor The instructor from ccle_CourseInstructorGet.
     * @param array $profcodeset A set of profcodes for the course.
     * @param string $subjarea The subject area of the request.
     * @return boolean If the instructor is viewable or not.
     **/
    public function get_viewable_status($instructor, $profcodeset, $subjarea) {
        if (function_exists('role_mapping')) {
            $printstr = $instructor->last_name_person . ' has '
                . $instructor->role . ' which is moodlerole ';

            try {
                $moodleroleid = role_mapping($instructor->role, $profcodeset,
                    $subjarea);
            } catch (moodle_exception $e) {
                $this->println($e->getMessage());

                // Safe.
                return false;
            }

            $printstr .= $moodleroleid . '. ';

            $reqcap = 'moodle/course:update';
            if (!isset($this->capcache)) {
                $caps = get_roles_with_capability($reqcap);
                $this->capcache = $caps;
            }

            // Do a local cache.
            $res = isset($this->capcache[$moodleroleid]);

            if ($res) {
                $printstr .= "Has $reqcap, will be emailed.";
            } else {
                $printstr .= "Does not have capability [$reqcap], "
                    . "not emailing.";
            }

            $this->println($printstr);

            return $res;
        } else {
            debugging('No role_mapping function exists.');
            return false;
        }
    }

    /**
     * Link to another external tool...yay.
     **/
    public function get_myucla_urlupdater() {
        global $CFG;

        if (empty($this->myuclaurlupdater)) {
            require_once($CFG->dirroot . '/' . $CFG->admin .
                    '/tool/myucla_url/myucla_urlupdater.class.php');
            $this->myuclaurlupdater = new myucla_urlupdater();
        }

        return $this->myuclaurlupdater;
    }

    /**
     * Return the current cache.
     * @return Array The current state of the cache.
     **/
    public function dump_cache() {
        return $this->crontermcache;
    }

    /* ******* */
    /* Closers */
    /* ******* */

    /**
     * This will close the file pointer.
     *
     * Will change the state of the function.
     **/
    public function close_log_file_pointer() {
        if (isset($this->logfp)) {
            fclose($this->logfp);

            unset($this->logfp);

            return true;
        }

        return false;
    }

    /* ********* */
    /* Modifiers */
    /* ********* */

    /**
     * Sets the current term, validates the term.
     *
     * @param string $term The term to set the current term to.
     * @return boolean If the term has been set.
     **/
    public function set_cron_term($term) {
        if (isset($this->cronterm)) {
            return false;
        }

        if (!$this->validate_term($term)) {
            return false;
        }

        $this->cronterm = $term;

        return true;
    }

    /**
     * Sets the terms to be run.
     *
     * Changes the state of the function.
     *
     * @param array $termslist The array of terms to run for.
     **/
    public function set_term_list($termslist) {
        if ($termslist != null && !empty($termslist)) {
            $this->termslist = $termslist;
        } else {
            $this->figure_terms();
        }
    }

    /**
     * Forces a fail condition to activate at the end of a term.
     *
     * @param boolean $bool Is force fail on
     **/
    public function set_autofail($bool) {
        $this->forcefail = $bool;
    }

    /**
     * Allows mails to be sent to requestors and instructors.
     * @param boolean $b  true = no mails sent, false = mails sent
     **/
    public function set_mailer($b) {
        $this->nosendmails = $b;
    }

    /**
     * If mail is allowed to be sent to requestors and instructors, send mail.
     */
    public function send_mails() {
        return !$this->nosendmails;
    }

    /* ************************* */
    /* Cron-Controller Functions */
    /* ************************* */

    /**
     * Set the term that we are working on.
     * Flush the current state of the course creator.
     * @param string $term The term to work for.
     **/
    public function start_cron_term($term) {
        global $DB;

        $this->flush_cron_term();

        if (!$this->set_cron_term($term)) {
            throw new course_creator_exception(
                'Could not set the term [' . $term . ']'
            );
        }

        // This will let both build and rebuild be built.
        $conds = array(
            'action' => UCLA_COURSE_TOBUILD,
            'term' => $term
        );

        $DB->set_field('ucla_request_classes', 'action',
            UCLA_COURSE_LOCKED, $conds);

        $this->println("-------- Starting $term ---------");
    }

    /**
     * Will remove all previously set term information.
     *
     * Will change the state of the object.
     **/
    public function flush_cron_term() {
        unset($this->crontermcache);

        $this->crontermcache = array();

        unset($this->cronterm);
    }

    /**
     * This will mark the entries as either finished or reset.
     *
     * @param boolean $done If we should mark the requests as done or reset them
     * TODO Instead of figuring out whether or not something is done or.
     *     not, have each individual subtask sort out stuff.
     *     This function should only have to logic for determining if
     *     a request failed or not, NOT how something failed and why.
     **/
    public function mark_cron_term($done) {
        global $DB;

        $thiscronterm = $this->get_cron_term();
        if (!$thiscronterm) {
            return false;
        }

        $this->println("Determining what happened for $thiscronterm...");

        // Do something with these requests.
        $actionids = array();

        // Save a config setting.
        $reverting = $this->get_config('revert_failed_cron');

        if (isset($this->crontermcache['created_courses'])) {
            $createdcourses =& $this->crontermcache['created_courses'];
        }

        if (!empty($this->crontermcache['term_rci'])) {
            $termrci =& $this->crontermcache['term_rci'];
        } else {
            $termrci = array();
        }

        // We're going to attempt to delete a course, and if we fail,
        // save it somewhere.
        if (!empty($this->crontermcache['requests'])) {
            $requests =& $this->crontermcache['requests'];
        } else {
            $requests = array();
        }

        // Some stats and counters.
        $numdeletedcourses = 0;
        $failed = 0;

        foreach ($requests as $reqkey => $request) {
            $rid = $request->id;

            $action = UCLA_COURSE_FAILED;
            if (isset($createdcourses[$reqkey])) {
                // The course got built, but the process was interrupted at
                // one point... but why?
                $course = $createdcourses[$reqkey];
                $courseid = $course->id;

                if ($done) {
                    $action = UCLA_COURSE_BUILT;

                    // Save the created course for the trigger later...
                    $this->build_courseids[$courseid] = $courseid;
                }
            } else if (isset(
                        $this->crontermcache['retry_requests'][$reqkey]
                    )) {
                $this->debugln(". $reqkey retry later");
                $action = UCLA_COURSE_TOBUILD;
            } else {
                $this->debugln("! Did not create a course for $reqkey");
            }

            if (empty($actionids[$action])) {
                $actionids[$action] = array();
            }

            $actionids[$action][$rid] = $rid;
        }

        if ($numdeletedcourses > 0) {
            // Update course count in categories.
            fix_course_sortorder();
        }

        // Mark these entries as failed.
        // So far, the only possible actions are 'rebuild' and 'failed'.
        foreach ($actionids as $action => $ids) {
            if (!empty($ids)) {
                list($sqlin, $params) = $DB->get_in_or_equal($ids);

                $sqlwhere = 'id ' . $sqlin;

                $DB->set_field_select('ucla_request_classes', 'action',
                    $action, $sqlwhere, $params);

                $this->debugln('Marked ' . count($params) . ' request(s) '
                    . 'as \'' . $action . '\'.');
            }
        }

        $this->println('-------- Finished ' . $this->get_cron_term()
            . ' --------');

        if ($done) {
            // Update this other table...
            $this->insert_term_rci();

            // Save this build for the event triggered when course creator
            // is finished.
            $this->save_created_courses();

            // Prep stuff for requestors.
            $this->queue_requestors();
        }

        return true;
    }

    /* ****************** */
    /* Cron Functionality */
    /* ****************** */

    /**
     *  Calculates the course requests for a particular term.
     *  Also maintains the crosslisted relationships.
     *
     *  Will alter the state of the object.
     **/
    public function retrieve_requests() {
        global $DB;

        $term = $this->get_cron_term();
        if (!$term) {
            throw new course_creator_exception('Term not set properly!');
        }

        $sqlparams = array(
            'action' => UCLA_COURSE_LOCKED,
            'term' => $term
        );

        $courserequests = $DB->get_records('ucla_request_classes',
            $sqlparams);

        if (empty($courserequests)) {
            return false;
        }

        $this->debugln('--- ' . count($courserequests) . ' requests for '
            . $term . ' ---');

        // Figure out crosslists and filter out faulty requests.
        foreach ($courserequests as $key => $courserequest) {
            $srs = trim($courserequest->srs);

            if (!ucla_validator('srs', $srs)) {
                $this->debugln('Faulty SRS: '
                    . $courserequest->course
                    . ' [' . $srs . ']');

                unset($courserequests[$key]);
                continue;
            }
        }

        // Re-index and save the the courses for the rest of the
        // cron run.
        $coursesets = array();
        foreach ($courserequests as $cr) {
            // Check if there are too many course requests to process.
            if (count($coursesets) > $this->maxcoursestoprocess) {
                break;
            }

            $setid = $cr->setid;
            if (empty($coursesets[$setid])) {
                $coursesets[$setid] = array();
            }

            $coursesets[$setid][make_idnumber($cr)] = $cr;
            $this->crontermcache['requests']
                [self::cron_requests_key($cr)] = $cr;
        }

        if (count($coursesets) >= $this->maxcoursestoprocess) {
            // Var $coursesets shouldn't have more than MAX_COURSES_TO_PROCESS
            // because of earlier loop break condition.
            $this->println('Too many course requests to process at once, ' .
                    'processing only the first ' . $this->maxcoursestoprocess);
        } else {
            $this->debugln('--- ' . count($coursesets) . ' courses requests found'
                . ' ---');
        }

        unset($courserequests);

        // Print out the requests that we're going to work with.
        foreach ($coursesets as $courseset) {
            $arrcourseset = array();

            foreach ($courseset as $key => $course) {
                $arrcourseset[$key] = get_object_vars($course);
            }

            $hostcoursekey = set_find_host_key($arrcourseset);
            $hostcourse = $courseset[$hostcoursekey];
            unset($courseset[$hostcoursekey]);

            $course = $this->trim_object($hostcourse);
            $this->debugln('-  '
                . self::courseinfoline($course));

            foreach ($courseset as $course) {
                $this->debugln(' + '
                    . self::courseinfoline($course));
            }
        }

        $this->println('  Finished processing requests.');

        return true;
    }

    /**
     * Convenience function.
     *
     * @param object $course
     **/
    static public function courseinfoline($course) {
        return $course->term . ' ' . $course->srs .  ' '
            . $course->department . ' ' . $course->course;
    }

    /**
     *  Convenience wrapper function to use a globalized-seek key for
     *  cron_term_cache['requests'].
     *
     * @param object $course
     **/
    static public function cron_requests_key($course) {
        return make_idnumber($course);
    }

    /**
     *  Trim the requests to term srs.
     *  This is only used for sending data to the Registrar stored procedures.
     *  Also converts these objects to Array();
     *
     *  Will change the state of the object.
     **/
    public function trim_requests() {
        if (empty($this->crontermcache['requests'])) {
            throw new course_creator_exception('Requests does not exist.');
        }

        $trimrequests = array();

        foreach ($this->crontermcache['requests'] as $request) {
            $term = $request->term;
            $srs = $request->srs;

            $key = $term . '-' . $srs;

            // CCLE-3044 - Now keys are important.
            $trimrequests[$key] = array('term' => $term, 'srs' => $srs);
        }

        $this->crontermcache['trim_requests'] = $trimrequests;
    }

    /**
     * Take the requests and get the data for the courses from the Registrar.
     *
     * @see registrar_ccle_getclasses.
     **/
    public function requests_to_rci() {
        if (!isset($this->crontermcache['trim_requests'])) {
            $this->trim_requests();
        }

        $tr = $this->crontermcache['trim_requests'];

        $requests =& $this->crontermcache['requests'];
        $this->crontermcache['retry_requests'] = array();

        // Run the Stored Procedure with the data.
        $rci = array();
        $this->println('  Fetching course information from registrar...');
        foreach ($tr as $k => $request) {
            $requestdata = registrar_query::run_registrar_query(
                    'ccle_getclasses', $request
                );

            if ($requestdata === false) {
                $this->debugln('!! No response from Registrar !!');
                $this->crontermcache['retry_requests'][$k] = true;
                continue;
            } else if (empty($requestdata)) {
                $this->debugln('Registrar did not find a course: ' . $k);
                continue;
            }

            foreach ($requestdata as $rqd) {
                $rqd = (object)$rqd;
                $rci[self::cron_requests_key($rqd)] = $rqd;
            }
        }

        $this->println('. ' . count($rci)
            . ' courses exist at Registrar.');

        $this->crontermcache['term_rci'] = $rci;
    }

    /**
     * Returns a sorted list of categories.
     *
     * When asking for $parent='none' it will return all the categories, regardless
     * of depth. Wheen asking for a specific parent, the default is to return
     * a "shallow" resultset. Pass false to $shallow and it will return all
     * the child categories as well.
     *
     * @param string $parent The parent category if any
     * @param string $sort the sortorder
     * @param bool   $shallow - set to false to get the children too
     * @return array of categories
     */
    public function get_categories($parent='none', $sort=null, $shallow=true) {
        global $DB;

        if ($sort === null) {
            $sort = 'ORDER BY cc.sortorder ASC';
        } else if ($sort !== '') {
            $sort = "ORDER BY $sort";
        }

        // @codingStandardsIgnoreStart
        // list($ccselect, $ccjoin) = context_instance_preload_sql('cc.id', CONTEXT_COURSECAT, 'ctx');
        // @codingStandardsIgnoreEnd
        $select = ", " . context_helper::get_preload_record_columns_sql('ctx');
        $join = "LEFT JOIN {context} ctx ON (ctx.instanceid = cc.id AND ctx.contextlevel = " . CONTEXT_COURSECAT . ")";

        if ($parent === 'none') {
            $sql = "SELECT cc.* $select
                      FROM {course_categories} cc
                    $join
                    $sort";
            $params = array();

        } else if ($shallow) {
            $sql = "SELECT cc.* $select
                      FROM {course_categories} cc
                    $join
                     WHERE cc.parent=?
                    $sort";
            $params = array($parent);

        } else {
            $sql = "SELECT cc.* $select
                      FROM {course_categories} cc
                    $join
                      JOIN {course_categories} ccp
                           ON ((cc.parent = ccp.id) OR (cc.path LIKE ".$DB->sql_concat('ccp.path', "'/%'")."))
                     WHERE ccp.id=?
                    $sort";
            $params = array($parent);
        }
        $categories = array();

        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $cat) {
            context_helper::preload_from_record($cat);
            $catcontext = context_coursecat::instance($cat->id);
            if ($cat->visible || has_capability('moodle/category:viewhiddencategories', $catcontext)) {
                $categories[$cat->id] = $cat;
            }
        }
        $rs->close();
        return $categories;
    }

    /**
     *  Makes categories.
     **/
    public function prepare_categories() {
        if (empty($this->crontermcache['term_rci'])) {
            throw new course_creator_exception(
                'No request data obtained from the Registrar.'
            );
        }

        $requests = $this->crontermcache['requests'];

        $nestingorder = array();

        $this->debugln('Preparing categories...');
        if ($this->get_config('make_division_categories')) {
            $this->debugln('. Nesting division categories.');
            $nestingorder[] = 'division';
        }

        $nestingorder[] = 'subj_area';

        $rcicourses =& $this->crontermcache['term_rci'];

        // Get all categories and index them.
        $idcategories = $this->get_categories();

        // Add "root" to available categories.
        $fakeroot = new stdclass();
        $fakeroot->name = 'Root';
        $fakeroot->id = 0;
        $fakeroot->parent = 'None';
        $idcategories[0] = $fakeroot;

        $namecategories = array();

        $forbiddennames = array();

        foreach ($idcategories as $cat) {
            // Note standard is same here #OOOO.
            $catname = $cat->name . '-' . $cat->parent;
            if (isset($namecategories[$catname])) {
                $forbiddennames[$catname] = $catname;
            }

            $namecategories[$catname] = $cat;
        }

        $truecatreferences = array();
        foreach ($rcicourses as $reqkey => $rcicourse) {
            if (!isset($requests[$reqkey])
                    || $requests[$reqkey]->hostcourse == 0) {
                continue;
            }

            $immediateparentcatid = 0;

            foreach ($nestingorder as $type) {
                $field = trim($rcicourse->$type);

                $function = 'get_' . $type . '_translation';

                if (!method_exists($this, $function)) {
                    // Should never run.
                    throw new coding_exception($function
                        . ' does not exist.');
                }

                // The translation method will return the short name that will
                // be used to populate the idnumber for the category.
                list($idnumber, $trans) = $this->$function($field);

                // Note that standard is same here #OOOO.
                $namecheck = $trans . '-' . $immediateparentcatid;

                if (isset($forbiddennames[$namecheck])) {
                    $this->debugln('! Category name: '
                        . $trans . ' is ambiguous as a '
                        . $type);

                    break;
                }

                // Not an existing category.
                if (!isset($namecategories[$namecheck])) {
                    $newcategory = $this->new_category($trans,
                        $immediateparentcatid, $idnumber);

                    // Figure name for display and debugging purposes.
                    $parentname = $idcategories[$immediateparentcatid]->name;

                    $idcategories[$newcategory->id] = $newcategory;

                    $this->debugln('  Created ' . $type . ' category: '
                         . $trans . ' parent: ' . $parentname);

                    $namecategories[$namecheck] = $newcategory;

                    unset($newcategory);
                }

                // As the loop continues, the parent will be set.
                $immediateparentcatid = $namecategories[$namecheck]->id;
            }

            $truecatreferences[$trans] = $namecategories[$namecheck];
        }

        // Save this for when building courses.
        $this->categoriescache = $truecatreferences;

        // Creates the category paths, very necessary.
        fix_course_sortorder();
    }

    /**
     * Creates given category.
     *
     * @param string $name
     * @param int $parent
     * @param string $idnumber  Subject area shortname or division code.
     * @return coursecat
     */
    public function new_category($name, $parent=0, $idnumber=null) {
        global $DB;
        $newcategory = new stdClass();
        $newcategory->name = $name;
        $newcategory->parent = $parent;
        $newcategory->idnumber = $idnumber;

        // If we have idnumber, then we can have a conflict if we try to add
        // a category with the same idnumber (CCLE-5100).
        if (!empty($idnumber) && ($record = $DB->get_record('course_categories',
                array ('idnumber' => $idnumber)))) {
             $coursecat = coursecat::get($record->id);
             $coursecat->update($newcategory);
             return $coursecat;
        } else {
            return coursecat::create($newcategory);
        }
    }

    /**
     * Inserts the instructor into our local Arrays.
     *
     * Used in send_emails().
     * Will modify the state of the object.
     *
     * @param object $entry The entry from the Registrar.
     * @return string The Array key.
     **/
    public function key_field_instructors($entry) {
        $entry = (object) $entry;

        $srs = $entry->srs;

        if (!isset($this->crontermcache['instructors'])) {
            $this->crontermcache['instructors'] = array();
        }

        if (!isset($this->crontermcache['profcodes'])) {
            $this->crontermcache['profcodes'] = array();
        }

        if (!isset($entry->ucla_id)) {
            return false;
        }

        // Save the instructor indexed by UID.
        $this->crontermcache['instructors'][$srs][$entry->ucla_id] = $entry;

        // Save the profcodes of the course.
        $profcode = $entry->role;

        $this->crontermcache['profcodes'][$srs][$profcode] = $profcode;
    }

    /**
     *  Creates all the courses that were requested.
     **/
    public function create_courses() {
        if (!isset($this->crontermcache['term_rci'])
                && empty($this->crontermcache['term_rci'])) {
            return false;
        }

        $term = $this->get_cron_term();
        $requests =& $this->crontermcache['requests'];

        // This is a hack for assigning course urls to non-host courses.
        $nhcourses = array();

        $newcourses = array();
        foreach ($this->crontermcache['term_rci'] as $reqkey => $rciobject) {
            unset($reqcourse);

            $courseobj = clone($this->coursedefaults);
            $courseobj->summaryformat = FORMAT_HTML;

            // See if we can get certain information from the requests.
            if (!isset($requests[$reqkey])) {
                throw new moodle_exception('strange request ' . $reqkey);
            } else {
                $reqcourse = $requests[$reqkey];

                // We don't need to build a site for child courses.
                if ($reqcourse->hostcourse < 1) {
                    $nhcourses[$reqcourse->setid][$reqkey] = $reqcourse;
                    continue;
                }

                $courseobj->visible = !$reqcourse->hidden;
            }

            $courseobj->shortname = self::make_course_shortname($rciobject);

            // Sorry for the inconsistent calling scheme.
            $courseobj->fullname = self::make_course_title(
                $rciobject->coursetitle, $rciobject->sectiontitle
            );

            // Get the long version of the subject area (for category).
            $subj = rtrim($rciobject->subj_area);
            list($idnumber, $categoryname) = $this->get_subj_area_translation($subj);

            if (isset($this->categoriescache[$categoryname])) {
                $category = $this->categoriescache[$categoryname];
            } else {
                // Default category (miscellaneous), but this may lead to
                // the first category displayed in course/category.php.
                $category = get_course_category(1);
                $this->println('Could not find category: ' . $categoryname
                    . ', putting course into ' . $category->name);
            }

            $session = $rciobject->session;
            switch ($session) {
                case '6A':
                case '6C':
                case 'FP':
                case 'PC':
                case 'OC':
                    $courseobj->numsections = 6;
                    break;
                case '8A':
                    $courseobj->numsections = 8;
                    break;
                case 'OS':
                    $courseobj->numsections = 12;
                    break;
                default:
                    $courseobj->numsections = 10;
                    break;
            }
            $courseobj->category = $category->id;

            // Save course.
            $newcourses[$reqkey] = $courseobj;
        }

        $existingcourses = $this->match_existings($newcourses,
            'course', array('shortname'));

        foreach ($existingcourses as $eck => $existingcourse) {
            // Mark these as already built...
            unset($newcourses[$eck]);
            $this->debugln("!WARNING $eck built outside of course creator");
        }

        $this->debugln('Creating courses...');
        $builtcourses = $this->bulk_create_courses($newcourses);
        $this->debugln('Created ' . count($builtcourses) . ' courses');

        foreach ($builtcourses as $btk => $course) {
            $req = $requests[$btk];
            $rsid = $req->setid;

            $this->println(". $btk built successfully");
            associate_set_to_course($rsid, $course->id);

            // Apply associate all requests to a built course.
            if (isset($nhcourses[$rsid])) {
                foreach ($nhcourses[$rsid] as $rk => $rq) {
                    $builtcourses[$rk] = $course;
                }
            }
        }

        $this->crontermcache['created_courses'] = $builtcourses;
    }

    /**
     * Checks the database for existing entries in table, and returns
     * those existing entries.
     * @param  array $runners    Array( of Obj ) Existing data to check for
     * @param  array $table      Table to use
     * @param  array $fields     Fields that need to bee in each Obj
     * @return Array( of Obj ) of entries in the database.
     **/
    public function match_existings($runners, $table, $fields) {
        global $DB;

        $returns = array();

        $sqlparams = array();
        $sqlstates = array();
        foreach ($fields as $fora) {
            // This is the field data.
            $fd = array();

            foreach ($runners as $runner) {
                $fd[] = $runner->{$fora};
            }

            if (empty($fd)) {
                // DEBUGGING STATEMENT.
                $this->debugln('match_existings: empty $fd before calling get_in_or_equal');
            }
            list($forasql, $foraparams) = $DB->get_in_or_equal($fd);
            $sqlstates[] = $fora . ' ' . $forasql;

            $sqlparams = array_merge($sqlparams, $foraparams);
        }

        $sqlwhere = implode(' OR ', $sqlstates);

        $existings = $DB->get_records_select($table, $sqlwhere, $sqlparams);

        foreach ($fields as $fora) {
            $optind = array();
            foreach ($existings as $runner) {
                $optind[$runner->{$fora}] = $runner;
            }

            foreach ($runners as $ckey => $runner) {
                if (isset($optind[$runner->{$fora}])) {
                    $returns[$ckey] = $runner;
                }
            }
        }

        return $returns;
    }

    /**
     * Builds a bunch of courses, indexed the keys in the courses sent in.
     * TODO optimize
     *
     * @param array $courses Array of courses
     * @throws moodle_exception from create_course().
     **/
    public function bulk_create_courses($courses) {
        $returns = array();

        foreach ($courses as $key => $course) {
            $returns[$key] = create_course($course);
        }

        return $returns;
    }

    /**
     *  Sends the URLs of the courses to MyUCLA.
     *
     **/
    public function update_myucla_urls() {
        if (!isset($this->crontermcache['created_courses'])) {
            throw new course_creator_exception(
                'IMS did not seem to create any courses'
            );
        }

        // Figure out what to build as the URL of the course.
        $relevanturlinfo = array();

        $urlupdater = $this->get_myucla_urlupdater();
        if (!$urlupdater) {
            $this->debugln('Could not find urlupdater.');
        }

        $this->println('  Starting MyUCLA URL Hook.');

        // Create references, not copies.
        $created =& $this->crontermcache['created_courses'];
        $requests =& $this->crontermcache['requests'];

        $urlarr = array();

        // For each requested course, figure out the URL.
        foreach ($requests as $cronkey => $request) {
            // Check_build_requests() should have been run.
            if (!isset($created[$cronkey])) {
                continue;
            }

            $urlinfo = $created[$cronkey];
            $url = $this->build_course_url($urlinfo);

            $urlobj = array(
                'url' => $url,
                'term' => $request->term,
                'srs' => $request->srs
            );

            if ($urlupdater) {
                if ($request->nourlupdate == 1) {
                    $urlobj['flag'] = myucla_urlupdater::neverflag;
                } else {
                    $urlobj['flag'] = myucla_urlupdater::nooverwriteflag;
                }
            }

            $urlarr[$cronkey] = $urlobj;

            // Store for emails.
            $relevanturlinfo[$request->term][$request->srs] = $url;
        }

        if ($urlupdater) {
            $urlupdater->sync_MyUCLA_urls($urlarr);
            $skipreasoncounter = array();

            $ks = array('failed', 'successful');
            foreach ($ks as $k) {
                if (!empty($urlupdater->{$k})) {
                    // Print set of stuff returned by the myucla
                    // updater module.
                    $this->println('  Url update ' . $k . ': ');
                    foreach ($urlupdater->{$k} as $kid => $ked) {
                        // If it was skipped (not sent on purpose) then also
                        // document that fact. But only for successful entries
                        // do you document why an entry was skipped.
                        if ($k != 'failed' && isset($urlupdater->skipped[$kid])
                                && isset($urlarr[$kid]['flag'])) {

                            $string = get_string($urlarr[$kid]['flag'],
                                'tool_myucla_url');

                            if (!isset($skipreasoncounter[$string])) {
                                $skipreasoncounter[$string] = 0;
                            }

                            $skipreasoncounter[$string]++;

                            $ked .= ' (' . $string . ')';
                        }

                        $this->println('  ' . $kid . ' ' . $ked);

                    }

                    $this->debugln(count($urlupdater->{$k})
                        . " URL updates $k");
                }

                foreach ($skipreasoncounter as $str => $cnt) {
                    $this->debugln("$cnt skipped because [$str]");
                }
            }

            $this->println('  Finished MyUCLA URL hook.');
        }

        // This needs to be saved for emails.
        $this->crontermcache['url_info'] = $relevanturlinfo;
    }

    /**
     * Get a human-friendly list of courses the given instructor has recently taught.
     *
     * @param string|int $uclaid
     * @param int $nterms Optional. Indicate how many terms to search back.
     * @return string
     */
    public function get_instructor_previous_courses($uclaid, $nterms = 8) {
        global $CFG, $DB;

        // Check cache.
        $cache = cache::make('tool_uclacoursecreator', 'previouscourses');
        $key = "$CFG->currentterm $uclaid";
        $data = $cache->get($key);
        if ($data !== false) {
            // Use cache.
            return $data;
        }

        // Generate expression to check for terms in the past $nterms quarters (default 2 years).
        $terms = array();
        // Start from the previous term.
        $term = term_get_prev($CFG->currentterm);
        for ($i = 0; $i < $nterms; $i++) {
            $terms[] = $term;
            $term = term_get_prev($term);
        }
        $termsexpr = $DB->get_in_or_equal($terms);

        // Only include the primary course for cross-listings.
        // Make sure the course isn't canceled by checking urci.enrolstat.
        // Look for courses where this instructor has the editinginstructor role.
        $sql = "SELECT c.id,
                       urci.term,
                       urci.subj_area,
                       urci.coursenum,
                       urci.sectnum,
                       urci.coursetitle
                  FROM {course} c
                  JOIN {ucla_request_classes} urc
                    ON (urc.courseid = c.id AND
                        urc.term $termsexpr[0] AND
                        urc.hostcourse = 1)
                  JOIN {ucla_reg_classinfo} urci
                    ON (urci.term = urc.term AND
                        urci.srs = urc.srs AND
                        urci.enrolstat <> 'X')
                  JOIN {context} ct
                    ON (ct.instanceid = c.id AND ct.contextlevel = ?)
                  JOIN {role_assignments} ra
                    ON (ra.contextid = ct.id)
                  JOIN {role} r ON r.id = ra.roleid
                  JOIN {user} u ON u.id = ra.userid
                 WHERE r.shortname = ?
                       AND u.idnumber = ?";
        $params = $termsexpr[1];
        $params[] = CONTEXT_COURSE;
        $params[] = 'editinginstructor';
        $params[] = $uclaid;

        $result = $DB->get_records_sql($sql, $params);
        $coursestrings = array();
        foreach ($result as $course) {
            $prettyterm = ucla_term_to_text($course->term);
            $coursestrings[] = "$course->subj_area $course->coursenum-$course->sectnum - " .
                    "$course->coursetitle ($prettyterm)";
        }

        $data = implode("\n", $coursestrings);
        // Set cache.
        $cache->set($key, $data);
        return $data;
    }

    /**
     * Sends emails to instructors and course requestors.
     * TODO move this outside the course creator as well..
     * @throws course_creator_exception
     **/
    public function send_emails() {
        if (empty($this->crontermcache['url_info'])) {
            $this->debugln(
                'ERROR: We have no URL information for emails.'
            );
            return false;
        }

        if (!isset($this->crontermcache['trim_requests'])) {
            $this->trim_requests();
        }

        // This should fill the term cache 'instructors' with data from
        // ccle_CourseInstructorsGet.
        $this->println('Getting instructors for '
            . count($this->crontermcache['trim_requests'])
            . ' request(s) from registrar...');

        $results = array();
        // Var $trreq previously tr_req.
        foreach ($this->crontermcache['trim_requests'] as $trreq) {
            $results[] = registrar_query::run_registrar_query(
                    'ccle_courseinstructorsget', $trreq
                );
        }

        $this->println('Finished fetching from registrar.');

        if (empty($results)) {
            // TODO Maybe change the default behavior.
            $this->debugln('No instructors for this term!');
        }

        foreach ($results as $res) {
            foreach ($res as $inst) {
                $this->key_field_instructors($inst);
            }
        }

        // I think the old version works pretty well...
        // These are read-only, no need to duplicate contents.
        $courses =& $this->crontermcache['requests'];
        $rciobjects =& $this->crontermcache['term_rci'];
        $instructors =& $this->crontermcache['instructors'];
        $profcodes =& $this->crontermcache['profcodes'];
        $courseurls =& $this->crontermcache['url_info'];

        $createdcoursescheck =& $this->crontermcache['created_courses'];

        // This is to maintain people without reported URSA emails.
        $this->crontermcache['no_emails'] = array();

        // These are the collection of people we are going to email.
        $emails = array();

        // These are non-host-courses.
        $indexedhc = array();
        foreach ($courses as $cronkey => $course) {
            // TODO make this comparison a function.
            if ($course->hostcourse < 1) {
                if (isset($createdcoursescheck[$cronkey])) {
                    $indexedhc[$course->setid][$cronkey]
                        = $rciobjects[$cronkey];
                }
            }
        }

        // Parse through each request.
        foreach ($courses as $cronkey => $course) {
            if ($course->hostcourse < 1) {
                continue;
            }

            $csrs = $course->srs;
            $term = $course->term;

            if (!isset($createdcoursescheck[$cronkey])) {
                continue;
            }

            $rcicourse = $rciobjects[$cronkey];

            $prettyterm = ucla_term_to_text($term,
                $rcicourse->session_group);

            // This is the courses to display the email for. Var $coursec used to be course_c.
            $coursec = array($rcicourse);

            $csid = $course->setid;
            if (!empty($indexedhc[$csid])) {
                foreach ($indexedhc[$csid] as $nhcronkey => $nhc) {
                    $coursec[] = $nhc;
                }
            }

            // Var $coursed used to be course_d.
            $coursed = array();
            foreach ($coursec as $courseinfo) {
                $coursed[] = $this->make_email_course_name($courseinfo);
            }

            $coursedesc = array(); // Going to be an array of string of course descriptions.
            if (count($coursed) == 1) { // If we have only one course, just concat the course descriptions.
                $coursedesc[] = implode(' ', $coursed[0]);
            } else { // If we have multiple courses.
                foreach ($coursed as $course) {
                    $coursestring = $course['subjectarea'].' '.$course['coursenum'];
                    // We only need course area and number, not type and section number.
                    if (!in_array($coursestring, $coursedesc)) {
                        // Add this coursestring in the array if it is not added before.
                        array_push($coursedesc, $coursestring);
                    }
                }
            }

            $coursetext = implode(' / ', $coursedesc);

            $coursedept = $rcicourse->subj_area;
            $coursedivision = $rcicourse->division;

            unset($rcicourse);

            // The instructors to be emailed.
            $showinstructors = array();

            // Determine which instructors to email.
            if (!isset($profcodes[$csrs])) {
                $this->debugln('No instructors for '
                    . "$term $csrs $coursetext.");
            } else {
                $profcodeset = $profcodes[$csrs];

                if (isset($instructors[$csrs])) {
                    foreach ($instructors[$csrs] as $instructor) {
                        $viewable = $this->get_viewable_status($instructor,
                            $profcodeset, $coursedept);

                        if ($viewable) {
                            $showinstructors[] = $instructor;
                        } else {
                            $this->debugln(
                                'Not emailing ' . $instructor->last_name_person
                                . ' Profcode: ' . $instructor->role
                            );
                        }
                    }
                }

                if (empty($showinstructors)) {
                    $this->debugln("No instructors to email for "
                        . "$term $csrs ($coursetext)!");
                }
            }

            if (isset($courseurls[$term][$csrs])) {
                $courseurl = $courseurls[$term][$csrs];
            } else {
                $courseurl = 'No URL';
            }

            // Check if we should email the professors.
            // Default to not emailing professors.
            $retainemails = true;
            if (!empty($course->mailinst)) {
                $retainemails = false;
            }

            foreach ($showinstructors as $instructor) {
                $lastname = ucla_format_name(
                    trim($instructor->last_name_person)
                );

                $email = trim($instructor->ursa_email);

                $uid = $instructor->ucla_id;

                // If they do not have an email from the Registrar, and we did
                // not already find one locally, attempt to find one locally.
                if ($email == '' && !isset($this->local_emails[$uid])) {
                    $this->crontermcache['no_emails'][$uid] = $instructor;
                }

                unset($emailref);

                $emailref['lastname'] = $lastname;
                $emailref['to'] = $email;
                $emailref['coursenum-sect'] = $coursetext;
                $emailref['dept'] = '';
                $emailref['url'] = $courseurl;
                $emailref['term'] = $term;
                $emailref['nameterm'] = $prettyterm;
                $emailref['previouscourses'] = $this->get_instructor_previous_courses($instructor->ucla_id);

                // These are not parsed.
                $emailref['subjarea'] = $coursedept;
                $emailref['division'] = $coursedivision;
                $emailref['userid'] = $uid;
                $emailref['srs'] = $csrs;
                $emailref['block'] = $retainemails;
                $emails[] = $emailref;
            }
        }

        // Try to check out local records for emails.
        $localemails = array();

        if (!empty($this->crontermcache['no_emails'])) {
            $this->get_local_emails();

            $localemails =& $this->crontermcache['local_emails'];
        }

        if (!$this->send_mails()) {
            $this->debugln('--- Email sending disabled ---');
            // Continue so that we can see debugging messages.
        }

        // TODO move the rest of this out
        // Parsed
        // This may take the most memory.
        $emailsummarydata = array();
        foreach ($emails as $emailing) {
            $addsubject = '';
            $emailto = '';

            // This is going to be used later.
            $csrs = $emailing['srs'];
            unset($emailing['srs']);

            // Filter out no emails.
            $userid = $emailing['userid'];

            // Preformat the email summary.
            if (!isset($emailsummarydata[$csrs])) {
                $emailsummarydata[$csrs] = array();
            }

            $emailsummarydata[$csrs][$userid] = '';

            if ($emailing['to'] == '') {
                // Attempt to find user.
                if (!isset($localemails[$userid])) {
                    // No email, specify that and send to BCCs.
                    $this->println("Cannot email $userid "
                        . $emailing['lastname']);

                    $addsubject = ' (No email)';

                    $emailsummarydata[$csrs][$userid] .= "! "
                        . $emailing['lastname']
                        . "\t $userid \tNo email address.\n";
                } else {
                    $emailing['to'] = $localemails[$userid];

                    $emailsummarydata[$csrs][$userid] .= '* '
                        . $emailing['lastname']
                        . "\t $userid \t" . $localemails[$userid]
                        . " - Local email ONLY\n";
                }
            }

            // This is also used later to not send the email...
            $blockemail = $emailing['block'];
            unset($emailing['block']);

            // Handle special emails to THE STAFF and TA.
            if (is_dummy_ucla_user($userid)) {
                $emailto = '';
                $addsubject = ' (' . $emailing['lastname'] . ')';
            } else {
                // Set the destination.
                $emailto = $emailing['to'];
            }

            unset($emailing['userid']);

            // Parse the email.
            $subj = $emailing['subjarea'];
            $division = $emailing['division'];

            // Figure out which email template to use.
            if ($this->send_mails() && !isset($this->parsedparam[$subj])) {
                if (!isset($this->emailprefix)) {
                    $this->figure_email_vars();
                }

                $deptfile = $this->emailprefix . $subj . $this->emailsuffix;

                if (file_exists($deptfile)) {
                    $this->debugln('Using special template for subject area '
                        . $subj);

                    $file = $deptfile;
                } else {
                    // Else search for a division template.
                    $divisionfile = $this->emailprefix . $division . $this->emailsuffix;
                    if (file_exists($divisionfile)) {
                        $this->debugln('Using special template for division ' . $division);
                        $file = $divisionfile;
                    } else {
                        // Then use default template.
                        $file = $this->defaultemailfile;
                    }
                }

                $this->parsedparam[$subj] = $this->email_parse_file($file);
            }

            if (!isset($this->parsedparam[$subj])) {
                $headers = '';
                $emailsubject = '-not parsed - '
                    . $emailing['coursenum-sect'] . ' '
                    . $emailing['url']
                    . $addsubject;

                $emailbody = '!-not parsed-!';
            } else {
                $usedparam = $this->parsedparam[$subj];
                unset($emailing['subjarea']);

                $emailparams = $this->email_fill_template($usedparam, $emailing);

                // Setup the email.
                $from = trim($emailparams['from']);
                $bcc = trim($emailparams['bcc']);

                // Headers, include the Blind Carbon Copy and From
                // (make sure there are no errant spaces or else email headers
                // wouldn't parse correctly).
                $headers = "From: $from\r\nBcc: $bcc\r\n";

                $emailsubject = $emailparams['subject'];

                // Append filler user explanations.
                $emailsubject .= $addsubject;

                $emailbody = $emailparams['body'];
            }

            $emailsummarydata[$csrs][$userid] .= '. '
                . $emailing['lastname'] . "\t $userid \t"
                . $emailto . " \t $emailsubject";

            if ($this->send_mails() && !$blockemail) {
                $this->println("Emailing: $emailto");

                ucla_send_mail($emailto, $emailsubject,
                    $emailbody, $headers);
            } else {
                if ($blockemail) {
                    $this->println('Blocked this email - from setting in '
                        . 'course requestor.');
                }

                $this->println("to: $emailto");
                $this->println("headers: $headers");
                $this->println("subj: $emailsubject");
            }
        }

        foreach ($emailsummarydata as $srs => $coursedata) {
            foreach ($coursedata as $instrdata) {
                $this->emailln($instrdata);
            }
        }
    }

    /**
     *  An array containing essential course
     *  information in the email that conveys which courses were built
     *  in the current session of course creator.
     *
     * @param object $reginfo
     * @return array
     **/
    public function make_email_course_name($reginfo) {
        list($idnumber, $subjarea) = $this->get_subj_area_translation(
                trim($reginfo->subj_area));
        return array(
            'subjectarea' => trim($idnumber),
            'coursenum' => trim($reginfo->coursenum),
            'type' => $reginfo->acttype,
            'sectionnum' => $reginfo->sectnum
        );
    }

    /**
     *  This will try to see if any instructors without emails from the
     *  Registrar have accounts with emails on our local server.
     *
     *  Changes the state of the object.
     **/
    public function get_local_emails() {
        global $DB;
        // Try to check out local records for emails.
        $noemails =& $this->crontermcache['no_emails'];

        // This should not happen.
        if (empty($noemails)) {
            return false;
        }

        $localuserids = array();

        foreach ($noemails as $emailless) {
            // Attempt to find user.
            $userid = $emailless->ucla_id;
            $name = trim($emailless->first_name_person) . ' '
                . trim($emailless->last_name_person);
            $this->println("$name $userid has no email.");

            $localuserids[] = $userid;
        }

        if (empty($localuserids)) {
            // DEBUGGING STATEMENT.
            $this->debugln('get_local_emails: empty $local_userids before calling get_in_or_equal');
        }
        list($sqlin, $params) = $DB->get_in_or_equal($localuserids);
        $sqlwhere = 'idnumber ' . $sqlin;

        $this->println("Searching local MoodleDB for idnumbers $sqlin...");

        $localusers = $DB->get_records_select('user', $sqlwhere, $params);

        if (!empty($localusers)) {
            foreach ($localusers as $localuser) {
                $email = trim($localuser->email);

                if ($email != '') {
                    $idnumber = $localuser->idnumber;
                    $this->println("Found user $idnumber $email");
                    $this->local_emails[$localuser->idnumber] = $email;
                }
            }
        }
    }

    /**
     * Parses the reference file into an array.
     * @param string $file The file location.
     * @return The elements of the email parsed into an array.
     **/
    public function email_parse_file($file) {
        $emailparams = array();

        $fp = @fopen($file, 'r');

        if (!$fp) {
            $this->debugln("ERROR: could not open email template file: "
                . "$file.\n");
            return;
        }

        // First 3 lines are headers.
        for ($x = 0; $x < 3; $x++) {
            $line = fgets($fp);
            if (preg_match('/'.'^FROM:(.*)'.'/i', $line, $matches)) {
                $emailparams['from'] = trim($matches[1]);
            } else if (preg_match('/'.'^BCC:(.*)'.'/i', $line, $matches)) {
                $emailparams['bcc'] = trim($matches[1]);
            } else if (preg_match('/'.'^SUBJECT:(.*)'.'/i', $line, $matches)) {
                $emailparams['subject'] = trim($matches[1]);
            }
        }

        if (count($emailparams) != 3) {
            $this->debugln("ERROR: failed to parse headers in $file \n");
            return false;
        }

        $emailparams['body'] = '';

        while (!feof($fp)) { // The rest of the file is the body.
            $emailparams['body'] .= fread($fp, 8192);
        }

        $this->debugln("Parsing $file successful \n");
        fclose($fp);

        return $emailparams;
    }

    /**
     * Replaces values in the email with values provided in arguments.
     * @param array $params The parsed email.
     * @param array $arguments The values to replace the parsed entries with.
     * @return The reparsed emails.
     **/
    public function email_fill_template($params, $arguments) {
        foreach ($params as $key => $value) {
            // Fill in template placeholders.
            foreach ($arguments as $akey => $avalue) {
                $params[$key] = str_replace('#=' . $akey . '=#',
                    $avalue, $params[$key]);
            }

            if (preg_match('/#=.*?=#/', $params[$key])) {
                echo $params[$key];
            }
        }

        return $params;
    }

    /**
     *  Fills the {ucla_reg_classinfo} table with the information obtained
     *  from the Registrar for the requests.
     *  Called by mark_cron_term().
     *
     *  Changes the state of the function.
     **/
    public function insert_term_rci() {
        global $DB;

        if (!$this->get_cron_term()) {
            return false;
        }

        if (!isset($this->crontermcache['term_rci'])
          || empty($this->crontermcache['term_rci'])) {
            return false;
        }

        // Reference.
        $termrci =& $this->crontermcache['term_rci'];

        // TODO move the bulk sql functions elsewhere.
        $fields = array();
        foreach ($termrci as $rcidata) {
            foreach ($rcidata as $field => $data) {
                if (!isset($fields[$field])) {
                    $fields[$field] = $field;
                }
            }
        }

        $params = array();
        foreach ($termrci as $rcidata) {
            foreach ($fields as $field) {
                if (!isset($rcidata->$field)) {
                    $params[] = '';
                } else {
                    $params[] = $rcidata->$field;
                }
            }
        }

        $filler = array_fill(0, count($fields), '?');

        $builderes = array();

        $drivesize = count($termrci);

        $fieldsstring = "(" . implode(", ", $fields) . ")";

        for ($i = 0; $i < $drivesize; $i++) {
            $builderes[] = "(" . implode(", ", $filler) . ")\n";
        }

        $buildline = implode(', ', $builderes);

        // TODO use moodle api better, or extract this functionality out.
        $sql = "
            INSERT INTO {ucla_reg_classinfo}
            $fieldsstring
            VALUES
            $buildline
        ";

        // Fix this.
        try {
            $DB->execute($sql, $params);
        } catch (dml_exception $e) {
            $this->debugln('Registrar Class Info mass insert failed.');

            foreach ($termrci as $rcidata) {
                // Maybe failed, because term/srs already exists in ucla_reg_classinfo.
                if ($DB->record_exists('ucla_reg_classinfo',
                        array('term' => $rcidata->term, 'srs' => $rcidata->srs))) {
                    $this->debugln('ucla_reg_classinfo record already exists: '
                        . $rcidata->term . ' ' . $rcidata->srs);
                    continue;
                }

                try {
                    $DB->insert_record('ucla_reg_classinfo',
                        $rcidata);
                } catch (dml_exception $e) {
                    $this->debugln('ucla_reg_classinfo insert failed: '
                        . $rcidata->term . ' ' . $rcidata->srs . ' '
                        . $e->debuginfo);
                }
            }
        }

        $this->println('  Finished dealing with ucla_reg_classinfo.');
    }


    /**
     *  Gathers the information needed to mail to the requestors.
     *  Called at the finish of every term by mark_cron_term().
     *
     *  Changes the state of the function.
     **/
    public function queue_requestors() {
        if (!isset($this->crontermcache['requests'])) {
            return false;
        }

        $urlinfo =& $this->crontermcache['url_info'];

        // Gather requestors' courses.
        foreach ($this->crontermcache['requests'] as $course) {
            if (empty($course->requestoremail)) {
                continue;
            }

            // TODO pluralize, work for csv of emails.
            $contact = $course->requestoremail;

            // Validate contact.
            if (!isset($this->requestor_emails[$contact])) {
                if (validate_email($contact)) {
                    $this->requestor_emails[$contact] = array();
                } else {
                    $this->emailln("Requestor email $contact not valid "
                        . "for $term $csrs");
                }
            }

            if (isset($this->requestor_emails[$contact])) {
                if (isset($urlinfo[$course->term][$course->srs])) {
                    $courseurl = $urlinfo[$course->term][$course->srs];
                } else {
                    $courseurl = 'Failed to build.';
                }

                $reqkey = $course->term . '-' . $course->srs . ' '
                    . $course->course;

                $this->requestor_emails[$contact][$reqkey] = $courseurl;
            }
        }
    }

    /**
     *  This will mail the requestors with the information we gathered.
     *  This is called by finish_cron().
     **/
    public function mail_requestors() {
        if (empty($this->requestor_emails)) {
            return false;
        }

        $requestormesgstart = "The courses you've requested:\n";
        $requestormesgend = "\nhave been run through course creator.";

        $requestorheaders = '';

        // Email course requestors.
        foreach ($this->requestor_emails as $requestor => $createdcourses) {

            // Var $crecoucnt previously crecou_cnt.
            $crecoucnt = count($createdcourses);
            if ($crecoucnt > 1) {
                $reqsubjsubj = $crecoucnt . ' courses';
            } else {
                $temp = array_keys($createdcourses);
                $reqsubjsubj = reset($temp);
            }

            $reqsubj = "Your request for $reqsubjsubj has been processed.";

            $createdcoursessummary = array();
            foreach ($createdcourses as $key => $status) {
                $createdcoursessummary[] = $key . " - " . $status;
            }

            $reqsummary = implode("\n", $createdcoursessummary);

            $reqmes = $requestormesgstart
                . $reqsummary . $requestormesgend;

            if ($this->send_mails()) {
                $resp = ucla_send_mail($requestor, $reqsubj, $reqmes,
                    $requestorheaders);

                if ($resp) {
                    $this->debugln("Emailed $requestor");
                } else {
                    $this->println("ERROR: course not email $requestor");
                }
            } else {
                $this->debugln("Would have emailed: $requestor [$reqmes]");
            }

            $this->emailln("Requestor: $requestor for $crecoucnt courses");
        }
    }

    /**
     *  Saves a set of requests with courseid in the object into
     *  the course creator object.
     *  Uses cron term cache 'requests' 'created_courses'
     *  Sets $this->built_requests.
     **/
    public function save_created_courses() {
        if (empty($this->crontermcache['requests'])) {
            return false;
        }

        $requests =& $this->crontermcache['requests'];

        if (empty($this->crontermcache['created_courses'])) {
            // No courses created this term, no courses need to be saved.
            return false;
        }

        $createdcourses =& $this->crontermcache['created_courses'];

        $counter = 0;
        foreach ($requests as $key => $request) {
            if (!empty($createdcourses[$key])) {
                $request->courseid = $createdcourses[$key]->id;
                $this->builtrequests[$key] = $request;
                $counter++;
            }
        }

        $this->debugln("* Saved $counter courses.");
    }

    /**
     *  Triggers the event with course created data.
     *  Uses $this->built_requests.
     **/
    public function events_trigger_with_data() {
        if (empty($this->builtrequests)) {
            return false;
        }

        $edata = new stdclass();
        $edata->completed_requests = $this->builtrequests;

        $this->println('. Triggering event.');

        $event = \tool_uclacoursecreator\event\course_creator_finished::create(array(
            'other' => json_encode($edata)
        ));
        $event->trigger();

        $this->debugln('Triggered event with '
            . count($edata->completed_requests) . ' requests.');
    }

    /* ********************* */
    /* More Global Functions */
    /* ********************* */

    /**
     *  Check that we have an outpath set, if not, we will use moodledata.
     *  Check that we have write priviledges to the outpath, if not, we will
     *      use moodledata.
     *
     *  Changes:
     *      shelldate
     *      fulldate
     *      outputpath
     **/
    public function check_write() {
        global $CFG;

        if (isset($this->outputpath)) {
            return true;
        }

        // Check if we have a path to write to.
        $ccoutpath = $this->get_config('outpath');
        if ($ccoutpath) {
            $this->outputpath = $ccoutpath;
        } else {
            // Defaulting to moodledata.
            $this->outputpath = $CFG->dataroot . '/course_creator';

            // This means we have no write priveledges to moodledata.
            if (!file_exists($this->outputpath)) {
                if (!mkdir($this->outputpath)) {
                    throw new course_creator_exception('Could not make '
                        . $this->outputpath);
                }
            }
        }

        // Test that we actually can write to the output path.
        $testfile = $this->outputpath . '/write_test.txt';

        if (!fopen($testfile, 'w')) {
            throw new course_creator_exception('No write permissions to '
                . $this->outputpath);
        }

        unlink($testfile);

        // This is saved for creating XML and log files.
        $this->shelldate = date('Ymd-Hi');
        $this->fulldate = date('r');
    }

    /**
     * Will determine whether or not we can run this function.
     * @param boolean $lock true for lock, false for unlock.
     * @param boolean $warn display a message if unlocking without lock.
     * @return boolean If we the action was successful or not.
     * @since Moodle 2.0.
     **/
    public function handle_locking($lock, $warn=true) {
        global $DB;

        // Get a unique id for this lock.
        $this->make_dbid();
        $this->check_write();

        $cclock = $this->outputpath . '/' . $this->dbid . '.lock';
        $fe = file_exists($cclock);
        // Prevent new requests that come in during course creation from
        // affecting course creator.
        if ($lock) {
            // We sometimes want to do a file lock.
            if ($fe) {
                $msg = "Lock file $cclock already exists!";
                if (RUN_CLI) {
                    echo $msg . "\n";
                }
                throw new course_creator_exception($msg);
            }

            $lockfp = fopen($cclock, 'x');
            fclose($lockfp);
            if (RUN_CLI) {
                $this->println('Lock successful.');
            }
        } else {
            if ($fe) {
                unlink($cclock);
                if (RUN_CLI) {
                    $this->println('Unlock successful');
                }
            } else {
                if ($warn) {
                    $this->debugln(
                        'WARNING: Lock file disappeared during course'
                        . 'creation!'
                    );

                    $this->debugln('!! Your tables MAY be volatile !!');
                }
            }
        }

        return true;
    }

    /**
     * Tests to see if lock file exists.
     */
    public function lock_exists() {
        $this->make_dbid();
        $this->check_write();
        $cclock = $this->outputpath . '/' . $this->dbid . '.lock';
        $fe = file_exists($cclock);
        return($fe);
    }

    /**
     *  Temporary wrapper for finishing up cron.
     *  Email admin.
     *  Cleanup certain things?
     **/
    public function finish_cron() {
        $this->mail_requestors();

        $this->println(
            '---- Course creator end at ' . date('r') . ' ----'
        );

        if (!empty($this->emaillog)) {
            $emailbody = get_string('checklogs', 'tool_uclacoursecreator')
                . ": " . $this->log_file . "\n" . $this->emaillog;

            // Email the summary to the admin.
            ucla_send_mail($this->get_config('course_creator_email'),
                'Course Creator Summary ' . $this->fulldate, $emailbody);
        }

        $this->close_log_file_pointer();

        // Trigger event.
        $this->events_trigger_with_data();

        return true;
    }

    /**
     *  Populates $this->db_id with a unique identifier per instance of
     *  Moodle.
     *  Currently just uses dbname
     **/
    public function make_dbid() {
        if (!isset($this->dbid)) {
            $this->dbid = get_config(null, 'dbname');
        }
    }

    /**
     * Sets the terms to be run.
     * @deprecated v2011041900 - Renamed to set_term_list()
     *
     * Changes the state of the function.
     *
     * @param array $termslist The array of terms to run for.
     **/
    public function set_terms($termslist) {
        $this->set_term_list($termslist);
    }

    /* ************************** */
    /* Non-Variants Initializers  */
    /* ************************** */

    /**
     *  Will figure out the terms to work for.
     *  Currently only uses the config file as a source.
     *
     *  Only called by get_terms_creating().
     *
     *  Will change the state of the object.
     **/
    public function figure_terms() {
        global $DB;

        // Get all terms that have pending course builds (also running, since
        // they might have been left off to complete running the next build
        // cycle).
        $sql = "SELECT  DISTINCT term
                FROM    {ucla_request_classes}
                WHERE   action=:build OR
                        action=:running";
        $termslist = $DB->get_fieldset_sql($sql,
                array('build' => UCLA_COURSE_TOBUILD,
                      'running' => UCLA_COURSE_LOCKED));
        if (!empty($termslist)) {
            $this->termslist = $termslist;
            return $termslist;
        }

        return false;
    }

    /**
     *  This will figure out the paths for the email files using the config
     *  variables.
     *
     *  You just need to call this once.
     **/
    public function figure_email_vars() {
        if (!$this->send_mails()) {
            return false;
        }

        if (!$this->get_config('email_template_dir')) {
            throw new course_creator_exception(
                'ERROR: email_template_dir not set!'
            );
        }

        $this->emailprefix = $this->get_config('email_template_dir') . '/';

        $this->emailsuffix = '_course_setup_email.txt';

        $this->defaultemailfile = $this->emailprefix . 'DEFAULT'
            . $this->emailsuffix;
    }

    /* ************************ */
    /* Global Function Wrappers */
    /* ************************ */

    /**
     * Will figure out what to interpret as the webpage.
     *
     * @param object $course The course object.
     * @return string The URL of the course (no protocol).
     **/
    public static function build_course_url($course) {
        // TODO put this in the proper namespace.
        if (get_config('local_ucla', 'friendly_urls_enabled')) {
            return new moodle_url(make_friendly_url($course));
        }

        return new moodle_url('/course/view.php', array('id' => $course->id));
    }

    /**
     * Wrapper function for get_config().
     *
     * @param string $config
     **/
    public function get_config($config) {
        return get_config('tool_uclacoursecreator', $config);
    }

    /**
     * Make sure the term is valid.
     * @param string $term The term.
     * @return boolean Whether the term is valid or not.
     **/
    public function validate_term($term) {
        return ucla_validator('term', $term);
    }

    /**
     * Build the shortname from registrar information.
     * @param object $rciobject Object with fields:
     *     term, session_group, subj_area, coursenum, sectnum
     * @return string The shortname, without the term.
     **/
    static public function make_course_shortname($rciobject) {
        $rci = get_object_vars($rciobject);

        foreach ($rci as $k => $v) {
            $rci[$k] = trim($v);
        }

        $course = $rci['term'] . $rci['session_group'] . '-'
            . $rci['subj_area'] . $rci['coursenum'] . '-'
            . $rci['sectnum'];

        // Remove spaces and ampersands.
        $course = preg_replace('/[\s&]/', '', $course);

        return $course;
    }

    /**
     * Will make a course title from Registrar course and section title data.
     *
     * @param string $coursetitle The course title.
     * @param string $sectiontitle The section title.
     * @return string The combined title.
     */
    static public function make_course_title($coursetitle, $sectiontitle) {
        $coursetitle = trim($coursetitle);
        $sectiontitle = trim($sectiontitle);
        if (empty($sectiontitle)) {
            return $coursetitle;
        }

        return "$coursetitle: $sectiontitle";
    }

    /**
     * Recursively trim() fields.
     * @param object $oldobj The object to trim().
     * @return Object The object, trimmed.
     **/
    public function trim_object($oldobj) {
        foreach ($oldobj as $f => $v) {
            if (is_array($v)) {
                // Do nothing.
                continue;
            } else if (is_object($v)) {
                $oldobj->{$f} = $this->trim_object($v);
            } else {
                $oldobj->{$f} = trim($v);
            }
        }

        return $oldobj;
    }

    /**
     * Will revert failed course builds. Will do the following:
     *  1) Query for failed course builds (for given term, if applicable)
     *  2) Check if a course was built, if so, then delete it
     *  3) Reinsert the course request as a new entry with action of "to build"
     *     and empty courseid
     *
     * @param string $term  If term is passed, then only requests for that
     *                      term will be reverted.
     */
    public function revert_failed_requests($term = null) {
        global $DB;

        // 1) Query for failed course builds (for given term, if applicable).
        $params['action'] = UCLA_COURSE_FAILED;
        $params['hostcourse'] = 1;  // Only interested in the failed hosts.
        if (!empty($term)) {
            $params['term'] = $term;
        }

        $failedrequests = $DB->get_records('ucla_request_classes', $params);
        $this->println(sprintf('Reverting %d course requests', count($failedrequests)));

        foreach ($failedrequests as $failedrequest) {
            $this->println(sprintf('Reverting request %d for term/srs: %s/%s courseid/setid %d/%d',
                    $failedrequest->id, $failedrequest->term,
                    $failedrequest->srs, $failedrequest->courseid,
                    $failedrequest->setid));

            // Get all associated course request entries (need to do this before
            // a delete, because a delete will also clear the requests).
            $associatedrequests = $DB->get_records('ucla_request_classes',
                    array('setid' => $failedrequest->setid, 'hostcourse' => 0));

            // 2) Check if a course was built, if so, then delete it.
            if (!empty($failedrequest->courseid)) {
                if (!delete_course($failedrequest->courseid, false)) {
                    // Returning false, meaning that course was deleted before
                    // somehow, but the request was never deleted. Need to
                    // invoke the course_deleted event for cleanup of request
                    // table.
                    $course = new stdClass();
                    $course->id = $failedrequest->courseid;
                    $this->debugln('Function delete_course returned false for' .
                            $failedrequest->courseid);
                }
                $this->debugln('Deleted courseid ' . $failedrequest->courseid);
            } else {
                $this->debugln('No course to delete for request ' . $failedrequest->id);
                // If no course found, then delete existing requests.
                $DB->delete_records('ucla_request_classes', array('setid' => $failedrequest->setid));
            }

            // Combine request and associated requests.
            $comborequests = array_merge(array($failedrequest), $associatedrequests);

            foreach ($comborequests as $request) {
                // 3) Reinsert the course request as a new entry with action of
                // "to build" and empty courseid.
                unset($request->id);    // Make this a new request.
                unset($request->courseid);
                $request->timerequested = time();
                $request->action = UCLA_COURSE_TOBUILD;
                try {
                    $DB->insert_record('ucla_request_classes', $request);
                } catch (Exception $e) {
                    die($e->error);
                }

                $this->println(sprintf('Reinserted request for %s, %s',
                        $failedrequest->term, $failedrequest->srs));
            }

        }
    }
}

// EOF.
