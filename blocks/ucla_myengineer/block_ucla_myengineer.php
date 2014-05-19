<?php
defined('MOODLE_INTERNAL') || die();

require_once(dirname(dirname(dirname(__FILE__))) . '/local/ucla/lib.php');

class block_ucla_myengineer extends block_base {

    /**
     * Called by moodle
     */
    public function init() {
        $this->title = get_string('title', 'block_ucla_myengineer');
        $this->name = get_string('pluginname', 'block_ucla_myengineer');
    }

    /**
     * Block doesn't have any content to display.
     * 
     * @return null
     */
    public function get_content() {
        return null;
    }

    /**
     * Use UCLA Course menu block hook
     */
    public static function get_navigation_nodes($course) {
        $nodes = array();
        $courseid = $course['course']->id;
        $reginfos = ucla_get_course_info($courseid);
        foreach ($reginfos as $reginfo) {
            if ($reginfo->division == 'EN') {
                $myengineerurl = 'https://my.engineer.ucla.edu';
                $urlobj = new moodle_url($myengineerurl);
                $node = navigation_node::create(get_string('title', 'block_ucla_myengineer'), $urlobj);

                $node->add_class('myengineer-link');
                $nodes[] = $node;

                return $nodes;
            }
        }
    }

    /**
     * Called by moodle
     */    
    function applicable_formats() {
        return array(
            'site-index' => false,
            'course-view' => false,
            'my' => false,
            'not-really-applicable' => true
        );
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
