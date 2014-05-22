<?php
/**
 *  Base class for UCLA download course content.
 **/

abstract class course_content {

    protected $course;
    protected $userid;

    function __construct($courseid, $userid) {
        $this->course = get_course($courseid);
        $this->userid = $userid;
    }

    /** 
     *  
     **/
    abstract function create_zip($filesforzipping);

    /** 
     *  
     **/
    function download_zip() {

    }

    /** 
     *  Email requestor once zip file is created or updated.
     **/
    function email_request($request) {
        global $DB;
        
        $user = $DB->get_record('user', array('id' => $request->userid));
        $admin = get_admin(); // Change to "SYSTEM"?
        $subject = get_string('email_subject', 'block_ucla_course_download', 'Files');
        
        // TODO: Add time updated and other information.
        $message = get_string('email_message', 'block_ucla_course_download');
        
        return email_to_user($user, $admin, $subject, $message);
    }

    /** 
     *  Check if request is over 30 days old.
     **/
    static function is_old($request) {
        $timerequested = $request->timerequested;

        if (time()-$timerequested > 2592000) { // TODO: make config variable.
            return true;
        }
        else {
            return false;
        }
    }

    /** 
     *  Delete request and corresponding file entry.
     **/
    static function delete_zip($request) {
        global $DB;

        $zipfileid = $DB->get_field('ucla_archives', 'fileid', array('id' => $request->id));

        $DB->delete_records('ucla_archives', array('id' => $request->id));
        $DB->delete_records('files', array("id" => $zipfileid));
    }

    /** 
     *  Check if similar zip file exists.
     *  @return existing similar zip or NULL.
     **/
    function has_zip($contexthash) {
        global $DB;

        if ($requests = $DB->get_records('ucla_archives', array('contexthash' => $contexthash))) {
            // Find request that has already been processed.
            foreach ($requests as $request) {
                if( isset($request->fileid)) {
                    return $request;
                }
            }
        }
        else {
            return NULL;
        }
    }
}
