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
 * Rearrange library
 *
 * Files relevant to using the rearranger
 *
 * @package block_ucla_rearrange
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  UC Regents
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/../moodleblock.class.php');

/**
 * Rearrange block definition
 *
 * @package block_ucla_rearrange
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  UC Regents
 */
class block_ucla_rearrange extends block_base {

    /**
     * This is where the entire section stuff should go.
     * @var string
     */
    const PRIMARY_DOMNODE = 'major-ns-container';
    /**
     * @var string
     */
    const DEFAULT_TARGETJQ = '.ns-primary';

    /**
     * This is the id of top UL.
     * @var string
     */
    const PAGELIST = 'ns-list';

    /**
     * This the class of UL.
     * @var string
     */
    const PAGELISTCLASS = 'ns-list-class';

    /**
     * This the class of LI.
     * @var string
     */
    const PAGEITEM = 'ns-list-item';

    /**
     * This the id of UL.
     * @var string
     */
    const SECTIONLIST = 's-list';

    /**
     * This the class of UL.
     * @var string
     */
    const SECTIONLISTCLASS = 's-list-class';

    /**
     * This is the LI class.
     * @var string
     */
    const SECTIONITEM = 's-list-item';

    /**
     * Non-nesting class.
     * @var string
     */
    const NONNESTING = 'ns-invisible';

    /**
     * Style "hidden" indicator for non-visisble sections/modules.
     * @var string
     */
    const HIDDENCLASS = 'ucla_rearrange_hidden';

    /**
     *  Required for Moodle.
     **/
    public function init() {
        $this->title = get_string('pluginname', 'block_ucla_rearrange');
        $this->cron = 0;
    }

    /**
     *  Do not allow block to be added anywhere
     */
    public function applicable_formats() {
        return array(
            'site-index' => false,
            'course-view' => false,
            'my' => false,
            'not-really-applicable' => true
        );
    }

    /**
     * Returns an array of root modnode objects for a particular section.
     * @param int $section     The section number
     * @param string $sectinfo    Section info that includes the sequence of course
     *                     modules in the section
     * @param array $mods        The list of mods from get_all_mods().
     * @param object $modinfo     The mod information from get_all_mods().
     * @param string $courseid    The id of the course
     **/
    public static function mods_to_modnode_tree($section, &$sectinfo, &$mods,
            &$modinfo, $courseid) {

        $nodes = array();
        $sectionmods = array();

        $sectionmods = explode(',', $sectinfo->sequence);

        foreach ($sectionmods as $modid) {
            if (isset($mods[$modid])) {
                $cm =& $mods[$modid];

                if ($cm->section != $sectinfo->id) {
                    // For some reason code branch seems to occur
                    // intrinsically in Moodle.
                    // TODO Figure out why this happens and what we should do
                    // 'bout it.
                    debugging('Mismatching section for ' . $cm->name
                        . "(got {$cm->section} expecting {$sectinfo->id})\n");
                    continue;
                }

                $displaytext = format_string($modinfo->cms[$modid]->name,
                    true, $courseid);
                $ishidden = !$modinfo->cms[$modid]->visible;

                $nodes[] = new modnode($modid, $displaytext, $cm->indent,
                        false, $ishidden);
            }
        }

        $rootnodes = modnode::build($nodes);

//        // Add a pseudo-node that is required for section-to-section movement
//        // of modules.
//        $rootnodes = array_merge(array(
//                new modnode($section . "-" . 0, '', 0, true)
//            ), $rootnodes);

        return $rootnodes;
    }

    /**
     * Gets section modnode objects
     *
     * @param string $courseid
     * @param array $sections
     * @param array $mods
     * @param object $modinfo
     * @return array
     */
    public static function get_sections_modnodes($courseid, &$sections, &$mods,
            &$modinfo) {

        $sectionnodes = array();
        foreach ($sections as $section) {
            $sectionnodes[$section->id] = self::mods_to_modnode_tree(
                $section->section,
                $section, $mods, $modinfo, $courseid
            );
        }

        return $sectionnodes;
    }

    /**
     * Takes an array of an array of modnode objects and renders in such
     * a way that you get back an array of HTML.
     * @param array $setmodnodes
     * @return array of HTML.
     **/
    public static function render_set_modnodes(&$setmodnodes) {
        $rendered = array();
        foreach ($setmodnodes as $index => $modnodes) {
            $local = html_writer::start_tag('ul',
                array('class' => self::PAGELISTCLASS,
                    'id' => self::PAGELIST . '-' . $index));

            foreach ($modnodes as $modnode) {
                $local .= $modnode->render();
            }

            $local .= html_writer::end_tag('ul');

            $rendered[$index] = $local;
        }

        return $rendered;
    }

    /**
     * Convenience function, returns an array of the HTML rendered
     * UL and LI DOM Objects ready to be spit out into JSON.
     * @param string $courseid
     * @param array $sections
     * @param array $mods
     * @param object $modinfo
     **/
    public static function get_section_modules_rendered(&$courseid, &$sections,
            &$mods, &$modinfo) {
        $snodes = self::get_sections_modnodes($courseid, $sections, $mods,
            $modinfo);

        return self::render_set_modnodes($snodes);
    }

    /**
     * Adds the required javascript files.
     * This is the actual jQuery scripts along with the nestedsortable
     * javascript files.
     **/
    public static function javascript_requires() {
        global $CFG, $PAGE;

        $jspath = '/blocks/ucla_rearrange/javascript/';

        $PAGE->requires->js($jspath . 'jquery-1.6.2.min.js');
        $PAGE->requires->js($jspath . 'interface-1.2.min.js');
        if (debugging()) {
            $PAGE->requires->js($jspath . 'inestedsortable-1.0.1.js');
        } else {
            $PAGE->requires->js($jspath . 'inestedsortable-1.0.1.pack.js');
        }
    }

    /**
     * Convenience function to generate a variable assignment
     * statement in JavaScript.
     * @param string $var
     * @param string $val
     * @param boolean $quote
     **/
    public static function js_variable_code($var, $val, $quote = true) {
        if ($quote) {
            $val = '"' . addslashes($val) . '"';
        }

        return 'M.block_ucla_rearrange.' . $var . ' = ' . $val;
    }

    /**
     * Adds all the javascript code for the global PAGE object.
     * This does not add any of the functionality calls, it just
     * makes the NestedSortable API available to all those who dare
     * to attempt to use it.
     *
     * @param string $sectionslist
     * @param string $targetobj
     * @param array $customvars
     **/
    public static function setup_nested_sortable_js($sectionslist, $targetobj=null,
            $customvars=array()) {
        global $PAGE;

        // Include all the jQuery, interface and nestedSortable files.
        self::javascript_requires();

        // This file contains all the library functions that we need.
        $PAGE->requires->js('/blocks/ucla_rearrange'
            . '/javascript/block_ucla_rearrange.js');
        $PAGE->requires->js('/blocks/ucla_rearrange/yui/unload.js');

        // These are all the sections, neatly split by section.
        $PAGE->requires->js_init_code(self::js_variable_code(
            'sections', json_encode($sectionslist), false));

        // This is the jQuery query used to find the object to
        // run NestedSortable upon.
        $PAGE->requires->js_init_code(self::js_variable_code(
            'targetjq', $targetobj));

        // This is a list of customizable variables, non-essential
        // Hooray for lack of convention, so I have to link each
        // of the PHP variables to its JavaScript variable!
        // Note to self: Try to stick with convention.
        $othervars = array(
            'sortableitem' => self::SECTIONITEM,
            'nestedsortableitem' => self::PAGEITEM,
            'nonnesting' => self::NONNESTING
        );

        // This API has some variables.
        foreach ($othervars as $variable => $default) {
            $rhs = $default;
            if (isset($customvars[$variable])) {
                $rhs = $customvars[$variable];
            }

            $PAGE->requires->js_init_code(
                self::js_variable_code($variable, $rhs)
            );
        }

        // And what the hell you can spec out some of your own variables.
        foreach ($customvars as $variable => $custom) {
            if (!isset($othervars[$variable])) {
                $PAGE->requires->js_init_code(
                    self::js_variable_code($variable, $custom)
                );
            }
        }
        $PAGE->requires->js_init_call('M.block_ucla_rearrange.init', array());
        $PAGE->requires->js_init_code(
            'M.block_ucla_rearrange.build_ns_config();'
        );
    }

    /**
     * Wraps around PHP native function parse_str();
     *
     * @param string $serializedstr
     * @return array
     */
    public static function parse_serial($serializedstr) {
        parse_str($serializedstr, $returner);

        return $returner;
    }

    /**
     * Cleans section order
     * @param array $sections
     * @return array array of cleaned sections
     */
    public static function clean_section_order($sections) {
        if (empty($sections)) {
            return array();
        }

        $clean = array();
        foreach ($sections as $sectionold => $sectiontext) {
            // Accounting for the unavailablity of section 0.
            $text = explode('-', $sectiontext);
            $clean[$sectionold] = end($text);
        }

        return $clean;
    }

    /**
     * Moves a bunch of course modules to a different section
     * There should already be a function for this, but there is not.
     * @param array $sectionmodules
     *     An Array of [ OLD_SECTION_DESTINATION ]
     *         => Array ( MODULE->id, indent )
     * @param array $ordersections
     *     An Array of [ NEW_SECTION_ORDER ] => OLD_SECTION ID
     **/
    public static function move_modules_section_bulk($sectionmodules,
            $ordersections=array()) {
        global $DB, $COURSE;

        // Split the array of oldsections with new modules into
        // an array of section sequences, and module indents?
        $coursemodules = array();
        $sections = array();

        foreach ($sectionmodules as $section => $modules) {
            $modulearr = array();
            $sectionarr = array();
            $sectseq = array();

            foreach ($modules as $module) {
                // Repitch the values.
                foreach ($module as $k => $v) {
                    $modulearr[$k] = $v;
                }

                // This should never hit.
                if (!isset($modulearr['id'])) {
                    print_error(get_string('error_module_consistency',
                        'block_ucla_rearrange'));

                    return false;
                }

                // Add section.
                $modulearr['section'] = $section;

                // Create the sequence.
                $sectseq[] = $modulearr['id'];

                $coursemodules[] = $modulearr;
            }

            // Get the sequence.
            $sectionarr['sequence'] = trim(implode(',', $sectseq));

            // Move the section itself.
            if (isset($ordersections[$section])) {
                $sectionarr['section'] = $ordersections[$section];
            }

            $sectionarr['id'] = $section;

            // Save the new section.
            $sections[$section] = $sectionarr;
        }

        foreach ($coursemodules as $module) {
            // Note the boolean at the end is not used in mysql
            // (as of moodle 2.1.1) also, this always returns true...
            $DB->update_record('course_modules', $module, true);
        }

        // CCLE-3930 - Rearrange erases modules in sections when javascript is turned off or not fully loaded.
        // Var $empty initially set to true.
        $empty = true;
        // Disregards the first section. This code only matters when jQuery is
        // disabled, so no rearrange changes can be made in any of the sections
        // anyways. If jQuery is disabled, there should be no section numbers
        // in the remaining sections so $empty should never be set to false.
        foreach (array_slice($sections, 1) as $section) {
            if (isset($section['section'])) {
                $empty = false;
            }
        }

        // Using the largest positive value that can be stored in an int, minus 1.
        $tempnum = 2147483646; // This is 2147483647-1.

        // Var $empty is false only when javascript is enabled and
        // sections have been rearranged.
        if (!$empty) {
            foreach ($sections as $section) {
                // Check if we are updating course menu sections.
                if (isset($section['section'])) {
                    $coursesectionpair = array('course' => $COURSE->id, 'section' => $section['section']);
                    if ($DB->record_exists('course_sections', $coursesectionpair)) {
                        $DB->set_field('course_sections', 'section', $tempnum, $coursesectionpair);
                        $tempnum--;
                    }
                    $DB->update_record('course_sections', $section, true);
                }
            }
        }
    }

    /**
     * Adding link to site menu block header.
     *
     * @param array $params
     *
     * @return array   Returns link to tool.
     */
    public static function get_editing_link($params) {
        $icon = 'fa-arrows';    // Fontawesome icon.
        $link = new moodle_url('/blocks/ucla_rearrange/rearrange.php',
                array('courseid' => $params['course']->id, 'section' => $params['section']));
        $text = get_string('pluginname', 'block_ucla_rearrange');
        return array('icon' => $icon, 'link' => $link->out(false), 'text' => $text);
    }
}

/**
 * Class representing a nested-form of indents and modules in a section.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  UC Regents
 **/
class modnode {
    /**
     * Mod id
     * @var string
     */
    public $modid;
    /**
     * Mode string
     * @var string
     */
    public $modtext;
    /**
     * How far to indent
     * @var int
     */
    public $modindent;
    /**
     * Makes node invisible
     * @var bool
     */
    public $invis = false;
    /**
     * Makes node hidden
     * @var bool
     */
    public $ishidden = false;

    /**
     * Array of child nodes
     * @var array
     */
    public $children = array();

    /**
     * Constructor
     *
     * @param string $id          ID of module
     * @param string $text        Text to display for node
     * @param int $indent      How far to indent node
     * @param boolean $invis       If true, then applies invisible class for node
     * @param boolean $ishidden   If true, then adds in text to indicate if given
     *                          node/module if hidden
     */
    public function __construct($id, $text, $indent, $invis=false, $ishidden=false) {
        $this->modid = $id;
        $this->modtext = $text;
        $this->modindent = $indent;
        $this->invis = $invis;
        $this->ishidden = $ishidden;
    }

    /**
     * Adds child node
     * @param string $node
     */
    public function add_child(&$node) {
        $this->children[] =& $node;
    }

    /**
     * Returns string to render
     * @return string
     */
    public function render() {
        $childrender = '';

        if (!empty($this->children)) {
            $insides = '';

            foreach ($this->children as $child) {
                $insides .= $child->render();
            }

            $childrender = html_writer::tag('ul', $insides, array(
                'class' => block_ucla_rearrange::PAGELISTCLASS
            ));
        }

        $class = block_ucla_rearrange::PAGEITEM;
        if ($this->invis) {
            $class .= ' ' . block_ucla_rearrange::NONNESTING;

        }

        $ishiddentext = '';
        if ($this->ishidden) {
            $ishiddentext = ' ' . html_writer::tag('span',
                    '(' . get_string('hidden', 'block_ucla_rearrange') . ')',
                    array('class' => block_ucla_rearrange::HIDDENCLASS));
        }

        $current = html_writer::tag('div', $this->modtext . $ishiddentext);
        $self = html_writer::tag('li', $current . $childrender,
                array('id' => 'ele-' . $this->modid,
                      'class' => $class)
        );

        return $self;
    }

    /**
     * Translates a root nodes into a flattened list with indents.
     * @param string $root
     * @param int $indent
     * @return string
     **/
    public static function flatten($root, $indent=0) {
        $set = array();

        if (empty($root)) {
            return array();
        }

        foreach ($root as $node) {
            if (!$node['id']) {
                continue;
            }

            $nodeindent = new stdclass();

            $nodeindent->id = $node['id'];
            $nodeindent->indent = $indent;

            $set[] = $nodeindent;

            if (isset($node['children']) && !empty($node['children'])) {
                $return = self::flatten($node['children'], $indent + 1);

                $set = array_merge($set, $return);
            }
        }

        return $set;
    }

    /**
     * Translates a flat list with indents into a set of root nodes.
     *
     * @param array $nodes
     **/
    public static function build(&$nodes) {
        $parentstack = array();
        $rootnodes = array();

        // Take the numerated depth structure and get a nested tree.
        foreach ($nodes as $index => &$node) {
            if (count($parentstack) == 0) {
                array_push($rootnodes, $node);
            } else {
                $indentdiff = $node->modindent - $nodes[$index - 1]->modindent;

                if ($indentdiff <= 0) {
                    // Goto the previous possible parent at the same
                    // indentation level.
                    for ($i = abs($indentdiff) + 1; $i > 0; $i--) {
                        array_pop($parentstack);
                    }

                    if (count($parentstack) == 0) {
                        array_push($rootnodes, $node);
                    } else {
                        $nodes[end($parentstack)]->add_child($node);
                    }
                } else {
                    $nodes[end($parentstack)]->add_child($node);
                }
            }

            array_push($parentstack, $index);
        }

        return $rootnodes;
    }
}
