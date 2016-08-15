<?php
// This file is part of the UCLA video reserves block for Moodle - http://moodle.org/
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
 * Displays video reserves video links (chronologically).
 *
 * @package    block_ucla_bruincast
 * @author     Anant Mahajan
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/../../config.php');
require_once($CFG->dirroot . '/blocks/ucla_video_reserves/locallib.php');

$courseid = required_param('courseid', PARAM_INT);

if (!$course = get_course($courseid)) {
    print_error('coursemisconf');
}
require_login($course);
$context = context_course::instance($courseid, MUST_EXIST);

init_page($course, $context,
        new moodle_url('/blocks/ucla_bruincast/index.php',
                array('courseid' => $courseid)));
echo $OUTPUT->header();

// Are we allowed to display this page?
if (is_enrolled($context) || has_capability('moodle/course:view', $context)) {
    display_bruincast_videos($course);
} else {
    echo get_string('guestsarenotallowed', 'error');
}

echo $OUTPUT->footer();

function display_bruincast_videos($course) {
    global $OUTPUT;

    echo html_writer::start_tag('div', array('id' => 'vidreserves-wrapper'));

    echo $OUTPUT->heading(get_string('header', 'block_ucla_bruincast',
            $course->fullname), 2, 'headingblock');

    echo html_writer::tag('p', get_string('intro', 'block_ucla_bruincast'),
            array('id' => 'videoreserves-intro'));

    $videos = get_videos($course->id);

    echo html_writer::start_tag('div', array('id' => 'vidreserves-content'));
        print_videos($videos, get_string('currentvideo', 'block_ucla_bruincast'));

    echo html_writer::end_tag('div');

    echo html_writer::end_tag('div');
}

/**
 * Obtains raw video data from the db, and returns the list
 *
 * @param int $courseid - the course info of the course that the video data is from.
 * courseinfo is array of courses array(0=>array(term, srs), 1=>array(term, srs))
 *
 * @return An array of videos
 */
function get_videos($courseid) {
    global $DB;

    // Adding GROUP BY to the where clause of the database query ensures no
    // duplicate videos are displayed for crosslisted courses.
    $videos = $DB->get_records_select('ucla_bruincast',
            "courseid = ? ", array($courseid));

    return $videos;
}

function print_videos($videolist, $headertitle) {

    echo html_writer::tag('h3', $headertitle);
    echo html_writer::start_tag('div');
    echo '<br>';
    $output = array();
    for ($i = 1; $i <= 10; $i++) {
        echo html_writer::start_tag('div');
        echo html_writer::tag('h4', "Week ".$i);
        echo ('<br>');
        for ($j = 1; $j <= 2; $j++) {
            echo html_writer::start_tag('div');
            if ($j == 1) {
                echo html_writer::tag('p', "Videos");
            } else {
                echo html_writer::tag('p', "Audio");
            }
            foreach ($videolist as $video) {
                $outputstr = '';
                if ($video->week == $i) {
                    if ($j == 1 && ($video->media_type == "video")) {
                        $outputstr = html_writer::link(
                            new moodle_url('/blocks/ucla_bruincast/view.php',
                                    array('id' => $video->id)), $video->name);
                    } else if ($j == 2 && ($video->media_type == "audio")) {
                        $outputstr = html_writer::link(
                            new moodle_url('/blocks/ucla_bruincast/view.php',
                                    array('id' => $video->id)), $video->name);
                    }
                }
                if ($outputstr != '') {
                    echo html_writer::alist(array($outputstr));
                }
            }
            echo html_writer::end_tag('div');
        }
        echo html_writer::end_tag('div');
        echo ('<br>');
    }
    echo html_writer::end_tag('div');
}