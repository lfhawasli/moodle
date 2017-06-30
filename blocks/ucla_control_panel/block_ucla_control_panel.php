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
 *  Defines the block_ucla_control_panel class
 *
 * @package block_ucla_control_panel
 * @copyright  UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License
 **/
defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/../moodleblock.class.php');
require_once(dirname(__FILE__) . '/ucla_cp_module.php');
require_once($CFG->dirroot.'/local/ucla/lib.php');
require_once($CFG->dirroot.'/course/format/ucla/lib.php');

/**
 * This class defines the ucla control panel block
 *
 * @copyright  UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License
 **/
class block_ucla_control_panel extends block_base {
    /** Static variables for the static function **/
    const HOOK_FN = 'ucla_cp_hook';
    /**
     * @var string MOD_PREFIX
     */
    const MOD_PREFIX = 'ucla_cp_mod_';
    /**
     * @var string CP_MODULE_BLOCKS
     */
    const CP_MODULE_BLOCKS = '__blocks__';

    /**
     * Sets the title and the content type
     * @return void
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_ucla_control_panel');
        $this->content_type = BLOCK_TYPE_TEXT;
    }

    /**
     * Returns a moodle_url to the course content
     * @return moodle_url
     */
    public function get_content() {
        if (!isset($this->course)) {
            global $COURSE;
            $this->course = $COURSE;
        }

        return self::get_action_link($this->course);
    }

    /**
     * Allows config
     * @return boolean
     */
    public function instance_allow_config() {
        return true;
    }

    /**
     *  Returns the applicable places that this block can be added.
     *  This block really cannot be added anywhere, so we just made a place
     *  up (hacky). If we do not do this, we will get this
     *  plugin_devective_exception.
     * @return array
     **/
    public function applicable_formats() {
        return array(
            'site-index' => false,
            'course-view' => false,
            'my' => false,
            'blocks-ucla_control_panel' => false,
            'not-really-applicable' => true
        );
    }

    /**
     *  This will return the views defined by a view file.
     *  Views are each group of command, sorted by tabs.
     * @param string $customloc
     * @return array
     **/
    public static function load_cp_views($customloc=null) {
        $default = '/cp_views.php';

        if ($customloc != null) {
            if (!preg_match('/.*\.php$/', $customloc)) {
                $customloc = $default;
            }
        } else {
            $customloc = $default;
        }

        $file = dirname(__FILE__) . $customloc;

        if (!file_exists($file)) {
            debugging('Could not find views file: ' . $file);
        } else {
            include($file);
        }

        if (!isset($views)) {
            $views = array();
        }

        if (!isset($views['default'])) {
            $views['default'] = array('ucla_cp_mod_common',
                'ucla_cp_mod_myucla', 'ucla_cp_mod_other', 'ucla_cp_mod_student');
        }

        ksort($views);

        return $views;
    }

    /**
     *  This will create a link to the control panel.
     * @param stdClass $course
     * @return moodle_url
     **/
    public static function get_action_link($course) {
        global $CFG;

        $courseid = $course->id;
        // Obtain the section number here.

        $course->format = 'ucla';
        $section = course_get_format($course)->figure_section($course);

        if ($section >= 0) {
            return new moodle_url($CFG->wwwroot . '/blocks/ucla_control_panel/'
                . 'view.php', array('course_id' => $courseid,
                                    'section' => $section));
        } else if ($section == UCLA_FORMAT_DISPLAY_ALL) {
            return new moodle_url($CFG->wwwroot . '/blocks/ucla_control_panel/'
                . 'view.php', array('course_id' => $courseid,
                                    'show_all' => 1));
        } else {
            return new moodle_url($CFG->wwwroot . '/blocks/ucla_control_panel/'
                . 'view.php', array('course_id' => $courseid));
        }
    }

    /**
     * This will load the custom control panel elements, as well as any blocks
     * that have the designated hook function to create elements.
     * @param stdClass $course
     * @param course_context $context
     * @return Array ( Views => Array ( Tags => Array ( Modules ) ) )
     **/
    public static function load_cp_elements($course, $context=null) {
        if (!isset($course->id) && is_string($course)) {
            $courseid = $course;

            $course = new stdClass();
            $course->id = $courseid;
        }

        if ($course->id == SITEID) {
            throw new moodle_exception('Cannot open UCLA control panel '
                 . ' for site home!');
        }

        if ($context === null) {
            $context = context_course::instance($courseid);
        }

        // Grab the possible collections of modules to display.
        $views = self::load_cp_views();

        // Load all the control panel modules.
        $file = dirname(__FILE__) . '/cp_modules.php';
        if (!file_exists($file)) {
            debugging('No control panel module list ' . $file);
            return false;
        }

        $modules = array();

        include($file);

        if (empty($modules)) {
            debugging('No modules found in ' . $file);
        }

        $sections = array();
        $tags = array();

        // The modular block sections.
        $blockmodules = self::load_cp_block_elements(
            $course, $context
        );

        foreach ($blockmodules as $block => $blocksmodules) {
            $modules = array_merge($modules, $blocksmodules);
        }

        // Figure out which elements of the control panel to display and
        // which section to display the element in.
        foreach ($modules as $module) {
            // If the module's capability matches that of the current context.
            if ($module->validate($course, $context)) {
                $modulename = $module->get_key();

                if (!$module->is_tag()) {
                    // If something fits with more than one tag, add
                    // it to both of them.

                    foreach ($module->tags as $section) {
                        $sections[$section][] = $module;
                    }
                } else {
                    $tags[$modulename] = $module;
                }
            }
        }

        // Eliminate unvalidated sections as well as repeated-displayed
        // sections
        // Note that these sections appear in the order they were placed
        // into cp_modules.php.
        $alreadyused = array();
        foreach ($sections as $tag => $modules) {
            // This means that a module has multiple tags, and one of the tags
            // are not view-valid.
            if (!isset($tags[$tag])) {
                unset($sections[$tag]);
                continue;
            }

            // Go through and make sure we're not repeating modules.
            foreach ($modules as $index => $module) {
                $mkey = $module->get_key();

                if ($mkey == 'row_module') {
                    continue;   // Don't dedup MyUCLA links.
                }

                if (isset($alreadyused[$mkey])) {
                    unset($sections[$tag][$index]);
                } else {
                    $alreadyused[$mkey] = true;
                }
            }
        }

        // Now based on each view, sort the tags into their proper
        // tabs.
        $allmodules = array();
        $usedtags = array();
        foreach ($views as $view => $tags) {
            foreach ($tags as $tag) {
                if (isset($sections[$tag])) {
                    // If this tag already exists in another tab,
                    // skip it.
                    if (isset($usedtags[$tag])) {
                        continue;
                    }

                    $usedtags[$tag] = true;

                    if (!isset($allmodules[$view])) {
                        $allmodules[$view] = array();
                    }

                    $allmodules[$view][$tag] = $sections[$tag];
                }
            }
        }

        // Now we're going to add more tabs based on tags we don't
        // have in our views already.
        foreach ($sections as $tag => $modules) {
            if (isset($usedtags[$tag])) {
                continue;
            }

            $allmodules[$tag][$tag] = $modules;
        }
        return $allmodules;
    }

    /**
     * Loads control panel block elements
     * @param stdClass $course
     * @param course_context $context
     * @return array
     */
    public static function load_cp_block_elements($course=null, $context=null) {
        global $CFG, $PAGE;

        $allblocks = $PAGE->blocks->get_installed_blocks();

        $static = self::HOOK_FN;

        $cpelements = array();

        // This functionality is repeated somewhere I don't know where
        // and it sucks.

        foreach ($allblocks as $block) {
            $blockname = 'block_' . $block->name;
            if (!class_exists($blockname)) {
                $filedir = $CFG->dirroot . '/blocks/' . $block->name
                     . '/';

                $filename = $filedir . $blockname . '.php';

                if (file_exists($filename)) {
                    require_once($filename);
                }

                $renderclass = $blockname . '_cp_render.php';
                $rendername = $filedir . $renderclass;
                if (!class_exists($renderclass)
                        && file_exists($rendername)) {
                    require_once($rendername);
                }
            }

            if (method_exists($blockname, $static)) {
                $blockmodules = $blockname::$static($course,
                    $context);

                if (!empty($blockmodules)) {
                    foreach ($blockmodules as $blockmodule) {
                        $module = ucla_cp_module::build($blockmodule);
                        $module->associatedblock = $blockname;
                        $cpelements[$blockname][] = $module;
                    }
                }
            }
        }

        return $cpelements;
    }
}

// EOF.
