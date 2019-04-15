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
 * The modchooser renderable.
 *
 * @package    core_course
 * @copyright  2016 Frédéric Massart - FMCorz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_course\output;
defined('MOODLE_INTERNAL') || die();

use core\output\chooser;
use core\output\chooser_section;
use context_course;
use lang_string;
use moodle_url;
use pix_icon;
use renderer_base;
use stdClass;

/**
 * The modchooser renderable class.
 *
 * @package    core_course
 * @copyright  2016 Frédéric Massart - FMCorz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class modchooser extends chooser {

    /** @var stdClass The course. */
    public $course;

    // START UCLA MOD: CCLE-7143 - Reimplement CCLE-5967 Simplify activity chooser.
    /** @var int Indicates if modchooser is customizable. */
    public $customizable;
    // END UCLA MOD: CCLE-7143.
    // START UCLA MOD: CCLE-6984 - Improve method of optioning in or out of simplified activity chooser.
    // Current display section.
    public $displaysection;
    // END UCLA MOD: CCLE-6984.

    /**
     * Constructor.
     *
     * @param stdClass $course The course.
     * @param stdClass[] $modules The modules.
     */
    public function __construct(stdClass $course, array $modules) {
        $this->course = $course;
        // START UCLA MOD: CCLE-7143 - Reimplement CCLE-5967 Simplify activity chooser.
        $this->customizable = get_user_preferences('modchoosersetting', 0);
        // END UCLA MOD: CCLE-7143.
        // START UCLA MOD: CCLE-6984 - Improve method of optioning in or out of simplified activity chooser.
        /* @var $format format_ucla.*/
        $format = course_get_format($course);
        if (property_exists( $format, 'format' ) && ($format->get_format() == 'ucla')) {
            $this->displaysection = $format->figure_section();
        }
        // END UCLA MOD: CCLE-6984.

        $sections = [];
        $context = context_course::instance($course->id);

        // START UCLA MOD: CCLE-7143 - Reimplement CCLE-5967 Simplify activity chooser.
        if ($this->customizable) {
            // Combined list of modules.
            $tools = array_filter($modules, function($mod) {
                return ($mod->archetype !== MOD_ARCHETYPE_SYSTEM);
            });
            if (count($tools)) {
                $sections[] = new chooser_section('tools', new lang_string('activitiesresources'),
                    array_map(function($module) use ($context) {
                        return new modchooser_item($module, $context);
                    }, $tools)
                );
            }
        } else {
        // END UCLA MOD: CCLE-7143.    
            // Activities.
            $activities = array_filter($modules, function($mod) {
                return ($mod->archetype !== MOD_ARCHETYPE_RESOURCE && $mod->archetype !== MOD_ARCHETYPE_SYSTEM);
            });
            if (count($activities)) {
                $sections[] = new chooser_section('activities', new lang_string('activities'),
                    array_map(function($module) use ($context) {
                        return new modchooser_item($module, $context);
                    }, $activities)
                );
            }

            $resources = array_filter($modules, function($mod) {
                return ($mod->archetype === MOD_ARCHETYPE_RESOURCE);
            });
            if (count($resources)) {
                $sections[] = new chooser_section('resources', new lang_string('resources'),
                    array_map(function($module) use ($context) {
                        return new modchooser_item($module, $context);
                    }, $resources)
                );
            }
        // START UCLA MOD: CCLE-7143 - Reimplement CCLE-5967 Simplify activity chooser.    
        }
        // END UCLA MOD: CCLE-7143.

        $actionurl = new moodle_url('/course/jumpto.php');
        $title = new lang_string('addresourceoractivity');
        parent::__construct($actionurl, $title, $sections, 'jumplink');

        // START UCLA MOD: CCLE-7143 - Reimplement CCLE-5967 Simplify activity chooser.
        if ($this->customizable) {
        // END UCLA MOD: CCLE-7143.    
            $this->set_instructions(new lang_string('selectmoduletoviewhelp'));
        // START UCLA MOD: CCLE-7143 - Reimplement CCLE-5967 Simplify activity chooser.    
        } else {
            // Add link to new modchooser preference option.
            /* $modchooserlink = '<a href=' . new moodle_url('/course/modchooser_preferences.php',
                    array('returnto' => $this->course->id)) . '>' .
                    get_string('customizemodchooserlink', 'moodle') . '</a>';
            $this->set_instructions(new lang_string('selectmoduletoviewhelpmodchooser', 'moodle', $modchooserlink)); */
            $customizedmclink = '<div class=custmclink><a id=custmc href=' . new moodle_url('/course/view.php',
                    array('id' => $this->course->id, 'section' => $this->displaysection, 'custmc' => 1)) . '>' .
                    get_string('customizemodchooserlink', 'moodle') . '</a></div>';
            $this->set_instructions(new lang_string('selectmoduletoviewhelpmodchooser', 'moodle', $customizedmclink));
        }
        // END UCLA MOD: CCLE-7143.
        $this->add_param('course', $course->id);
    }

    /**
     * Export for template.
     *
     * @param renderer_base  The renderer.
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        $data = parent::export_for_template($output);
        $data->courseid = $this->course->id;
        // START UCLA MOD: CCLE-7143 - Reimplement CCLE-5967 Simplify activity chooser.
        $data->newmodchooser = $this->customizable;

        if ($this->customizable) {
            $defaulttools = get_config('moodlecourse', 'modchooserdefaults');
            $userpinnedtools = get_user_preferences('pinnedtools', $defaulttools);
            // $data->custommodchooserhelp = get_string('custommodchooserhelp');
            $completemclink = '<a id=custmc href=' . new moodle_url('/course/view.php',
                    array('id' => $this->course->id, 'section' => $this->displaysection, 'custmc' => 0)) . '>' .
                    get_string('completemodchooserlink', 'moodle') . '</a>';
            $data->custommodchooserhelp = get_string('custommodchooserhelp').$completemclink;
            $data->defaulttools = $defaulttools;
            $data->userpinnedtools = $userpinnedtools;

            // Mark pinned tools for template.
            $data->sections = array_map(function($section) use ($userpinnedtools) {
                $section->items = array_map(function($item) use ($userpinnedtools) {
                    if (in_array($item->id, explode(',', $userpinnedtools))) {
                        $item->pinned = 'pinned';
                    }
                    return $item;
                }, $section->items);
                return $section;
            }, $data->sections);
        }
        // END UCLA MOD: CCLE-7143.
        return $data;
    }

}
