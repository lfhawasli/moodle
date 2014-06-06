<?php
// This file is part of the UCLA course download plugin for Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Class file.
 *
 * @package     block_ucla_course_download
 * @copyright   2014 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Class to handle the querying and zipping of course files.
 *
 * @package    block_ucla_course_download
 * @copyright   2014 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_ucla_course_download_files extends block_ucla_course_download_base {

    /**
     * Array of stored_files objects for course.
     * @var array
     */
    private $files;

    /**
     * Constructor that gets the info for all the files and checks if a 
     * zip already exists.
     *
     * @param int $courseid
     * @param int $userid
     */
    public function __construct($courseid, $userid) {
        global $CFG;
        require_once("$CFG->dirroot/mod/resource/locallib.php");
        require_once("$CFG->libdir/completionlib.php");
        require_once("$CFG->libdir/filelib.php");

        parent::__construct($courseid, $userid);

        $this->files = $this->get_content();
    }

    /**
     * Returns request status.
     *
     * @return string
     */
    public function get_request_status() {
        global $DB;

        if ($request = $this->get_request()) {
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
     * Returns the timerequested and timeupdated for course/user request.
     * @return array
     */
    public function get_request_update_time() {
        if ($request = $this->get_request()) {
            return array($request->timerequested, $request->timeupdated);
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

    /**
     * Adds request for given user/course to ucla_archives table.
     *
     * @return boolean  Returns true if new request was added, else returns
     *                  false if request already existed.
     */
    public function add_request() {
        global $DB;

        $request = new stdClass();
        $request->courseid      = $this->course->id;
        $request->userid        = $this->userid;
        $request->type          = $this->get_type();
        $request->content       = json_encode($this->build_zip_array());
        $request->contexthash   = sha1($request->content);
        $request->timerequested = time();

        $conditions = array(
            'courseid'  => $request->courseid,
            'userid'    => $request->userid,
            'type'      => $this->get_type()
        );

        if (!$DB->record_exists('ucla_archives', $conditions)) {
            $DB->insert_record('ucla_archives', $request);
            return true;
        }
        return false;
    }

    /**
     * Get all the resources that are viewable to the user in this course.
     *
     * @return array    Returns an array of stored_files objects.
     */
    public function get_content() {
        $modinfo = new course_modinfo($this->course, $this->userid);
        $resourcemods = $modinfo->get_instances_of('resource');

        if (!empty($resourcemods)) {
            $files = array();

            // Fetch file info and add to files array if they are under the limit.
            $fs = get_file_storage();
            foreach ($resourcemods as $resourcemod) {
                // Do not include hidden or inaccessible files.
                if (!$resourcemod->uservisible) {
                    continue;
                }

                $context = context_module::instance($resourcemod->id);
                $fsfiles = $fs->get_area_files($context->id, 'mod_resource',
                        'content', 0, 'sortorder DESC, id ASC', false);

                if (count($fsfiles) >= 1) {
                    $mainfile = reset($fsfiles);
                    if ($mainfile->get_filesize() < 524288000) { // TODO: MAKE THIS A CONFIG VARIABLE.
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
     *
     * @return bool
     */
    public function has_content() {
        if (!empty($this->files)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Creates the zip file.
     *
     * @param array $filesforzipping    Array of zip file contents.
     * @return mixed                    Returns new file object of created zip
     *                                  file if successful, else returns null.
     */
    public function create_zip($filesforzipping) {
        global $DB, $CFG;

        $filename = clean_filename($this->course->shortname . '-' . 
                get_string('files_archive', 'block_ucla_course_download') . '.zip');
        $tempzip = tempnam($CFG->tempdir . '/', $filename);

        $zipper = new zip_packer();

        if ($zipper->archive_to_pathname($filesforzipping, $tempzip)) {
            return $this->add_new_file_record($filename, $tempzip);
        }
        return null;
    }

    /**
     * Add record to file table for either new zip file or existing zip file.
     *
     * @param string $filename      Name of zip file.
     * @param mixed $tempzip        Name of newly created zip file, null if
     *                              using existing zip file.
     * @param mixed $similarzipid   Id of existing zip file, null if using newly
     *                              created zip file.
     * @return stored_file
     */
    public function add_new_file_record($filename, $tempzip = null, $similarzipid = null) {
        global $DB;

        // Add new file.
        $fs = get_file_storage();

        $context = context_course::instance($this->course->id);
        $request = $this->get_request();

        $filerecord = array(
            'contextid'     => $context->id,
            'component'     => 'block_ucla_course_download',
            'filearea'      => $this->get_type(),
            'itemid'        => $request->id,
            'filepath'      => '/',
            'filename'      => $filename,
            'userid'        => $this->userid,
            'timecreated'   => time(),
            'timemodified'  => time()
        );

        if ($fs->file_exists($filerecord['contextid'], $filerecord['component'],
                        $filerecord['filearea'], $filerecord['itemid'],
                        $filerecord['filepath'], $filerecord['filename'])) {
            // Delete previous file record.
            $fs->delete_area_files($filerecord['contextid'],
                    $filerecord['component'], $filerecord['filearea'],
                    $filerecord['itemid']);
        }

        if (is_null($tempzip) && !is_null($similarzipid)) {
            // Create new file record for existing zip.
            $newfile = $fs->create_file_from_storedfile($filerecord,
                    $fs->get_file_by_id($similarzipid));
        } else {
            $newfile = $fs->create_file_from_pathname($filerecord, $tempzip);
        }

        return $newfile;
    }

    /**
     * Builts array used for creating the zip file.
     *
     * @return array    Returns an array indexed by
     *                  section name (folder)/filename => file path on system.
     */
    public function build_zip_array() {
        global $DB;

        // Make sure there are files to be zipped.
        if (empty($this->files)) {
            return array();
        }

        // Build a list of files to zip.
        $filesforzipping = array();

        $format = course_get_format($this->course);

        // Add files to list of files to zip organized by section.
        $sectionnames = array();    // Cache indexed by sectionid => name.
        foreach ($this->files as $file) {
            if (!array_key_exists($file->section, $sectionnames)) {
                $section = $DB->get_record('course_sections',
                        array('id' => $file->section));
                $sectionnames[$file->section] = $format->get_section_name($section);
            }
            $index = $sectionnames[$file->section].'/'.$file->get_filename();
            $filesforzipping[$index] = $file;
        }

        return $filesforzipping;
    }

    /**
     * Check for new class content by comparing context hash values.
     *
     * @param array $filesforzipping
     * @param string $newcontexthash
     * @return boolean
     */
    public function has_new_content(&$filesforzipping, &$newcontexthash) {
        global $DB;

        $filesforzipping = $this->build_zip_array();
        $newcontexthash = sha1(json_encode($filesforzipping));

        $request = $this->get_request();
        $oldcontexthash = $request->contexthash;
        if ($oldcontexthash != $newcontexthash) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Processes request for object's course and user request record.
     *
     * @return mixed      Returns processed $request record. Returns false on
     *                    error. Returns null if request was deleted, because
     *                    there is no content or request is old.
     */
    public function process_request() {
        global $DB;

        $request = $this->get_request();
        if (empty($request)) {
            return false;
        }

        // Check update zip for existing archive.
        if (isset($request->fileid)) {
            // Check for changed content.
            if (empty($this->has_content())) {
                // Course no longer has files, so delete file and request.
                self::delete_zip($request);
                return null;
            } else if ($this->has_new_content($filesforzipping, $contexthash)) {
                if (!$similarrequest = $this->has_zip($contexthash)) {
                    $newfile = $this->create_zip($filesforzipping);
                    if ($newfile) {
                        $request->fileid = $newfile->get_id();
                        $request->contexthash = $contexthash;
                        $request->content = json_encode($filesforzipping);
                        $request->timeupdated = time();
                    } else {
                        return false;
                    }
                } else {
                    $filename = $DB->get_field('files', 'filename',
                            array('id' => $similarrequest->fileid));
                    $newfile = $this->add_new_file_record($filename, null,
                            $similarrequest->fileid);
                    if ($newfile) {
                        $request->fileid = $newfile->get_id();
                        $request->contexthash = $similarrequest->contexthash;
                        $request->content = $similarrequest->content;
                        $request->timeupdated = $similarrequest->timeupdated;
                    } else {
                        return false;
                    }
                }
                // Send update email.
                $this->email_request($request);
            }
        } else {
            // Make sure we have files to zip.
            if (empty($this->has_content())) {
                self::delete_zip($request); // Delete request.
                return null;
            }

            // Process new request.
            $filesforzipping = $this->build_zip_array();
            if (!$similarrequest = $this->has_zip(sha1(json_encode($filesforzipping)))) {
                $newfile = $this->create_zip($filesforzipping);
                if ($newfile) {
                    $request->fileid = $newfile->get_id();
                    $request->content = json_encode($filesforzipping);
                    $request->contexthash = sha1($request->content);
                    $request->timeupdated = time();
                } else {
                    return false;
                }
            } else {
                $filename = $DB->get_field('files', 'filename',
                        array('id' => $similarrequest->fileid));
                $newfile = $this->add_new_file_record($filename, null,
                        $similarrequest->fileid);
                if ($newfile) {
                    $request->fileid = $newfile->get_id();
                    $request->contexthash = $similarrequest->contexthash;
                    $request->content = $similarrequest->content;
                    $request->timeupdated = $similarrequest->timeupdated;
                } else {
                    return false;
                }
            }
            // Send update email.
            $this->email_request($request);
        }
        $DB->update_record('ucla_archives', $request);
        return $request;
    }

}
