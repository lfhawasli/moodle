<?php
/**
 * Collection of classes/functions used across multiple scripts for the UCLA 
 * Help and Feedback block.
 * 
 * Else, can be called displayed in a site or course context.
 *
 * @package    ucla
 * @subpackage ucla_help
 * @copyright  2011 UC Regents    
 * @author     Rex Lorenzo <rex@seas.ucla.edu>                                      
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later 
 */
defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/../../lib/adminlib.php');
require_once($CFG->dirroot . '/lib/validateurlsyntax.php');

/*** CLASSES ***/

/**
 * Dervived from admin_setting_emoticons. Used to allow 
 * admins to edit the 'ucla_help' support contact settings.
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class admin_setting_ucla_help_support_contact extends admin_setting {
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
        
        $support_contacts = $this->process_form_data($data);        
        if ($support_contacts === false) {
            return false;
        }
        
        if ($this->config_write($this->name, $this->manager->encode_stored_config($support_contacts))) {
            return ''; // success
        } else {
            return get_string('errorsetting', 'admin') . $this->visiblename . html_writer::empty_tag('br');
        }
    }

    /**
     * Return XHTML field(s) for options
     *
     * @param array $data Array of options to set in HTML
     * @return string XHTML string for the fields and wrapping div(s)
     */
    public function output_html($data, $query='') {
        global $block_ucla_help_support_contacts, $OUTPUT;

        /**
         * Data is in following format:
         * 
         * Array
         * (
         *     [context0] => System
         *     [support_contact0] => dkearney
         *     [context1] => Computer Science
         *     [support_contact1] => rlorenzo
         *     [context2] => 
         *     [support_contact2] => 
         * )
         */        
        $t = new html_table();
        $t->attributes = array('class' => 'generaltable');
        $t->head = array('', get_string('settings_support_contacts_table_context', 'block_ucla_help'), 
                get_string('settings_support_contacts_table_contact', 'block_ucla_help'), '');

        $i = 0; $row_num = 1; $cur_context = ''; $row = array();       
        foreach((array) $data as $field => $value) {

            // first cell is row number
            if ($i == 0) {  
                $row = new html_table_row();
                $cell = new html_table_cell();
                $cell->text = sprintf('%d.', $row_num);
                $row->cells[] = $cell;
                $row_num++;
                
                // save context for later on
                $cur_context = $value;
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
                // on last element, so end row
                $cell = new html_table_cell();
                
                // if context ws defined in config file, then give warning that
                // user cannot change setting
                if (!empty($block_ucla_help_support_contacts[$cur_context])) {
                    $cell->text = html_writer::tag('div', 'Defined in config.php', array('class' => 'form-overridden'));
                }                             
                $row->cells[] = $cell; 
                $t->data[] = $row;

                $i = 0;
            } else {
                $i++;
            }            
        }

        return format_admin_setting($this, $this->visiblename, html_writer::table($t), $this->description, false, '', NULL, $query);
    }

    /**
     * Converts the array of support_contacts provided by 
     * {@see $support_contacts_manager} into admin settings form data
     *
     * @see self::process_form_data()
     * @param array $support_contacts   array of support_contacts as returned by 
     *                                  {@see support_contacts_manager}
     * @return array of form fields and their values
     */
    protected function prepare_form_data(array $support_contacts) {
        
        $form = array();
        $i = 0;
        foreach ((array) $support_contacts as $context => $support_contact) {
            $form['context'.$i]             = $context;
            $form['support_contact'.$i]     = $support_contact;
            $i++;
        }
        // add one more blank field set for new support contact
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
        $NUM_FORM_ELEMENTS = 2;
        
        if (!is_array($form)) {
            return false;
        }
        
        $count = count($form); // number of form field values
        if ($count % $NUM_FORM_ELEMENTS) {
            // we must get two fields per support_contact
            return false;
        }

        $support_contacts = array();
        $count = $count / $NUM_FORM_ELEMENTS;        
        for ($i = 0; $i < $count; $i++) {
            $context         = clean_param(trim($form['context'.$i]), PARAM_NOTAGS);
            $support_contact = clean_param(trim($form['support_contact'.$i]), PARAM_NOTAGS);

            // make sure that entries exists
            if (!empty($context) && !empty($support_contact)) {
                $support_contacts[$context] = $support_contact;
            }            
        }
        return $support_contacts;
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
 * Dervived from emoticon_manager(). Used to encode and decode support_contacts
 * array for block_ucla_block config table.
 *
 * @see admin_setting_support_contacts
 */
class support_contacts_manager {

    /**
     * Returns the current support_contacts.
     *
     * @return array    Array of support_contacts indexed by context => contact.
     */
    public function get_support_contacts() {
        global $block_ucla_help_support_contacts, $CFG;
        
        $support_contacts = get_config('block_ucla_help', 'support_contacts');
        $support_contacts = $this->decode_stored_config((string) $support_contacts);

        // now merge with values from config file to overwrite user set ones
        $support_contacts = array_merge((array) $support_contacts, 
                (array) $block_ucla_help_support_contacts);        

        // If there is no one listed at 'System' context, then try to guess it.
        if (!isset($support_contacts['System'])) {
            $support_contacts['System'] = $CFG->supportemail;
        }

        return $support_contacts;
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
        // make sure that decoded string is an array
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
    // Source: http://stackoverflow.com/questions/79960/how-to-truncate-a-string-in-php-to-the-word-closest-to-a-certain-number-of-chara
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
function create_help_message(&$fromform)
{
    global $COURSE, $CFG, $DB, $SESSION, $USER;
    
    // If user is not logged in, then a majority of these values will raise PHP 
    // notices, so supress them with @    
    
    // setup description array
    $description['maildisplay'][0] = '0 - '.get_string('emaildisplayno');
    $description['maildisplay'][1] = '1 - '.get_string('emaildisplayyes');
    $description['maildisplay'][2] = '2 - '.get_string('emaildisplaycourse');
    $description['autosubscribe'][0] = '0 - '.get_string('autosubscribeno');
    $description['autosubscribe'][1] = '1 - '.get_string('autosubscribeyes');
    $description['emailstop'][0] = '0 - '.get_string('emailenable');
    $description['emailstop'][1] = '1 - '.get_string('emaildisable');
    $description['htmleditor'][0] = '0 - '.get_string('texteditor');
    $description['htmleditor'][1] = '1 - '.get_string('htmleditor');
    $description['trackforums'][0] = '0 - '.get_string('trackforumsno');
    $description['trackforums'][1] = '1 - '.get_string('trackforumsyes');
    
    if (isset($USER->currentcourseaccess[$COURSE->id])) {
        $accesstime = date('r' , $USER->currentcourseaccess[$COURSE->id]);
    } else {
        @$accesstime = date('r' , $USER->lastaccess);
    }

    // Needs stripslashes after obtaining information that has been escaped for security reasons    
    $body = stripslashes($fromform->ucla_help_name) . " wrote: \n\n" . 
            stripslashes($fromform->ucla_help_description) . "\n
    Name: " . stripslashes($fromform->ucla_help_name) . "
    UCLA ID: " . @$USER->idnumber . "
    Email: " . stripslashes($fromform->ucla_help_email) . "
    Server: $_SERVER[SERVER_NAME]
    User_Agent: $_SERVER[HTTP_USER_AGENT]
    Host: $_SERVER[REMOTE_ADDR]
    Referer: $_SERVER[HTTP_REFERER]
    Course Shortname: $fromform->course_name
    Access Time: $accesstime
    User Profile: $CFG->wwwroot/user/view.php?id=$USER->id
    SESSION_fromdiscussion   = " . @$SESSION->fromdiscussion . "
    USER_id                  = $USER->id
    USER_auth                = " . @$USER->auth . "
    USER_username            = " . @$USER->username . "
    USER_institution         = " . @$USER->institution . "
    USER_firstname           = " . @$USER->firstname . "
    USER_lastname            = " . @$USER->lastname . "
    USER_email               = " . @$USER->email . "
    USER_emailstop           = " . @$description['emailstop'][$USER->emailstop] . "
    USER_lastaccess          = " . @date('r' , $USER->lastaccess) . "
    USER_lastlogin           = " . @date('r' , $USER->lastlogin) . "
    USER_lastip              = " . @$USER->lastip . "
    USER_maildisplay         = " . @$description['maildisplay'][$USER->maildisplay] . "
    USER_htmleditor          = " . @$description['htmleditor'][$USER->htmleditor] . "
    USER_autosubscribe       = " . @$description['autosubscribe'][$USER->autosubscribe] . "
    USER_trackforums         = " . @$description['trackforums'][$USER->trackforums] . "
    USER_timemodified        = " . @date('r' , $USER->timemodified);
    $body .= "\n";
    
    // Get logging records. If the user is logged in as a guest (user ID 1), or
    // is not logged in (user ID 0), then use their IP address to get records.
    $log_records;
    if (!isloggedin() || isguestuser()) {
        // Get the guest user's IP address.
        $guest_ip = $_SERVER['REMOTE_ADDR'];
        $log_records = $DB->get_records('log', array('ip' => $guest_ip), 'time DESC', '*', 0, 10);
    } else {
        $log_records = $DB->get_records('log', array('userid' => $USER->id), 'time DESC', '*', 0, 10);
    }
    
    if (empty($log_records)) {
        $body .= "No log entries\n";
    } else {
        $body .= print_ascii_table($log_records);
    }
    
    $body .= 'This message was generated by ' . __FILE__;    
    
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
 * @param string $subject
 * @param string $body
 *
 * @return boolean                  Returns false on error, otherwise true.
 */
function message_support_contact($supportcontact, $from=null, $fromname=null,
                                 $subject, $body) {
    global $CFG;

    $result = false;
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
        // send message via email
        $mail = get_mailer();

        // Check if we want the from email to be something else.
        $altfrom = get_config('block_ucla_help', 'fromemail');
        if (!empty($altfrom)) {
            $mail->From = $altfrom;
        } else if (!empty($from)) {
            $mail->From = $from;
        } else {
            $mail->From = $CFG->noreplyaddress;
        }

        if (!empty($fromname)) {
            $mail->FromName = $fromname;
        }

        // Add support contact email address.
        $mail->AddAddress($supportcontact);

        $mail->Subject = $subject;
        $mail->Body = $body;

        $result = $mail->Send();

    } else if (!empty($supportcontact)) {
        // Send message via JIRA.
        $params = array(
            'pid' => get_config('block_ucla_help', 'jira_pid'),
            'issuetype' => 1,
            'os_username' => get_config('block_ucla_help', 'jira_user'),
            'os_password' => get_config('block_ucla_help', 'jira_password'),
            'summary' => $subject,
            'assignee' => $supportcontact,
            'reporter' => $supportcontact,
            'description' => $body,
        );

        // Try to create the issue.
        $result = send_jira_request(get_config('block_ucla_help', 'jira_endpoint'),
                $params, 'POST');
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
 * @param object $curcontext    Current context object
 * 
 * @return array                Returns an array of support contacts matching
 *                              most specific context first until it reaches the
 *                              "System" context. Can be a mix of email or JIRA
 *                              users.
 */
function get_support_contact($curcontext) {
    $retval = null;

    // get support contacts
    $manager = get_support_contacts_manager();
    $supportcontacts = $manager->get_support_contacts();

   // get list of contexts to check
    $contextids = array_merge((array) $curcontext->id,
            (array) $curcontext->get_parent_context_ids());

    foreach ((array) $contextids as $contextid) {
        $context = context::instance_by_id($contextid);
        $contextname = $context->get_context_name(false, true);
        
        // see if context matches something in support_contacts list
        if (!empty($supportcontacts[$contextname])) {
            $retval = $supportcontacts[$contextname];
            break;
        }
    }

    if (empty($retval)) {
        // There should be a "System" contact.
        $retval = $supportcontacts['System'];
    }

    $retval = explode(',', $retval);

    return $retval;
}

/**
 * Copied from CCLE 1.9 feedback code.
 * @param type $stuff
 * @return string 
 */
function print_ascii_table($stuff)
{
    $formatted_table = array();
    $formatted_string = "";

    // Parse through once to get proper formatting length
    $line_count = 0;
    foreach ($stuff as $line) {
        $line_count++;
        foreach (get_object_vars($line) as $key => $data) {
            unset($test_string);

            // Make the testing string
            $test_string = ' ';
            if ($key == 'time') {
                $test_string .= date('r', $data);
            } else {
                $test_string .= $data;
            }
            $test_string .= ' ';

            // Get length
            $string_length = strlen($test_string);

            // Get max length
            if (!isset($formatted_table[$key])) {
                $formatted_table[$key] = $string_length;
            } else if ($formatted_table[$key] < $string_length) {
                $formatted_table[$key] = $string_length;
            }

            if ($formatted_table[$key] < strlen(" " . $key . " ")) {
                $formatted_table[$key] = strlen(" " . $key . " ");
            }
        }
    }

    $formatted_table['KINDEX'] = 0;
    while ($line_count >= 1) {
        $line_count = $line_count / 10;
        $formatted_table['KINDEX']++;
    }

    $line_count = 0;
    $formatted_string .= "\n";

    // Print field names
    $formatted_line = "| ";
    while (strlen($formatted_line) - 2 < $formatted_table['KINDEX']) {
        $formatted_line .= "-";
    }
    $formatted_line .= " |";
    $formatted_set = strlen($formatted_line);

    $sampleline = $stuff[array_rand($stuff)];
    foreach (get_object_vars($sampleline) as $key => $data) {
        $formatted_line .= " ";
        $formatted_line .= $key;

        while (strlen($formatted_line) - $formatted_set < $formatted_table[$key]) {
            $formatted_line .= " ";
        }
        $formatted_line .= "|";
        $formatted_set = strlen($formatted_line);
    }
    $formatted_string .= $formatted_line . "\n";

    for ($i = 0; $i < $formatted_set; $i++) {
        $formatted_string .= "-";
    }
    $formatted_string .= "\n";

    foreach ($stuff as $line) {
        $line_count++;
        $formatted_line = "| " . $line_count;
        while (strlen($formatted_line) - 3 < $formatted_table['KINDEX']) {
            $formatted_line .= " ";
        }
        $formatted_line .= "|";
        $formatted_set = strlen($formatted_line);

        foreach (get_object_vars($line) as $key => $data) {
            $formatted_line .= " ";
            if ($key == 'time') {
                $formatted_line .= date('r', $data);
            } else {
                $formatted_line .= $data;
            }

            while (strlen($formatted_line) - $formatted_set < $formatted_table[$key]) {
                $formatted_line .= " ";
            }
            $formatted_line .= "|";
            $formatted_set = strlen($formatted_line);
        }
        $formatted_string .= $formatted_line . "\n";
    }
    return $formatted_string;
}
