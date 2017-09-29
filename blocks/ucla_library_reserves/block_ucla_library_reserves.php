<?php
defined('MOODLE_INTERNAL') || die();

require_once(dirname(dirname(dirname(__FILE__))) . '/local/ucla/lib.php');

class block_ucla_library_reserves extends block_base {

    /**
     * Called by moodle
     */
    public function init() {
        // Initialize name and title
        $this->title = get_string('title', 'block_ucla_library_reserves');
    }

    /**
     * Called by moodle
     */
    public function get_content() {
        global $COURSE;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass;

        return $this->content;
    }

    /**
     * Use UCLA Course menu block hook
     */
    public static function get_navigation_nodes($course) {
        // get global variables
        global $DB, $COURSE;

        $nodes = array();
        $links = array();        
        
        $reserves = $DB->get_records('ucla_library_reserves', 
                array('courseid' => $COURSE->id));

        // if only one entry was found, then just give the name "Library reserves"
        $lr_string = get_string('title', 'block_ucla_library_reserves');        
        if (count($reserves) == 1) {
            $link = array_pop($reserves);
            $node = navigation_node::create($lr_string,
                            new moodle_url($link->url));  
            $node->add_class('library-reserve-link');
            $nodes[] = $node;
        } else {
            // else display link with subj_area and coursenum appended
            foreach ($reserves as $reserve) {
                $node = navigation_node::create(sprintf('%s %s %s', 
                        $lr_string, $reserve->department_code, 
                        $reserve->course_number), 
                        new moodle_url($reserve->url));    
                $node->add_class('library-reserve-link');
                $nodes[] = $node;
            }            
        }
        
        return $nodes;
    }

    /**
     * Displays settings link in admin menu.
     *
     * @return boolean
     */
    public function has_config() {
        return true;
    }

    /**
     * Called by moodle
     */
    public function applicable_formats() {

        return array(
            'site-index' => false,
            'course-view' => false,
            'my' => false,
            'block-ucla_library_reserves' => false,
            'not-really-applicable' => true
        );
        // hack to make sure the block can never be instantiated
    }

    /**
     * Called by moodle
     */
    public function instance_allow_multiple() {
        return false; //disables multiple blocks per page
    }

    /**
     * Called by moodle
     */
    public function instance_allow_config() {
        return false; // disables instance configuration
    }

}
