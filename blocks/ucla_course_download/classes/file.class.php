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
     *  Construct the zip file.
     *  @param array of zip file contents
     **/
    function create_zip($filesforzipping) {
        global $DB, $CFG, $USER;
        
        // TODO: add role and/or timestamp to zip filename?
        $filename = clean_filename($this->course->shortname . '-' . get_string('files_archive', 'block_ucla_course_download') . '.zip');
        $tempzip = tempnam($CFG->tempdir.'/', $filename);
        
        $zipper = new zip_packer();
        if ($zipper->archive_to_pathname($filesforzipping, $tempzip)) {
            
            // Add new file.
            $fs = get_file_storage();
            
            $context = context_course::instance($this->course->id);
            $requestid = $DB->get_field('ucla_archives', 'id', array("courseid" => $this->course->id, "userid" => $USER->id,
                                                 "type" => get_string('files_archive', 'block_ucla_course_download')));
            
            $filerecord = array(
            'contextid'   => $context->id,
            'component'   => 'block_ucla_course_download',
            'filearea'    => 'files',
            'itemid'      => 0, // TODO: set to $requestid
            'filepath'    => '/',
            'filename'    => $filename,
            'userid'      => $USER->id,
            'timecreated' => time(),
            'timemodified'=> time());
            
            $newfile = $fs->create_file_from_pathname($filerecord, $tempzip);
            return $newfile;
        }
        return NULL;
    }
    
    /** 
     *  Check if similar zip file exists.
     **/
    function has_zip() {
        global $DB, $USER;
        
        // Build a list of files to zip.
        $filesforzipping = array();

        $format = course_get_format($this->course);
        
        // Add files to list of files to zip.
        foreach ($this->files as $file) {
            $section = $DB->get_record('course_sections', array('id'=>$file->section));
            $sectionname = $format->get_section_name($section);
            
            $filepath = $file->get_filepath() . $file->get_filename();
            $filesforzipping[$sectionname . '/' . $file->get_filename()] = $filepath;
        }
        
        // Obtain SHA1 context hash of array of zip file contents.
        $contexthash = sha1(json_encode($filesforzipping));

        // Update request file pointer to newly created zip file or existing similar zip file.
        $request = $DB->get_record('ucla_archives', array("courseid" => $this->course->id, "userid" => $USER->id,
                                                 "type" => get_string('files_archive', 'block_ucla_course_download')));
        
        if (!$similarzip = $DB->get_record('ucla_archives', array('contexthash' => $contexthash))) {
            $newfile = $this->create_zip($filesforzipping);
            
            if (!$newfile) {
                $request->fileid = $newfile->fileid;
            } else {
                // Error creating zip file.
            }
        }
        else {
            $request->fileid = $similarzip->fileid;
        }
        
        $DB->update_record('ucla_archives', $request);
    }
    
    /** 
     *  Check if request is over 30 days old.
     **/
    function is_old() {
        global $DB, $USER;
        
        $timerequested = $DB->get_field('ucla_archives', 'timerequested', array("courseid" => $this->course->id, "userid" => $USER->id,
                                         "type" => get_string('files_archive', 'block_ucla_course_download')));
        
        if (time()-$timerequested > 2592000) { // TODO: make config variable.
            return true;
        }
        else {
            return false;
        }
    }
    
    /** 
     *  Delete request.
     **/
    function delete_zip() {
        global $DB, $USER;
        
        $DB->delete_records('ucla_archives', array("courseid" => $this->course->id, "userid" => $USER->id,
                                                 "type" => get_string('files_archive', 'block_ucla_course_download'))); 
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
