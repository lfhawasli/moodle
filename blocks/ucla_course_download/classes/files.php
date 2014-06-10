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
 * @package     block_ucla_course_download
 * @copyright   2014 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_ucla_course_download_files extends block_ucla_course_download_base {

    /**
     * Returns that this handles files for the course content download.
     *
     * @return string
     */
    public function get_type() {
        return 'files';
    }

    /**
     * Get all the resources that are viewable to the user in this course.
     *
     * @return array    Returns an array of stored_files objects.
     */
    public function get_content() {
        global $DB;
        
        // See if there is a cached copy.
        if (!empty($this->content)) {
            return $this->content;
        }

        $this->content = array();

        $format = course_get_format($this->course);
        $modinfo = new course_modinfo($this->course, $this->userid);
        $resourcemods = $modinfo->get_instances_of('resource');

        if (empty($resourcemods)) {
            return $this->content;
        }

        // Fetch file info and add to content array if they are under the limit.
        $fs = get_file_storage();
        $sectionnames = array();    // Cache indexed by sectionid => name
        foreach ($resourcemods as $resourcemod) {
            // Do not include hidden or inaccessible files.
            if (!$resourcemod->uservisible) {
                continue;
            }

            if (!array_key_exists($resourcemod->section, $sectionnames)) {
                $section = $DB->get_record('course_sections',
                        array('id' => $resourcemod->section));
                $sectionnames[$resourcemod->section] = $format->get_section_name($section->section);
            }

            $context = context_module::instance($resourcemod->id);
            $fsfiles = $fs->get_area_files($context->id, 'mod_resource',
                    'content', 0, 'sortorder DESC, id ASC', false);

            if (count($fsfiles) >= 1) {
                $mainfile = reset($fsfiles);
                if ($mainfile->get_filesize() < 524288000) { // TODO: MAKE THIS A CONFIG VARIABLE.
                    // Saving contenthash, because it will be used in checking
                    // if the contents of the zip changed.
                    $mainfile->contenthash = $mainfile->get_contenthash();
                    $index = $sectionnames[$resourcemod->section].'/'.$mainfile->get_filename();
                    $this->content[$index] = $mainfile;
                }
            }
        }

        return $this->content;
    }

}
