<?php

class block_ucla_course_download_files extends block_ucla_course_download_base {

    private $files;

    /**
     * Constructor that gets the info for all the files and checks if a 
     * zip already exists.
     */
    function __construct($courseid, $userid) {
        global $CFG, $USER;
        require_once("$CFG->libdir/filelib.php");
        require_once("$CFG->dirroot/mod/resource/locallib.php");
        require_once($CFG->libdir.'/completionlib.php');

        parent::__construct($courseid, $userid);

        $this->files = $this->get_content();
    }

    /**
     * Get button status.. place elsewhere?
     * @return string
     */
    function get_request_status(&$timerequested, &$timeupdated) {
        global $DB;

        if ($request = $this->get_request()) {
            $timerequested = $request->timerequested;
            $timeupdated = $request->timeupdated;

            if (isset($request->timeupdated)) {
                return 'request_completed';
            } else {
                return 'request_in_progress';
            }
        } else {
            if ($this->has_content()) {
                return 'request_available';
            } else {
                return 'request_unavailable';
            }
        }
    }

    /**
     * Returns that this handles files for the course content download.
     *
     * @return string
     */
    public function get_type() {
        return 'files';
    }

    function add_request() {
        global $DB;

        $request = new stdClass();

        $request->courseid = $this->course->id;
        $request->userid = $this->userid;
        $request->type = $this->get_type();
        $request->content = json_encode($this->build_zip_array());
        $request->contexthash = sha1($request->content);
        $request->timerequested = time();

        $conditions = array('courseid' => $request->courseid, 'userid' =>$request->userid, 'type' => $this->get_type());

        if(!$DB->record_exists('ucla_archives', $conditions)) {
            $DB->insert_record('ucla_archives', $request);
        }
    }

    /**
     * Get all the resources that are viewable to the user in this course.
     */
    function get_content() {
        $modinfo = new course_modinfo($this->course, $this->userid);
        $resourcemods = $modinfo->get_instances_of('resource');

        if (!empty($resourcemods)){
            $files = array();

            // Fetch file info and add to files array if they are under the limit.
            $fs = get_file_storage();
            foreach ($resourcemods as $resourcemod) {
                $context = context_module::instance($resourcemod->id);
                $fsfiles = $fs->get_area_files($context->id, 'mod_resource', 'content', 0, 'sortorder DESC, id ASC', false);

                if (count($fsfiles) >= 1) {
                    $mainfile = reset($fsfiles);
                    if ($mainfile->get_filesize() < 524288000) { // TODO: MAKE THIS A CONFIG VARIABLE
                        $mainfile->section = $resourcemod->section;
                        $files[] = $mainfile;
                    }
                }
            }
            return $files;
        }
    }

    /**
     * Checks if there are files in the course that are visible to the user.
     * @return bool
     */
    function has_content() {
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
        global $DB, $CFG;

        $filename = clean_filename($this->course->shortname . '-'. get_string('files_archive', 'block_ucla_course_download') . '.zip');
        $tempzip = tempnam($CFG->tempdir.'/', $filename);

        $zipper = new zip_packer();

        if ($zipper->archive_to_pathname($filesforzipping, $tempzip)) {
            return $this->add_new_file_record($filename, $tempzip);
        }
        return NULL;
    }

    /**
     * Add record to file table for either new zip file or existing zip file.
     * @param string $filename name of zip file
     * @param $tempzip name of newly created zip file, false if existing zip file
     * @param $similarzipid id of existing zip file, false if newly created zip file
     */
    function add_new_file_record($filename, $tempzip = NULL, $similarzipid = NULL) {
        global $DB;

        // Add new file.
        $fs = get_file_storage();

        $context = context_course::instance($this->course->id);
        $requestid = $DB->get_field('ucla_archives', 'id', array("courseid" => $this->course->id, "userid" => $this->userid,
                                              "type" => $this->get_type()));

         $filerecord = array(
            'contextid'   => $context->id,
            'component'   => 'block_ucla_course_download',
            'filearea'    => $this->get_type(),
            'itemid'      => $requestid,
            'filepath'    => '/',
            'filename'    => $filename,
            'userid'      => $this->userid,
            'timecreated' => time(),
            'timemodified'=> time());

        if ($fs->file_exists($filerecord['contextid'], $filerecord['component'], $filerecord['filearea'],
                                         $filerecord['itemid'], $filerecord['filepath'], $filerecord['filename'])) {
           // Delete previous file record.
           $fs->delete_area_files($filerecord['contextid'], $filerecord['component'], $filerecord['filearea'], $filerecord['itemid']);
        }    

        if (is_null($tempzip) && !is_null($similarzipid)) {
            // Create new file record for existing zip.
            $newfile = $fs->create_file_from_storedfile($filerecord, $fs->get_file_by_id($similarzipid));
        } else {
            $newfile = $fs->create_file_from_pathname($filerecord, $tempzip);
        }

        return $newfile;       
    }

    function build_zip_array() {
        global $DB;

        // Build a list of files to zip.
        $filesforzipping = array();

        $format = course_get_format($this->course);

        // Add files to list of files to zip.
        foreach ($this->files as $file) {
            $section = $DB->get_record('course_sections', array('id'=>$file->section));
            $sectionname = $format->get_section_name($section);

           // $filepath = $file->get_filepath() . $file->get_filename();
            $filesforzipping[$sectionname . '/' . $file->get_filename()] = $file;
        }

        return $filesforzipping;
    }

    /** 
     *  Check for new class content by comparing context hash values.
     **/
    function has_new_content(&$filesforzipping, &$newcontexthash) {
        global $DB;

        $filesforzipping = $this->build_zip_array();

        $newcontexthash = sha1(json_encode($filesforzipping));

        $oldcontexthash = $DB->get_field('ucla_archives', 'contexthash', array("courseid" => $this->course->id, "userid" => $this->userid,
                                                 "type" => $this->get_type()));
        if( $oldcontexthash != $newcontexthash ) {
            return true;
        } else {
            return false;
        }
    }

    function process_request($request) {
        global $DB;

        // Check update zip for existing archive.
        if (isset($request->fileid)) {

            echo "existing archive  ";

            // Check for new content.
            // TODO: handle case when course no longer has files.
            if ($this->has_new_content($filesforzipping, $contexthash)) {
                echo "has new content  ";

                if (!$similarrequest = $this->has_zip($contexthash)) {
                    echo "creating new zip  \n";
                    $newfile = $this->create_zip($filesforzipping);

                    if ($newfile) {
                        $request->fileid = $newfile->get_id();
                        $request->contexthash = $contexthash;
                        $request->content = json_encode($filesforzipping);
                        $request->timeupdated = time();
                    } else {
                        // Error creating zip file.
                    }
                 }
                 else {
                     echo "similar zip found  ";
                     $filename = $DB->get_field('files', 'filename', array('id'=>$similarrequest->fileid));
                     $newfile = $this->add_new_file_record($filename, NULL,$similarrequest->fileid);

                     if ($newfile) {
                         $request->fileid = $newfile->get_id();
                         $request->contexthash = $similarrequest->contexthash;
                         $request->content = $similarrequest->content;
                         $request->timeupdated = $similarrequest->timeupdated;
                     }
                     else {
                         // Error creating zip file.
                     }
                 }
                 // Send update email.
                 $this->email_request($request);
             }
             else {
                echo "no new content  ";
             }
         }
         // Process new request.
         else {
            echo "process new request  ";
            $filesforzipping = $this->build_zip_array();

            if (!$similarrequest = $this->has_zip(sha1(json_encode($filesforzipping)))) {
                echo "no similar zip  ";

                $newfile = $this->create_zip($filesforzipping);

                if ($newfile) {
                    echo "new file  ";
                    $request->fileid = $newfile->get_id();
                    $request->content = json_encode($filesforzipping);
                    $request->contexthash = sha1($request->content);
                    $request->timeupdated = time();
                } else {
                     // Error creating zip file.
                }
             }
             else {
                echo "similar zip found  ";

                $filename = $DB->get_field('files', 'filename', array('id'=>$similarrequest->fileid));
                $newfile = $this->add_new_file_record($filename, NULL,$similarrequest->fileid);

                if ($newfile) {
                    $request->fileid = $newfile->get_id();
                    $request->contexthash = $similarrequest->contexthash;
                    $request->content = $similarrequest->content;
                    $request->timeupdated = $similarrequest->timeupdated;
                }
                else {
                   // Error creating zip file.
                }
            }
            // Send update email.
            $this->email_request($request);
        }
        $DB->update_record('ucla_archives', $request);       
    }
}
