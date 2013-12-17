<?php
// This file is part of the UCLA syllabus plugin for Moodle - http://moodle.org/
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
 * Generator class to create syllabi entries.
 *
 * @package    local_ucla_syllabus
 * @category   test
 * @copyright 2013 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * UCLA syllabus data generator.
 *
 * @package    local_ucla_syllabus
 * @category   test
 * @copyright 2013 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_ucla_syllabus_generator_testcase extends advanced_testcase {

    /**
     * Reset database after each testcase.
     */
    protected function setUp() {
        // Must be a non-guest user to create resources.
        $this->setAdminUser();

        $this->resetAfterTest(true);
    }

    /**
     * Make sure syllabus generator creates private syllabus with an url.
     */
    public function test_create_private_url() {
        global $DB;

        // There are 0 syllabi initially.
        $this->assertEquals(0, $DB->count_records('ucla_syllabus'));

        $generator = $this->getDataGenerator()->get_plugin_generator('local_ucla_syllabus');

        // Create course to put syllabi into.
        $uclagenerator = $this->getDataGenerator()->get_plugin_generator('local_ucla');
        $course = $uclagenerator->create_class();
        $class = array_pop($course);

        // Create private syllabus.
        $privatesyllabus = new stdClass();
        $privatesyllabus->courseid = $class->courseid;
        $privatesyllabus->access_type = UCLA_SYLLABUS_ACCESS_TYPE_PRIVATE;
        $privatesyllabus->url = 'http://ucla.edu';
        $syllabus = $generator->create_instance($privatesyllabus);

        // Make sure it was actually created.
        $this->assertNotEmpty($syllabus);
        $this->assertInstanceOf('ucla_private_syllabus', $syllabus);
        $this->assertEquals(1, $DB->count_records('ucla_syllabus'));

        // Make sure it has the correct access type and course.
        $this->assertEquals(UCLA_SYLLABUS_ACCESS_TYPE_PRIVATE, $syllabus->access_type);
        $this->assertEquals($privatesyllabus->url, $syllabus->url);
        $this->assertEquals($class->courseid, $syllabus->courseid);

        // Make sure no file was created.
        $file = $syllabus->stored_file;
        $this->assertEmpty($file);
    }

    /**
     * Make sure syllabus generator creates public syllabus with a file.
     */
    public function test_create_public_file() {
        global $DB;

        // There are 0 syllabi initially.
        $this->assertEquals(0, $DB->count_records('ucla_syllabus'));

        $generator = $this->getDataGenerator()->get_plugin_generator('local_ucla_syllabus');

        // Create course to put syllabi into.
        $uclagenerator = $this->getDataGenerator()->get_plugin_generator('local_ucla');
        $course = $uclagenerator->create_class();
        $class = array_pop($course);

        // Create public syllabus.
        $publicsyllabus = new stdClass();
        $publicsyllabus->courseid = $class->courseid;
        $publicsyllabus->access_type = UCLA_SYLLABUS_ACCESS_TYPE_PUBLIC;
        $syllabus = $generator->create_instance($publicsyllabus);

        // Make sure it was actually created.
        $this->assertNotEmpty($syllabus);
        $this->assertInstanceOf('ucla_public_syllabus', $syllabus);
        $this->assertEquals(1, $DB->count_records('ucla_syllabus'));

        // Make sure it has the correct access type and course.
        $this->assertEquals(UCLA_SYLLABUS_ACCESS_TYPE_PUBLIC, $syllabus->access_type);
        $this->assertEquals($class->courseid, $syllabus->courseid);

        // Make sure file was created.
        $file = $syllabus->stored_file;
        $this->assertInstanceOf('stored_file', $file);
    }
}

