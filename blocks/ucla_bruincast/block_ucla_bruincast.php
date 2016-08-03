<?php
defined('MOODLE_INTERNAL') || die();

require_once(dirname(dirname(dirname(__FILE__))) . '/local/ucla/lib.php');

class block_ucla_bruincast extends block_base {

    /**
     * Called by moodle
     */
    public function init() {

        // Initialize title and name.
        $this->title = get_string('title', 'block_ucla_bruincast');
        $this->name = get_string('pluginname', 'block_ucla_bruincast');
    }

    /**
     * Called by moodle
     */
    public function get_content() {

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass;

        return $this->content;
    }

    /**
     * Hook into UCLA Site menu block.
     *
     * @param object $course
     *
     * @return array
     */
    public static function get_navigation_nodes($course) {
        global $DB;
        $nodes = array();
        $courseid = $course['course']->id;

        $recordsfound = $DB->record_exists('ucla_bruincast',
                array('courseid' => $courseid));

        if ($recordsfound) {
            $node = navigation_node::create(get_string('title',
                    'block_ucla_bruincast'), new moodle_url('/blocks/ucla_bruincast/index.php', array('courseid' => $courseid)));
            $node->add_class('video-reserves-link');
            $nodes[] = $node;
        }

        return $nodes;
    }

    /**
     *  Called by moodle
     */
    public function applicable_formats() {

        return array(
            'site-index' => false,
            'course-view' => false,
            'my' => false,
            'block-ucla_bruincast' => false,
            'not-really-applicable' => true
        );
        // Hack to make sure the block can never be instantiated.
    }

    /**
     *  Called by moodle
     */
    public function instance_allow_multiple() {
        return false; // Disables multiple block instances per page.
    }

    /**
     *  Called by moodle
     */
    public function instance_allow_config() {
        return false; // Disables instance configuration.
    }

}
