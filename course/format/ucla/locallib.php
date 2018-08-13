<?php
// This file is part of the UCLA Media block for Moodle - http://moodle.org/
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
 * Local format file.
 *
 * @package    
 * @copyright  2018 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
use html_writer;

function email_students($course, $container) {

    global $DB;

    // See if need to unhide announcement form.
    $unhide = optional_param('unhide', false, PARAM_INT);
    // Get all the forums.
    $courseforums = $DB->get_records('forum', array('course' => $course->id, 'type' => 'news'));
    // Let's try to save some cycles and use moodle's modinfo mechanism.
    $fastmodinfo = get_fast_modinfo($course);

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
        
    // This means that the forum exists.  
    if ($coursemodule->visible == '1') {
        $url = new moodle_url('/mod/forum/post.php', array('forum' => $targetforum->id));
        $container->add(get_string('emailstudents', 'format_ucla'), $url, navigation_node::TYPE_SETTING);
    } else {
        $section = optional_param('section', 0, PARAM_INT);
        $url = new moodle_url('/course/format/ucla/admin_panel.php', 
                array('courseid' => $course->id, 'section'=> $section, 'unhide' => 1, 'sesskey' => sesskey()));
        
        $fulltext = get_string('emailstudents_hidden', 'format_ucla').' '. get_string('unhidelink', 'local_ucla');
        $container->add($fulltext, $url, navigation_node::TYPE_SETTING);
    }
}