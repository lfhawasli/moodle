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
 * Displays bruincast videos
 *
 * @package    block_ucla_media
 * @author     Anant Mahajan
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/../../config.php');
require_once($CFG->dirroot . '/blocks/ucla_media/locallib.php');

$pageparams = array();
$courseid = required_param('courseid', PARAM_INT);

if (!$course = get_course($courseid)) {
    print_error('coursemisconf');
}
require_login($course);
$context = context_course::instance($courseid, MUST_EXIST);
$pageparams['courseid'] = $courseid;

// See if user wants to view a particular video.
$videoid = optional_param('videoid', null, PARAM_INT);
if (!empty($video)) {
    $pageparams['video'] = $videoid;
}

init_page($course, $context,
        new moodle_url('/blocks/ucla_media/bcast.php', $pageparams));
echo $OUTPUT->header();

// Are we allowed to display this page?
if (is_enrolled($context) || has_capability('moodle/course:view', $context)) {
    $videos = $DB->get_records('ucla_bruincast', array('courseid' => $courseid));
    $count = count($videos);
    if ($count != 0) {
        print_media_page_tabs(get_string('headerbcast', 'block_ucla_media'), $course->id);
        
        // Show all videos.
        display_all($course);

        $event = \block_ucla_media\event\index_viewed::create(
            array('context' => $context, 'other' => array(
                'page' => get_string('headerbcast', 'block_ucla_media')
                    )));
        $event->trigger();
    } else {
        echo get_string('mediaresnotavailable', 'block_ucla_media');
    }
} else {
    echo get_string('guestsarenotallowed', 'error');
}

echo $OUTPUT->footer();

/**
 * Outputs Bruincast content for given course.
 *
 * @param object $course
 */
function display_all($course) {
    global $OUTPUT;

    echo html_writer::start_tag('div', array('id' => 'vidreserves-wrapper'));
    echo $OUTPUT->heading(get_string('headerbcast', 'block_ucla_media') .
            ": $course->fullname", 2, 'headingblock');

    echo html_writer::tag('p', get_string('intro', 'block_ucla_media'),
            array('id' => 'videoreserves-intro'));
    echo "<br>";

    // Later this will be replaced with a table listing of videos by week.
    $videos = get_videos($course->id);
    $items = array();
    foreach ($videos as $video) {
        $items[] = html_writer::link(new moodle_url('/blocks/ucla_media/view.php',
                array('mode' => MEDIA_BCAST, 'id' => $video->id)),
                $video->name);
    }
    echo html_writer::alist($items);

    echo html_writer::end_div('div');
}

/**
 * Returns Bruincast videos for course.
 *
 * @param int $courseid
 * @return array
 */
function get_videos($courseid) {
    global $DB;

    return $DB->get_records('ucla_bruincast', array('courseid' => $courseid));
}

/**
 *
 * @param type $videolist
 * @param type $i
 */
function print_bcast($videolist, $i) {
    $j = 0;
    $table = new html_table();
    $table->head = array('Name ', '' , '', '');
    $table->attributes = array('class' => 'bruincasttable generaltable');
    foreach ($videolist as $video) {
        if ($video->week == $i) {
            $name = $video->name;
            $vidurl = "";
            $audurl = "";
            $podurl = "";
            if ($video->bruincast_url != null) {
                $vidurl = html_writer::link(
                new moodle_url('/blocks/ucla_media/view.php',
                array('id' => $video->id, 'mode' => MEDIA_BCAST)), "Video");
            }
            if ($video->audio_url != null) {
                $audurl = $video->audio_url;
            }
            if ($video->podcast_url != null) {
                $podurl = $video->podcast_url;
            }
            $table->data[] = array($video->name, $vidurl, $audurl, $podurl);
            $j++;
        }
    }
    if ($j != 0) {
        echo html_writer::tag('h3', "Week ".$i);
        echo html_writer::table($table);
    }

}