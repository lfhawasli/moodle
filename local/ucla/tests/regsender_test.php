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
 * Test the sending of syllabus information to the Registrar.
 *
 * @package    local_ucla
 * @category   test
 * @copyright  2013 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

// TODO: When automatic class loading is available via Moodle 2.6, we no longer
// need to include the local_ucla_regsender class, so delete it.
global $CFG;
require_once($CFG->dirroot . '/local/ucla/classes/local_ucla_regsender.php');

/**
 * PHPunit testcase class.
 *
 * @copyright  2013 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group ucla
 * @group local_ucla
 */
class regsender_test extends advanced_testcase {

    /**
     * Record from the 'ucla_request_classes' table.
     *
     * @var object
     */
    private $_class = null;

    /**
     * Record from the 'ucla_reg_classinfo' table.
     *
     * @var object
     */
    private $_classinfo = null;

    /**
     * Local copy of local_ucla_regsender to share between tests.
     *
     * @var local_ucla_regsender
     */
    private $_local_ucla_regsender = null;

    /**
     * Deletes the manually created 'ucla_syllabus_test' table.
     */
    protected function cleanup_reg_database() {
        global $DB;

        $dbman = $DB->get_manager();
        $table = new xmldb_table('ucla_syllabus_test');
        $dbman->drop_table($table);
    }

    /**
     * Creates the 'ucla_syllabus_test' table.
     *
     * Code copied from enrol/database/tests/sync_test.php: init_enrol_database.
     * 
     * @throws exception
     */
    protected function init_reg_database() {
        global $CFG, $DB;

        $dbman = $DB->get_manager();

        set_config('registrar_dbhost', $CFG->dbhost);
        set_config('registrar_dbuser', $CFG->dbuser);
        set_config('registrar_dbpass', $CFG->dbpass);

        if (!empty($CFG->dboptions['dbport'])) {
            set_config('registrar_dbhost',
                    $CFG->dbhost . ':' . $CFG->dboptions['dbport']);
        }

        switch (get_class($DB)) {
            case 'mssql_native_moodle_database':
                set_config('registrar_dbtype', 'mssql_n');
                break;

            case 'mysqli_native_moodle_database':
                set_config('registrar_dbtype', 'mysqli');
                if (!empty($CFG->dboptions['dbsocket'])) {
                    $dbsocket = $CFG->dboptions['dbsocket'];
                    if ((strpos($dbsocket, '/') === false and strpos($dbsocket,
                                    '\\') === false)) {
                        $dbsocket = ini_get('mysqli.default_socket');
                    }
                    set_config('registrar_dbtype',
                            'mysqli://' .
                            rawurlencode($CFG->dbuser) . ':' .
                            rawurlencode($CFG->dbpass) . '@' .
                            rawurlencode($CFG->dbhost) . '/' .
                            rawurlencode($CFG->dbname) . '?socket=' .
                            rawurlencode($dbsocket));
                }
                break;

            case 'oci_native_moodle_database':
                set_config('registrar_dbtype', 'oci8po');
                break;

            case 'pgsql_native_moodle_database':
                set_config('registrar_dbtype', 'postgres7');
                if (!empty($CFG->dboptions['dbsocket']) and ($CFG->dbhost ===
                        'localhost' or $CFG->dbhost === '127.0.0.1')) {
                    if (strpos($CFG->dboptions['dbsocket'], '/') !== false) {
                        set_config('registrar_dbhost',
                                $CFG->dboptions['dbsocket']);
                    } else {
                        set_config('registrar_dbhost', '');
                    }
                }
                break;

            case 'sqlsrv_native_moodle_database':
                set_config('registrar_dbtype', 'mssqlnative');
                break;

            default:
                throw new exception('Unknown database driver ' . get_class($DB));
        }

        // NOTE: It is stongly discouraged to create new tables in
        // advanced_testcase classes, but there is no other simple way to test
        // reg databases, so let's disable transactions as try to cleanup after
        // the tests.

        $table = new xmldb_table('ucla_syllabus_test');
        $table->add_field('term_cd', XMLDB_TYPE_CHAR, '3', null, XMLDB_NOTNULL,
                null, null, null);
        $table->add_field('subj_area_cd', XMLDB_TYPE_CHAR, '7', null,
                XMLDB_NOTNULL, null, null, 'term_cd');
        $table->add_field('crs_catlg_no', XMLDB_TYPE_CHAR, '8', null,
                XMLDB_NOTNULL, null, null, 'subj_area_cd');
        $table->add_field('sect_no', XMLDB_TYPE_CHAR, '6', null, XMLDB_NOTNULL,
                null, null, 'crs_catlg_no');
        $table->add_field('term_seq_num', XMLDB_TYPE_INTEGER, '10', null, null,
                null, null, 'sect_no');
        $table->add_field('public_syllabus_url', XMLDB_TYPE_CHAR, '255', null,
                null, null, null, 'term_seq_num');
        $table->add_field('private_syllabus_url', XMLDB_TYPE_CHAR, '255', null,
                null, null, null, 'term_seq_num');
        $table->add_field('protect_syllabus_url', XMLDB_TYPE_CHAR, '255', null,
                null, null, null, 'term_seq_num');
        $table->add_field('update_timestamp', XMLDB_TYPE_CHAR, '20', null, null,
                null, null, 'protect_syllabus_url');
        $table->add_field('comments', XMLDB_TYPE_CHAR, '1200', null, null, null,
                null, 'update_timestamp');
        $table->add_key('key', XMLDB_KEY_PRIMARY,
                array('term_cd', 'subj_area_cd', 'crs_catlg_no', 'sect_no'));
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }
        $dbman->create_table($table);
        $this->assertTrue($dbman->table_exists($table));

        // Set syllabus table.
        set_config('regsyllabustable', $CFG->prefix . $table->getName(),
                'local_ucla');
    }

    /**
     * Helper method to check if the event queue is clear of UCLA syllabus
     * events.
     *
     * @param boolean $isclear  If true, will assert that queue is clear.
     *                          If false, will assert otherwise.
     */
    private function is_event_queue_clear($isclear) {
        global $DB;
        // Make sure there are events in the queue.
        $eventsql = "SELECT  qh.*
                FROM    {events_queue_handlers} qh
                JOIN    {events_handlers} h ON (qh.handlerid = h.id)
                WHERE   (h.eventname=? OR h.eventname=?)";
        $eventparams = array('ucla_syllabus_added', 'ucla_syllabus_deleted');
        $existingevents = $DB->record_exists_sql($eventsql, $eventparams);

        if ($isclear) {
            $this->assertFalse($existingevents);
        } else {
            $this->assertTrue($existingevents);
        }
    }

    /**
     * Data provider. Returns an array of links usable for regsender's
     * set_syllabus_links method. Will return either a valid URL or empty values
     * for public, private, and protect keys.
     */
    public function provider_syllabus_links() {
        global $CFG;
        $retval = array();

        // We use the same link for all 3 types of syllabi.
        // NOTE: We are using SITEID, because it doesn't matter what the URL
        // actually is and we cannot use $this->_class, since it hasn't been
        // created when this method via setUp() is called.
        $link = (new moodle_url('/local/ucla_syllabus/index.php',
                array('id' => SITEID)))->out();

        // Return an array of all the possible combinations of public, private,
        // and protect set or unset.
        $combos = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->power_set(local_ucla_regsender::$syllabustypes, 0);

        foreach ($combos as $index => $combo) {
            // For each combo set, build the return array consistenting of the
            // set's values.
            $retval[$index] = array();
            foreach (local_ucla_regsender::$syllabustypes as $syllabustype) {
                if (in_array($syllabustype, $combo)) {
                    $retval[$index][0][$syllabustype] = $link;
                } else {
                    $retval[$index][0][$syllabustype] = '';
                }
            }
        }

        return $retval;
    }

    /**
     * Cleares the 'ucla_syllabus_test' table.
     */
    protected function reset_reg_database() {
        global $DB;
        $DB->delete_records('ucla_syllabus_test', array());
    }

    /**
     * Creates the local test tables.
     *
     * Also turn off transactions in order to create the local test tables.
     */
    public function setUp() {
        $this->init_reg_database();

        $this->resetAfterTest(false);
        $this->preventResetByRollback();

        $course = $this->getDataGenerator()->get_plugin_generator('local_ucla')
                ->create_class(array());
        $this->_class = array_pop($course);

        $classinfos = ucla_get_course_info($this->_class->courseid);
        $this->_classinfo = array_pop($classinfos);

        $this->_local_ucla_regsender = new local_ucla_regsender();
    }

    /**
     * Makes sure that the local test tables are deleted.
     */
    public function tearDown() {
        $this->cleanup_reg_database();
        $this->_local_ucla_regsender->close_regconnection();
        unset($this->_class);
        unset($this->_local_ucla_regsender);
    }

    /**
     * Make sure that event handler doesn't try to send syllabus links for 
     * collaboration sites.
     */
    public function test_collab_sites() {
        $collab = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_collab(array('type' => 'instruction'));

        $course = get_course($collab->id);
        $syllabusmanager = new ucla_syllabus_manager($course);
        $this->setAdminUser();  // Generator requires user to be set.
        // For given course, create a public syllabus.
        $syllabus = new stdClass();
        $syllabus->courseid = $collab->id;
        $syllabus->access_type = UCLA_SYLLABUS_ACCESS_TYPE_PUBLIC;
        $syllabus = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla_syllabus')
                ->create_instance($syllabus);

        // Trigger event and make sure nothing remains in queue.
        events_cron('ucla_syllabus_added');
        $this->is_event_queue_clear(true);

        // Delete course and make sure that event queue is clear.
        delete_course($collab->id);
        events_cron('ucla_syllabus_deleted');
        $this->is_event_queue_clear(true);
    }

    /**
     * Make sure that get_recent_syllabus_links returns the appropiate number
     * of rows.
     */
    public function test_get_recent_syllabus_links() {
        $this->setAdminUser();

        // Empty data should return nothing.
        $results = $this->_local_ucla_regsender->get_recent_syllabus_links();
        $this->assertEquals(0, count($results));

        // Create 5 courses with syllabi.
        $numcourses = 5;
        for ($i = 0; $i < $numcourses; $i++) {
            $course = $this->getDataGenerator()->get_plugin_generator('local_ucla')
                    ->create_class(array());
            $class = array_pop($course);
            $courseid = $class->courseid;

            $syllabus = new stdClass();
            $syllabus->courseid = $courseid;
            $syllabus->access_type = UCLA_SYLLABUS_ACCESS_TYPE_PUBLIC;
            $syllabus = $this->getDataGenerator()
                    ->get_plugin_generator('local_ucla_syllabus')
                    ->create_instance($syllabus);
        }

        // Sending syllabi links is done via cron, so need to trigger that.
        events_cron('ucla_syllabus_added');

        // Now Registrar table should have all syllabi links, lets get the most
        // recent ones.
        $results = $this->_local_ucla_regsender->get_recent_syllabus_links();
        $this->assertEquals($numcourses, count($results));

        $results = $this->_local_ucla_regsender->get_recent_syllabus_links($numcourses);
        $this->assertEquals($numcourses, count($results));

        $lesser = $numcourses - rand(1, $numcourses - 1);
        $results = $this->_local_ucla_regsender->get_recent_syllabus_links($lesser);
        $this->assertEquals($lesser, count($results));

        // Test if set invalid number to method.
        $results = $this->_local_ucla_regsender->get_recent_syllabus_links(-1);
        $this->assertEquals($numcourses, count($results));
    }

    /**
     * Makes sure that the get_syllabus_links method returns empty when there is
     * no data set.
     */
    public function test_get_syllabus_links_empty() {
        // No data in syllabus table. Should return nothing for given term/srs.
        $result = $this->_local_ucla_regsender
                ->get_syllabus_links($this->_class->courseid);
        $this->assertEmpty($result[$this->_class->term][$this->_class->srs]);
    }

    /**
     * Makes sure that the set_syllabus_links is able to set syllabus links
     * for the 3 different types.
     *
     * @dataProvider provider_syllabus_links
     *
     * @param array $links  An array with the following possible keys:
     *                      public, private, and protect.
     */
    public function test_set_syllabus_links($links) {
        $courseid = $this->_class->courseid;

        // Call setter.
        $result = $this->_local_ucla_regsender->set_syllabus_links($courseid,
                $links);
        $this->assertEquals(local_ucla_regsender::SUCCESS, $result);

        // Make sure the getter matches the input.
        $results = $this->_local_ucla_regsender->get_syllabus_links($courseid);

        foreach ($links as $type => $link) {
            $this->assertEquals($results[$this->_class->term]
                    [$this->_class->srs][$type . '_syllabus_url'], $link);
        }
    }

    /**
     * Make sure that set_syllabus_links returns the partial update return code
     * if we send it syllabi links that are a mix of non currently set and
     * already set.
     */
    public function test_set_syllabus_links_partial() {
        $courseid = $this->_class->courseid;

        $courselink = (new moodle_url('/local/ucla_syllabus/index.php',
                array('id' => $courseid)))->out();

        // First set public link.
        $links['public'] = $courselink;
        $result = $this->_local_ucla_regsender->set_syllabus_links($courseid,
                $links);
        $this->assertEquals($result, local_ucla_regsender::SUCCESS);

        // Create new course and crosslist it with the existing course.
        $anothercourse = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_class(array(array('term' => $this->_class->term)));
        $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->crosslist_courses($this->_class, $anothercourse);

        // Now set same link and should return a partial update return code,
        // because the newly crosslisted course did not have the link, but the
        // original course did.
        $result = $this->_local_ucla_regsender->set_syllabus_links($courseid,
                $links);
        $this->assertEquals($result, local_ucla_regsender::PARTIALUPDATE);
    }

    /**
     * Makes sure that set_syllabus_links will only update an entry on the
     * Registrar if it is empty for the given syllabus type or belongs to the
     * same server.
     */
    public function test_set_syllabus_links_same_server() {
        $courseid = $this->_class->courseid;

        // Seed initial value of links to update.
        $sitelink = (new moodle_url('/local/ucla_syllabus/index.php',
                array('id' => SITEID)))->out();
        $courselink = (new moodle_url('/local/ucla_syllabus/index.php',
                array('id' => $courseid)))->out();

        /* Testing the following scenarios:
         * 1) URL on same server. Should be able to change or erase it.
         * 2) URL on different server. Should not be able to change or erase it.
         *
         * Test it for each syllabus type.
         */
        foreach (local_ucla_regsender::$syllabustypes as $type) {
            // First set link to be same server.
            $result = $this->_local_ucla_regsender->set_syllabus_links($courseid,
                    array($type => $sitelink));
            $this->assertEquals($result, local_ucla_regsender::SUCCESS);

            // Then change link, but still on same server.
            $result = $this->_local_ucla_regsender->set_syllabus_links($courseid,
                    array($type => $courselink));
            $this->assertEquals($result, local_ucla_regsender::SUCCESS);

            // Update link again using the same value as before, should be no
            // update.
            $result = $this->_local_ucla_regsender->set_syllabus_links($courseid,
                    array($type => $courselink));
            $this->assertEquals($result, local_ucla_regsender::NOUPDATE);

            // Now  change link at Registrar to be a different server.
            // Subsequent updates will be skipped.
            $result = $this->_local_ucla_regsender->set_syllabus_links($courseid,
                    array($type => 'http://ucla.edu'));
            $this->assertEquals($result, local_ucla_regsender::SUCCESS);
            $result = $this->_local_ucla_regsender->set_syllabus_links($courseid,
                    array($type => $courselink));
            $this->assertEquals($result, local_ucla_regsender::NOUPDATE);
        }
    }

    /**
     * Makes sure that the events system is triggering properly whenever a
     * syllabus is added, updated, and deleted for a course.
     */
    public function test_ucla_syllabus_events() {
        global $DB;

        $courseid = $this->_class->courseid;
        $course = get_course($courseid);
        $syllabusmanager = new ucla_syllabus_manager($course);
        $this->setAdminUser();  // Generator requires user to be set.
        // For given course, create a public syllabus.
        $syllabus = new stdClass();
        $syllabus->courseid = $courseid;
        $syllabus->access_type = UCLA_SYLLABUS_ACCESS_TYPE_PUBLIC;
        $syllabus = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla_syllabus')
                ->create_instance($syllabus);

        // Sending syllabi links is done via cron, so need to trigger that.
        events_cron('ucla_syllabus_added');

        // This should have triggered an event and the Registrar table should
        // now have a record.
        $links = $this->_local_ucla_regsender->get_syllabus_links($courseid);
        $this->assertNotEmpty($links[$this->_class->term][$this->_class->srs]['public_syllabus_url']);
        $this->assertEmpty($links[$this->_class->term][$this->_class->srs]['private_syllabus_url']);
        $this->assertEmpty($links[$this->_class->term][$this->_class->srs]['protect_syllabus_url']);

        // Now convert this syllabus to a private syllabus, which will trigger
        // an delete and add event.
        $syllabusmanager->convert_syllabus($syllabus,
                UCLA_SYLLABUS_ACCESS_TYPE_PRIVATE);
        events_cron('ucla_syllabus_deleted');
        events_cron('ucla_syllabus_added');
        $links = $this->_local_ucla_regsender->get_syllabus_links($courseid);
        $this->assertEmpty($links[$this->_class->term][$this->_class->srs]['public_syllabus_url']);
        $this->assertNotEmpty($links[$this->_class->term][$this->_class->srs]['private_syllabus_url']);
        $this->assertEmpty($links[$this->_class->term][$this->_class->srs]['protect_syllabus_url']);

        // Add and then delete the same syllabus. Make sure that the event queue
        // is processed.
        $syllabus = new stdClass();
        $syllabus->courseid = $courseid;
        $syllabus->access_type = UCLA_SYLLABUS_ACCESS_TYPE_PUBLIC;
        $syllabus = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla_syllabus')
                ->create_instance($syllabus);
        $syllabi = $syllabusmanager->get_syllabi();
        $publicyllabus = $syllabi[UCLA_SYLLABUS_TYPE_PUBLIC];
        $syllabusmanager->delete_syllabus($publicyllabus);
        // Make sure there are events in the queue.
        $this->is_event_queue_clear(false);
        events_cron('ucla_syllabus_added');
        events_cron('ucla_syllabus_deleted');
        // Make sure there are no more events in the queue.
        $this->is_event_queue_clear(true);

        // Now add a syllabus that requires someone to login to view.
        $syllabus = new stdClass();
        $syllabus->courseid = $courseid;
        $syllabus->access_type = UCLA_SYLLABUS_ACCESS_TYPE_LOGGEDIN;
        $syllabus = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla_syllabus')
                ->create_instance($syllabus);
        events_cron('ucla_syllabus_added');
        $links = $this->_local_ucla_regsender->get_syllabus_links($courseid);
        $this->assertEmpty($links[$this->_class->term][$this->_class->srs]['public_syllabus_url']);
        $this->assertNotEmpty($links[$this->_class->term][$this->_class->srs]['private_syllabus_url']);
        $this->assertNotEmpty($links[$this->_class->term][$this->_class->srs]['protect_syllabus_url']);

        // Delete course and make sure that syllabi are wiped out.
        delete_course($courseid);
        events_cron('ucla_course_deleted');
        events_cron('ucla_syllabus_deleted');
        // Make sure there are no more events in the queue.
        $this->is_event_queue_clear(true);
        // Need to get syllabi links via classinfo, because course is deleted.
        $links = $this->_local_ucla_regsender
                ->get_syllabus_link(
                $this->_classinfo->term, $this->_classinfo->subj_area,
                $this->_classinfo->crsidx, $this->_classinfo->classidx);
        $this->assertNotEmpty($links);
        $this->assertEmpty($links['public_syllabus_url']);
        $this->assertEmpty($links['protect_syllabus_url']);
        $this->assertEmpty($links['private_syllabus_url']);
    }

}
