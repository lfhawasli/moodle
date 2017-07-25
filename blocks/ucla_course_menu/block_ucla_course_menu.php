<?php
// This file is part of the UCLA course menu block for Moodle - http://moodle.org/
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
 * Contains the block ucla course menu class.
 *
 * @copyright 2004 Moodle
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/blocks/navigation/block_navigation.php');
require_once($CFG->dirroot . '/blocks/ucla_tasites/block_ucla_tasites.php');

/**
 * UCLA course menu block class
 *
 * @package block_ucla_course_menu
 * @copyright 2004 Moodle
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_ucla_course_menu extends block_navigation {

    /**
     * Whether content has been generated.
     * @var boolean
     */
    protected $contentgenerated = false;

    /**
     * The section user is currently viewing.
     * @var int
     */
    protected $displaysection = 0;

    /**
     * Hook function used to get other blocks' junk into this trunk.
     * @var string
     */
    const BLOCK_HOOK_FN = 'get_navigation_nodes';

    /**
     * Hook function used to get other blocks' junk into this trunk.
     * @var string
     */
    const BLOCK_EDITORS_FN = 'get_editing_link';
    /**
     *  Called by Moodle.
     **/
    public function init() {
        global $CFG;
        $this->blockname = get_class($this);
        $this->title = get_string('title', $this->blockname);
        $this->content = new stdClass();
    }

    /**
     * Hide the delete icon, make block undeletable.
     * @return boolean
     */
    public function user_can_edit() {
        return false;
    }

    /**
     *  Called by Moodle.
     **/
    public function instance_allow_multiple() {
        return false;
    }

    /**
     *  Called by Moodle.
     **/
    public function instance_allow_config() {
        return true;
    }

    /**
     *  Called by Moodle.
     **/
    public function has_config() {
        return true;
    }

    /**
     *  Called by Moodle.
     **/
    public function applicable_formats() {
        return array(
            'course' => true
        );
    }

    /**
     *  Called by Moodle.
     **/
    public function get_content() {
        global $CFG;

        if ($this->contentgenerated === true) {
            return $this->content;
        } else {
            $this->content->text = '';
        }

        // Get course preferences and store section user is viewing.
        $format = course_get_format($this->page->course);
        if (($format->get_format() === 'ucla')) {
            $this->displaysection = $format->figure_section();
        }

        $renderer = $this->get_renderer();

        // CCLE-2380 Rearrange Course Materials link when editing is on
        // only display rearrange tool in ucla format.
        if ($this->page->user_is_editing() &&
                $format->get_format() === 'ucla') {

            $specialeditinglinks = $this->create_block_elements(
                    self::BLOCK_EDITORS_FN
                );

            if (!empty($specialeditinglinks)) {
                // We don't always want the editing links to be in alpha order.
                ksort($specialeditinglinks);
                $linkshtml = '';

                foreach ($specialeditinglinks as $specialeditinglink) {
                    $linkshtml .= html_writer::tag('div',
                        $specialeditinglink, array(
                            'class' => 'btn btn-warning edit-control-links'
                        ));
                }

                $this->content->text .= html_writer::tag('div', $linkshtml, array('class' => 'btn-group-vertical edit-controls'));
            }
        }

        // Get section nodes.
        $sectionelements = $this->create_section_elements();
        $sectionelements = $this->trim_nodes($sectionelements);

        $sectionclass = array('class' => 'block_tree list');
        if (block_ucla_tasites::is_tasite($this->page->course->id)) {
            $sectionclass['class'] .= ' tasites';
        }
        $this->content->text .= $renderer->navigation_node($sectionelements, $sectionclass);

        // Separate out non-section nodes so that we can have a different style
        // to them.
        $blockelements = $this->create_block_elements();
        ksort($blockelements);

        $moduleelements = array();
        $formatoptions = $format->get_format_options();
        if (empty($formatoptions['hide_autogenerated_content'])) {
            $moduleelements = $this->create_module_elements();
        }

        $elements = array_merge($blockelements, $moduleelements);
        $elements = $this->trim_nodes($elements);

        $this->content->text .= $renderer->navigation_node($elements,
                array('class' => 'block_tree list module-elements-section'));

        // For some reason cannot use html_writer::start_tag/html_writer::end_tag
        // so use hard-coded HTML.
        // Need to use outside div, because cannot get styling to make
        // background a different color to work with navigation_node class.

        $this->contentgenerated = true;

        return $this->content;
    }

    /**
     *  Fetches the hard-coded defaults for each of the elements that can be
     *  displayed in the block.
     **/
    private function create_section_elements() {
        global $CFG;
        $courseid = $this->page->course->id;

        // Create section links.
        $modinfo = get_fast_modinfo($this->page->course);
        $sections = $modinfo->get_section_info_all();

        // The elements.
        $elements = array();

        // For "Show all".
        $showallurlparams = array(
            'id' => $courseid,
            'show_all' => 1
        );

        // Special case for TA sites.
        $tasiteenrol = block_ucla_tasites::get_tasite_enrol_meta_instance($courseid);
        if (!empty($tasiteenrol)) {
            $elements['parent-course'] = navigation_node::create(
                get_string('parentcourse', 'block_ucla_course_menu'),
                new moodle_url('/course/view.php', array(
                        'id' => $tasiteenrol->customint1
                    )),
                navigation_node::TYPE_SECTION
            );
        }

        // Add node for syllabus (if needed).
        include_once($CFG->dirroot . '/local/ucla_syllabus/locallib.php');
        if (class_exists('ucla_syllabus_manager')) {
            global $COURSE;
            $uclasyllabusmanager = new ucla_syllabus_manager($COURSE);
            $syllabusnode = $uclasyllabusmanager->get_navigation_nodes();
            if (!empty($syllabusnode)) {
                // Found node, so add to section elements.
                $elements[] = $syllabusnode;
            }
        }

        // Set active url to make sure that section 0 and show all are highlighted.
        $format = course_get_format($this->page->course);
        if (($format->get_format() === 'ucla')) {
            // This will allow the navigation node to highlight the
            // current section, including show all
            // but this won't change it for the navigation bar.
            if ($this->displaysection == UCLA_FORMAT_DISPLAY_ALL) {
                navigation_node::override_active_url(
                    new moodle_url('/course/view.php', $showallurlparams)
                );
            } else if ($this->displaysection >= 0) {
                // TODO: there are other pages that we don't want the url to be
                // overridden, but for now just really care about the syllabus.
                if (strpos($this->page->url->get_path(),
                        '/local/ucla_syllabus/') === false) {
                    navigation_node::override_active_url(
                        new moodle_url('/course/view.php',
                                array('id' => $courseid, 'section' => $this->displaysection))
                    );
                }
            }
        }

        $viewhiddensections = has_capability(
            'moodle/course:viewhiddensections', $this->page->context);

        $numsections = course_get_format($this->page->course)->get_course()->numsections;

        foreach ($sections as $section) {
            // TESTINGCCLE-531: Course setting for num sections not reflected.
            if ($section->section > $numsections) {
                continue;
            }

            if (empty($section->name)) {
                $sectionname = get_section_name($this->page->course,
                    $section);
            } else {
                $sectionname = $section->name;
            }

            $sectionname = strip_tags($sectionname);

            // No content in section.
            $nocontent = empty($section->sequence) && empty($section->summary);

            // Check if something may be displayed.
            if (empty($nocontent) || $viewhiddensections) {
                $showsection = $section->uservisible || ($section->visible &&
                    !$section->available && !empty($section->availableinfo));
                if (!$showsection) {
                    unset($section);
                    continue;
                }

                if ($nocontent && $viewhiddensections) {
                    $sectionname = $sectionname . " (empty)";
                }

                $sectnum = $section->section;
                $key = 'section-' . $sectnum;
                $elements[$key] = navigation_node::create($sectionname,
                    new moodle_url('/course/view.php', array(
                        'id' => $courseid,
                        'section' => $sectnum
                    )), navigation_node::TYPE_SECTION
                );

                if (!$section->visible) {
                    $elements[$key]->classes = array('block_ucla_course_menu_hidden');
                }

                if (empty($nocontent)) {
                    if (is_array($elements[$key]->classes)) {
                        $elements[$key]->classes[] = 'hascontent';
                    } else {
                        $elements[$key]->classes = array('hascontent');
                    }
                } else if (!$viewhiddensections) {
                    unset($elements[$key]);
                }
            }
            unset($section);
        }

        // Create view-all section link.
        if (($format->get_format() === 'ucla')) {
            $elements['view-all'] = navigation_node::create(
                get_string('show_all', 'format_ucla'),
                new moodle_url('/course/view.php', $showallurlparams),
                navigation_node::TYPE_SECTION
            );
            $elements['view-all']->classes = array('hascontent');
        }

        return $elements;
    }

    /**
     * Iterates through the blocks and attempts to generate course menu
     * items.
     * @param string $fn
     **/
    private function create_block_elements($fn=null) {
        global $CFG, $COURSE;

        $elements = array();

        if ($fn === null) {
            $fn = self::BLOCK_HOOK_FN;
        }

        if (!isset($this->page)) {
            return $elements;
        }

        $allblocks = $this->page->blocks->get_installed_blocks();
        $course = $this->page->course;

        foreach ($allblocks as $block) {
            // This function provided by core Moodle does not check for
            // the existance of the function, thus the @ will suppress
            // warning messages.

            // If calling BLOCK_EDITORS_FN, then need to also pass in section.
            if ($fn == self::BLOCK_EDITORS_FN) {
                $blockelements = @block_method_result($block->name, $fn, array(
                    'course' => $course,
                    'section' => $this->displaysection
                ));
            } else {
                $blockelements = @block_method_result($block->name, $fn, array(
                    'course' => $course
                ));
            }

            if ($blockelements) {
                // Check if key for block element is non-zero. If so, then we
                // want to add arrays instead of using array_merge to keep keys
                // order.
                if (key($blockelements) != 0) {
                    $elements = $elements + $blockelements;
                } else {
                    $elements = array_merge($elements, $blockelements);
                }
            }
        }

        return $elements;
    }

    /**
     * Create module elements
     * @return array
     */
    private function create_module_elements() {
        global $CFG;

        $courseid = $this->page->course->id;

        $modnamesplural = get_module_types_names(true);
        $modnamesused = get_fast_modinfo($courseid)->get_used_module_names();

        // We don't bother displaying labels because they don't have an index.
        if (isset($modnamesused['label'])) {
            unset($modnamesused['label']);
        }

        // Generate the navigation nodes.
        $navigs = array();
        foreach ($modnamesused as $modname => $modvisible) {
            $modpath = '/mod/' . $modname . '/index.php';

            if (file_exists($CFG->dirroot . $modpath)) {
                // The space is appended to provide some spacing between the icon and the text.
                $modnameshown = ' ' . $modnamesplural[$modname];
                $navigs[] = navigation_node::create($modnameshown,
                    new moodle_url($modpath, array('id' => $courseid)),
                    navigation_node::TYPE_ACTIVITY, null, null,
                    new pix_icon('icon', '', $modname)
                    );
            }
        }
        return $navigs;
    }

    /**
     *  Convenience function to get renderer.
     **/
    private function get_renderer() {
        if (!isset($this->page)) {
            throw new moodle_exception();
        }

        return $this->page->get_renderer('block_ucla_course_menu');
    }

    /**
     *  This allows us to borrow navigation block's stylsheets.
     **/
    public function html_attributes() {
        $orig = parent::html_attributes();
        $orig['class'] .= ' ';

        return $orig;
    }

    /**
     * Convenience function to trim all node elements.
     *
     * @param array $elements   Expecting array of navigation_node elements
     *
     * return array             Returns array of trimmed navigation_nodes
     */
    private function trim_nodes($elements) {
        $trimmode   = $this->config->trimmode;
        $trimlength = $this->config->trimlength;

        foreach ($elements as $element) {
            $this->trim($element, $trimmode, $trimlength,
                ceil($trimlength / 2));
        }

        return $elements;
    }

    /**
     * CCLE-2829 - Remove "Site Menu" block heading
     * @return boolean true
     */
    public function hide_header() {
        return true;
    }

    /**
     * Disallow docking to hide dock icon when header is removed
     * @return boolean false
     */
    public function instance_can_be_docked() {
        return false;
    }

    /**
     * Makes sure that the course menu block is in the top, left of the page.
     */
    public function set_default_location() {
        global $DB;
        // Check block_instances table.
        if ($this->instance->defaultregion != BLOCK_POS_LEFT ||
                $this->instance->defaultweight != -10) {
            // Block is not in proper location, so set it.
            $this->instance->defaultregion = BLOCK_POS_LEFT;
            $this->instance->defaultweight = -10;
            $DB->update_record('block_instances', $this->instance);
        }

        // Check block_positions table.
        if (!empty($this->instance->blockpositionid) &&
                ($this->instance->region != BLOCK_POS_LEFT ||
                 $this->instance->weight != -10)) {
            // Block is not in proper position for page, construct
            // block_positions object.
            $blockpositions = new stdClass();
            $blockpositions->id = $this->instance->blockpositionid;
            $blockpositions->region = BLOCK_POS_LEFT;
            $blockpositions->weight = -10;
            $DB->update_record('block_positions', $blockpositions);
        }

        // See if any other bocks are above this block.
        $where = 'contextid = :contextid AND blockinstanceid <> :id AND region = :region AND weight <= :weight';
        $topblocks = $DB->get_records_select('block_positions', $where,
                array('contextid' => $this->instance->parentcontextid,
                      'id' => $this->instance->id,
                      'region' => $this->instance->defaultregion,
                      'weight' => $this->instance->defaultweight));
        if (!empty($topblocks)) {
            // Found blocks that are above site menu, move them down to -9.
            foreach ($topblocks as $topblock) {
                $topblock->weight = -9;
                $DB->update_record('block_positions', $topblock, true);
            }

            // If we found blocks above site menu, then site menu was moved down.
            $where = 'contextid = :contextid AND blockinstanceid = :id AND weight <> :weight';
            $nottopblock = $DB->get_record_select('block_positions', $where,
                    array('contextid' => $this->instance->parentcontextid,
                          'id' => $this->instance->id,
                          'weight' => $this->instance->defaultweight));
            if (!empty($nottopblock)) {
                // We are not at the top, so fix it.
                $nottopblock->weight = $this->instance->defaultweight;
                $DB->update_record('block_positions', $nottopblock, true);
            }
        }
    }

    /**
     * Set block defaults for trimlength and trimmode
     */
    public function specialization() {
        // Set default values for trimlength and trimmode.
        $setdefaults = false;

        if (is_null($this->config)) {
            $this->config = new stdClass();
        }

        if (!isset($this->config->trimlength)) {
            // If this is the first time loading the block, then use default trimlength.
            $this->config->trimlength = get_config('block_ucla_course_menu', 'trimlength');
            $setdefaults = true;
        }
        if (!isset($this->config->trimmode)) {
            // If this is the first time loading the block, then use default trimlength.
            $this->config->trimmode = get_config('block_ucla_course_menu', 'trimmode');
            $setdefaults = true;
        }
        if (!empty($setdefaults)) {
            $this->instance_config_commit();
        }

        $this->set_default_location();
    }

}
