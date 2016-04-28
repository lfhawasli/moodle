<?php

/**
 * This file contains classes used to manage and display competencies fulfilled
 * by a Moodle course.
 *
 * @package   block_competencies
 * @copyright 2012 UC Regents
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Eric Bollens <ebollens@ucla.edu>
 */

require_once(dirname(__FILE__).'/lib.php');

/**
 * The competencies block class
 *
 * @package   block_competencies
 * @author    Eric Bollens <ebollens@ucla.edu>
 */
class block_competencies extends block_list {
    
    /**
     * Initialization of Competencies block
     */
    function init() {
        
        global $PAGE;
        $PAGE->requires->js(new moodle_url('/blocks/competencies/js/jquery.js'));
        
        $this->title = get_string('title', 'block_competencies');
        
    }
    
    /**
     * Content generator for Competencies block
     * 
     * @return stdClass
     */
    public function get_content() {
        
        global $COURSE;
        
        if ($this->content !== null) {
            return $this->content;
        }
        
        $context = context_course::instance($COURSE->id);

        $this->content         =  new stdClass;
        
        if($COURSE->format == 'site'){
            return;
        }
        
        $this->content->text = '';
        $this->content->items  = array();
        $this->content->icons  = array();
        if(count(block_competencies_db::get_course_items($COURSE->id)) > 0){
            $this->content->items[] = html_writer::link(
                    new moodle_url('/blocks/competencies/course.php', array('id' => $COURSE->id)), 
                    get_string('courseviewcontrol', 'block_competencies'),
                    array('class'=>'control course view')
                );
            $this->content->icons[] = '';
        }
        
        $this->content->footer = '';
        
        return $this->content;

    }
    
    /**
     * Competencies block has configurable settings.
     *
     * @return boolean
     */
    function has_config() {
        return true;
    }

    /**
     * Only allow one copy of this block per course.
     * 
     * @return false
     */
    public function instance_allow_multiple() {
        
        return false;
        
    }
    
    /**
     * Only display this block for front page and course view page.
     * 
     * @return type
     */
    public function applicable_formats() {
        return array(
                   'site-index' => true,
                  'course-view' => true, 
                          'mod' => false
        );
    }
}
