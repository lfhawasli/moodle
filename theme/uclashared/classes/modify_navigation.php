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
 * Reorder and modify the navigation.
 *
 * @package   theme_uclashared
 * @copyright 2018 UC Regents
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_uclashared;
defined('MOODLE_INTERNAL') || die();

/**
 * Adds/remove/rearranges nodes in navigation drawer.
 *
 * Borrows a lot of code from: https://moodle.org/plugins/local_boostnavigation.
 *
 * @package   theme_uclashared
 * @copyright 2018 UC Regents
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class modify_navigation {

    /**
     * Holds sections that should have js to collapse/expand.
     *
     * @var array
     */
    private $collapsenodesforjs = array();

    /**
     * Course node to add elements at the end.
     *
     * @var navigation_node
     */
    private $_coursenode = null;

    /**
     * Moodle's navigation tree.
     *
     * @var global_navigation
     */
    private $navigation = null;

    /**
     * Magic getter.
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name) {
        global $COURSE;
        if ($name == 'coursenode') {
            if (!isset($this->_coursenode)) {
                $this->_coursenode = $this->navigation->find($COURSE->id, \navigation_node::TYPE_COURSE);
            }
            return $this->_coursenode;
        }
    }

    /**
     * Add link to new Control Panel/Site administration page, see CCLE-7193.
     */
    private function add_courseadmin() {
        global $PAGE;

        if ($PAGE->course->id != SITEID) {
            $adminurl = new \moodle_url('/blocks/ucla_control_panel/view.php',
                    array('course_id' => $PAGE->course->id));
            $courseadmin = \navigation_node::create(get_string('courseadministration'),
                        $adminurl, \navigation_node::TYPE_SETTING,
                        null, null, new \pix_icon('i/settings', ''));

            $this->coursenode->add_node($courseadmin);
        }
    }

    /**
     * Add Turn editing on/off.
     */
    private function add_editingmode() {
        global $PAGE;

        if ($PAGE->user_allowed_editing() && $PAGE->course->id != SITEID) {
            // Add the turn on/off settings.
            if ($PAGE->url->compare(new \moodle_url('/course/view.php'), URL_MATCH_BASE)) {
                // We are on the course page, retain the current page params e.g. section.
                $baseurl = clone($PAGE->url);
                $baseurl->param('sesskey', sesskey());
            } else {
                // Edit on the main course page.
                $baseurl = new \moodle_url('/course/view.php',
                        array('id' => $PAGE->course->id,
                              'return' => $PAGE->url->out_as_local_url(false),
                              'sesskey' => sesskey()));
            }

            $editurl = clone($baseurl);
            if ($PAGE->user_is_editing()) {
                $editurl->param('edit', 'off');
                $editstring = get_string('turneditingoff');
            } else {
                $editurl->param('edit', 'on');
                $editstring = get_string('turneditingon');
            }

            // Create flat nav node so we can set divider.
            $turneditingon = new \flat_navigation_node(\navigation_node::create($editstring, $editurl,
                    \navigation_node::TYPE_SETTING,
                    null, null, new \pix_icon('i/edit', '')), 0);
            $turneditingon->set_showdivider(true);
            $this->coursenode->add_node($turneditingon);
        }
    }

    /**
     * Rearrange nodes.
     */
    private function rearrange_nodes() {
        $nodestomove = array('grades' => \global_navigation::TYPE_SETTING,
            'participants' => \global_navigation::TYPE_CONTAINER);
        foreach ($nodestomove as $name => $type) {
            if ($node = $this->navigation->find($name, $type)) {
                $node->remove();
                $this->coursenode->add_node($node);
            }
        }
    }

    /**
     * Remove items we do not want.
     */
    private function remove_nodes() {
        global $COURSE;
        $nodestoremove = array('badgesview' => \global_navigation::TYPE_SETTING,
            'competencies' => \global_navigation::TYPE_SETTING,
            'home' => \global_navigation::TYPE_ROOTNODE,
            'privatefiles' => \global_navigation::TYPE_SETTING);

        // Some nodes we want on My sites.
        if ($COURSE->id != SITEID) {
            $this->navigation->showinflatnavigation = false;
            $nodestoremove['calendar'] = \global_navigation::TYPE_CUSTOM;
        }

        foreach ($nodestoremove as $name => $type) {
            if ($node = $this->navigation->find($name, $type)) {
                $node->remove();
            }
        }

        // Next, we will need the mycourses node and the mycourses node children in any case and don't want to fetch them more
        // than once.
        $mycoursesnode = $this->navigation->find('mycourses', \global_navigation::TYPE_ROOTNODE);
        $mycourseschildrennodeskeys = $mycoursesnode->get_children_key_list();

        if ($mycoursesnode) {
            // Hide mycourses node.
            $mycoursesnode->showinflatnavigation = false;

            // Hide all courses below the mycourses node.
            foreach ($mycourseschildrennodeskeys as $k) {
                $mycoursesnode->get($k)->showinflatnavigation = false;
            }
        }
    }

    /**
     * Modifies Moodle navigation tree.
     *
     * Called from local/ucla/lib.php: local_ucla_extend_navigation().
     * 
     * @param \global_navigation $navigation
     */
    public function run(\global_navigation $navigation) {
        global $PAGE;

        $this->navigation = $navigation;

        // Remove nodes.
        $this->remove_nodes();

        // Add nodes.
        $this->add_editingmode();
        $this->add_courseadmin();

        // Rearrange items.
        $this->rearrange_nodes();

        // If at least one section needs to be collapsed.
        if (!empty($this->collapsenodesforjs)) {
            // Add JavaScript for collapsing nodes to the page.
            $PAGE->requires->js_call_amd('theme_uclashared/collapsenavdrawernodes', 'init', [$this->collapsenodesforjs]);
            // Allow updating the necessary user preferences via Ajax.
            foreach ($this->collapsenodesforjs as $node) {
                user_preference_allow_ajax_update('theme_uclashared-collapse_'.$node.'node', PARAM_BOOL);
            }
        }
    }
}
