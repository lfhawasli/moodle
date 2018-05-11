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
 * Backup public/private settings.
 *
 * @package    local_publicprivate
 * @copyright  2018 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

include_once($CFG->dirroot.'/local/publicprivate/lib/course_exception.class.php');
include_once($CFG->dirroot.'/local/publicprivate/lib/course.class.php');
include_once($CFG->dirroot.'/local/publicprivate/lib/module.class.php');
include_once($CFG->dirroot.'/local/publicprivate/lib/site.class.php');

class backup_local_publicprivate_plugin extends backup_local_plugin {
    /**
     * Caches value if public/private is enabled for this course.
     * @var boolean
     */
    private static $ppenabled = false;

    /**
     * Define what should be in the course.xml file for course.
     */
    protected function define_course_plugin_structure() {
        // Quit early.
        if(!PublicPrivate_Site::is_enabled()) {
            self::$ppenabled = false;
            return;
        }

        $plugin = $this->get_plugin_element();
        $publicprivate = new backup_nested_element($this->get_recommended_name(),
                array(), array('enablepublicprivate', 'grouppublicprivate',
                    'groupingpublicprivate'));
        $plugin->add_child($publicprivate);

        $ppcourse = new PublicPrivate_Course($this->task->get_courseid());

        $rec = new stdClass();
        if ($ppcourse->is_activated()) {
            $rec->enablepublicprivate = 1;
            $rec->grouppublicprivate = $ppcourse->get_group();
            $rec->groupingpublicprivate = $ppcourse->get_grouping();
            self::$ppenabled = true;
        } else {
            $rec->enablepublicprivate = 0;
            $rec->grouppublicprivate = 0;
            $rec->groupingpublicprivate = 0;
            self::$ppenabled = false;
        }

        $publicprivate->set_source_array(array($rec));
    }

    /**
     * Define what should be in the module.xml file for an activity.
     */
    protected function define_module_plugin_structure() {
        // If public/private is not active for course then skip this.
        if (empty(self::$ppenabled)) {
            return;
        }

        // Add module public/private settings.
        $plugin = $this->get_plugin_element();
        $publicprivate = new backup_nested_element($this->get_recommended_name(),
                array(), array('private'));
        $plugin->add_child($publicprivate);

        $ppmod = PublicPrivate_Module::build($this->task->get_moduleid());

        $rec = new stdClass();
        $rec->private = $ppmod->is_private() ? 1 : 0;

        $publicprivate->set_source_array(array($rec));
    }
}
