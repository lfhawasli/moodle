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

        // Test that external URLs are kept with the full path.
        $data4 = array(
            'name' => 'test4',
            'url' => 'http://test.com',
            'description' => 'test4',
        );

        $tool = \local_ucla_support_tools_tool::create($data4);
        $tools = \local_ucla_support_tools_tool::fetch_all();
        $last = array_pop($tools);
        $this->assertEquals($data4['name'], $last->name);
        $this->assertEquals($data4['url'], $last->url);

        // Test that trying to add another tool with the same url as an existing
        // tool will throw an exception.
        $data5 = array(
            'name' => 'test5',
            'url' => 'http://test.com',
            'description' => 'test5',
        );

        $thrownexception = false;
        try {
            $tool = \local_ucla_support_tools_tool::create($data5);
        } catch (Exception $e) {
            $thrownexception = true;
            $this->assertEquals('Duplicate URL', $e->getMessage());
        }
        $this->assertTrue($thrownexception);
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

    /**
     * Makes sure that favorited tools are returned first when returning tools
     * for a given category.
     */
    public function test_favorite_category_sort() {
        // Favoriting a tool requires an logged in user.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $atool = \local_ucla_support_tools_tool::create(array(
            'name' => 'A favorite tool',
            'url' => '/aurl',
            'description' => ''
        ));
        $btool = \local_ucla_support_tools_tool::create(array(
            'name' => 'B favorite tool',
            'url' => '/burl',
            'description' => ''
        ));
        $ctool = \local_ucla_support_tools_tool::create(array(
            'name' => 'C favorite tool',
            'url' => '/curl',
            'description' => ''
        ));

        // Put all tools into the same category.
        $category = \local_ucla_support_tools_category::create(array(
                    'name' => 'category',
                    'color' => 'abcdef'
        ));
        $category->add_tool($atool);
        $category->add_tool($btool);
        $category->add_tool($ctool);

        // Favorite C tool and it should be returned first. Then A and B tools.
        $ctool->toggle_favorite();
        $tools = $category->get_tools();
        $this->assertEquals($ctool->name, $tools[0]->name);
        $this->assertEquals($atool->name, $tools[1]->name);
        $this->assertEquals($btool->name, $tools[2]->name);

        // Favorite A tool and it first be returned first, then C, and B tools.
        $atool->toggle_favorite();
        $tools = $category->get_tools();
        $this->assertEquals($atool->name, $tools[0]->name);
        $this->assertEquals($ctool->name, $tools[1]->name);
        $this->assertEquals($btool->name, $tools[2]->name);
    }

    /**
     * Makes sure that the is_favorite and toggle_favorite methods work as
     * expected.
     */
    public function test_favorite_tool() {
        // Favoriting a tool requires an logged in user.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $tool = \local_ucla_support_tools_tool::create(array(
            'name' => 'My favorite tool',
            'url' => '/url',
            'description' => ''
        ));

        $this->assertFalse($tool->is_favorite());
        $this->assertTrue($tool->toggle_favorite());
        $this->assertTrue($tool->is_favorite());
        $this->assertFalse($tool->toggle_favorite());
        $this->assertFalse($tool->is_favorite());

        // Try making two as a favorite.
        $anothertool = \local_ucla_support_tools_tool::create(array(
            'name' => 'My other favorite tool',
            'url' => '/someplace/else.php',
            'description' => ''
        ));

        $this->assertFalse($anothertool->is_favorite());
        $this->assertTrue($tool->toggle_favorite());
        $this->assertTrue($anothertool->toggle_favorite());
        $this->assertFalse($tool->toggle_favorite());
        $this->assertFalse($anothertool->toggle_favorite());
    }

    /**
     * Makes sure that anyone who favorited a tool and that url changed, then
     * the corresponding hash urls should be updated as well.
     */
    public function test_favorite_url_change() {
        global $USER;

        // Favoriting a tool requires an logged in user.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $tool = \local_ucla_support_tools_tool::create(array(
            'name' => 'My favorite tool',
            'url' => '/url',
            'description' => ''
        ));
        $this->assertTrue($tool->toggle_favorite());

        // Now change URL and make sure that it is still marked as a favorite.
        $tool->url = '/anotherurl';
        $tool->update();

        // Force refresh of user preferences cache (120s is cache lifetime).
        $USER->preference['_lastloaded'] = time() - 121;
        $this->assertTrue($tool->is_favorite());

        // Check if multiple users favorited a tool that it still works.
        $anotheruser = $this->getDataGenerator()->create_user();
        $this->setUser($anotheruser);

        $anothertool = \local_ucla_support_tools_tool::create(array(
            'name' => 'Another favorite tool',
            'url' => '/throwinginanothertool',
            'description' => ''
        ));

        $this->assertTrue($tool->toggle_favorite());
        $this->assertTrue($anothertool->toggle_favorite());

        $tool->url = '/yetanotherurl';
        $tool->update();

        $USER->preference['_lastloaded'] = time() - 121;
        $this->assertTrue($tool->is_favorite());
        $this->setUser($user);
        $this->assertTrue($tool->is_favorite());
    }
}
