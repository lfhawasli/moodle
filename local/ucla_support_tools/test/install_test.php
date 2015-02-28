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

global $CFG;
require_once($CFG->dirroot . '/local/ucla_support_tools/db/install.php');
require_once($CFG->dirroot . '/report/uclastats/locallib.php');

/**
 * Test cases.
 */
class install_test extends advanced_testcase {
    /**
     * Holds list of tools in ucla support tools.
     * @var array
     */
    private $tools = null;

    /**
     * Tries to find specified tool by name and makes assertions that url and
     * description match.
     *
     * @param string $name
     * @param string $url
     * @param string $description Optional.
     * @return boolean
     */
    private function find_tool($name, $url, $description = null) {
        if (empty($this->tools)) {
            $this->tools = \local_ucla_support_tools_tool::fetch_all();
        }

        foreach ($this->tools as $tool) {
            if ($tool->name == $name) {
                $this->assertEquals($tool->url, $url);
                if (empty($description)) {
                    $this->assertEmpty($tool->description);
                } else {
                    $this->assertEquals($tool->description, $description);
                }
                return true;
            }
        }

        return false;
    }

    /**
     * Resets the database between tests.
     */
    protected function setUp() {
        $this->resetAfterTest(true);

        // Make sure that tool database is empty.
        $this->assertEmpty(count(\local_ucla_support_tools_tool::fetch_all()));
    }

    /**
     * Makes sure that all the UCLA stats console reports are loaded properly.
     */
    public function test_load_report_uclastats() {
        load_report_uclastats();

        $reports = get_all_reports();
        $tools = \local_ucla_support_tools_tool::fetch_all();
        $this->assertEquals(count($reports), count($tools));

        // Make sure that each report created exists.
        foreach ($reports as $class => $name) {
            // Look for entry with the same name.
            $url = new moodle_url('/report/uclastats/view.php',
                array('report' => $class));
            if (!$this->find_tool($name, $url->out_as_local_url(),
                    get_string($class . '_help', 'report_uclastats'))) {
                $this->assertTrue(false, "Did not find $name");
            }
        }
    }

    /**
     * Makes sure that all the UCLA roles reports are loaded properly.
     */
    public function test_load_tool_uclaroles() {
        load_tool_uclaroles();
        $reporttypes = array(
            'listing',
            'rolemappings',
            'remapping'
        );
        foreach ($reporttypes as $type) {
            $name = get_string($type, 'tool_uclaroles');
            $url = new moodle_url("/admin/tool/uclaroles/report/$type.php");
            if (!$this->find_tool($name, $url->out_as_local_url())) {
                $this->assertTrue(false, "Did not find $type");
            }
        }
    }

    /**
     * Makes sure that all the UCLA site indicator reports are loaded properly.
     */
    public function test_load_tool_uclasiteindicator() {
        load_tool_uclasiteindicator();
        $reporttypes = array(
            'orphans',
            'requesthistory',
            'sitelisting',
            'sitetypes',
        );
        foreach ($reporttypes as $type) {
            $name = get_string($type, 'tool_uclasiteindicator');
            $url = new moodle_url("/admin/tool/uclasiteindicator/report/$type.php");
            if (!$this->find_tool($name, $url->out_as_local_url())) {
                $this->assertTrue(false, "Did not find $type");
            }
        }
    }

    /**
     * Makes sure that some support console tools are loaded properly.
     */
    public function test_load_tool_uclasupportconsole() {
        load_tool_uclasupportconsole();
        // Only checking limited subset, because there is no clean way to get
        // the entire listing.
        $toolstocheck = array('syslogs', 'prepoprun', 'syllabusrecentlinks',
            'ccle_getclasses');
        foreach ($toolstocheck as $tool) {
            $name = get_string($tool, 'tool_uclasupportconsole');
            $url = new moodle_url('/admin/tool/uclasupportconsole/index.php', null, $tool);
            if (!$this->find_tool($name, $url->out_as_local_url())) {
                $this->assertTrue(false, "Did not find $name");
            }
        }
    }
}
