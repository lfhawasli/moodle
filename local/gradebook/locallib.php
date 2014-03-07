<?php

require_once($CFG->dirroot.'/local/ucla/lib.php');
require_once('ucla_grade_grade.php');
require_once('ucla_grade_item.php');

final class grade_reporter {

    // Vars
    const NOTSENT = -1;
    const SUCCESS = 0;
    const DATABASE_ERROR = 1;
    const BAD_REQUEST = 2;
    const CONNECTION_ERROR = 3;
    const MAX_COMMENT_LENGTH = 7900;

    private static $_instance = null;
    

    //Private constructor so no one else can use it
    private function __construct() {
    }

    //Cloning is not allowed
    private function __clone() {
    }

    /**
     * Gets the singleton instance of a SOAP connection to MyUCLA
     *
     * @return Object - The connection to MyUCLA
     */
    public static function get_instance() {
        if (self::$_instance === NULL) {
            global $CFG;
            // Fixing coding issues between UTF8 and Windows encoding.
            // See http://stackoverflow.com/a/12551101/6001 for more info.
            $settings = array('exceptions' => true, 'encoding' => 'ISO-8859-1');

            //Careful - can raise exceptions
            self::$_instance = new SoapClient($CFG->gradebook_webservice, $settings);
        }
        return self::$_instance;
    }
    
    /**
     * Add entry to mdl_log
     * 
     * @param array $params these are variables needed to create a proper log entry
     */
    public static function add_to_log($params) {
        
        // It's possible for a grade item not to be a module, in this case
        // module name cannot be NULL in the log, so we use the 'itemtype' instead
        $modname = empty($params['itemmodule']) ? $params['itemtype'] : $params['itemmodule'];
        
        // We need the module ID if it exists
        $cmid = 0;
        try {
            $cmid = get_coursemodule_from_instance($modname, $params['iteminstance']);
        } catch (dml_exception $e) {
            // error happens if trying to look for a non-existant module table            
        }
        $cmid = empty($cmid) ? 0 : $cmid->module;
        
        // Log vars
        $courseid = $params['courseid'];
        $module = $modname;
        $action = $params['action'];
        $url = 'view.php?id=' . $cmid;
        $info = $params['info'];
        $cm = $cmid;
        $user = $params['transactionuser'];
        
        add_to_log($courseid, $module, $action, $url, $info, $cm, $user);
    }
    
    /**
     * Return the ID of the last record in the *_history table.
     * Also sends back the userid of the user to last modify the grade
     * 
     * @param type $table
     * @param type $id
     * @return type 
     */
    public static function get_transactionid($table, $id) {
        global $DB;
        
        $history = $DB->get_records($table . '_history', 
                array('oldid' => $id), 'id DESC', 'id, loggeduser', 0, 1);
        $rec = array_shift($history);
        
        return array($rec->id, $rec->loggeduser);
    }

    public static function change_class(&$obj, $class_type) {
        if (class_exists($class_type, true)) {
            $obj = unserialize(preg_replace("/^O:[0-9]+:\"[^\"]+\":/i", "O:" . strlen($class_type) . ":\"" . $class_type . "\":", serialize($obj)));
            return $obj;
        }
    }

    /**
     * Get the user that made the last grade edit. When called by the
     * event handler, this will be stored in the $this->_user property.
     *
     * Else should be the person from the grade history table. If that user
     * no longer exists or wasn't recorded, them use admin user.
     *
     * @param object $grade_object  Grade object that you are trying to get
     *                              transaction user for
     * @param int $loggeduser       User from grading history table. Can be null
     * @return object               Returns user who made edit
     */
    public static function get_transaction_user($grade_object, $loggeduser = null) {
        global $DB;
        if (empty($grade_object->_user)) {
            $grade_object->_user = $DB->get_record('user', array('id' => $loggeduser));
            if (empty($grade_object->_user)) {
                // user was still not found, so use admin user
                $grade_object->_user = get_admin();
            }
        }
        return $grade_object->_user;
    }
}
