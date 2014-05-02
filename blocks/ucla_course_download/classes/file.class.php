<?php

class file extends course_content {

    private $files;

    /**
     * Constructor that gets the info for all the files and checks if a 
     * zip already exists.
     */
    function __construct($courseid, $userid) {
        global $CFG;
        require_once("$CFG->libdir/filelib.php");
        require_once("$CFG->dirroot/mod/resource/locallib.php");
        require_once($CFG->libdir.'/completionlib.php');
    
        parent::__construct($courseid);

        // Get all the resources that are viewable to the user in this course.
        $modinfo = new course_modinfo($this->course, $userid);
        $resourcemods = $modinfo->get_instances_of('resource');
        
        if (!empty($resourcemods)){
            $this->files = array();
            
            // Fetch file info and add to files array if they are under the limit.
            $fs = get_file_storage();
            foreach ($resourcemods as $resourcemod) {
                $context = context_module::instance($resourcemod->id);
                $fsfiles = $fs->get_area_files($context->id, 'mod_resource', 'content', 0, 'sortorder DESC, id ASC', false);
                
                if (count($fsfiles) >= 1) {
                    $mainfile = reset($fsfiles);
                    if ($mainfile->get_filesize() < 524288000) { // TODO: MAKE THIS A CONFIG VARIABLE
                        $mainfile->section = $resourcemod->section;
                        $this->files[] = $mainfile;
                    }
                }
                
            }
        }        
    }

    /**
     * Checks if there are files in the course that are visible to the user.
     * @return bool
     */
    private function has_content() {
        if(!empty($this->files)) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * 
     */
    private function get_content() {
        
    
    }

    /** 
     *  
     **/
    function get_zip() {
        
    }
    
    /** 
     *  
     **/
    function has_zip() {
        
    }
    
    /** 
     *  
     **/
    function is_old() {
        
    }
    
    /** 
     *  
     **/
    function delete_zip() {
        
    }
    
    /** 
     *  
     **/
    function has_new_content() {
        
    }
    
    /** 
     *  
     **/
    function download_zip() {
        
    }
    
    /** 
     *  
     **/
    function email_request() {
        
    }

}
