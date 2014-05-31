<?php
/**
 *  Base class for UCLA download course content.
 **/

abstract class block_ucla_course_download_base {


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
     * Sends file associate with course and request for user.
     *
     * Will send file or file not found error if no file requests. Dies after
     * sending file.
     */
    function download_zip() {
        global $DB;
        
        // Get file record for given request.
        $request = $this->get_request();
        $fileid = $request->fileid;
        if (empty($fileid)) {
            debugging('here1');
            send_file_not_found();
        }

        $fs = get_file_storage();
        $file = $fs->get_file_by_id($fileid);
        if (empty($file)) {
            debugging('here2');
            send_file_not_found();
        }

        send_stored_file($file, 86400, 0, true);
    }

    /**
     * Returns record from ucla_archives table.
     *
     * @return object
     */
    public function get_request() {
        global $DB;
        return $DB->get_record('ucla_archives',
                array('courseid' => $this->course->id,
                      'userid'   => $this->userid,
                      'type'     => $this->get_type()));
    }

    /**
     * Need to define what type of course content class will be obtaining.
     */
    abstract public function get_type();

    /** 
     *  Email requestor once zip file is created or updated.
     **/
    function email_request($request) {
        global $DB;
        
        $user = $DB->get_record('user', array('id' => $request->userid));
        $from = get_string('email_sender', 'block_ucla_course_download');
        $subject = get_string('email_subject', 'block_ucla_course_download', 'Files');
        
        // TODO: Add time updated and other information.
        $message = get_string('email_message', 'block_ucla_course_download');
        
        return email_to_user($user, $from, $subject, $message);
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
     * Delete request and corresponding file entry.
     *
     * @param object $request   Entry from ucla_archives table.
     */
    static function delete_zip($request) {
        global $DB;
        if (!empty($request->fileid)) {
            $fs = get_file_storage();
            $file = $fs->get_file_by_id($request->fileid);
            $file->delete();
        }
        $DB->delete_records('ucla_archives', array('id' => $request->id));
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
