<?php
// This file is part of the UCLA Help plugin for Moodle - http://moodle.org/
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
 *
 * Collection of classes/functions used across multiple scripts for the UCLA Help and Feedback block.
 *
 * Else, can be called displayed in a site or course context.
 *
 * @package    block_ucla_help
 * @author     Rex Lorenzo <rex@seas.ucla.edu>
 * @copyright  2011 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/../../lib/adminlib.php');
require_once($CFG->dirroot . '/lib/validateurlsyntax.php');

/*** CLASSES ***/

/**
 * Derived from admin_setting_emoticons.
 *
 * Used to allow
 * admins to edit the 'ucla_help' support contact settings.
 *
 * @author     Rex Lorenzo <rex@seas.ucla.edu>
 * @copyright  2011 UC Regents
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class admin_setting_ucla_help_support_contact extends admin_setting {

    /**
     * Support contacts manager
     * @var array
     */
    private $manager;

    /**
     * Calls parent::__construct with specific args
     */
    public function __construct() {
        global $CFG;
        $this->manager = get_support_contacts_manager();
        parent::__construct('block_ucla_help/support_contacts',
                get_string('settings_support_contacts', 'block_ucla_help'), '', '');
    }

    /**
     * Return the current setting(s)
     *
     * @return array Current settings array
     */
    public function get_setting() {
        $config = $this->manager->get_support_contacts();

        return $this->prepare_form_data($config);
    }

    /**
     * Saves support contact into key-value pairs in
     * $CFG->block_ucla_help->support_contacts array. Ignores support contacts
     * for contexts that are defined in the config.php file.
     *
     * @param array $data Array of settings to save
     * @return bool
     */
    public function write_setting($data) {

        $supportcontacts = $this->process_form_data($data);
        if ($supportcontacts === false) {
            return false;
        }

        if ($this->config_write($this->name, $this->manager->encode_stored_config($supportcontacts))) {
            return ''; // Success.
        } else {
            return get_string('errorsetting', 'admin') . $this->visiblename . html_writer::empty_tag('br');
        }
    }

    /**
     * Return XHTML field(s) for options
     *
     * @param array $data Array of options to set in HTML
     * @param string $query
     * @return string XHTML string for the fields and wrapping div(s)
     */
    public function output_html($data, $query='') {
        global $blockuclahelpsupportcontacts, $OUTPUT;

         // Data is in following format:
         //
         // Array
         // (
         // [context0] => System
         // [support_contact0] => dkearney
         // [context1] => Computer Science
         // [support_contact1] => rlorenzo
         // [context2] =>
         // [support_contact2] =>
         // )
         // .
        $t = new html_table();
        $t->attributes = array('class' => 'generaltable');
        $t->head = array('', get_string('settings_support_contacts_table_context', 'block_ucla_help'),
                get_string('settings_support_contacts_table_contact', 'block_ucla_help'), '');

        $i = 0; $rownum = 1; $curcontext = ''; $row = array();
        foreach ((array) $data as $field => $value) {

            // First cell is row number.
            if ($i == 0) {
                $row = new html_table_row();
                $cell = new html_table_cell();
                $cell->text = sprintf('%d.', $rownum);
                $row->cells[] = $cell;
                $rownum++;

                // Save context for later on.
                $curcontext = $value;
            }

            $cell = new html_table_cell();
            $cell->text = html_writer::empty_tag('input',
                    array(
                        'type'  => 'text',
                        'class' => 'form-text',
                        'name'  => $this->get_full_name().'['.$field.']',
                        'value' => $value,
                    ));
            $row->cells[] = $cell;

            if ($i == 1) {
                // On last element, so end row.
                $cell = new html_table_cell();

                // If context ws defined in config file, then give warning that
                // user cannot change setting.
                if (!empty($blockuclahelpsupportcontacts[$curcontext])) {
                    $cell->text = html_writer::tag('div', 'Defined in config.php', array('class' => 'form-overridden'));
                }
                $row->cells[] = $cell;
                $t->data[] = $row;

                $i = 0;
            } else {
                $i++;
            }
        }

        return format_admin_setting($this, $this->visiblename, html_writer::table($t), $this->description, false, '', null, $query);
    }

    /**
     * Converts the array of support_contacts provided by
     * support_contacts_manager into admin settings form data
     *
     * @see ucla_help_lib::process_form_data($form)
     * @param array $supportcontacts   array of support_contacts as returned by
     *                                  support_contacts_manager
     * @return array of form fields and their values
     */
    protected function prepare_form_data(array $supportcontacts) {

        $form = array();
        $i = 0;
        foreach ((array) $supportcontacts as $context => $supportcontact) {
            $form['context'.$i]             = $context;
            $form['support_contact'.$i]     = $supportcontact;
            $i++;
        }
        // Add one more blank field set for new support contact.
        $form['context'.$i]            = '';
        $form['support_contact'.$i]       = '';

        return $form;
    }

    /**
     * Converts the data from admin settings form into an array of
     * support_contacts
     *
     * @see self::prepare_form_data()
     * @param array $form array of admin form fields and values
     * @return false|array of support_contacts
     */
    protected function process_form_data($form) {
        $numformelements = 2;

        if (!is_array($form)) {
            return false;
        }

        $count = count($form); // Number of form field values.
        if ($count % $numformelements) {
            // We must get two fields per support_contact.
            return false;
        }

        $supportcontacts = array();
        $count = $count / $numformelements;
        for ($i = 0; $i < $count; $i++) {
            $context         = clean_param(trim($form['context'.$i]), PARAM_NOTAGS);
            $supportcontact = clean_param(trim($form['support_contact'.$i]), PARAM_NOTAGS);

            // Make sure that entries exists.
            if (!empty($context) && !empty($supportcontact)) {
                $supportcontacts[$context] = $supportcontact;
            }
        }
        return $supportcontacts;
    }
}

/**
 * Factory function for support_contacts_manager
 *
 * @return support_contacts_manager singleton
 */
function get_support_contacts_manager() {
    static $singleton = null;

    if (is_null($singleton)) {
        $singleton = new support_contacts_manager();
    }

    return $singleton;
}

/**
 * Derived from emoticon_manager().
 *
 * Used to encode and decode support_contacts
 * array for block_ucla_block config table.
 *
 * @copyright  2011 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see admin_setting_support_contacts
 */
class support_contacts_manager {

    /**
     * Returns the current support_contacts.
     *
     * @return array    Array of support_contacts indexed by context => contact.
     */
    public function get_support_contacts() {
        global $blockuclahelpsupportcontacts, $CFG;

        $supportcontacts = get_config('block_ucla_help', 'support_contacts');
        $supportcontacts = $this->decode_stored_config((string) $supportcontacts);

        // Now merge with values from config file to overwrite user set ones.
        $supportcontacts = array_merge((array) $supportcontacts,
                (array) $blockuclahelpsupportcontacts);

        // If there is no one listed at 'System' context, then try to guess it.
        if (!isset($supportcontacts['System'])) {
            $supportcontacts['System'] = $CFG->supportemail;
        }

        return $supportcontacts;
    }

    /**
     * Encodes the array of support contacts into a string storable in config table
     *
     * @see self::decode_stored_config()
     * @param array $contacts array of support contacts objects
     * @return string
     */
    public function encode_stored_config(array $contacts) {
        return json_encode($contacts);
    }

    /**
     * Decodes the string into an array of support contacts
     *
     * @see self::encode_stored_config()
     * @param string $encoded
     * @return string|null
     */
    public function decode_stored_config($encoded) {
        // Make sure that decoded string is an array.
        $decoded = (array) json_decode((string) $encoded);
        return $decoded;
    }
}

/*** FUNCTIONS ***/

/**
 * Constructs description for the subject of email to speed the routing of the ticket
 *
 * @param mixed $fromform   Form data submitted by user. Passed by reference.
 *
 * @return string           Returns
 */
function create_description(&$fromform) {
    // Set the maximum number of characters.
    $summarylength = 40;
    // Get the body of the message.
    $headersummary = stripslashes($fromform->ucla_help_description);
    // Remove unnecessary spaces.
    $headersummary = preg_replace('!\s+!', ' ', $headersummary);

    // Limit the summary into 40 characters and stop at the last complete word.
    // Source: http://stackoverflow.com/questions/79960/how-to-truncate-a-string-in-php-to-
    // the-word-closest-to-a-certain-number-of-chara.
    $parts = preg_split('/([\s\n\r]+)/', $headersummary, null, PREG_SPLIT_DELIM_CAPTURE);
    $partscount = count($parts);
    $length = 0;
    $lastpart = 0;
    for (; $lastpart < $partscount; ++$lastpart) {
        $length += strlen($parts[$lastpart]);
        if ($length > $summarylength) {
            break;
        }
    }
    $headersummary = implode(array_slice($parts, 0, $lastpart));

    return trim($headersummary);
}

/**
 * Constructs body of email that will be sent when user submits help form.
 *
 * @param mixed $fromform   Form data submitted by user. Passed by reference.
 *
 * @return string           Returns
 */
function create_help_message(&$fromform) {
    global $CFG, $SESSION, $USER;

    // Some fields do not make sense for non-logged in users.
    $isloggedin = true;
    if (!isloggedin() || isguestuser()) {
        $isloggedin = false;
    }

    // Parse user agent string.
    require_once($CFG->dirroot.'/vendor/autoload.php');
    $ua = $_SERVER['HTTP_USER_AGENT'];
    $parser = UAParser\Parser::create();
    $result = $parser->parse($ua);

    // Create message body.
    $body = $fromform->ucla_help_description . "\n\n";
    $body .= "*User info*\n";
    $body .= "Name: " . $fromform->ucla_help_name . "\n";
    $body .= "Email: " . $fromform->ucla_help_email . "\n";
    if ($isloggedin) {
        $body .= "UCLA ID: " . $USER->idnumber . "\n";
        $body .= "Username: " . $USER->username . "\n";
        $body .= "Profile: " . $CFG->wwwroot . "/user/view.php?id=" . $USER->id . "\n";
        $body .= "Time modified: " . date('D, M d Y G:H:s A' , $USER->timemodified) . "\n";
        $body .= "Last access: " . date('D, M d Y G:H:s A' , $USER->lastaccess) . "\n";
        $body .= "Last login: " . date('D, M d Y G:H:s A' , $USER->lastlogin) . "\n";
    } else {
        $body .= "_Not logged in_\n";
    }

    $body .= "\n*Referring site detail*\n";
    $body .= "Course Shortname: " . $fromform->course_name . "\n";
    $body .= "SESSION_fromdiscussion: " . @$SESSION->fromdiscussion . "\n";
    if ($isloggedin) {
        if (isset($USER->currentcourseaccess[$fromform->ucla_help_course])) {
            $accesstime = date('D, M d Y G:H:s A' , $USER->currentcourseaccess[$fromform->ucla_help_course]);
        } else {
            $accesstime = date('D, M d Y G:H:s A' , $USER->lastaccess);
        }

        $body .= "Access Time: " . $accesstime . "\n";
    }

    $body .= "\n*System detail*\n";
    if ($isloggedin) {
        $body .= "Auth type: " . $USER->auth . "\n";
        $body .= "Institution: " . $USER->institution . "\n";
    }
    $body .= "Server: " . $_SERVER['SERVER_NAME'] . "\n";
    $body .= "OS: " . $result->os->toString() . "\n";
    $body .= "Browser: " . $result->ua->toString() . "\n";
    $body .= "User agent: " . $_SERVER['HTTP_USER_AGENT'] . "\n";
    $body .= "IP: " . $_SERVER['REMOTE_ADDR'] . "\n";

    $body .= "\n*Recent activity*\n";

    // Get logging records. If the user is logged in as a guest (user ID 1), or
    // is not logged in (user ID 0), then use their IP address to get records.
    $logmanager = get_log_manager();
    $readers = $logmanager->get_readers();
    $reader = $readers['logstore_standard'];
    if (!$isloggedin) {
        // Get the guest user's IP address.
        $guestip = $_SERVER['REMOTE_ADDR'];
        $events = $reader->get_events_select("ip=?", array($guestip), 'timecreated DESC', 0, 10);
    } else {
        $events = $reader->get_events_select("userid=?", array($USER->id), 'timecreated DESC', 0, 10);
    }

    if (empty($events)) {
        $body .= "\n_No log entries_";
    } else {
        $body .= print_ascii_table($events, $isloggedin);
    }

    return $body;
}

/**
 * Given the support contact, it will either message the support contact via
 * email or create a JIRA ticket.
 *
 * Note, this will send real email, because we need to be able to test email
 * integration with 3rd-party support systems. But we will honor the
 * $CFG->noemailever setting. The $CFG->noemailever setting will also prevent
 * JIRA tickets from being created.
 *
 * @param string $supportcontact    Can be email or JIRA account.
 * @param string $from              Optional. Requestor email.
 * @param string $fromname          Optional. Requestor name.
 * @param string $subject           Email subject
 * @param string $body              Email body
 * @param boolean $isfeaturereport  Is report type a feature request.
 * @param string $attachmentfile    Path to file
 * @param string $attachmentname    Name of attachment
 *
 * @return boolean                  Returns false on error, otherwise true.
 */
function message_support_contact($supportcontact, $from=null, $fromname=null,
                                 $subject, $body, $isfeaturereport, $attachmentfile=null, $attachmentname=null) {
    global $CFG, $DB, $USER;

    $result = false;
    // Comment out to test JIRA.
    if (!empty($CFG->noemailever)) {
        // We don't want any messages sent.
        return true;
    }

    if (defined('BEHAT_SITE_RUNNING')) {
        // Fake email sending in behat.
        return true;
    }

    // Now, is the support contact an email address?
    if (validateEmailSyntax($supportcontact)) {
        // Create the user to send the email to.
        $touser = new stdClass();
        $touser->email = $supportcontact;
        $touser->firstname = null;
        $touser->lastname = null;
        $touser->maildisplay = true;
        $touser->mailformat = 1;
        $touser->id = -99;
        $touser->firstnamephonetic = null;
        $touser->lastnamephonetic = null;
        $touser->middlename = null;
        $touser->alternatename = null;

        // Create the user who is sending the email.
        if (isloggedin()) {
            $fromuser = $DB->get_record('user', array('id' => $USER->id), '*', MUST_EXIST);
        } else {
            $fromuser = $DB->get_record('user', array('username' => 'guest'), '*', MUST_EXIST);
        }
        $altfrom = get_config('block_ucla_help', 'fromemail');
        if (!empty($altfrom)) {
            $fromuser->email = $altfrom;
        } else if (!empty($from)) {
            $fromuser->email = $from;
        } else {
            $fromuser->email = $CFG->noreplyaddress;
        }
        if (!empty($fromname)) {
            $fromuser->firstname = $fromname;
        }
        $fromuser->lastname = null;
        $fromuser->maildisplay = true;
        $fromuser->mailformat = 1;

        $result = email_to_user($touser, $fromuser, $subject, $body, '',
                        $attachmentfile, $attachmentname);
    } else if (!empty($supportcontact)) {
        $labels = array();
        if ($isfeaturereport) {
            $labels[] = 'feature-request';
        }

        // Send message via JIRA.
        $params = array(
            'fields' => array(
                'project' => array('id' => get_config('block_ucla_help', 'jira_pid')),
                'issuetype' => array('id' => 1),
                'summary' => $subject,
                'assignee' => array('name' => $supportcontact),
                'reporter' => array('name' => $supportcontact),
                'description' => $body,
                'labels' => $labels
            )
        );

        // Add support request as an adhoc task. Adhoc tasks are retried until successful.
        $task = new block_ucla_help_try_support_request();
        $task->set_custom_data(array(
            'params'            => $params,
            'attachmentfile'    => $attachmentfile,
            'attachmentname'    => $attachmentname
        ));
        $result = \core\task\manager::queue_adhoc_task($task);
    } else {
        // No $supportcontact specified, so return false.
        return $result;
    }

    return $result;
}

/**
 * Returns the jira user or email address to provide support given a context
 * level. Uses support_contacts_manager to get list of support contacts.
 *
 * @see support_contacts_manager
 *
 * @param object $curcontext          Current context object
 * @param boolean $isfeaturereport    Is report type a feature request.
 *
 * @return array                Returns an array of support contacts matching
 *                              most specific context first until it reaches the
 *                              "System" context. Can be a mix of email or JIRA
 *                              users.
 */
function get_support_contact($curcontext, $isfeaturereport) {
    $retval = null;

    // Get support contacts.
    $manager = get_support_contacts_manager();
    $supportcontacts = $manager->get_support_contacts();

    // Get list of contexts to check.
    $contextids = array_merge((array) $curcontext->id,
            (array) $curcontext->get_parent_context_ids());

    foreach ((array) $contextids as $contextid) {
        $context = context::instance_by_id($contextid);
        $contextname = $context->get_context_name(false, true);

        // See if context matches something in support_contacts list.
        if (!empty($supportcontacts[$contextname])) {
            $retval = $supportcontacts[$contextname];
            break;
        }
    }

    if (empty($retval) || $isfeaturereport) {
        // There should be a "System" contact.
        $retval = $supportcontacts['System'];
    }

    $retval = explode(',', $retval);

    return $retval;
}

/**
 * Sends either a "GET", or "POST" request along with the specified parameters
 * using JIRA's REST API
 *
 * @param string $url       The JIRA URL to POST to or GET
 * @param boolean $post     If true, then POST
 * @param array $headers    Array containing the headers for the request
 * @param array $data       Array containing the parameters for the request
 *
 * @return string           Returns false on error, otherwise request result
 */
function send_jira_request($url, $post = false, $headers, $data) {
    $username = get_config('block_ucla_help', 'jira_user');
    $password = get_config('block_ucla_help', 'jira_password');

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_USERPWD, "$username:$password");
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    // JIRA's API can return responses but with an error code, which we would want to indicate failure.
    curl_setopt($curl, CURLOPT_FAILONERROR, true);

    if ($post) {
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    }

    $result = curl_exec($curl);
    curl_close($curl);

    return $result;
}

/**
 * Copied from CCLE 1.9 feedback code.
 * @param array $events
 * @param boolean $isloggedin
 * @return string
 */
function print_ascii_table($events, $isloggedin) {
    global $DB;

    $formattedtable = array();
    $returnval = "";
    $eventsinfo = array();

    // Cache usernames to avoid redundant database accesses.
    $usernames = array();

    // Parse through once to get proper formatting length.
    $count = 0;
    foreach ($events as $event) {
        $eventinfo = array();
        $eventinfo['–'] = ++$count;
        $eventinfo['Time'] = date('D, M d Y G:H:s A', $event->timecreated);
        if (!$isloggedin) {
            // Get the user's fullname.
            if (!array_key_exists($event->userid, $usernames)) {
                $user = $DB->get_record('user', array('id' => $event->userid));
                if (!empty($user)) {
                    $fullname = fullname($user);
                    $usernames[$user->id] = $fullname;
                    $eventinfo['User name'] = $fullname;
                } else {
                    $usernames[$event->userid] = '';
                    $eventinfo['User name'] = '';
                }
            } else {
                $eventinfo['User name'] = $usernames[$event->userid];
            }
        }
        $context = context::instance_by_id($event->contextid, IGNORE_MISSING);
        if ($context) {
            $eventinfo['Event context'] = $context->get_context_name(true);
        } else {
            $eventinfo['Event context'] = '';
        }
        $eventinfo['Event name'] = $event->get_name();
        $eventinfo['URL'] = (string)$event->get_url();
        $logextra = $event->get_logextra();
        $eventinfo['IP'] = $logextra['ip'];

        // Save formatted table entry.
        $eventsinfo[] = $eventinfo;

        // Figure out table padding.
        foreach ($eventinfo as $key => $data) {
            // Get string length.
            $stringlength = strlen(" $data ");

            // Get max length.
            if (!isset($formattedtable[$key])) {
                $formattedtable[$key] = $stringlength;
            } else if ($formattedtable[$key] < $stringlength) {
                $formattedtable[$key] = $stringlength;
            }

            if ($formattedtable[$key] < strlen(" " . $key . " ")) {
                $formattedtable[$key] = strlen(" " . $key . " ");
            }
        }
    }

    // Print table headers.
    $formattedline = "||";
    foreach ($eventinfo as $key => $data) {
        $formattedset = strlen($formattedline);
        $formattedline .= " ";
        $formattedline .= $key;

        // Plus 1 additional, because || is one extra character.
        while (strlen($formattedline) - $formattedset + 1 < $formattedtable[$key]) {
            $formattedline .= " ";
        }
        $formattedline .= "||";
    }
    $returnval .= $formattedline . "\n";

    // Print table entries.
    foreach ($eventsinfo as $eventinfo) {
        $formattedline = "|";
        $formattedset = strlen($formattedline);
        foreach ($eventinfo as $key => $data) {
            $formattedline .= " $data";
            while (strlen($formattedline) - $formattedset < $formattedtable[$key]) {
                $formattedline .= " ";
            }
            $formattedline .= "|";
            $formattedset = strlen($formattedline);
        }
        $returnval .= $formattedline . "\n";
    }
    return $returnval;
}
