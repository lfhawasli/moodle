<?php
/**
 *  Base class for UCLA download course content.
 **/

abstract class course_content {

    protected $course;
    
    function __construct($courseid) {
        $this->course = get_course($courseid);
    }

    /** 
     *  
     **/
    abstract function create_zip($filesforzipping);
    
    /** 
     *  
     **/
    abstract function has_zip();
    
    /** 
     *  
     **/
    abstract function is_old();
    
    /** 
     *  
     **/
    abstract function delete_zip();
    
    /** 
     *  
     **/
    abstract function has_new_content();
    
    /** 
     *  
     **/
    abstract function download_zip();
    
    /** 
     *  
     **/
    abstract function email_request();
    
    /** 
     *  Check if existing similar zip file exists by comparing file hash values.
     **/
    function similar_zips() {}
}
