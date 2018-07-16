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
     * @return int              Returns id of newly created section.
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
            $nextnum = $DB->get_field('course_sections', 'MAX(section) + 1',
                    array('course' => $course->id));
            $section['section'] = $nextnum;
        }

        return $DB->insert_record('course_sections', $section);
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
        $numsections = course_get_format($course)->get_last_section_number();
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
     * @param boolean $withcontent  Default false. If true, will make sure
     *                              deleted section has a course module.
     *
     * @return boolean              Return false if error. Otherwise true.
     */
    private function delete_section($course, $withcontent = false) {
        global $DB;

        if ($withcontent) {
            // We need to find a section with a course module.
            $sectionid = $DB->get_field('course_modules', 'section',
                    array('course' => $course->id), IGNORE_MULTIPLE);
            if (empty($sectionid)) {
                return false;
            }
        } else {
            // Get a random section to delete.
            $sections = $DB->get_records('course_sections',
                    array('course' => $course->id));
            shuffle($sections);
            $section = array_pop($sections);
            $sectionid = $section->id;
        }

        $DB->delete_records('course_sections', array('id' => $sectionid));
        return true;
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
     * Make sure check_sections_exist returns false when a course module belongs
     * to a non-existent section.
     */
    public function test_check_sections_exist() {
        $course = $this->create_course_with_content();
        $result = local_ucla_course_section_fixer::check_sections_exist($course);
        $this->assertTrue($result);

        // Delete section but not the course modules.
        $this->delete_section($course, true);
        $result = local_ucla_course_section_fixer::check_sections_exist($course);
        $this->assertFalse($result);
    }

    /**
     * Make sure that fix_problems properly fixes a course's sections.
     *
     */
    public function test_fix_problems() {
        $course = $this->create_course_with_content();

        $this->add_section($course);
        $this->add_section($course);
        $this->delete_section($course, true);
        $this->delete_section($course);
        $this->replace_section($course);

        $result = local_ucla_course_section_fixer::fix_problems($course);

        // With the amount of changes we are doing, we should have a return of
        // more than zero on these changes.
        $this->assertEquals(0, $result['added']);
        $this->assertGreaterThan(0, $result['deleted']);
        $this->assertGreaterThan(0, $result['updated']);

        // If we check, there should be no problems.
        $result = local_ucla_course_section_fixer::has_problems($course);
        $this->assertFalse($result);
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
     * Make sure handle_sections_exist deletes missing sections.
     */
    public function test_handle_sections_exist() {
        global $DB;
        $course = $this->create_course_with_content();
        $result = local_ucla_course_section_fixer::handle_sections_exist($course);

        $this->assertEquals(0, $result['added']);
        $this->assertEquals(0, $result['deleted']);
        $this->assertEquals(0, $result['updated']);

        // Delete section with course modules.
        $sql = $DB->get_field('course_modules', 'section', array('course' => $course->id), IGNORE_MULTIPLE);
        $this->delete_section($course, true);

        // Count number of course modules before.
        $beforecount = $DB->count_records('course_modules', array('course' => $course->id));
        $result = local_ucla_course_section_fixer::handle_sections_exist($course);
        $aftercount = $DB->count_records('course_modules', array('course' => $course->id));

        // Course module was deleted.
        $this->assertLessThan($beforecount, $aftercount);
        $this->assertEquals(0, $result['added']);
        // One section with content was deleted.
        $this->assertEquals(1, $result['deleted']);
        $this->assertEquals(0, $result['updated']);
    }

    /**
     * Make sure handle_sections_exist hides sections with invalid course
     * modules.
     */
    public function test_handle_sections_exist_error() {
        global $DB;
        $course = $this->create_course_with_content();
        $result = local_ucla_course_section_fixer::handle_sections_exist($course);

        $this->assertEquals(0, $result['added']);
        $this->assertEquals(0, $result['deleted']);
        $this->assertEquals(0, $result['updated']);

        // Create new section.
        $sectionid = $this->add_section($course);

        // Create course modules with 0 for instance id.
        $urlid = $DB->get_field('modules', 'id', array('name' => 'url'));
        $module = new stdClass();
        $module->course = $course->id;
        $module->module = $urlid;
        $module->instance = 0;
        $module->section = $sectionid;
        $module->added = time();
        $moduleid = $DB->insert_record('course_modules', $module);

        // Delete section.
        $DB->delete_records('course_sections', array('id' => $sectionid));
        rebuild_course_cache($course->id);

        // Count number of course modules before.
        $beforecount = $DB->count_records('course_modules', array('course' => $course->id));
        $result = local_ucla_course_section_fixer::handle_sections_exist($course);
        $aftercount = $DB->count_records('course_modules', array('course' => $course->id));

        // Course section was added.
        $this->assertEquals(1, $result['added']);
        $this->assertEquals(0, $result['deleted']);
        $this->assertEquals(0, $result['updated']);

        // Course section is hidden.
        $hashiddensection = $DB->record_exists('course_sections',
                array('course' => $course->id, 'visible' => 0));
        $this->assertTrue($hashiddensection);
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
