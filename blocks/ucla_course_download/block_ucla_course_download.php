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
 * Block class file.
 *
 * @package     block_ucla_course_download
 * @copyright   2014 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Block class to handle cron and display.
 *
 * @package     block_ucla_course_download
 * @copyright   2014 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_ucla_course_download extends block_base {

    /**
     * Processes the requests for the UCLA course download block.
     *
     * @param progress_trace $trace     If passed, will output process.
     * @return boolean
     */
    function cron(progress_trace $trace = null) {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->dirroot . '/course/format/lib.php');
        require_once($CFG->dirroot . '/blocks/ucla_course_download/classes/base.php');

        if (empty($trace)) {
            $trace = new null_progress_trace();
        }

        // Get all requests that are active (ignore stale requests).
        $requests = $DB->get_recordset('ucla_archives', array('active' => '1'));
        if (!$requests->valid()) {
            $trace->output('No records to process.');
            return true;    // No records to process.
        }

        foreach ($requests as $request) {
            $trace->output(sprintf('Processing request %d, type %s',
                    $request->id, $request->type));

            // TODO: Until we get autoclass loading with Moodle 2.6 we will need to
            // include class files manuall.
            // http://docs.moodle.org/dev/Automatic_class_loading
            require_once($CFG->dirroot . '/blocks/ucla_course_download/classes/'.$request->type.'.php');

            $classname = 'block_ucla_course_download_' . $request->type;
            try {
                $archive = new $classname($request->courseid, $request->userid);
            } catch (dml_exception $coursenotfound) {
                $trace->output('Course not found, deleting request', 1);
                $DB->delete_records('ucla_archives', array('id' => $request->id));
                continue;
            }
            $archive->process_request();
            unset($archive);
        }
        $requests->close();
        return true;
    }

    public function init() {
        $this->title = get_string('pluginname', 'block_ucla_course_download');
    }
    
    /**
     * Returns true because block has a settings.php file.
     *
     * @return boolean
     */
    public function has_config() {
        return true;
    }
}
    