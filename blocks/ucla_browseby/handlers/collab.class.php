<?php

class collab_handler extends browseby_handler {
    private $MAX_USER_DISPLAY = 5;
    
    static function get_default_roles_visible() {
        return array('projectlead', 'coursecreator', 'editinginstructor');
    }

    function get_params() {
        return array('category');
    }

    function handle($args) {
        global $CFG, $PAGE;

        // Load search
        $PAGE->requires->yui_module('moodle-block_ucla_search-search', 'M.ucla_search.init', 
                array(array('name' => 'course-search')));
        
        $navbar =& $PAGE->navbar;

        $collablibfile = $CFG->dirroot . '/' . $CFG->admin 
            .'/tool/uclasiteindicator/lib.php';

        $collab_cat = false;

        $t = '';
        $s = '';

        if (file_exists($collablibfile)) {
            
            require_once($collablibfile);
        
            $s .= block_ucla_search::search_form('collab-search');

            $collab_cat = $this->get_collaboration_category();
            siteindicator_manager::filter_category_tree($collab_cat);

            // Check if the category specified is a sub-category
            // of the collaboration category; if so, use that
            if ($collab_cat && isset($args['category'])) {
                $collab_cat_id = $args['category'];

                $collab_subcat = $this->find_category($collab_cat_id, 
                    $collab_cat->categories, 'id');

                if (!$collab_subcat) {
                    print_error('collab_notcollab', 'block_ucla_browseby');
                }
            
                $collab_cat = $collab_subcat;
                $t = get_string('collab_viewin', 'block_ucla_browseby',
                    $collab_subcat->name);
            }
        } else {
            // 
            return array(false, false);
        }

        if (!$collab_cat) {
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
        if (!empty($collab_cat->categories)) {
            $categorylist = $collab_cat->categories;
        }

        // Render list of categories

        $courselist = array();
        if (!empty($collab_cat->courses)) {
            // Default roles to use, get these shortname's role.id
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
                foreach ($collab_cat->courses as $course) {
                    // Skip NULL courses
                    if(empty($course)) {
                        continue;
                    }
                    
                    $context = context_course::instance($course->id);

                    $viewroles = $this->get_role_users($roleids, $context,
                        false, 'u.id, u.firstname, u.lastname, r.shortname');

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
   
        // Sort category list in alphabetical order
        array_alphasort($categorylist, "name");
        
        $rendercatlist = array();
        foreach ($categorylist as $category) {
            if(!empty($category)) {
                $rendercatlist[] = html_writer::link(
                    new moodle_url('/blocks/ucla_browseby/view.php',
                        array('category' => $category->id, 'type' => 'collab')),
                    $category->name
                );
            }
        }

        // Category heading
        if(empty($collab_cat->name)) {
            $title = get_string('collab_allcatsincat', 
                    'block_ucla_browseby');
        } else {
            $title = get_string('collab_catsincat', 
                    'block_ucla_browseby', $collab_cat->name);
        }
        
        if(!empty($rendercatlist)) {
            $s .= $this->heading($title, 3);
        }
        
        $s .= block_ucla_browseby_renderer::ucla_custom_list_render(
            $rendercatlist);

        $title = '';
        $list = '';
        if (!empty($courselist)) {
            $title = get_string('collab_coursesincat', 
                'block_ucla_browseby', $collab_cat->name);
            $data = array();

            // sort courselist of fullname (using closures, see:
            // http://stackoverflow.com/a/10159521/6001)
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
                    // limit display of users
                    if (count($nameimploder) > $this->MAX_USER_DISPLAY) {
                        $nameimploder = array_slice($nameimploder, 0, $this->MAX_USER_DISPLAY);
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

    function get_collaboration_category() {
        global $CFG;

        $colcat = new stdClass();
                
        // Want the whole category tree for siteindicator to filter
        $colcat->categories = $this->get_category_tree();

        // Give up
        if (empty($colcat->categories)) {
            return false;
        }

        return $colcat;
    }
    
    /**
     *  Finds the category from the tree.
     **/
    function get_category($name) {
        if (!isset($this->cat_tree)) {
            $this->cat_tree = $this->get_category_tree();
        }

        $tree = $this->cat_tree;

        return $this->find_category($name, $tree);
    }

    function find_category($name, $categories, $field='name') {
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
     *  Some more decoupled functions...
     **/
    protected function get_role_users($roles, $context, $parent=false, 
                                      $fields='') {
        return get_role_users($roles, $context, $parent, $fields);
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
            if (!$category->visible && !has_capability('moodle/category:viewhiddencategories', context_coursecat::instance($category->id))) {
                continue;
            }
            $categories[] = $category;
            $categoryids[$category->id] = $category;
            if (empty($CFG->maxcategorydepth) || $depth <= $CFG->maxcategorydepth) {
                list($category->categories, $subcategories) = $this->get_category_tree($category->id, $depth+1);
                foreach ($subcategories as $subid=>$subcat) {
                    $categoryids[$subid] = $subcat;
                }
                $category->courses = array();
            }
        }

        if ($depth > 0) {
            // This is a recursive call so return the required array
            return array($categories, $categoryids);
        }

        if (empty($categoryids)) {
            // No categories available (probably all hidden).
            return array();
        }

        // The depth is 0 this function has just been called so we can finish it off

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
            // loop through them
            foreach ($courses as $course) {
                if ($course->id == SITEID) {
                    continue;
                }
                context_helper::preload_from_record($course);
                if (!empty($course->visible) || has_capability('moodle/course:viewhiddencourses', context_course::instance($course->id))) {
                    $categoryids[$course->category]->courses[$course->id] = $course;
                }
            }
        }
        return $categories;
    }

    protected function heading($heading, $level=1) {
        global $OUTPUT;

        return $OUTPUT->heading($heading, $level);
    }
}
