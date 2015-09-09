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
 * Abstract class file.
 *
 * @package     block_ucla_course_download
 * @copyright   2014 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Base class to handle the querying and zipping of course content.
 *
 * @package     block_ucla_course_download
 * @copyright   2014 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class block_ucla_course_download_base {

    /**
     * Array of whatever content subsquence class needs to query to generate
     * zip file.
     *
     * @var array
     */
    protected $content;

    /**
     * Course object.
     *
     * @var object
     */
    protected $course;

    /**
     * Record from ucla_archive table.
     *
     * @var object
     */
    protected $request;

    /**
     * User id of user for which we are processing/generating a request.
     *
     * @var int
     */
    protected $userid;

    /**
     * Constructor.
     *
     * @param int $courseid
     * @param int $userid
     *
     * @throws dml_exception If course is not found in database.
     */
    public function __construct($courseid, $userid) {
        $this->course = get_course($courseid);
        $this->userid = $userid;
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
            'contextid' => $context->id,
            'component' => 'block_ucla_course_download',
            'filearea' => $this->get_type(),
            'itemid' => $request->id,
            'filepath' => '/',
            'filename' => $filename,
            'userid' => $this->userid,
            'timecreated' => time(),
            'timemodified' => time()
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
     * Adds request for given user/course to ucla_archives table.
     *
     * @return boolean  Returns true if new request was added, else returns
     *                  false if request already existed.
     */
    public function add_request() {
        global $DB;

        $retval = true;
        $request = new stdClass();
        $request->courseid = $this->course->id;
        $request->userid = $this->userid;
        $request->type = $this->get_type();
        $request->content = json_encode($this->get_content());
        $request->contexthash = sha1($request->content);
        $request->timerequested = time();
        $request->numdownloaded = 0;
        $request->active = 1;

        $conditions = array(
            'courseid' => $request->courseid,
            'userid' => $request->userid,
            'type' => $this->get_type()
        );

        $requestid = null;
        $existingrequest = $DB->get_record('ucla_archives', $conditions);
        if (empty($existingrequest)) {
            // If doesn't exist, create it.
            $requestid = $DB->insert_record('ucla_archives', $request);
        } else {
            // Get the inactive request and make it active again.
            $request->id = $existingrequest->id;
            $request->numdownloaded = $existingrequest->numdownloaded;
            $DB->update_record('ucla_archives', $request);
            $retval = false;
            $requestid = $existingrequest->id;
        }

        // Log requests.
        $event = \block_ucla_course_download\event\request_created::create(array(
            'context' => context_course::instance($this->course->id),
            'objectid' => $requestid
        ));
        $event->trigger();

        return $retval;
    }

    /**
     * Creates the zip file.
     *
     * Depends on get_content to return an array appropiate for
     * zip_packer->archive_to_pathname().
     *
     * @return mixed    Returns new file object of created zip file if
     *                  successful, else returns null.
     */
    public function create_zip() {
        global $CFG;

        $content = $this->get_content();
        if (!empty($content)) {
            $filename = clean_filename($this->course->shortname . '-' .
                    $this->get_type() . '.zip');
            if (!file_exists($CFG->tempdir.'/coursedownloaddir')) {
                mkdir($CFG->tempdir.'/coursedownloaddir');
            }
            $tempzip = tempnam($CFG->tempdir.'/coursedownloaddir' . '/', $filename);
            $zipper = new zip_packer();
            if ($zipper->archive_to_pathname($content, $tempzip)) {
                $zipfilerecord = $this->add_new_file_record($filename, $tempzip);
                @unlink($tempzip);  // Cleanup after ourselves.
                return $zipfilerecord;
            }
        }

        return null;
    }

    /**
     * Delete corresponding file entry and make request inactive.
     *
     * @return boolean
     */
    public function delete_zip() {
        global $DB;

        $request = $this->get_request();
        if (empty($request)) {
            return false;
        }

        if (!empty($request->fileid)) {
            $fs = get_file_storage();
            $file = $fs->get_file_by_id($request->fileid);
            if (!empty($file)) {
                // If file doesn't exist, then ignore it.
                $file->delete();
            }
        }

        // Make the request inactive.
        $request->fileid = null;
        $request->timeupdated = time();
        $request->active = 0;
        $DB->update_record('ucla_archives', $request);
        $this->refresh();

        return true;
    }

    /**
     * Sends file associate with course and request for user.
     *
     * Will send file or file not found error if no file requests. Dies after
     * sending file.
     */
    public function download_zip() {
        global $DB;

        // Get file record for given request.
        $request = $this->get_request();
        $fileid = $request->fileid;
        if (empty($fileid)) {
            send_file_not_found();
        }

        $fs = get_file_storage();
        $file = $fs->get_file_by_id($fileid);
        if (empty($file)) {
            send_file_not_found();
        }

        // Record download.
        $request->timedownloaded = time();
        $request->numdownloaded++;
        $objid = $DB->update_record('ucla_archives', $request);

        $event = \block_ucla_course_download\event\zip_downloaded::create(array(
            'context' => context_course::instance($this->course->id),
            'objectid' => $objid
        ));
        $event->trigger();

        send_stored_file($file, 86400, 0, true);
    }

    /**
     * Email requestor once zip file is created or updated.
     *
     * @return boolean
     */
    public function email_request() {
        global $CFG, $DB;

        $request = $this->get_request();        
        $user = $DB->get_record('user', array('id' => $request->userid));

        // Prepare email variables.
        $a = new stdClass();
        $a->shortname = $this->course->shortname;
        $a->type = $this->get_type();
        $a->ziplifetime = get_config('block_ucla_course_download', 'ziplifetime');
        $url = new moodle_url('/blocks/ucla_course_download/view.php', 
                array('courseid' => $request->courseid));
        $a->url = $url->out();

        $from = get_string('emailsender', 'block_ucla_course_download');
        $subject = get_string('emailsubject', 'block_ucla_course_download', $a);
        $message = get_string('emailmessage', 'block_ucla_course_download',$a);

        $coursecontext = context_course::instance($request->courseid);
        if (!has_capability('moodle/course:manageactivities', $coursecontext, $user)) {
            // User does not have ability to manage course, so append copyright
            // statment.
            $message .= "\n\n" . get_string('emailcopyright', 'block_ucla_course_download');
        }

        return email_to_user($user, $from, $subject, $message);
    }

    /**
     * Needs to be defined in subsquence classes.
     *
     * Must return an array usable in zip_packer->archive_to_pathname().
     *
     * @return array    Array needs to be returned in the following way:
     *                  [<section>/<filename>] => [ospathname|stored_file|string]
     */
    abstract public function get_content();

    /**
     * Returns record from ucla_archives table.
     *
     * @param int $activestatus Default of 1 (active).
     * @return object
     */
    public function get_request($activestatus = 1) {
        global $DB;

        if (empty($this->request) || $this->request->active != $activestatus) {
            $this->request = $DB->get_record('ucla_archives', array(
                    'courseid' => $this->course->id,
                    'userid' => $this->userid,
                    'type' => $this->get_type(),
                    'active' => $activestatus)
            );
        }
        return $this->request;
    }

    /**
     * Returns when request is expired.
     *
     * @return int  Returns timestamp of when request is expired. Returns false
     *              on error.
     */
    public function get_request_expiration() {
        $request = $this->get_request();
        if (!empty($request)) {
            $ziplifetime = get_config('block_ucla_course_download',
                    'ziplifetime');
            if (!empty($ziplifetime)) {
                return $request->timerequested + $ziplifetime * DAYSECS;
            }
        }
        return false;
    }

    /**
     * Returns request status.
     *
     * @return string
     */
    public function get_request_status() {
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
     * Need to define what type of course content class will be obtaining.
     */
    abstract public function get_type();

    /**
     * Checks if there are files in the course that are visible to the user.
     *
     * @return bool
     */
    public function has_content() {
        $content = $this->get_content();
        return !empty($content);
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

        $filesforzipping = $this->get_content();
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
     * Check if similar zip file exists for given request.
     *
     * @param string $contexthash   Content hash of zip file that we are looking
     *                              for.
     * @return object   Returns existing request that has similar zip, otherwise
     *                  return null.
     */
    public function has_zip($contexthash) {
        global $DB;

        $where = "contexthash=:contexthash AND type=:type AND " .
                $DB->sql_isnotempty('ucla_archives', 'fileid', true, false);
        return $DB->get_record_select('ucla_archives', $where,
                        array('contexthash' => $contexthash,
                    'type' => $this->get_type()), '*', IGNORE_MULTIPLE);
    }

    /**
     * Check if request is over the ziplifetime limit.
     *
     * @return boolean
     */
    public function is_old() {
        $expiration = $this->get_request_expiration();
        return time() > $expiration;
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

        // Check if request is old.
        if ($this->is_old()) {
            $this->delete_zip();
            return null;
        }

        // Check update zip for existing archive.
        if (isset($request->fileid)) {
            $hascontent = $this->has_content();
            if (empty($hascontent)) {
                // Course no longer has files, so delete file and request.
                $this->delete_zip();
                return null;
            } else if ($this->has_new_content($filesforzipping, $contexthash)) {
                // Check for changed content.
                if (!$similarrequest = $this->has_zip($contexthash)) {
                    // There isn't a similar request, create new zip.
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
                    // There exists a similar zip, so copy it.
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
            }
        } else {
            // Make sure we have files to zip.
            $hascontent = $this->has_content();
            if (empty($hascontent)) {
                $this->delete_zip(); // Delete request.
                return null;
            }

            // Process new request.
            $filesforzipping = $this->get_content();
            if (!$similarrequest = $this->has_zip(sha1(json_encode($filesforzipping)))) {
                // There isn't a similar request, create new zip.
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
                // There exists a similar zip, so copy it.
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

            // Send update email for new requests.
            $this->email_request();
        }
        $DB->update_record('ucla_archives', $request);
        $this->refresh();
        return $request;
    }

    /**
     * Sometimes, like after processing a request or files changed, the cached
     * content like the request may be out of date.
     *
     * Will clear class caches.
     */
    public function refresh() {
        unset($this->content);
        unset($this->request);
    }

}
