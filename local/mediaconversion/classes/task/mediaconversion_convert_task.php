<?php
// This file is part of Moodle - http://moodle.org/
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
 * The media conversion task.
 *
 * @package    local_mediaconversion
 * @copyright  2017 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mediaconversion\task;
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__.'/../../locallib.php');

/**
 * This is the media conversion task class that extends adhoc task.
 *
 * @package    local_mediaconversion
 * @copyright  2017 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mediaconversion_convert_task extends \core\task\adhoc_task {
    /**
     * Executes the task.
     *
     * @throws Exception on error
     */
    public function execute() {
        global $CFG;
        $customdata = $this->get_custom_data();
        $data = $customdata->eventdata;
        // Get the file.
        $fs = get_file_storage();
        $files = $fs->get_area_files($data->contextid, 'mod_resource', 'content');
        // Find a video file.
        $mainfile = null;
        foreach ($files as $file) {
            // Check for a proper filesize and a video.
            if (intval($file->get_filesize()) > 0 && substr($file->get_mimetype(), 0, 5) === 'video') {
                $mainfile = $file;
            }
        }
        // Check if file was found.
        if (!$mainfile) {
            return;
        }
        // Try to copy file.
        if (!($path = $mainfile->copy_content_to_temp())) {
            mtrace('Failed to copy file with id ' . $mainfile->get_id() . ' for cm instance ' . $data->objectid);
            return;
        }
        // Get course module data.
        $courseandmodinfo = get_course_and_cm_from_cmid($data->objectid, $data->other->modulename, $data->courseid);
        // Get the necessary info from the course module.
        $argsinfo = local_cm_package_argsinfo($data, $courseandmodinfo);
        // Find the file directory.
        $dir = $path;
        // Add the kalvidres.
        if (!$newmodinfo = local_cm_convert_video($dir, $argsinfo, $customdata->userid)) {
            mtrace('Failed to convert video at ' . $dir . ' for cm instance ' . $data->objectid);
            return;
        }
        mtrace('Successfully uploaded video with entry ID ' . $newmodinfo->entry_id
                . ' for cm instance ' . $data->objectid);
        // Delete the temp file.
        if (file_exists($dir)) {
            unlink($dir);
        }
        $course = get_course($argsinfo->course);
        $res = null;
        $res = add_moduleinfo($newmodinfo, $course);
        // Check for entry_id to make sure Kaltura upload succeeded.
        if (empty($res) || empty($res->entry_id)) {
            throw new coding_exception("The Kaltura Video Resource could not be added");
        }
        mtrace('Successfully added new Kaltura Video Resource with id ' . $res->instance
                . 'replacing cm instance ' . $data->objectid);
        // Delete the old module.
        course_delete_module($data->objectid);
    }

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('taskmediaconversion_convert', 'local_mediaconversion');
    }
}