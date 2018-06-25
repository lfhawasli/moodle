<?php
// This file is part of the UCLA shared theme for Moodle - http://moodle.org/
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
 * UCLA course renderer and overrides Boost course_renderer.
 *
 * @package    theme_uclashared
 * @copyright  2018 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_uclashared\output\core;
defined('MOODLE_INTERNAL') || die();

use moodle_url;
use cm_info;

require_once($CFG->dirroot . '/course/renderer.php');
require_once($CFG->dirroot . '/local/publicprivate/lib/module.class.php');

/**
 * UCLA course renderer and overrides Boost course_renderer.
 *
 * @package    theme_uclashared
 * @copyright  2018 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_renderer extends \theme_boost\output\core\course_renderer{
    /**
     * Display menu with public/private using moodle code
     *
     * @param action_link[] $actions Array of action_link objects
     * @param cm_info $mod The module we are displaying actions for.
     * @param array $displayoptions additional display options:
     *     ownerselector => A JS/CSS selector that can be used to find an cm node.
     *         If specified the owning node will be given the class 'action-menu-shown' when the action
     *         menu is being displayed.
     *     constraintselector => A JS/CSS selector that can be used to find the parent node for which to constrain
     *         the action menu to when it is being displayed.
     *     donotenhance => If set to true the action menu that gets displayed won't be enhanced by JS
     * @return string
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

        // Move delete to the end.
        if (isset($actions['delete'])) {
            $deledtionaction = $actions['delete'];
            unset($actions['delete']);
            $actions = array_merge($actions, array($deledtionaction));
        }

        return parent::course_section_cm_edit_actions($actions, $mod, $displayoptions);
    }

    /**
     * Renders html to display the module content on the course page (i.e. text of the labels)
     *
     * @param cm_info $mod
     * @param array $displayoptions
     * @return string
     */
    public function course_section_cm_text(cm_info $mod, $displayoptions = array()) {
        $output = '';
        if (!$mod->uservisible && empty($mod->availableinfo)) {
            // Nothing to be displayed to the user.
            return $output;
        }
        $content = $mod->get_formatted_content(array('overflowdiv' => true, 'noclean' => true));
        $accesstext = '';
        $textclasses = '';
        if ($mod->uservisible) {
            $conditionalhidden = $this->is_cm_conditionally_hidden($mod);
            $accessiblebutdim = (!$mod->visible || $conditionalhidden) &&
                has_capability('moodle/course:viewhiddenactivities', $mod->context);
            if (!$mod->visible) {
                $textclasses .= ' dimmed_text';
            }
            if ($accessiblebutdim) {
                if ($conditionalhidden) {
                    $textclasses .= ' conditionalhidden';
                }
                // Show accessibility note only if user can access the module himself.
                $accesstext = get_accesshide(get_string('hiddenfromstudents').':'. $mod->modfullname);
            }
        } else {
            $textclasses .= ' dimmed_text';
        }
        if ($mod->url) {
            if ($content) {
                // If specified, display extra content after link.
                $output = \html_writer::tag('div', $content, array('class' =>
                        trim('contentafterlink ' . $textclasses)));
            }
        } else {
            // No link, so display only content.
            $output = \html_writer::tag('div', $accesstext . $content,
                    array('class' => 'contentwithoutlink ' . $textclasses));
        }
        return $output;
    }

    /**
     * Renders HTML to display one course module in a course section
     *
     * This includes link, content, availability, completion info and additional information
     * that module type wants to display (i.e. number of unread forum posts)
     *
     * This function calls:
     * {@link core_course_renderer::course_section_cm_name()}
     * {@link core_course_renderer::course_section_cm_text()}
     * {@link core_course_renderer::course_section_cm_availability()}
     * {@link core_course_renderer::course_section_cm_completion()}
     * {@link course_get_cm_edit_actions()}
     * {@link core_course_renderer::course_section_cm_edit_actions()}
     *
     * @param stdClass $course
     * @param completion_info $completioninfo
     * @param cm_info $mod
     * @param int|null $sectionreturn
     * @param array $displayoptions
     * @return string
     */
    public function course_section_cm($course, &$completioninfo, cm_info $mod, $sectionreturn, $displayoptions = array()) {
        $output = '';
        // We return empty string (because course module will not be displayed at all) if: 1) The activity is not visible to users
        // and 2) The 'availableinfo' is empty, i.e. the activity was hidden in a way that leaves no info, such as using the eye
        // icon.
        if (!$mod->uservisible && empty($mod->availableinfo)) {
            return $output;
        }

        $indentclasses = 'mod-indent';
        if (!empty($mod->indent)) {
            $indentclasses .= ' mod-indent-'.$mod->indent;
            if ($mod->indent > 15) {
                $indentclasses .= ' mod-indent-huge';
            }
        }

        $output .= \html_writer::start_tag('div');

        if ($this->page->user_is_editing()) {
            $output .= course_get_cm_move($mod, $sectionreturn);
        }

        $output .= \html_writer::start_tag('div', array('class' => 'mod-indent-outer'));

        // This div is used to indent the content.
        $output .= \html_writer::div('', $indentclasses);

        // Start a wrapper for the actual content to keep the indentation consistent.
        $output .= \html_writer::start_tag('div');

        // Display the link to the module (or do nothing if module has no url).
        $cmname = $this->course_section_cm_name($mod, $displayoptions);

        // Create availability conditions popup.
        $this->page->requires->yui_module('moodle-local_ucla-availabilityconditions', 'M.local_ucla.init_availabilityconditions');
        $availabilityhtml = $this->course_section_cm_availability($mod, $displayoptions);

        $ppmodule = \PublicPrivate_Module::build($mod);
        $ppstate = $ppmodule->is_private() ? 'private' : 'public';

        $link = \html_writer::link('#', get_string('availabilityconditions', 'local_ucla'), array('aria-haspopup' => 'true'));
        $classes = 'groupinglabel availabilitypopup'; // CSS classes for the popup.
        if ($ppstate == 'public' || empty($availabilityhtml)) {
            $classes .= ' hide';
        }
        $availabilitypopup = \html_writer::span($link, $classes, array(
            'data-availabilityconditions' => $availabilityhtml,
            'data-ppstate' => $ppstate
        ));

        if (!empty($cmname)) {
            // Start the div for the activity title, excluding the edit icons.
            $output .= \html_writer::start_tag('div', array('class' => 'activityinstance'));
            $output .= $this->course_section_cm_completion($course, $completioninfo, $mod, $displayoptions);
            $output .= $cmname;

            // Module can put text after the link (e.g. forum unread).
            $output .= $mod->afterlink;

            // CCLE-5989.
            $output .= $availabilitypopup;

            // Closing the tag which contains everything but edit icons. Content part of the module should not be part of this.
            // For .activityinstance.
            $output .= \html_writer::end_tag('div');
        }

        // If there is content but NO link (eg label), then display the content here (BEFORE any icons). In this case cons must be
        // displayed after the content so that it makes more sense visually and for accessibility reasons, e.g. if you have a
        // one-line label it should work similarly (at least in terms of ordering) to an activity.
        $contentpart = $this->course_section_cm_text($mod, $displayoptions);
        $url = $mod->url;
        if (empty($url)) {
            $output .= $contentpart;
            // CCLE-5989.
            $output .= $availabilitypopup;
        }

        $modicons = '';
        if ($this->page->user_is_editing()) {
            $editactions = course_get_cm_edit_actions($mod, $mod->indent, $sectionreturn);
            $modicons .= ' '. $this->course_section_cm_edit_actions($editactions, $mod, $displayoptions);
            $modicons .= $mod->afterediticons;
        }

        if (!empty($modicons)) {
            $output .= \html_writer::span($modicons, 'actions');
        }

        // If there is content AND a link, then display the content here (AFTER any icons). Otherwise it was displayed before.
        if (!empty($url)) {
            $output .= $contentpart;
        }

        // For $indentclasses.
        $output .= \html_writer::end_tag('div');

        // End of indentation div.
        $output .= \html_writer::end_tag('div');

        $output .= \html_writer::end_tag('div');
        return $output;
    }

    /**
     * Returns the CSS classes for the activity name/content
     *
     * @param cm_info $mod
     * @return array array of two elements ($linkclasses, $textclasses)
     */
    protected function course_section_cm_classes(cm_info $mod) {
        $linkclasses = '';
        $textclasses = '';
        if ($mod->uservisible) {
            $conditionalhidden = $this->is_cm_conditionally_hidden($mod);
            $accessiblebutdim = (!$mod->visible || $conditionalhidden) &&
                has_capability('moodle/course:viewhiddenactivities', $mod->context);
            // Lines 50-59 restructured from original method to add dimmed class
            // only to hidden section items.
            if (!$mod->visible) {
                $linkclasses .= ' dimmed';
                $textclasses .= ' dimmed_text';
            }
            if ($accessiblebutdim) {
                if ($conditionalhidden) {
                    $linkclasses .= ' conditionalhidden';
                    $textclasses .= ' conditionalhidden';
                }
            }
            if ($mod->is_stealth()) {
                // Stealth activity is the one that is not visible on course page.
                // It still may be displayed to the users who can manage it.
                $linkclasses .= ' stealth';
                $textclasses .= ' stealth';
            }
        } else {
            $linkclasses .= ' dimmed';
            $textclasses .= ' dimmed dimmed_text';
        }
        return array($linkclasses, $textclasses);
    }
}
