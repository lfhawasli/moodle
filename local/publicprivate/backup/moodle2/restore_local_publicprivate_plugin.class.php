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
 * Restore public/private settings.
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

class restore_local_publicprivate_plugin extends restore_local_plugin {
    /**
     * The public/private setting for module.
     * @var boolean Default to true for older backups without new flags, let's
     *              play it safe and make everything private.
     */
    private $modisprivate = true;

    /**
     * Caches value if course has public/private enabled.
     *
     * @var boolean
     */
    private static $ppenabled = null;

    /**
     * Executes after the course module is restored.
     *
     * Ensures module is public or private.
     */
    public function after_restore_module() {
        if (!is_null(self::$ppenabled) && empty(self::$ppenabled)) {
            // Public/Private is disabled, quit early.
            return;
        }
        // Enabled flag is not set yet.
        if (is_null(self::$ppenabled)) {
            if(!PublicPrivate_Site::is_enabled()) {
                self::$ppenabled = false;
                return; // Public/private is disabled on site level.
            }
            $ppcourse = new PublicPrivate_Course($this->task->get_courseid());
            if (!$ppcourse->is_activated()) {
                self::$ppenabled = false;
                return; // Public/private is disabled on course level.
            }
            // Make sure group/groupings exists.
            $ppcourse->detect_problems(true);

            self::$ppenabled = true;    // Passed all checks.
        }

        $ppmod = PublicPrivate_Module::build($this->task->get_moduleid());
        if ($this->modisprivate) {
            $ppmod->enable();
        } else {
            $ppmod->disable();
        }
    }

    /**
     * Returns the paths to be handled by the plugin at course level.
     */
    protected function define_course_plugin_structure() {
        $paths = array();
        $elename = 'course'; // This defines the postfix of 'process_*' below.
        $elepath = $this->get_pathfor('/');
        $paths[] = new restore_path_element($elename, $elepath);
        return $paths;
    }

    /**
     * Returns the paths to be handled by the plugin at module level.
     */
    protected function define_module_plugin_structure() {
        $paths = array();
        $elename = 'module'; // This defines the postfix of 'process_*' below.
        $elepath = $this->get_pathfor('/');
        $paths[] = new restore_path_element($elename, $elepath);
        return $paths;
    }

    /**
     * Process the 'plugin_local_publicprivate_course' element within the
     * 'course' element in the 'course.xml' file in the '/course' folder of the
     * zipped backup 'mbz' file.
     *
     * @param array $data
     */
    public function process_course($data) {
        $ppcourse = new PublicPrivate_Course($this->task->get_courseid());

        if (!empty($data['enablepublicprivate'])) {
            if (!$ppcourse->is_activated()) {
                $ppcourse->activate();
            }
        } else {
            if ($ppcourse->is_activated()) {
                $ppcourse->deactivate();
            }
        }
    }

    /**
     * Process the 'plugin_local_publicprivate_course' element within the
     * activity 'module.xml' file in the '/course' folder of the zipped backup
     * 'mbz' file.
     *
     * @param array $data
     */
    public function process_module($data) {
        if (empty($data['private'])) {
            $this->modisprivate = false;    // Default is true.
        }
    }
}
