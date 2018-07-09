<?php
// This file is part of UCLA Modify Coursemenu plugin for Moodle - http://moodle.org/
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
 * Contains the UCLA modify_coursemenu block, which extends the moodle block_base.
 *
 * @package    block_ucla_modify_coursemenu
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/moodleblock.class.php');

/**
 * Class block_ucla_modify_coursemenu
 *
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_ucla_modify_coursemenu extends block_base {
    /**
     * Primary domnode.
     * @var string
     */
    const PRIMARY_DOMNODE = 'ucla-modifycoursemenu-main';
    /**
     * Maintable domnode.
     * @var string
     */
    const MAINTABLE_DOMNODE = 'sections-table';
    /**
     * New nodes domnode.
     * @var string
     */
    const NEWNODES_DOMNODE = 'new-sections';
    /**
     * Landing page domnode.
     * @var string
     */
    const LANDINGPAGE_DOMNODE = 'landing-page';
    /**
     * Serialized domnode.
     * @var string
     */
    const SERIALIZED_DOMNODE = 'serialized-data';
    /**
     * Add section button.
     * @var string
     */
    const ADD_SECTION_BUTTON = 'add-section-button';
    /**
     * Landing page by dates date range domnode.
     * @var string
     */
    const DATERANGE_DOMNODE = 'daterange-data';

    /**
     * Initializes the title of the coursemenu.
     */
    public function init() {
        $this->title = get_string('pluginname',
            'block_ucla_modify_coursemenu');
    }

    /**
     * Gets the content of this coursemenu.
     *
     * @return stdClass|stdObject
     */
    public function get_content() {
        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();

        return $this->content;
    }

    /**
     * Fetches the course_module information of a section.
     * Note: there is a way to get it just with modinfo, and bypassing
     *     sections completely...
     * @param  object $section
     * @param  stdClass $course     (optional if $section->course exists)
     * @param  object $modinfo      (results of get_fast_modinfo(), optional)
     * @return array of cm_info, can be used with get_print_section_cm_text or anything else...
     */
    public static function get_section_content($section, $course=null, $modinfo=null) {
        global $DB;
        if ($modinfo == null) {
            if ($course == null) {
                if (empty($section->course)) {
                    throw new moodle_exception('sectioncontentnocourse');
                }
            } else {
                $course = $DB->get_record('course', array('id' => $courseid),
                    '*', MUST_EXIST);
            }

            $modinfo = get_fast_modinfo($course);
        }

        if (empty($section->sequence)) {
            return array();
        }

        $cminstances = array_map('trim', explode(',',
                $section->sequence));
        $cminfos = array();

        foreach ($cminstances as $cminstanceid) {
            $cminfos[] = $modinfo->cms[$cminstanceid];
        }

        return $cminfos;
    }

    /**
     * Convenience function to overwrite values of oldsection with
     * values in newsection, but only if oldsection has the field already.
     *
     * @param object $oldsection
     * @param object $newsection
     * @return mixed
     */
    public static function section_apply($oldsection, $newsection) {
        foreach ($newsection as $f => $v) {
            if (isset($oldsection->{$f})) {
                $oldsection->{$f} = $v;
            }
        }

        // The way that checkboxes in html work.
        if (!isset($newsection['visible'])) {
            $oldsection->visible = 1;
        }

        return $oldsection;
    }

    /**
     * Checks if section is empty.
     *
     * @param object $section
     * @return bool
     */
    public static function section_is_empty($section) {
        return empty($section->sequence);
    }

    /**
     * Convenience function for generating code that sets data to
     * associated js object.
     * @param string $varname
     * @param string $value
     */
    public static function js_init_code_helper($varname, $value) {
        global $PAGE;

        $PAGE->requires->js_init_code(
                js_writer::set_variable(
                    'M.block_ucla_modify_coursemenu.' . $varname,
                    $value
                )
            );
    }

    /**
     * Convenience function. Wrapper to run js_init_code_helper on an array.
     * @param array $vararr of strings
     */
    public static function many_js_init_code_helpers($vararr) {
        foreach ($vararr as $vn => $vd) {
            self::js_init_code_helper($vn, $vd);
        }
    }

    /**
     * Adding link to site menu block header.
     *
     * @param array $params
     * @return array   Returns link to tool.
     */
    public static function get_editing_link($params) {
        $icon = 'fa-pencil';    // Fontawesome icon.
        $link = new moodle_url('/blocks/ucla_modify_coursemenu/modify_coursemenu.php',
                array('courseid' => $params['course']->id, 'section' => $params['section']));
        $text = get_string('pluginname', 'block_ucla_modify_coursemenu');
        return array('icon' => $icon, 'link' => $link->out(false), 'text' => $text);
    }

    /**
     * Called by moodle.
     * @return array mapping format strinvgs to booleans.
     */
    public function applicable_formats() {
        return array(
            'site-index' => false,
            'course-view' => false,
            'my' => false,
            'not-really-applicable' => true
        );
        // Hack to make sure the block can never be instantiated.
    }
}
