<?php
// This file is part of the UCLA browse-by plugin for Moodle - http://moodle.org/
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
 * Class file to handle Browse-By collaboration site listings.
 *
 * @package    block_ucla_browseby
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class definition for browsing by collaboration sites.
 *
 * @package    block_ucla_browseby
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class collab_handler extends browseby_handler {

    /**
     * @var $maxuserdisplay Maximum number of users displayed.
     */
    private $maxuserdisplay = 5;

    /**
     * Get the roles that are visible by default.
     *
     * @return array
     */
    static public function get_default_roles_visible() {
        return array('projectlead', 'coursecreator', 'editinginstructor');
    }

    /**
     * Returns what parameters are required for this handler.
     *
     * @return array
     */
    public function get_params() {
        return array('category');
    }

    /**
     * Fetches a list of collaboration sites with an alphabetized index.
     *
     * @param array $args
     */
    public function handle($args) {
        global $CFG, $PAGE;

        // Load search.
        $PAGE->requires->yui_module('moodle-block_ucla_search-search', 'M.ucla_search.init',
                array(array('name' => 'course-search')));

        $navbar =& $PAGE->navbar;

        $collablibfile = $CFG->dirroot . '/' . $CFG->admin
            .'/tool/uclasiteindicator/lib.php';

        $collabcat = false;

        $t = '';
        $s = '';

        if (file_exists($collablibfile)) {

            require_once($collablibfile);

            $s .= block_ucla_search::search_form('collab-search');

            $collabcat = $this->get_collaboration_category();
            siteindicator_manager::filter_category_tree($collabcat);

            // Check if the category specified is a sub-category
            // of the collaboration category; if so, use that.
            if ($collabcat && isset($args['category'])) {
                $collabcatid = $args['category'];

                $collabsubcat = $this->find_category($collabcatid,
                    $collabcat->categories, 'id');

                if (!$collabsubcat) {
                    print_error('collab_notcollab', 'block_ucla_browseby');
                }

                $collabcat = $collabsubcat;
                $t = get_string('collab_viewin', 'block_ucla_browseby',
                    $collabsubcat->name);
            }
        } else {
            return array(false, false);
        }

        if (!$collabcat) {
            print_error('collab_notfound', 'block_ucla_browseby');
        }

        $defaulttitle = get_string('collab_viewall', 'block_ucla_browseby');
        if (empty($t)) {
            $t = $defaulttitle;
        } else {
            $navbar->add($defaulttitle,
                new moodle_url('/blocks/ucla_browseby/view.php',
                    array('type' => 'collab')));
        }

        $categorylist = array();
        if (!empty($collabcat->categories)) {
            $categorylist = $collabcat->categories;
        }

        // Render list of categories.

        $courselist = array();
        if (!empty($collabcat->courses)) {
            // Default roles to use, get these shortname's role.id.
            $rolenames = self::get_default_roles_visible();
            $allroles = get_all_roles();

            $iroles = array();
            foreach ($allroles as $role) {
                $iroles[$role->shortname] = $role;
            }

            $roleids = array();
            $rolefullnames = array();
            foreach ($rolenames as $rolename) {
                if (isset($iroles[$rolename])) {
                    $role = $iroles[$rolename];

                    $rshortname = $role->shortname;
                    $roleids[$rshortname] = $role->id;
                    $rolefullnames[$rshortname] = $role->name;
                }
            }

            if (empty($roleids)) {
                debugging('No roles to use in printing!');
            } else {
                foreach ($collabcat->courses as $course) {
                    // Skip NULL courses.
                    if (empty($course)) {
                        continue;
                    }

                    $context = context_course::instance($course->id);
                    $allnames = get_all_user_name_fields(true, 'u');
                    $viewroles = get_role_users($roleids, $context,
                        false, 'ra.id, r.shortname,u.id,' . $allnames);

                    $courseroles = array();
                    foreach ($viewroles as $viewrole) {
                        $rsh = $viewrole->shortname;
                        if (isset($iroles[$rsh])) {
                            if (!isset($courseroles[$rsh])) {
                                $courseroles[$rsh] = array();
                            }

                            $courseroles[$rsh][] = $viewrole;
                        }
                    }

                    $course->roles = $courseroles;

                    $courselist[] = $course;
                }
            }
        }

        // Sort category list in alphabetical order.
        array_alphasort($categorylist, "name");

        $rendercatlist = array();
        foreach ($categorylist as $category) {
            if (!empty($category)) {
                $rendercatlist[] = html_writer::link(
                    new moodle_url('/blocks/ucla_browseby/view.php',
                        array('category' => $category->id, 'type' => 'collab')),
                    $category->name
                );
            }
        }

        // Category heading.
        if (empty($collabcat->name)) {
            $title = get_string('collab_allcatsincat',
                    'block_ucla_browseby');
        } else {
            $title = get_string('collab_catsincat',
                    'block_ucla_browseby', $collabcat->name);
        }

        if (!empty($rendercatlist)) {
            $s .= $this->heading($title, 3);
        }

        $s .= block_ucla_browseby_renderer::ucla_custom_list_render(
            $rendercatlist);

        $title = '';
        $list = '';
        if (!empty($courselist)) {
            $title = get_string('collab_coursesincat',
                'block_ucla_browseby', $collabcat->name);
            $data = array();

            // Sort courselist of fullname (using closures, see:
            // http://stackoverflow.com/a/10159521/6001).
            usort($courselist, function($a, $b) {
                return strcmp($a->fullname, $b->fullname);
            });

            foreach ($courselist as $course) {
                $datum = array();
                $datum[] = html_writer::link(
                    uclacoursecreator::build_course_url($course),
                    $course->fullname
                );

                $nameslist = array();
                $nameimploder = array();
                foreach ($roleids as $shortname => $roleid) {
                    if (!empty($course->roles[$shortname])) {
                        foreach ($course->roles[$shortname] as $role) {
                            $nameimploder[] = fullname($role);
                        }
                    }
                }

                if (!empty($nameimploder)) {
                    // Limit display of users.
                    if (count($nameimploder) > $this->maxuserdisplay) {
                        $nameimploder = array_slice($nameimploder, 0, $this->maxuserdisplay);
                        $nameimploder[] = get_string('moreusers', 'block_ucla_browseby');
                    }
                    $nameslist[] = implode(' / ', $nameimploder);
                }

                $datum[] = empty($nameslist) ? get_string('nousersinrole',
                            'block_ucla_browseby') : implode(' ', $nameslist);

                $data[] = $datum;
            }

            $table = new html_table();
            $table->data = $data;

            $headers = array('sitename', 'projectlead');
            $dispheaders = array();

            foreach ($headers as $header) {
                $dispheaders[] = get_string($header, 'block_ucla_browseby');
            }

            $table->head = $dispheaders;

            $list = html_writer::table($table);
        }

        $s .= $this->heading($title, 3) . $list;

        return array($t, $s);
    }

    /**
     * Get the category of the collaboration site.
     *
     * @return boolean|\stdClass
     */
    public function get_collaboration_category() {
        global $CFG;

        $colcat = new stdClass();

        // Want the whole category tree for siteindicator to filter.
        $colcat->categories = $this->get_category_tree();

        // Give up.
        if (empty($colcat->categories)) {
            return false;
        }

        return $colcat;
    }

    /**
     * Finds the category from the tree.
     *
     * @param string $name
     **/
    public function get_category($name) {
        if (!isset($this->cat_tree)) {
            $this->cat_tree = $this->get_category_tree();
        }

        $tree = $this->cat_tree;

        return $this->find_category($name, $tree);
    }

    /**
     * Find the category.
     *
     * @param string $name
     * @param array $categories
     * @param string $field
     */
    public function find_category($name, $categories, $field='name') {
        foreach ($categories as $category) {
            if (!empty($category) && $category->{$field} == $name) {
                return $category;
            }

            $dfs = false;
            if (!empty($category->categories)) {
                $dfs = $this->find_category($name, $category->categories,
                    $field);
            }

            if ($dfs) {
                return $dfs;
            }
        }

        return false;
    }

    /**
     * This function generates a structured array of courses and categories.
     *
     * Copied from deprecated get_course_category_tree function.
     *
     * @param int $id
     * @param int $depth
     */
    protected function get_category_tree($id = 0, $depth = 0) {
        global $DB, $CFG;

        $categories = array();
        $categoryids = array();
        $sql = context_helper::get_preload_record_columns_sql('ctx');
        $records = $DB->get_records_sql("SELECT c.*, $sql FROM {course_categories} c ".
                "JOIN {context} ctx on ctx.instanceid = c.id AND ctx.contextlevel = ? WHERE c.parent = ? ORDER BY c.sortorder",
                array(CONTEXT_COURSECAT, $id));
        foreach ($records as $category) {
            context_helper::preload_from_record($category);
            if (!$category->visible && !has_capability('moodle/category:viewhiddencategories',
                    context_coursecat::instance($category->id))) {
                continue;
            }
            $categories[] = $category;
            $categoryids[$category->id] = $category;
            if (empty($CFG->maxcategorydepth) || $depth <= $CFG->maxcategorydepth) {
                list($category->categories, $subcategories) = $this->get_category_tree($category->id, $depth + 1);
                foreach ($subcategories as $subid => $subcat) {
                    $categoryids[$subid] = $subcat;
                }
                $category->courses = array();
            }
        }

        if ($depth > 0) {
            // This is a recursive call so return the required array.
            return array($categories, $categoryids);
        }

        if (empty($categoryids)) {
            // No categories available (probably all hidden).
            return array();
        }

        // The depth is 0 this function has just been called so we can finish it off.

        $ccselect = ', ' . context_helper::get_preload_record_columns_sql('ctx');
        $ccjoin = "LEFT JOIN {context} ctx ON (ctx.instanceid = c.id AND ctx.contextlevel = " . CONTEXT_COURSE . ")";
        list($catsql, $catparams) = $DB->get_in_or_equal(array_keys($categoryids));
        $sql = "SELECT
                c.id,c.sortorder,c.visible,c.fullname,c.shortname,c.summary,c.category
                $ccselect
                FROM {course} c
                $ccjoin
                WHERE c.category $catsql ORDER BY c.sortorder ASC";
        if ($courses = $DB->get_records_sql($sql, $catparams)) {
            // Loop through them.
            foreach ($courses as $course) {
                if ($course->id == SITEID) {
                    continue;
                }
                context_helper::preload_from_record($course);
                if (!empty($course->visible) || has_capability('moodle/course:viewhiddencourses',
                        context_course::instance($course->id))) {
                    $categoryids[$course->category]->courses[$course->id] = $course;
                }
            }
        }
        return $categories;
    }

    /**
     * Heading level.
     *
     * @param string $heading
     * @param int $level
     */
    protected function heading($heading, $level=1) {
        global $OUTPUT;

        return $OUTPUT->heading($heading, $level);
    }
}
