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
 * Displays videos.
 *
 * @package    block_ucla_media
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/../../config.php');
require_once($CFG->dirroot . '/blocks/ucla_media/locallib.php');
require_once($CFG->dirroot . '/filter/oidwowza/filter.php');

$mediaid = required_param('id', PARAM_INT);
$mode = required_param('mode', PARAM_INT);
$filename = optional_param('filename', null, PARAM_FILE);

// Try to find corresponding course for given video.
if ($mode == MEDIA_BCAST_VIDEO || $mode == MEDIA_BCAST_AUDIO) {
    $media = $DB->get_record('ucla_bruincast', array('id' => $mediaid));
    if (empty($media)) {
        print_error('errorinvalidvideo', 'block_ucla_media');
    } else if (empty($media->courseid) || !$course = get_course($media->courseid)) {
        print_error('coursemisconf');
    }
} else if ($mode == MEDIA_VIDEORESERVES) {
    $media = $DB->get_record('ucla_video_reserves', array('id' => $mediaid));
    if (($media->filename == null) && !empty($media->video_url)) {
        redirect($media->video_url);
    }
    if (empty($media)) {
        print_error('errorinvalidvideo', 'block_ucla_media');
    } else if (empty($media->courseid) || !$course = get_course($media->courseid)) {
        print_error('coursemisconf');
    }
}
require_login($course);
$context = context_course::instance($media->courseid, MUST_EXIST);

init_page($course, $context,
        new moodle_url('/blocks/ucla_media/view.php',
                array('id' => $mediaid, 'mode' => $mode, 'filename' => $filename)));
echo $OUTPUT->header();

// Are we allowed to display this page?
if (is_enrolled($context) || has_capability('moodle/course:view', $context)) {

    if ($mode == MEDIA_BCAST_VIDEO || $mode == MEDIA_BCAST_AUDIO) {
        echo $OUTPUT->heading($media->title, 2, 'headingblock');
        // Try to embed video or audio on page by calling filter.
        $filtertext = get_bruincast_filter_text($media, $mode, $filename);

        $filter = new filter_oidwowza($context, array());
        $html = $filter->filter($filtertext);
        echo $html;
        echo html_writer::empty_tag('br');
        echo $OUTPUT->container(html_writer::link(
                new moodle_url('/blocks/ucla_media/bcast.php',
                    array('courseid' => $course->id)),
                get_string('back', 'block_ucla_media')));

        $event = \block_ucla_media\event\bruincast_viewed::create(array(
            'context' => $context,
            'objectid' => $media->id,
            'other' => array(
                'filename' => $filename,
                'mode' => $mode
            )));
        $event->trigger();
    } else if ($mode == MEDIA_VIDEORESERVES) {
        $currentdate = time();
        $timeformat = get_string('strftimedate', 'langconfig');
        if ($currentdate > $media->stop_date) {
            // Is video is past.
            print_error('pastvideo_info', 'block_ucla_media', '',
                    userdate($media->stop_date, $timeformat));
        } else if ($currentdate < $media->start_date) {
            // Is video is in future.
            print_error('futurevideo_info', 'block_ucla_media', '',
                    userdate($media->start_date, $timeformat));
        }

        echo $OUTPUT->heading($media->video_title, 2, 'headingblock');

        // Try to embed video on page by calling filter.
        $filtertext = sprintf('{wowza:jw,%s,%s,%d,%d,%s}',
                'rtmpe://' . get_config('block_ucla_video_reserves', 'wowzaurl'),
                $media->filename, $media->width, $media->height, urlencode($media->video_url));
        $filter = new filter_oidwowza($context, array());
        $html = $filter->filter($filtertext);
        echo $html;

        echo html_writer::empty_tag('br');
        echo $OUTPUT->container(html_writer::link(
                new moodle_url('/blocks/ucla_media/videoreserves.php',
                    array('courseid' => $course->id, 'mode' => $mode)),
                get_string('back', 'block_ucla_media')));

        // Log the video the user is viewing.
        $event = \block_ucla_media\event\video_reserves_viewed::create(array(
            'context' => $context,
            'objectid' => $media->id,
            'other' => array(
                'name' => $media->video_title,
                'mode' => $mode
            )));
        $event->trigger();
    }
} else {
    echo get_string('guestsarenotallowed', 'error');
}

echo $OUTPUT->footer();
