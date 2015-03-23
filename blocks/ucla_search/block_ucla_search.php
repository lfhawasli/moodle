<?php


class block_ucla_search extends block_base {
        
    public function init() {
        $this->title = get_string('pluginname', 'block_ucla_search');
    }
    
    public function get_content() {
        global $PAGE;

        if ($this->content !== null) {
            return $this->content;
        }
        
        // Load YUI module
        $PAGE->requires->yui_module('moodle-block_ucla_search-search', 
                'M.ucla_search.init', 
                array(array('name' => 'block-search')));
        
        $this->content = new stdClass;

        // Write content
        $this->content->text = self::search_form();
        
        return $this->content;
    }
    
    public function applicable_formats() {
        return array(
            'site-index' => true,
            'course-view' => false,
            'my' => true,
        );
    }
    
    /**
     * Print advanced search form html for various components.  Compatible 
     * with default moodle search if javascript of off.
     * 
     * @param string $type
     * @param array $searchparams
     * @return html
     */
    static function search_form($type = 'block-search', $searchparams = null) {
        global $CFG;
        
        // Default.
        $collab = true;
        $course = true;
        $bytitle = true;
        $bydescription = true;
        $visibility = 'hidden';
        
        switch ($type) {
            case 'frontpage-search':
                break;
            case 'course-search':
                $collab = false;
                $course = true;
                break;
            case 'collab-search':
                $collab = true;
                $course = false;
                break;
            case 'block-search':
                $visibility = '';
        }

        // If search params were passed, then need to retain those settings.
        $searchterm = null;
        if (!empty($searchparams)) {
            if (isset($searchparams['collab'])) {
                $collab = $searchparams['collab'];
            }
            if (isset($searchparams['course'])) {
                $course = $searchparams['course'];
            }
            if (isset($searchparams['bytitle'])) {
                $bytitle = $searchparams['bytitle'];
            }
            if (isset($searchparams['bydescription'])) {
                $bydescription = $searchparams['bydescription'];
            }
            if (!empty($searchparams['search'])) {
                $searchterm = $searchparams['search'];
            }
        }
        
        $inputgroup = html_writer::empty_tag('input', 
                        array(
                            'id' => 'ucla-search', 
                            'type' => 'text', 
                            'class' => 'form-control ucla-search-input', 
                            'name' => 'search',
                            'placeholder' => get_string('placeholder', 'block_ucla_search'),
                            'value' => $searchterm
                            ));
        
        $checkboxes = html_writer::div(
                html_writer::tag('label', 
                        html_writer::checkbox('collab', 1, $collab) . ' ' . get_string('collab', 'block_ucla_search'),
                        array('class' => 'checkbox-inline')
                        ) . 
                html_writer::tag('label', 
                        html_writer::checkbox('course', 1, $course) . ' ' . get_string('course', 'block_ucla_search'),
                        array('class' => 'checkbox-inline')
                        )
                );
        
        $filtercheckboxes = html_writer::div(
                html_writer::tag('label', 
                        html_writer::checkbox('bytitle', 1, $bytitle) . ' ' . get_string('bytitle', 'block_ucla_search'),
                        array('class' => 'checkbox-inline')
                        ) . 
                html_writer::tag('label', 
                        html_writer::checkbox('bydescription', 1, $bydescription) . ' ' . get_string('bydescription', 'block_ucla_search'),
                        array('class' => 'checkbox-inline')
                        )
                );

        $filterbywell = html_writer::div(
                html_writer::span(get_string('filterby', 'block_ucla_search'), $visibility) . 
                html_writer::tag('fieldset', $filtercheckboxes, array('class' => $visibility)), 
                'well well-sm');

        $form = html_writer::tag('form', 
                    html_writer::span(get_string('show', 'block_ucla_search'), $visibility) .
                    html_writer::tag('fieldset', $checkboxes, array('class' => $visibility)) .
                    $filterbywell . $inputgroup, 
                    array('class' => '', 'action' => $CFG->wwwroot . '/course/search.php')
                );
        
        $grid = html_writer::div($form, 'ucla-search ' . $type);
        
        return $grid;   
    }

    /**
     * Prevent block from being collapsed.
     *
     * @return bool
     */
    public function instance_can_be_collapsed() {
        return false;
    }

}