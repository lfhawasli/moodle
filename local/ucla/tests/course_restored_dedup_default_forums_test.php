<?php
// This file is part of the UCLA local plugin for Moodle - http://moodle.org/
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
 * Tests the event handler course_restored_dedup_default_forums for the
 * course_restored event.
 *
 * @package    local_ucla
 * @category   phpunit
 * @copyright  2013 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/ucla/eventslib.php');

/**
 * PHPunit testcase class.
 *
 * @package    local_ucla
 * @category   phpunit
 * @copyright  2013 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_restored_dedup_default_forums_test extends advanced_testcase {
    /**
     * Courseid for test site.
     * @var int
     */
    private $_courseid = null;

    /**
     * Stores types and names of the default forums.
     * @var array 
     */
    private $_defaultforums = array();

    /**
     * Helper method to count the number of forums with the given type for the
     * test site.
     *
     * Note, need to use the course cache and not check the database directly,
     * because the course cache is what is used to display course contents.
     *
     * @param string $type
     * @return array    Returns an array with the indexes:
     *                  'totalforums' - count of number of forum modules.
     *                  'typeforums' - array of forum records for given type.
     */
    private function get_by_forums_type($type) {
        global $DB;
        $retval = array('typeforums' => array());
        $modinfo = get_fast_modinfo($this->_courseid);
        $forums = $modinfo->get_instances_of('forum');

        $retval['totalforums'] = count($forums);
        foreach ($forums as $forumcm) {
            // Get actual forum record.
            $result = $DB->get_record('forum',
                    array('type' => $type, 'id' => $forumcm->instance));
            if (!empty($result)) {
                // Found a forum of given type.
                $retval['typeforums'][] = $result;
            }
        }

        return $retval;
    }

    /**
     * Creates test course and setups default forums.
     */
    protected function setUp() {
        $this->resetAfterTest(true);

        $class = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_class(array('term' => '13S'));
        $course = array_pop($class);
        $this->_courseid = $course->courseid;

        $this->_defaultforums = array('news'    => get_string('namenews', 'forum'),
                                      'general' => get_string('discforum', 'format_ucla'));

        // Create default forums.
        foreach ($this->_defaultforums as $type => $defaultname) {
            forum_get_course_forum($this->_courseid, $type);
        }
    }

    /**
     * Make sure that duplicate forums are deleted.
     */
    function test_duplicate_forum() {
        $totalforums = 2;   // Two default forums.
        $typecount = 1;     // Start off with 1 of each type.
        
        foreach ($this->_defaultforums as $type => $defaultname) {
            $forums = $this->get_by_forums_type($type);
            $this->assertEquals($totalforums, $forums['totalforums']);
            $this->assertEquals($typecount, count($forums['typeforums']));

            // Add in another forum that is exactly the same.
            $original = array_pop($forums['typeforums']);
            $this->getDataGenerator()
                    ->get_plugin_generator('mod_forum')
                    ->create_instance($original);
            $forums = $this->get_by_forums_type($type);
            ++$totalforums;
            ++$typecount;
            $this->assertEquals($totalforums, $forums['totalforums']);
            $this->assertEquals($typecount, count($forums['typeforums']));

            // Run function and make sure it deletes the copy.
            $data = new stdClass();
            $data->courseid = $this->_courseid;
            $data->type = backup::TYPE_1COURSE;
            course_restored_dedup_default_forums($data);
            --$totalforums;
            --$typecount;

            $forums = $this->get_by_forums_type($type);
            $this->assertEquals($totalforums, $forums['totalforums']);
            $this->assertEquals($typecount, count($forums['typeforums']));

            $tocheck = array_pop($forums['typeforums']);
            $this->assertEquals($original->id, $tocheck->id);
        }
    }

    /**
     * Make sure that forums with the default name changed are not deleted.
     */
    function test_duplicate_forum_with_defaultname_changed() {
        $totalforums = 2;   // Two default forums.
        $typecount = 1;     // Start off with 1 of each type.

        foreach ($this->_defaultforums as $type => $defaultname) {
            $forums = $this->get_by_forums_type($type);
            $this->assertEquals($totalforums, $forums['totalforums']);
            $this->assertEquals($typecount, count($forums['typeforums']));

            // Add in another forum but with the name changed.
            $original = array_pop($forums['typeforums']);
            $original->name = substr(md5(rand()), 0, 100);
            $defaultchanged = $this->getDataGenerator()
                    ->get_plugin_generator('mod_forum')
                    ->create_instance($original);
            $forums = $this->get_by_forums_type($type);
            ++$totalforums;
            ++$typecount;
            $this->assertEquals($totalforums, $forums['totalforums']);
            $this->assertEquals($typecount, count($forums['typeforums']));

            // Run function and make sure it deletes the origina, empty forum.
            $data = new stdClass();
            $data->courseid = $this->_courseid;
            $data->type = backup::TYPE_1COURSE;
            course_restored_dedup_default_forums($data);
            --$totalforums;
            --$typecount;

            $forums = $this->get_by_forums_type($type);
            $this->assertEquals($totalforums, $forums['totalforums']);
            $this->assertEquals($typecount, count($forums['typeforums']));

            $tocheck = array_pop($forums['typeforums']);
            $this->assertEquals($defaultchanged->id, $tocheck->id);
        }
    }


    /**
     * Make sure that forums with content are not deleted.
     */
    function test_duplicate_forum_with_posts() {
        global $USER;
        $this->setAdminUser();
        $totalforums = 2;   // Two default forums.
        $typecount = 1;     // Start off with 1 of each type.

        foreach ($this->_defaultforums as $type => $defaultname) {
            $forums = $this->get_by_forums_type($type);
            $this->assertEquals($totalforums, $forums['totalforums']);
            $this->assertEquals($typecount, count($forums['typeforums']));

            // Add in another forum but add some posts.
            $original = array_pop($forums['typeforums']);
            $forumgenerator = $this->getDataGenerator()->get_plugin_generator('mod_forum');
            $withcontent = $forumgenerator->create_instance($original);
            $forums = $this->get_by_forums_type($type);
            ++$totalforums;
            ++$typecount;
            $this->assertEquals($totalforums, $forums['totalforums']);
            $this->assertEquals($typecount, count($forums['typeforums']));

            $discussion = new stdClass();
            $discussion->course = $this->_courseid;
            $discussion->forum = $withcontent->id;
            $discussion->userid = $USER->id;
            $forumgenerator->create_discussion($discussion);

            // Run function and make sure it deletes the origina, empty forum.
            $data = new stdClass();
            $data->courseid = $this->_courseid;
            $data->type = backup::TYPE_1COURSE;
            course_restored_dedup_default_forums($data);
            --$totalforums;
            --$typecount;

            $forums = $this->get_by_forums_type($type);
            $this->assertEquals($totalforums, $forums['totalforums']);
            $this->assertEquals($typecount, count($forums['typeforums']));

            $tocheck = array_pop($forums['typeforums']);
            $this->assertEquals($withcontent->id, $tocheck->id);
        }
    }

    /**
     * Make sure nothing happens when we are not doing a course restore.
     */
    function test_nonrestore() {
        $totalforums = 2;   // Two default forums.        

        foreach ($this->_defaultforums as $type => $defaultname) {
            $typecount = 1;     // Start off with 1 of each type.

            $forums = $this->get_by_forums_type($type);
            $this->assertEquals($totalforums, $forums['totalforums']);
            $this->assertEquals($typecount, count($forums['typeforums']));

            // Add in another forum that is exactly the same.
            $original = array_pop($forums['typeforums']);
            $this->getDataGenerator()
                    ->get_plugin_generator('mod_forum')
                    ->create_instance($original);
            $forums = $this->get_by_forums_type($type);
            ++$totalforums;
            ++$typecount;
            $this->assertEquals($totalforums, $forums['totalforums']);
            $this->assertEquals($typecount, count($forums['typeforums']));

            // Run function and make sure nothing happens.
            $data = new stdClass();
            $data->courseid = $this->_courseid;
            $data->type = backup::TYPE_1ACTIVITY;
            course_restored_dedup_default_forums($data);

            $forums = $this->get_by_forums_type($type);
            $this->assertEquals($totalforums, $forums['totalforums']);
            $this->assertEquals($typecount, count($forums['typeforums']));
        }
    }
}
