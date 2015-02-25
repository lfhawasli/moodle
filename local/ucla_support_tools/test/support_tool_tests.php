<?php
// This file is part of Moodle - http://moodle.org/
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
 * UCLA support tools plugin.
 *
 * @package    local_ucla_support_tools
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Basic class tests.
 */
class tool_creation_test extends advanced_testcase {
    
    protected function setUp() {
        $this->resetAfterTest(true);
    }
    
    function test_tool_creation() {
        global $CFG;

        $originalcount = count(\local_ucla_support_tools_tool::fetch_all());
        
        // Make sure constructor is protected.
        $class = new ReflectionClass('local_ucla_support_tools_tool');
        $constructor = $class->getConstructor();
        $this->assertFalse($constructor->isPublic());

        // Add absolute URL. Should return relative.
        $data = array(
            'name' => 'a ucla tool',
            'url' => $CFG->wwwroot . '/url',
            'description' => 'a tool description',
        );

        // Create tool.
        $tool = \local_ucla_support_tools_tool::create($data);

        $createdtool = \local_ucla_support_tools_tool::fetch($tool->get_id());
        $this->assertEquals($tool, $createdtool);

        $tools = \local_ucla_support_tools_tool::fetch_all();
        $first = array_pop($tools);
        $this->assertEquals($data['name'], $first->name);
        $this->assertEquals('/url', $first->url);

        // Test relative URL with get parameters.
        $data2 = array(
            'name' => 'test2',
            'url' => '/dir/test2.php?param1=1&param2=2',
            'description' => 'test2',
        );

        // Create another tool.
        $tool2 = \local_ucla_support_tools_tool::create($data2);
        $this->assertNotEquals($tool2, $tool);
        
        $this->assertEquals($data2['name'], $tool2->name);
        $this->assertEquals($data2['url'], $tool2->url);
        
        // Should have 2 tools by now.
        $tools = \local_ucla_support_tools_tool::fetch_all();
        $this->assertEquals($originalcount + 2, count($tools));
        
        $last = array_pop($tools);
        $this->assertEquals($data2['name'], $last->name);
        $this->assertEquals($data2['url'], $last->url);

        // Test bookmark URLs.
        $data3 = array(
            'name' => 'test3',
            'url' => '/script.php#bookmark',
            'description' => 'test3',
        );

        $tool = \local_ucla_support_tools_tool::create($data3);
        $tools = \local_ucla_support_tools_tool::fetch_all();
        $last = array_pop($tools);
        $this->assertEquals($data3['name'], $last->name);
        $this->assertEquals($data3['url'], $last->url);

        $exceptionthrown = false;
        try {
            \local_ucla_support_tools_tool::create(array(
            'name' => 'test4',
            'url' => 'http://test.com',
            'description' => 'test4',
        ));
        } catch (Exception $ex) {
            $this->assertEquals('coding_exception', get_class($ex));
            $exceptionthrown = true;
        }
        $this->assertTrue($exceptionthrown);
    }
    
    function test_tool_update() {
        global $CFG;

        // Create tool.
        $tool = \local_ucla_support_tools_tool::create(array(
                    'name' => 'a ucla tool',
                    'url' => '/url/2/3/4',
                    'description' => 'a tool description',
        ));

        $this->assertEquals($tool->name, 'a ucla tool');

        $tool->name = 'updated name';
        $tool->description = 'updated description';

        $tool->update();

        $updatedtool = \local_ucla_support_tools_tool::fetch($tool->get_id());
        $this->assertEquals($updatedtool->name, 'updated name');
        $this->assertEquals($updatedtool->description, 'updated description');
    }
    
    function test_tool_deletion() {
        $originalcount = count(\local_ucla_support_tools_tool::fetch_all());
        
        \local_ucla_support_tools_tool::create(array(
            'name' => 'a ucla tool',
            'url' => '/url/to/tool/1',
            'description' => 'a tool description'
        ));
        \local_ucla_support_tools_tool::create(array(
            'name' => 'another ucla tool',
            'url' => '/url/to/tool/2',
            'description' => 'a tool description'
        ));
        \local_ucla_support_tools_tool::create(array(
            'name' => 'and yet another ucla tool',
            'url' => '/url/to/tool/3',
            'description' => 'a tool description'
        ));

        $tools = \local_ucla_support_tools_tool::fetch_all();
        $this->assertEquals($originalcount + 3, count($tools));

        foreach ($tools as $tool) {
            $tool->delete();
        }

        $tools = \local_ucla_support_tools_tool::fetch_all();

        $this->assertEquals(0, count($tools));
    }

    function test_category_creation() {

        $data = array(
            'name' => 'a category',
            'color' => 'abcdef'
        );

        $cat = \local_ucla_support_tools_category::create($data);
        $this->assertEquals($data['name'], $cat->name);
        $this->assertEquals($data['color'], $cat->color);

        // Do not allow duplicate category names.
        try {
            $cat = \local_ucla_support_tools_category::create($data);
        } catch (Exception $ex) {
            /// exception thrown...
            return;
        }
        $this->fail('An expected exception has not been raised.');
    }

    function test_category_update() {

        $cat = \local_ucla_support_tools_category::create(array(
                    'name' => 'a category',
                    'color' => 'abcdef'
        ));
        $cat->name = 'updated category';
        $cat->color = '000000';
        $cat->update();

        $cat = \local_ucla_support_tools_category::fetch($cat->get_id());
        $this->assertEquals('updated category', $cat->name);
        $this->assertEquals('000000', $cat->color);
    }

    function test_category_deletion() {
        \local_ucla_support_tools_category::create(array(
            'name' => 'a ucla cat',
            'color' => '000000'
        ));
        \local_ucla_support_tools_category::create(array(
            'name' => 'another ucla cat',
            'color' => '11111a'
        ));
        // Duplicate
        \local_ucla_support_tools_category::create(array(
            'name' => 'and yet another ucla cat',
            'color' => 'abc123'
        ));

        $cats = \local_ucla_support_tools_category::fetch_all();
        $this->assertEquals(3, count($cats));

        foreach ($cats as $cat) {
            $cat->delete();
        }

        $cats = \local_ucla_support_tools_category::fetch_all();

        $this->assertEquals(0, count($cats));
    }

    function test_tool_category_insertion() {

        // Create a new tool.
        $tool = \local_ucla_support_tools_tool::create(array(
                    'name' => 'a ucla tool',
                    'url' => '/url/test',
                    'description' => 'a tool description s',
        ));

        // Create a new category.
        $cat = \local_ucla_support_tools_category::create(array(
                    'name' => 'a category',
                    'color' => 'abcdef'
        ));

        // Add the tool to the category
        $cat->add_tool($tool);

        // Try to insert again.
        $cat->add_tool($tool);

        // Now retrieve tool.
        $tools = $cat->get_tools();
        $this->assertEquals(1, count($tools));

        $cattool = array_pop($tools);

        $this->assertEquals($tool->name, $cattool->name);
        $this->assertEquals($tool->url, $cattool->url);

        // Now remove the tool from the category.
        $cat->remove_tool($cattool);

        // Try to remove again.
        $cat->remove_tool($cattool);

        // Now get tools for category.
        $tools = $cat->get_tools();
        $this->assertEquals(0, count($tools));
    }

    function test_tag_creation() {

        $data = array(
            'name' => 'a category',
            'color' => 'abcdef'
        );

        $tag = \local_ucla_support_tools_tag::create($data);
        $this->assertEquals($data['name'], $tag->name);
        $this->assertEquals($data['color'], $tag->color);

        $testingtag = \local_ucla_support_tools_tag::fetch($data['name']);
        $this->assertEquals($data['name'], $testingtag->name);

        $testingtag = \local_ucla_support_tools_tag::fetch($testingtag->get_id());
        $this->assertEquals($data['name'], $testingtag->name);

        $tags = \local_ucla_support_tools_tag::fetch_all();

        $atag = array_pop($tags);
        $this->assertEquals($data['name'], $atag->name);
        $this->assertEquals($data['color'], $atag->color);


        $tool = \local_ucla_support_tools_tool::create(array(
                    'name' => 'a ucla tool',
                    'url' => '/url',
                    'description' => 'a tool description',
        ));
        $testingtag->add_tool($tool);

        $atool = \local_ucla_support_tools_tool::fetch($tool->get_id());
        $tooltags = $atool->get_tags();
        $this->assertEquals(count($tooltags), 1);

        $anothertag = \local_ucla_support_tools_tag::create(array(
                    'name' => 'a tag',
                    'color' => '122222'
        ));
        $atool->add_tag($anothertag);

        $tooltags = $atool->get_tags();
        $this->assertEquals(count($tooltags), 2);
    }

}
