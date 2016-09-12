<?php

defined('MOODLE_INTERNAL') || die();

require_once(dirname(dirname(dirname(__FILE__))) . '/local/ucla/lib.php');

class block_ucla_bruinmedia extends block_base {

    /**
     * Called by moodle
     */
    public function init() {

        // initialize title and name
        $this->title = get_string('title', 'block_ucla_bruinmedia');
        $this->name = get_string('pluginname', 'block_ucla_bruinmedia');
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

        $courseid = $course['course']->id; // Course id from the hook function.

        $nodes = array();

        // Links will be indexed as: [coursetitle][restriction] => url.
        $links = array();

        if ($matchingcourses = $DB->get_records('ucla_bruinmedia', array('courseid' => $courseid))) {
            // A course might have more than 1 Bruinmedia link. Some possible 
            // reasons are if a course is cross-listed or if there are multiple
            // restriction types, or both.
            //
            // Logic to decide how to display links in these different scenarios:
            //
            // 1) If links all have same restriction, then get last part of url,
            //    which will be the course name and display it as:
            //      Bruinmedia (<course title>)
            // 2) If links have different restrictions, then display as:
            //      Bruinmedia (<restriction type>)
            // 3) If links have different restrictions and different course
            //    titles, then display as:
            //     Bruinmedia (<course title>/<restriction type>)
            // 4) If there is only 1 url, then display as:
            //     Bruinmedia (<restriction type>)
            $titlesused = array();
            $restrictionsused = array();
            foreach ($matchingcourses as $matchingcourse) {
                if (empty($matchingcourse->bruincast_url)) {
                    continue;
                }

                $title = basename($matchingcourse->bruincast_url);
                $title = core_text::strtoupper($title);

                $restriction = 'node_' . core_text::strtolower($matchingcourse->restricted);
                $restriction = str_replace(' ', '_', $restriction);                

                $links[$title][$restriction] = $matchingcourse->bruincast_url;

                $titlesused[] = $title;
                $restrictionsused[] = $restriction;
            }

            // See what type of display scenario we are going to use.
            $multipletitles = false;
            $multiplerestrictions = false;
            if (count(array_unique($titlesused)) > 1) {
                $multipletitles = true;
            }
            if (count(array_unique($restrictionsused)) > 1) {
                $multiplerestrictions = true;
            }

            foreach ($links as $title => $restrictions) {
                foreach ($restrictions as $restriction => $url) {
                    if ($multipletitles && !$multiplerestrictions) {
                        // 1) If links all have same restriction, then get last
                        //    part of url, which will be the course name and
                        //    display it as:
                        //      Bruinmedia (<course title>)
                        $node = navigation_node::create(sprintf('%s (%s)',
                                get_string('title', 'block_ucla_bruinmedia'), $title),
                                new moodle_url($url));
                    } else if (!$multipletitles && $multiplerestrictions) {
                        // 2) If links have different restrictions, then display
                        //    as:
                        //      Bruinmedia (<restriction type>)
                        $node = navigation_node::create(sprintf('%s (%s)',
                                get_string('title', 'block_ucla_bruinmedia'),
                                get_string($restriction, 'block_ucla_bruinmedia')),
                                new moodle_url($url));
                    } else if ($multipletitles && $multiplerestrictions) {
                        // 3) If links have different restrictions and different
                        //    course titles, then display as:
                        //     Bruinmedia (<course title>/<restriction type>)
                        $node = navigation_node::create(sprintf('%s (%s/%s)',
                                get_string('title', 'block_ucla_bruinmedia'),
                                $title,
                                get_string($restriction, 'block_ucla_bruinmedia')),
                                new moodle_url($url));
                    } else if (!$multipletitles && !$multiplerestrictions) {
                        // 4) If there is only 1 url, then display as:
                        //     Bruinmedia (<restriction type>)
                        $type = '';
                        if ($restriction != 'node_open') {
                            // Don't add restriction type text for open.
                            $type = sprintf(' (%s)', get_string($restriction, 'block_ucla_bruinmedia'));
                        }
                        $node = navigation_node::create(
                                get_string('title', 'block_ucla_bruinmedia') .
                                $type,
                                new moodle_url($url));
                    }

                    $node->add_class('bruinmedia-link');
                    $nodes[] = $node;
                }
            }
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
            'block-ucla_bruinmedia' => false,
            'not-really-applicable' => true
        );
        // hack to make sure the block can never be instantiated
    }

    /**
     *  Called by moodle
     */
    public function instance_allow_multiple() {
        return false; // disables multiple block instances per page
    }

    /**
     *  Called by moodle
     */
    public function instance_allow_config() {
        return false; // disables instance configuration
    }

}
