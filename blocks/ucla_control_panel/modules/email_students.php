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
 * This file defines ucla_cp_module_email_students class
 * @package block_ucla_control_panel
 * @copyright  UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
defined('MOODLE_INTERNAL') || die();
/**
 * This class is the email students module for the ucla control panel
 * @copyright  UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
class ucla_cp_module_email_students extends ucla_cp_module {
    /**
     * Constructs the object
     * @param stdClass $course
     * @return boolean
     */
    public function __construct($course) {
        global $CFG, $DB;

        // See if we want to do stuff.
        $unhide = optional_param('unhide', false, PARAM_INT);

        // Get all the forums.
        $courseforums = $DB->get_records('forum',
            array('course' => $course->id, 'type' => 'news'));

        // Let's try to save some cycles and use moodle's modinfo mechanism.
        $fastmodinfo = get_fast_modinfo($course);

        // This is used to have a slightly more stimulative visual notice.
        $this->pre_enabled = false;
        $this->post_enabled = true;

        // Setting default capability.
        $this->capability = 'mod/forum:addnews';

        // Test for forum functionality and catching.
        $initname = 'email_students';

        // Explicit unset.
        unset($initaction);

        // Check that there is only one news forum.
        if (count($courseforums) > 1) {
            $courseforum = false;
            $initname = 'email_students_fix';

            $initaction = new moodle_url($CFG->wwwroot
                . '/course/view.php', array('section' => '0',
                    'id' => $course->id));
        }

        $coursemodule = null;

        // This means that we found 1 news forum
        // Now we need to find the course module associated with it...
        if (count($courseforums) == 1) {
            $instances = $fastmodinfo->get_instances();

            // Just check out the first one.
            $targetforum = array_shift($courseforums);

            foreach ($instances['forum'] as $instance) {
                if ($instance->instance == $targetforum->id) {
                    $coursemodule = $instance;
                    break;
                }
            }
        }

        if (is_null($coursemodule)) {
            debugging('could not find one news forum');
            return false;
        }

        if ($unhide !== false && confirm_sesskey()) {
            $modcontext = context_module::instance($coursemodule->id);
            require_capability('moodle/course:activityvisibility', $modcontext);
            set_coursemodule_visible($coursemodule->id, true);
            \core\event\course_module_updated::create_from_cm($coursemodule, $modcontext)->trigger();
            // Get updated version of course module.
            $coursemodule = get_fast_modinfo($course->id)->get_cm($coursemodule->id);
        }

        if ($coursemodule->visible == '1') {
            // This means that the forum is fine.
            $initaction = new moodle_url($CFG->wwwroot
                . '/mod/forum/post.php',
                array('forum' => $targetforum->id));
        } else {
            // This means that the forum exists and the forum
            // is hidden.
            $this->pre_enabled = true;
            $this->post_enabled = false;

            $this->capability = 'moodle/course:activityvisibility';

            $initaction = new moodle_url($CFG->wwwroot
                . '/blocks/ucla_control_panel/view.php',
                array('unhide' => $targetforum->id,
                    'sesskey' => sesskey(),
                    'course_id' => $course->id));

            $initname = 'email_students_hidden';
        }

        $this->coursemodule = $coursemodule;

        if (!isset($initaction)) {
            $initname = 'email_students_exception';

            // Disable the action.
            $initaction = null;
        }

        parent::__construct($initname, $initaction);
    }

    /**
     * Returns the module key
     * @return string
     */
    public function get_key() {
        return 'email_students';
    }

    /**
     * Returns an array of tags
     * @return array
     */
    public function autotag() {
        return array('ucla_cp_mod_common');
    }

    /**
     * Returns the capability
     * @return string
     */
    public function autocap() {
        return $this->capability;
    }

    /**
     * Returns the module options
     * @return array
     */
    public function autoopts() {
        return array('pre' => $this->pre_enabled,
            'post' => $this->post_enabled);
    }

    /**
     * Validates vars
     * @param stdClass $course
     * @param course_context $context
     * @return boolean
     */
    public function validate($course, $context) {
        if (!isset($this->coursemodule)) {
            debugging('No forum available for emailing students.');
            return false;
        }

        $context = context_module::instance($this->coursemodule->id);

        return has_capability($this->autocap(), $context);
    }
}
