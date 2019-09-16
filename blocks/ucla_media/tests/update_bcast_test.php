<?php
// This file is part of the UCLA Media block for Moodle - http://moodle.org/
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
 * Unit tests for classes/task/update_bcast.php.
 *
 * @package    block_ucla_media
 * @copyright  2019 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Unit test file.
 *
 * @package    block_ucla_media
 * @copyright  2019 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class update_bcast_test extends advanced_testcase {
    /**
     * Shared mocked_update_bcast class between all tests.
     *
     * @var mocked_update_bcast
     */
    private $updatebcast = null;

    /**
     * Reset database on every run.
     */
    protected function setUp() {
        $this->resetAfterTest(true);
        $this->updatebcast = new mocked_update_bcast();
        $session[] = [
            'term' => '18S',
            'term_start' => '2017-12-30',
            'term_end' => '2018-07-08',
            'session' => 'RG',
            'session_name' => 'REGULAR SESSION',
            'session_start' => '2018-03-28',
            'session_end' => '2018-06-15',
            'instruction_start' => '2018-04-02'
        ];
        $this->updatebcast->add_term('18S', $session);
    }

    /**
     * Makes sure crosslisting works.
     */
    public function test_crosslisting() {
        global $DB;
        // Create courses.
        $uclagen = $this->getDataGenerator()->get_plugin_generator('local_ucla');
        $class = $uclagen->create_class(['term' => '18S']);
        $class1 = array_pop($class);
        $course1 = get_course($class1->courseid);
        $class = $uclagen->create_class(['term' => '18S']);
        $class2 = array_pop($class);
        $course2 = get_course($class2->courseid);

        // Set crosslisting.
        set_config('bruincast_crosslists', $course1->shortname . '=' .
                $course2->shortname, 'block_ucla_media');

        // Setup media.
        $this->updatebcast->add_course($class1->term, $class1->srs);
        $media = [
            'date_for_recording_s_' => '05/10/2018',
            'placeholder' => 1,
            'audio' => [],
            'video' => 'eeb162-1-20180510-13893.mp4',
            'title' => 'Lecture',
            'comments' => ''
        ];
        $this->updatebcast->add_media($class1->term, $class1->srs, $media);

        // Associate term/srs with courseid so crosslisted can happen.
        $this->updatebcast->add_match($class1->term, $class1->srs, $class1->courseid);

        $this->expectOutputRegex('/Inserted: 2, Updated: 0, Deleted: 0 records./');
        $this->updatebcast->execute(['18S']);

        // Verify that content is crosslisted.
        $result = $DB->get_record('ucla_bruincast_crosslist', []);
        $this->assertEquals($class2->courseid, $result->courseid);

        // Run again and there should be no changes.
        $this->expectOutputRegex('/Inserted: 0, Updated: 0, Deleted: 0 records./');
        $this->updatebcast->execute(['18S']);

        // Remove media and should see delete from course and crosslist.
        $this->updatebcast->clear_media();
        $this->expectOutputRegex('/Inserted: 0, Updated: 0, Deleted: 2 records./');
        $this->updatebcast->execute(['18S']);
    }

    /**
     * Makes sure we give error if there is no data.
     */
    public function test_empty_update() {
        $this->expectException('moodle_exception');
        $this->expectOutputRegex('/Starting BruinCast DB update:/');
        $this->updatebcast->execute();
    }

    /**
     * Makes sure that we get data and insert and delete.
     */
    public function test_insert_delete() {
        global $DB;

        // Setup media.
        $this->updatebcast->add_course('18S', '128672200');
        $media = [
            'date_for_recording_s_' => '05/10/2018',
            'placeholder' => 1,
            'audio' => [],
            'video' => 'eeb162-1-20180510-13893.mp4',
            'title' => 'Lecture',
            'comments' => '<p>No audio. Video present.</p>'
        ];
        $this->updatebcast->add_media('18S', '128672200', $media);

        $this->expectOutputRegex('/Inserted: 1, Updated: 0, Deleted: 0 records./');
        $this->updatebcast->execute(['18S']);
        $this->assertEquals(1, $DB->count_records('ucla_bruincast'));

        // Run again and nothing should change.
        $this->expectOutputRegex('/Inserted: 0, Updated: 0, Deleted: 0 records./');
        $this->updatebcast->execute(['18S']);
        $this->assertEquals(1, $DB->count_records('ucla_bruincast'));

        // Add more content.
        $media = [
            'date_for_recording_s_' => '05/17/2018',
            'placeholder' => 0,
            'audio' => 'eeb162-1-20180517-6903.mp3',
            'video' => 'eeb162-1-20180517-13896.mp4',
            'title' => 'Lecture',
            'comments' => []
        ];
        $this->updatebcast->add_media('18S', '128672200', $media);

        $this->expectOutputRegex('/Inserted: 1, Updated: 0, Deleted: 0 records./');
        $this->updatebcast->execute(['18S']);
        $this->assertEquals(2, $DB->count_records('ucla_bruincast'));

        // Test deleting.
        $this->updatebcast->clear_media();
        $this->updatebcast->add_media('18S', '128672200', $media);

        $this->expectOutputRegex('/Inserted: 0, Updated: 0, Deleted: 1 records./');
        $this->updatebcast->execute(['18S']);
        $this->assertEquals(1, $DB->count_records('ucla_bruincast'));
    }

    /**
     * Makes sure that we update existing records.
     */
    public function test_update() {
        global $DB;

        // Add media.
        $this->updatebcast->add_course('18S', '128672200');
        $media = [
            'date_for_recording_s_' => '05/10/2018',
            'placeholder' => 1,
            'audio' => [],
            'video' => 'eeb162-1-20180510-13893.mp4',
            'title' => 'Lecture',
            'comments' => '<p>No audio. Video present.</p>'
        ];
        $this->updatebcast->add_media('18S', '128672200', $media);
        $this->updatebcast->execute(['18S']);

        // Now set audio file.
        $media['audio'] = 'eeb162-1-20180510-13893.mp3';
        $this->updatebcast->clear_media();
        $this->updatebcast->add_media('18S', '128672200', $media);
        $this->expectOutputRegex('/Inserted: 0, Updated: 1, Deleted: 0 records./');
        $this->updatebcast->execute(['18S']);
        $this->assertEquals($media['audio'], $DB->get_field('ucla_bruincast', 'audio_files', []));

        // Now change the same media.
        $media['title'] = 'Lecture 2';
        $this->updatebcast->add_media('18S', '128672200', $media);
        // Changing title is treated as a new record.
        $this->expectOutputRegex('/Inserted: 1, Updated: 0, Deleted: 0 records./');
        $this->updatebcast->execute(['18S']);
        $this->assertEquals(2, $DB->count_records('ucla_bruincast'));

        // Check that we can detect duplicate content for same date/title.
        $media['audio'] = 'eeb162-1-20180510-9999.mp3';
        $this->updatebcast->add_media('18S', '128672200', $media);
        $this->expectOutputRegex('/Found duplicate BruinCast entry/');
        $this->updatebcast->execute(['18S']);
    }
}

/**
 * We extend update_bcast to stub out methods that call external services.
 *
 * @package    block_ucla_media
 * @copyright  2019 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mocked_update_bcast extends block_ucla_media\task\update_bcast {
    /**
     * Array indexed by term.
     * @var array
     */
    private $courses = [];

    /**
     * Array indexed by term and srs.
     * @var array
     */
    private $matches = [];

    /**
     * Array indexed by term and srs.
     * @var array
     */
    private $media = [];

    /**
     * Term session data indexed by term.
     * @var array
     */
    private $session = [];

    /**
     * Adds course to mocked data.
     * @param string $term
     * @param string $srs
     */
    public function add_course($term, $srs) {
        $this->courses[$this->convert_term($term)]['item'] = ['srs__' => $srs];
    }

    /**
     * Adds matched term/srs to courseid to mocked data.
     * @param string $term
     * @param string $srs
     * @param int $courseid
     */
    public function add_match($term, $srs, $courseid) {
        $this->matches[$term][$srs] = $courseid;
    }

    /**
     * Adds media to mocked data.
     * @param string $term
     * @param string $srs
     * @param array $media  Expecting array with date, audio/video, title, and comments.
     */
    public function add_media($term, $srs, $media) {
        $this->media[$this->convert_term($term)][$srs][] = $media;
    }

    /**
     * Adds term session to mocked data.
     * @param string $term
     * @param array $session
     */
    public function add_term($term, $session) {
        $this->session[$term] = $session;
    }

    /**
     * Removes mocked data for matches.
     */
    public function clear_matches() {
        $this->matches = [];
    }

    /**
     * Removes mocked data for media.
     */
    public function clear_media() {
        $this->media = [];
    }

    /**
     * Returns mocked up course data.
     * @param string $term
     * @return array
     */
    protected function curl_get_courses($term) {
        return $this->courses[$term];
    }

    /**
     * Returns mocked up media data.
     * @param string $term
     * @param string $srs
     * @return array
     */
    protected function curl_get_media($term, $srs) {
        if (isset($this->media[$term][$srs])) {
            return $this->media[$term][$srs];
        }
        return [];
    }

    /**
     * Stub out login method since it will do nothing.
     * @return boolean
     */
    protected function curl_login() {
        return true;
    }

    /**
     * returns mocked data.
     *
     * @param string $term
     * @param string $srs
     *
     * return int   Course id, if any, otherwise null.
     */
    public function match_course($term, $srs) {
        if (isset($this->matches[$term][$srs])) {
            return $this->matches[$term][$srs];
        }
        return null;
    }

    /**
     * Returns mocked up term data.
     * @param string $term
     * @return block_ucla_weeksdisplay_session
     */
    protected function registrar_get_term_session($term) {
        return \block_ucla_weeksdisplay_session::create($this->session[$term]);
    }
}