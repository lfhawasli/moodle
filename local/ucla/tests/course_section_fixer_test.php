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
 * Class local_ucla_course_section_fixer tests.
 *
 * @package    local_ucla
 * @category   test
 * @copyright  2013 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/ucla/classes/local_ucla_course_section_fixer.php');

/**
 * PHPunit testcase class.
 *
 * @copyright  2013 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group ucla
 * @group local_ucla
 */
class course_section_fixer_test extends advanced_testcase {

    /**
     * Add a course section to a given course, but bypass the course cache.
     * 
     * In order to do that, we will directly manipulate the DB.
     *
     * @param object $course
     * @param array $section    If passed, then will be used to create new
     *                          section.
     */
    private function add_section($course, $section = null) {
        global $DB;

        $defaultsection = array(
            'course' => $course->id,
            'name' => null,
            'summary' => '',
            'summaryformat' => '1', // FORMAT_HTML, but must be a string.
            'visible' => '1',
            'showavailability' => '0',
            'availablefrom' => '0',
            'availableuntil' => '0',
            'groupingid' => '0',
        );

        if (empty($section)) {
            $section = $defaultsection;
        } else {
            // If passed in section doesn't have all the columns specified,
            // then use the default value.
            foreach ($defaultsection as $column => $value) {
                if (!isset($section[$column])) {
                    $section[$column] = $value;
                }
            }
        }

        // If no section specified, just use the next biggest value.
        if (!isset($section['section'])) {
            $nextnum = $DB->get_field('course_sections', 'MAX(section)+1',
                    array('course' => $course->id));
            $section['section'] = $nextnum;
        }

        $DB->insert_record('course_sections', $section);
    }

    /**
     * Helper method to create a course with mixed content across many different
     * sections.
     *
     * @return object   Returns course object.
     */
    private function create_course_with_content() {
        global $DB;

        $course = $this->getDataGenerator()->create_course();

        // Make sure sections exists.
        $numsections = course_get_format($course)->get_course()->numsections;
        course_create_sections_if_missing($course, range(0, $numsections));

        // Course modules with datagenerators.
        $mods = array('mod_assign', 'mod_data', 'mod_forum', 'mod_label',
            'mod_page', 'mod_quiz');
        foreach ($mods as $mod) {
            $generator = $this->getDataGenerator()->get_plugin_generator($mod);
            // Figure out how many modules to add.
            $nummods = rand(1, 5);
            for ($i = 1; $i <= $nummods; $i++) {
                // Choose a random section to add module.
                $sectionnum = rand(0, $numsections);
                $generator->create_instance(array('course' => $course->id),
                        array('section' => $sectionnum));
            }
        }

        // Warm up the course caches.
        rebuild_course_cache($course->id);

        return $DB->get_record('course', array('id' => $course->id));
    }

    /**
     * Delete course section for a given course by directly manipulating the DB.
     *
     * @param object $course
     */
    private function delete_section($course) {
        global $DB;

        // Get a random section to delete.
        $sections = $DB->get_records('course_sections',
                array('course' => $course->id));
        shuffle($sections);
        $section = array_pop($sections);

        $DB->delete_records('course_sections', array('id' => $section->id));
    }

    /**
     * Replace course section for a given course by directly manipulating the DB.
     *
     * @param object $course
     */
    private function replace_section($course) {
        global $DB;

        // Get a random section to replace.
        $sections = $DB->get_records('course_sections',
                array('course' => $course->id));
        shuffle($sections);
        $section = array_pop($sections);

        // Change its section to a random number, that is not the original
        // number or an existing number.
        while (1) {
            $newsection = rand(1, 50);
            // Check if is an existing number.
            if ($DB->record_exists('course_sections',
                            array('course' => $course->id, 'section' => $newsection))) {
                continue;
            }
            $section->section = $newsection;
            break;
        }

        $DB->update_record('course_sections', $section);
    }

    /**
     * Setup method.
     */
    protected function setUp() {
        $this->resetAfterTest(true);
    }

    /**
     * Make sure that test_check_extra_sections returns false if a course has
     * a bunch of sections above numsections that can be safely deleted.
     */
    public function test_check_extra_sections() {
        global $DB;

        $course = $this->create_course_with_content();
        $result = local_ucla_course_section_fixer::check_extra_sections($course);
        $this->assertTrue($result);

        // Add a section above numsection. Default values.
        $this->add_section($course);
        $result = local_ucla_course_section_fixer::check_extra_sections($course);
        $this->assertFalse($result);

        // Increase numsection.
        $numsections = course_get_format($course)->get_course()->numsections;
        $data = array('numsections' => $numsections + 1);
        course_get_format($course)->update_course_format_options($data);
        $result = local_ucla_course_section_fixer::check_extra_sections($course);
        $this->assertTrue($result);

        // Add section above numsection with name set to "Week 10".
        $section = array('name' => 'Week 10');
        $this->add_section($course, $section);
        $result = local_ucla_course_section_fixer::check_extra_sections($course);
        $this->assertFalse($result);
        // Cleanup for next test.
        $DB->delete_records('course_sections',
                array('course' => $course->id, 'name' => 'Week 10'));

        // Add section above numsection with name set to "New section".
        $section = array('name' => 'New section');
        $this->add_section($course, $section);
        $result = local_ucla_course_section_fixer::check_extra_sections($course);
        $this->assertFalse($result);
        // Cleanup for next test.
        $DB->delete_records('course_sections',
                array('course' => $course->id, 'name' => 'New section'));

        // Add section above numsection with name set to some other value.
        $section = array('name' => 'Something else');
        $this->add_section($course, $section);
        $result = local_ucla_course_section_fixer::check_extra_sections($course);
        $this->assertTrue($result);

        // Add section with non-empty sequence.
        $section = array('sequence' => '1,2,3');
        $this->add_section($course, $section);
        $result = local_ucla_course_section_fixer::check_extra_sections($course);
        $this->assertTrue($result);

        // Add section with non-empty summary.
        $section = array('summary' => 'Testing');
        $this->add_section($course, $section);
        $result = local_ucla_course_section_fixer::check_extra_sections($course);
        $this->assertTrue($result);
    }

    /**
     * Make sure that check_section_order returns false when you rearrange
     * sections and skip numbers.
     */
    public function test_check_section_order() {
        $course = $this->create_course_with_content();
        $result = local_ucla_course_section_fixer::check_section_order($course);
        $this->assertTrue($result);

        // Replace section without updating course cache.
        $this->replace_section($course);
        $result = local_ucla_course_section_fixer::check_section_order($course);
        $this->assertFalse($result);
    }

    /**
     * Make sure that detect_numsections properly detects and then fixes the
     * number of sections.
     */
    public function test_detect_numsections() {
        $course = $this->create_course_with_content();
        $result = local_ucla_course_section_fixer::detect_numsections($course);
        $this->assertFalse($result);

        $before = course_get_format($course)->get_course()->numsections;

        // Add a section without increasing numsections.
        $this->add_section($course);
        $result = local_ucla_course_section_fixer::detect_numsections($course);
        $this->assertEquals(1, count($result));

        // Now see if it will adjust numsections.
        $result = local_ucla_course_section_fixer::detect_numsections($course,
                        true);
        $this->assertTrue($result);
        $result = local_ucla_course_section_fixer::detect_numsections($course);
        $this->assertFalse($result);

        $after = course_get_format($course)->get_course()->numsections;

        // Make sure we only added 1 more section.
        $this->assertEquals($before + 1, $after);

        // Make sure that numsections cannot be set to something higher than
        // the max.
        $maxsections = get_config('moodlecourse', 'maxsections');
        for ($i = 0; $i < $maxsections; $i++) {
            $this->add_section($course);
        }
        local_ucla_course_section_fixer::detect_numsections($course, true);
        $numsections = course_get_format($course)->get_course()->numsections;
        $this->assertEquals($maxsections, $numsections);
    }

    /**
     * Make sure that fix_problems properly fixes a course's sections.
     *
     */
    public function test_fix_problems() {
        $course = $this->create_course_with_content();

        $this->add_section($course);
        $this->add_section($course);
        $this->delete_section($course);
        $this->delete_section($course);
        $this->replace_section($course);

        $result = local_ucla_course_section_fixer::fix_problems($course);

        // With the amount of changes we are doing, we should have a return of
        // more than zero on these changes.
        $this->assertGreaterThan(0, $result['deleted']);
        $this->assertGreaterThan(0, $result['updated']);

        // If we check, there should be no problems.
        $result = local_ucla_course_section_fixer::has_problems($course);
        $this->assertFalse($result);
    }

    /**
     * Make sure that handle_extra_sections deletes the proper extra sections.
     */
    public function test_handle_extra_sections() {
        $course = $this->create_course_with_content();
        $result = local_ucla_course_section_fixer::handle_extra_sections($course);
        $this->assertEquals(0, $result['added']);
        $this->assertEquals(0, $result['deleted']);
        $this->assertEquals(0, $result['updated']);

        // Add a section above numsection. Default values.
        $this->add_section($course);
        $result = local_ucla_course_section_fixer::handle_extra_sections($course);
        $this->assertEquals(0, $result['added']);
        $this->assertGreaterThan(0, $result['deleted']);
        $this->assertEquals(0, $result['updated']);

        // Add section above numsection with name set to "Week 10".
        $section = array('name' => 'Week 10');
        $this->add_section($course, $section);
        $result = local_ucla_course_section_fixer::handle_extra_sections($course);
        $this->assertEquals(0, $result['added']);
        $this->assertGreaterThan(0, $result['deleted']);
        $this->assertEquals(0, $result['updated']);

        // Add section above numsection with name set to "Week 10 Resources".
        $section = array('name' => 'Week 10 Resources');
        $this->add_section($course, $section);
        $result = local_ucla_course_section_fixer::handle_extra_sections($course);
        $this->assertEquals(0, $result['added']);
        $this->assertEquals(0, $result['deleted']);
        $this->assertEquals(0, $result['updated']);

        // Add section above numsection with name set to "New section".
        $section = array('name' => 'New section');
        $this->add_section($course, $section);
        $result = local_ucla_course_section_fixer::handle_extra_sections($course);
        $this->assertEquals(0, $result['added']);
        $this->assertGreaterThan(0, $result['deleted']);
        $this->assertEquals(0, $result['updated']);

        // Add section above numsection with name set to some other value.
        $section = array('name' => 'Something else');
        $this->add_section($course, $section);
        $result = local_ucla_course_section_fixer::handle_extra_sections($course);
        $this->assertEquals(0, $result['added']);
        $this->assertEquals(0, $result['deleted']);
        $this->assertEquals(0, $result['updated']);

        // Add section with non-empty sequence.
        $section = array('sequence' => '1,2,3');
        $this->add_section($course, $section);
        $result = local_ucla_course_section_fixer::handle_extra_sections($course);
        $this->assertEquals(0, $result['added']);
        $this->assertEquals(0, $result['deleted']);
        $this->assertEquals(0, $result['updated']);

        // Add section with non-empty summary.
        $section = array('summary' => 'Testing');
        $this->add_section($course, $section);
        $result = local_ucla_course_section_fixer::handle_extra_sections($course);
        $this->assertEquals(0, $result['added']);
        $this->assertEquals(0, $result['deleted']);
        $this->assertEquals(0, $result['updated']);
    }

    /**
     * Make sure that check_section_order returns false when you rearrange
     * sections and skip numbers.
     */
    public function test_handle_section_order() {
        $course = $this->create_course_with_content();
        $result = local_ucla_course_section_fixer::handle_section_order($course);

        $this->assertEquals(0, $result['added']);
        $this->assertEquals(0, $result['deleted']);
        $this->assertEquals(0, $result['updated']);

        // Mess up ordering of sections so they are not in order.
        $this->replace_section($course);
        $this->replace_section($course);
        $this->delete_section($course);
        $this->replace_section($course);
        $this->replace_section($course);
        $this->delete_section($course);

        $result = local_ucla_course_section_fixer::handle_section_order($course);
        $this->assertEquals(0, $result['added']);
        $this->assertEquals(0, $result['deleted']);
        $this->assertGreaterThan(0, $result['updated']);
    }

    /**
     * Make sure that we don't make any changes for courses that have no
     * problems.
     */
    public function test_noproblems() {
        $course = $this->create_course_with_content();

        $result = local_ucla_course_section_fixer::has_problems($course);
        $this->assertFalse($result);

        $result = local_ucla_course_section_fixer::fix_problems($course);
        $this->assertEquals(0, $result['added']);
        $this->assertEquals(0, $result['deleted']);
        $this->assertEquals(0, $result['updated']);
    }

}
