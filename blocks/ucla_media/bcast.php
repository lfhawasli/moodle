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
$sort = optional_param('sort', 0, PARAM_INT);

if (!$course = get_course($courseid)) {
    print_error('coursemisconf');
}
require_login($course);
$context = context_course::instance($courseid, MUST_EXIST);
$pageparams['courseid'] = $courseid;

// See if user wants to view a particular video.
$mediaid = optional_param('videoid', null, PARAM_INT);
if (!empty($media)) {
    $pageparams['video'] = $mediaid;
}

init_page($course, $context,
        new moodle_url('/blocks/ucla_media/bcast.php', $pageparams), MEDIA_BCAST_VIDEO);
echo $OUTPUT->header();

// Are we allowed to display this page?
if (is_enrolled($context) || has_capability('moodle/course:view', $context)) {
    $videos = $DB->get_records('ucla_bruincast', array('courseid' => $courseid));
    $count = count($videos);
    if ($count != 0) {
        print_media_page_tabs(get_string('headerbcast', 'block_ucla_media'), $course->id);

        // Show all videos.
        display_all($course, $sort, $pageparams);

        $event = \block_ucla_media\event\bruincast_index_viewed::create(
            array('context' => $context, 'other' => array(
                'page' => get_string('headerbcast', 'block_ucla_media')
                    )));
        $event->trigger();
    } else {
        // Display request link.
        if (can_request_media($courseid)) {
            print_media_page_tabs(get_string('headerbcast', 'block_ucla_media'), $course->id);
            echo get_string('bcnotavailable', 'block_ucla_media');
            echo html_writer::empty_tag('br');
            echo html_writer::link('https://d7.oid.ucla.edu/request-media-services',
                get_string('bcrequest', 'block_ucla_media'));
        } else {
            echo get_string('bcnotavailable', 'block_ucla_media');
        }
    }
} else {
    echo get_string('guestsarenotallowed', 'error');
}

echo $OUTPUT->footer();

/**
 * Outputs Bruincast content for given course.
 *
 * @param object $course
 * @param string $sort by course date
 * @param array $pageparams
 */
function display_all($course, $sort, $pageparams) {
    global $OUTPUT;

    $pageparams['sort'] = !$sort;

    echo html_writer::start_tag('div', array('id' => 'vidreserves-wrapper'));
    echo $OUTPUT->heading(get_string('headerbcast', 'block_ucla_media') .
            ": $course->fullname", 2, 'headingblock');

    $notice = get_config('block_ucla_media', 'bruincast_notice');
    if (!empty($notice)) {
        echo $OUTPUT->notification($notice);
    }

    echo html_writer::tag('p', get_string('bchelp', 'block_ucla_media'));

    $bccontent = $sort ? get_bccontent($course->id, 'DESC') : get_bccontent($course->id);

    $table = new html_table();
    $sorticon = $sort ? 'fa fa-sort-desc' : 'fa fa-sort-asc';
    $icontag = html_writer::empty_tag('i', array('class' => $sorticon));
    $bcdatecell = new html_table_cell(html_writer::tag('a', get_string('bccoursedate', 'block_ucla_media').$icontag,
            array('href' => new moodle_url('/blocks/ucla_media/bcast.php', $pageparams))));
    $table->head = array($bcdatecell, new html_table_cell(get_string('bcmedia', 'block_ucla_media')));
    $table->size = array('25%', '75%');
    $table->id = 'bruincast-content-table';

    foreach ($bccontent as $media) {
        // Each video entry will have two rows. One row for Course date and
        // Media, then another row for Title and Comments.

        // Create Course date and Media row.
        $datecell = date('D, m/d/Y', $media->date);

        $mediacell = '';

        $hasmultivideos = false;
        if (!empty($media->video_files)) {
            // There might be multiple video files, separated by comma.
            $videos = explode(',', $media->video_files);
            $videos = array_map('trim', $videos);

            $hasmultivideos = count($videos) > 1;
            foreach ($videos as $index => $filename) {
                $buttontext = get_string('bcvideo', 'block_ucla_media');
                if ($hasmultivideos) {
                    // If there are multiple videos, then append number.
                    $buttontext .= ' ' . ($index + 1);
                }

                $buttontext = '<button type="button" class="btn btn-default">' .
                        '<i class="fa fa-video-camera" aria-hidden="true"></i> ' .
                        $buttontext . '</button>';
                $videolink = html_writer::link(new moodle_url('/blocks/ucla_media/view.php',
                        array('mode' => MEDIA_BCAST_VIDEO, 'id' => $media->id, 'filename' => $filename)),
                        $buttontext);
                $mediacell .= $videolink . ' ';
            }
        }

        if (!empty($media->audio_files)) {
            // If there are multiple videos and audio, put a new line.
            if ($hasmultivideos) {
                $mediacell .= '<br /><br />';
            }

            // There might be multiple audio files, separated by comma.
            $audio = explode(',', $media->audio_files);
            $audio = array_map('trim', $audio);

            $hasmultiaudio = count($audio) > 1;
            foreach ($audio as $index => $filename) {
                $buttontext = get_string('bcaudio', 'block_ucla_media');
                if ($hasmultiaudio) {
                    // If there are multiple audio, then append number.
                    $buttontext .= ' ' . ($index + 1);
                }

                $buttontext = ' <button type="button" class="btn btn-default">' .
                        '<i class="fa fa-microphone" aria-hidden="true"></i> ' .
                        $buttontext . '</button>';
                $audiolink = html_writer::link(new moodle_url('/blocks/ucla_media/view.php',
                        array('mode' => MEDIA_BCAST_AUDIO, 'id' => $media->id, 'filename' => $filename)),
                        $buttontext);
                $mediacell .= $audiolink . '';
            }
        }

        // Create Title and Comments row.
        $titlecommentstring = '';
        if (!empty($media->title)) {
            $titlecommentstring .= html_writer::tag('strong',
                    get_string('bctitle', 'block_ucla_media') . ':') . ' ' .
                    $media->title . '<br />';
        }
        if (!empty($media->comments)) {
            $titlecommentstring .= html_writer::tag('strong',
                    get_string('bccomments', 'block_ucla_media') . ':') . ' ' .
                    $media->comments;
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
 * Returns Bruincast content for course.
 *
 * @param int $courseid
 * @param string $sort by course date
 * @return array
 */
function get_bccontent($courseid, $sort = 'ASC') {
    global $DB;
    return $DB->get_records('ucla_bruincast', array('courseid' => $courseid), 'date '.$sort);
}
