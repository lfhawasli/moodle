<?php
/**
 *  Base class for UCLA download course content.
 **/

abstract class course_content {
    /** 
     *  
     **/
    abstract function get_zip();
    
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
