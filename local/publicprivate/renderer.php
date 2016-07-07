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
 * Public/private course renderer.
 *
 * @package   local_publicprivate
 * @copyright 2013 UC Regents
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/local/publicprivate/lib/course.class.php');
require_once($CFG->dirroot . '/local/publicprivate/lib/module.class.php');
require_once($CFG->dirroot . '/local/publicprivate/lib.php');
require_once($CFG->dirroot . '/course/renderer.php');

/**
 * Public/private course renderer.
 *
 * Used to override the core course renderer, so that we can inject the
 * public/private editing icon and rearrange the delete icon to be last.
 *
 * Also, we add the grouping label for labels.
 *
 * @package   local_publicprivate
 * @copyright 2013 UC Regents
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_publicprivate_renderer extends core_course_renderer {
    protected $ppcourse;

    /**
     * Public private needs to override the display of course module links. To 
     * achieve this with minimal core edits, it's necessary to write the
     * core_course_renderer and modify $mods
     * 
     * @param moodle_page $page
     * @param string $target
     */
    public function __construct(moodle_page $page, $target) {
        parent::__construct($page, $target);

        // Public private course?
        $this->ppcourse = new PublicPrivate_Course($page->course->id);
    }

    /**
     * Need to override this or else constructor will try to add another
     * modchoosertoggle instance.
     */
    public function add_modchoosertoggle() {
    }

    /**
     * Display menu with public/private using moodle code
     * 
     * @param type $actions
     * @param cm_info $mod
     * @param type $displayoptions
     * @return type
     */
    public function course_section_cm_edit_actions($actions, cm_info $mod = null, $displayoptions = array()) {

        // Add public private.
        $ppeditaction = get_private_public($mod);
        if (!empty($ppeditaction)) {
            $actions = array_merge($actions, $ppeditaction);
        }

        // Remove "Assign roles" from edit menu.
        if (isset($actions['assign'])) {
            unset($actions['assign']);
        }

        // Remove "Groupmodes" from menu.
        if (isset($actions['groupsnone'])) {
            unset($actions['groupsnone']);
        }

        if (isset($actions['groupsvisible'])) {
            unset($actions['groupsvisible']);
        }

        if (isset($actions['groupsseparate'])) {
            unset($actions['groupsseparate']);
        }

        if (isset($actions['nogroupsupport'])) {
            unset($actions['nogroupsupport']);
        }

        //  Move delete to the end.
        if (isset($actions['delete'])) {
            $deledtionaction = $actions['delete'];
            unset($actions['delete']);
            $actions = array_merge($actions, array($deledtionaction));
        }

        return parent::course_section_cm_edit_actions($actions, $mod, $displayoptions);
    }

    /**
     * We are overriding this method because labels do not have grouping label
     * by default.
     *
     * @param cm_info $mod
     * @param array $displayoptions
     * @return string
     */
    public function course_section_cm_name(cm_info $mod, $displayoptions = array()) {
        if ($this->ppcourse->is_activated()) {
            // Get the context from this course module, used to identify if user is in managegroup.
            $context = context_module::instance($mod->id);

            // Labels resources are not printed, so add the grouping name manually. Only instructors see the label
            if (strtolower($mod->modfullname) === 'label' && has_capability('moodle/course:managegroups', $context)) {
                $ppmod = new PublicPrivate_Module($mod->id);
                if ($ppmod->is_private()) {
                    $groupingid = $ppmod->get_grouping();
                    $groupings = groups_get_all_groupings($mod->course);
                    $pptext = html_writer::span('(' . $groupings[$groupingid]->name . ')',
                                    'groupinglabel');
                    $mod->set_after_link($pptext);
                }
            }
        }

        return parent::course_section_cm_name($mod, $displayoptions);
    }

}