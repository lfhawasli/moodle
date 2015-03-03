<?php
// This file is part of the UCLA support tools plugin for Moodle - http://moodle.org/
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
 * Tests the install script for the UCLA support tools plugin.
 *
 * @package    local_ucla_support_tools
 * @category   phpunit
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Test cases.
 *
 * @package    local_ucla_support_tools
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class migration_test extends advanced_testcase {

    /**
     * Generates given data to test different backup scenarios.
     *
     * Can be called multiple times so it will have a random string
     * added after each name.
     *
     * @param $options
     * @return array
     */
    protected function seed_data($options) {
        $returnobjects = array();
        if (!empty($options['tool'])) {
            $tool = \local_ucla_support_tools_tool::create(array(
                'name' => 'Tool ' . random_string(),
                'url' => '/' . random_string(),
                'description' => 'Description',
            ));
            $returnobjects['tool'] = $tool;
        }
        if (!empty($options['category'])) {
            $category = \local_ucla_support_tools_category::create(array(
                'name' => 'Category ' . random_string(),
                'color' => random_string(6)
            ));
            if (!empty($options['tool'])) {
                $category->add_tool($tool);
            }
            $returnobjects['category'] = $category;
        }
        if (!empty($options['tag'])) {
            $tag = \local_ucla_support_tools_tag::create(array(
                'name' => 'Tag ' . random_string(),
                'color' => random_string(6)
            ));
            if (!empty($options['tool'])) {
                $tag->add_tool($tool);
            }
            $returnobjects['tag'] = $tag;
        }

        return $returnobjects;
    }

    /**
     * Resets the database between runs.
     */
    protected function setUp() {
        $this->resetAfterTest(true);
    }

    /**
     * Makes sure that the export returns properly encoded JSON.
     */
    public function test_export() {
        // Create tool, add it to a category, and give it a tag.
        $objects = $this->seed_data(
            array('tool' => true, 'category' => true, 'tag' => true));
        $tool       = $objects['tool'];
        $category   = $objects['category'];
        $tag        = $objects['tag'];

        // Export data and try to see if data is formatted properly.
        $backupjson = \local_ucla_support_tools_migration::export();

        $backup = json_decode($backupjson, true);
        $this->assertArrayHasKey('ucla_support_tools', $backup);
        $this->assertArrayHasKey('ucla_support_categories', $backup);
        $this->assertArrayHasKey('ucla_support_tool_categories', $backup);
        $this->assertArrayHasKey('ucla_support_tags', $backup);
        $this->assertArrayHasKey('ucla_support_tool_tags', $backup);

        $backuptool = array_pop($backup['ucla_support_tools']);
        $this->assertEquals($tool->name, $backuptool['name']);
        $backupcategory = array_pop($backup['ucla_support_categories']);
        $this->assertEquals($category->name, $backupcategory['name']);
        $backuptag = array_pop($backup['ucla_support_tags']);
        $this->assertEquals($tag->name, $backuptag['name']);
    }

    /**
     * Makes sure that the import function works properly.
     */
    public function test_import() {
        // Test that tool is restored properly.
        $objects = $this->seed_data(array('tool' => true));
        $oldtool = array_pop($objects);
        $export = local_ucla_support_tools_migration::export();
        $oldtool->delete();
        $result = local_ucla_support_tools_migration::import($export);
        $this->assertTrue($result);
        $tools = local_ucla_support_tools_tool::fetch_all();
        $newtool = array_pop($tools);
        $this->assertEquals($oldtool->name, $newtool->name);
        $this->assertEquals($oldtool->url, $newtool->url);
        $this->assertEquals($oldtool->description, $newtool->description);

        // Test that category is restored properly.
        $objects = $this->seed_data(array('category' => true));
        $oldcategory = array_pop($objects);
        $export = local_ucla_support_tools_migration::export();
        $oldcategory->delete();
        $result = local_ucla_support_tools_migration::import($export);
        $this->assertTrue($result);
        $categories = local_ucla_support_tools_category::fetch_all();
        $newcategory = array_pop($categories);
        $this->assertEquals($oldcategory->name, $newcategory->name);
        $this->assertEquals($oldcategory->color, $newcategory->color);

        // Tests that tag is restored properly.
        $objects = $this->seed_data(array('tag' => true));
        $oldtag = array_pop($objects);
        $export = local_ucla_support_tools_migration::export();
        $oldtag->delete();
        $result = local_ucla_support_tools_migration::import($export);
        $this->assertTrue($result);
        $tags = local_ucla_support_tools_tag::fetch_all();
        $newtag = array_pop($tags);
        $this->assertEquals($oldtag->name, $newtag->name);
        $this->assertEquals($oldtag->color, $newtag->color);

        // Tests that tool to category is restored.
        $newcategory->add_tool($newtool);
        $export = local_ucla_support_tools_migration::export();
        $result = local_ucla_support_tools_migration::import($export);
        $this->assertTrue($result);
        $categorytools = $newcategory->get_tools();
        $categorytool = array_pop($categorytools);
        $this->assertEquals($oldtool->name, $categorytool->name);
        $this->assertEquals($oldtool->url, $categorytool->url);
        $this->assertEquals($oldtool->description, $categorytool->description);

        // Tests that tool to tag is restored.
        $newtag->add_tool($newtool);
        $export = local_ucla_support_tools_migration::export();
        $result = local_ucla_support_tools_migration::import($export);
        $this->assertTrue($result);
        $tagtools = $newtag->get_tools();
        $tagtool = array_pop($tagtools);
        $this->assertEquals($oldtool->name, $tagtool->name);
        $this->assertEquals($oldtool->url, $tagtool->url);
        $this->assertEquals($oldtool->description, $tagtool->description);
    }

    /**
     * Makes sure that the validate function works properly.
     */
    public function test_validate_import() {
        $this->seed_data(array('category' => true));
        $export = local_ucla_support_tools_migration::export();
        $result = local_ucla_support_tools_migration::validate_import($export);
        $this->assertTrue(is_array($result));

        $this->seed_data(array('tag' => true));
        $export = local_ucla_support_tools_migration::export();
        $result = local_ucla_support_tools_migration::validate_import($export);
        $this->assertTrue(is_array($result));

        $this->seed_data(
            array('tool' => true, 'category' => true, 'tag' => true));
        $export = local_ucla_support_tools_migration::export();
        $result = local_ucla_support_tools_migration::validate_import($export);
        $this->assertTrue(is_array($result));

        // Now, mess up data.
        $export = str_shuffle($export);
        $result = local_ucla_support_tools_migration::validate_import($export);
        $this->assertFalse($result);

        // Remove array element.
        $export = local_ucla_support_tools_migration::export();
        $export = json_decode($export, true);
        unset($export['ucla_support_tool_categories']);
        $export = json_encode($export);
        $result = local_ucla_support_tools_migration::validate_import($export);
        $this->assertFalse($result);
    }
}