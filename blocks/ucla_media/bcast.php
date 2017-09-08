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

    $notice = get_config('block_ucla_media', 'bruincast_notice');
    if (!empty($notice)) {
        echo $OUTPUT->notification($notice);
    }

    echo html_writer::tag('p', get_string('bchelp', 'block_ucla_media'));

    $videos = get_videos($course->id);

    $table = new html_table();
    $table->head = array(get_string('bccoursedate', 'block_ucla_media'),
        get_string('bcmedia', 'block_ucla_media'));
    $table->size = array('20%', '80%');
    $table->id = 'bruincast-content-table';

    foreach ($videos as $video) {
        // Each video entry will have two rows. One row for Course date and
        // Media, then another row for Title and Comments.

        // Create Course date and Media row.
        $datecell = date('D, m/d/Y', $video->date);

        $mediacell = '';
        if (!empty($video->bruincast_url)) {
            $videolink = html_writer::link(new moodle_url('/blocks/ucla_media/view.php',
                    array('mode' => MEDIA_BCAST_VIDEO, 'id' => $video->id)),
                    get_string('bcvideo', 'block_ucla_media'));
            $mediacell .= '<button type="button" class="btn btn-default">' .
                    '<i class="fa fa-video-camera" aria-hidden="true"></i> ' .
                    $videolink . '</button>';
        }

        if (!empty($video->audio_url)) {
            $audiolink = html_writer::link(new moodle_url('/blocks/ucla_media/view.php',
                    array('mode' => MEDIA_BCAST_AUDIO, 'id' => $video->id)),
                    get_string('bcaudio', 'block_ucla_media'));
            $mediacell .= ' <button type="button" class="btn btn-default">' .
                    '<i class="fa fa-microphone" aria-hidden="true"></i> ' .
                    $audiolink . '</button>';
        }

        // Create Title and Comments row.
        $titlecommentstring = '';
        if (!empty($video->name)) {
            $titlecommentstring .= html_writer::tag('strong',
                    get_string('bctitle', 'block_ucla_media') . ':') . ' ' .
                    $video->name . '<br />';
        }
        if (!empty($video->comments)) {
            $titlecommentstring .= html_writer::tag('strong',
                    get_string('bccomments', 'block_ucla_media') . ':') . ' ' .
                    $video->comments;
        }
        if (!empty($mediacell)) {
            // Add spacing if there are media buttons.
            $mediacell .= '<br><br>';
        }
        $mediacell .= $titlecommentstring;

        // Make date cell v align middle and font size larger.
        $datecellclass = new html_table_cell($datecell);
        $datecellclass->style = "vertical-align: middle; font-size: larger";

        $cells = array($datecellclass, $mediacell);
        $row = new html_table_row($cells);
        $table->data[] = $row;
    }

    echo html_writer::table($table);
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
    return $DB->get_records('ucla_bruincast', array('courseid' => $courseid), 'date ASC');
}
