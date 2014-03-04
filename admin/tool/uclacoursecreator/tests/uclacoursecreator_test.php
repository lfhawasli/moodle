<?php
// This file is part of the UCLA course creator plugin for Moodle - http://moodle.org/
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
 * Tests the UCLA course creator class.
 *
 * @package    tool_uclacoursecreator
 * @category   test
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/' . $CFG->admin . '/tool/uclacoursecreator/uclacoursecreator.class.php');

/**
 * PHPunit testcase class.
 *
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group ucla
 * @group tool_uclacoursecreator
 */
class uclacoursecreator_test extends advanced_testcase {

    /**
     * Instance of uclacoursecreator class.
     * @var uclacoursecreator
     */
    private $uclacoursecreator;

    /**
     * Setup.
     */
    protected function setUp() {
        $this->resetAfterTest();
        $this->uclacoursecreator = new uclacoursecreator();
    }

    /**
     * Teardown.
     */
    protected function tearDown() {
        $this->uclacoursecreator = null;
    }

    /**
     * Try to create a new category.
     */
    public function test_new_category() {
        global $DB;

        // Create a parent category.
        $this->uclacoursecreator->new_category('Parent');
        // Make sure it exists.
        $parent = $DB->get_record('course_categories', array('name' => 'Parent'));
        $this->assertFalse(empty($parent));
        $context = context_coursecat::instance($parent->id, true);
        $this->assertFalse(empty($context));

        // Create a child category.
        $this->uclacoursecreator->new_category('Child', $parent->id);
        // Make sure it exists.
        $child = $DB->get_record('course_categories', array('name' => 'Child'));
        $this->assertFalse(empty($child));
        $context = context_coursecat::instance($child->id, true);
        $this->assertFalse(empty($context));

        // Now see if category paths are propery set.
        fix_course_sortorder();
        $parent = $DB->get_record('course_categories', array('name' => 'Parent'));
        $this->assertFalse(empty($parent->path));
        $child = $DB->get_record('course_categories', array('name' => 'Child'));
        $this->assertFalse(empty($child->path));
    }

    /**
     * Try parse a valid email template.
     */
    public function test_valid_email_template() {
        $validtemplatefile = dirname(__FILE__) . '/fixtures/valid_email_template.txt';
        $result = $this->uclacoursecreator->email_parse_file($validtemplatefile);

        $this->assertTrue(is_array($result));
        $this->assertEquals($result['from'], 'CCLE <ccle@ucla.edu>');
        $this->assertEquals($result['bcc'],
                'Kearney, Deborah (dkearney@oid.ucla.edu)');
        $this->assertEquals($result['subject'],
                '#=nameterm=# #=coursenum-sect=# class site created');
        $this->assertFalse(empty($result['subject']));
    }

}
