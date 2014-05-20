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
    abstract function has_zip($contexthash);

    /** 
     *  
     **/
    abstract function download_zip();

    /** 
     *  
     **/
    abstract function email_request();
    
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
     *  Delete request and possibly file entry.
     **/
    static function delete_zip($request) {
        global $DB;

        $zipfileid = $DB->get_field('ucla_archives', 'fileid', array('id' => $request->id));
        
        $DB->delete_records('ucla_archives', array('id' => $request->id));
        
        // Delete file if deleted request was last one. TODO: Check
        if (!$DB->record_exists('ucla_archives', array("courseid" => $request->courseid, "type" => $request->type))) {
            $DB->delete_records('files', array("id" => $zipfileid));
        }
    }

    /** 
     *  Check if existing similar zip file exists by comparing file hash values.
     **/
    function similar_zips() {}
}
