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
 * Displays video reserves video inline.
 *
 * @package    block_ucla_video_reserves
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/../../config.php');
require_once($CFG->dirroot . '/blocks/ucla_video_reserves/locallib.php');
require_once($CFG->dirroot . '/filter/oidwowza/filter.php');

$videoid = required_param('id', PARAM_INT);
// Try to find corresponding course for given video.
$video = $DB->get_record('ucla_video_reserves', array('id' => $videoid));
if (empty($video)) {
    print_error('errorinvalidvideo', 'block_ucla_video_reserves');
} else if (empty($video->courseid) || !$course = get_course($video->courseid)) {
    print_error('coursemisconf');
}
require_login($course);
$context = context_course::instance($video->courseid, MUST_EXIST);

// See if we need to embed video or redirect user to the video url.
if (empty($video->filename) && !empty($video->video_url)) {
    redirect($video->video_url);
} else if (empty($video->filename) && empty($video->video_url)) {
    // No video to embed and no url. Invalid entry.
    print_error('errorinvalidvideo', 'block_ucla_video_reserves');
}

init_page($course, $context,
        new moodle_url('/blocks/ucla_video_reserves/view.php',
                array('id' => $videoid)));
echo $OUTPUT->header();

// Are we allowed to display this page?
if (is_enrolled($context) || has_capability('moodle/course:view', $context)) {

    // Is the video in a timeframe that we should display to users?
    $currentdate = time();
    $timeformat = get_string('strftimedate', 'langconfig');
    if ($currentdate > $video->stop_date) {
        // Is video is past.
        print_error('pastvideo_info', 'block_ucla_video_reserves', '',
                userdate($video->stop_date, $timeformat));
    } else if ($currentdate < $video->start_date) {
        // Is video is in future.
        print_error('futurevideo_info', 'block_ucla_video_reserves', '',
                userdate($video->start_date, $timeformat));
    }

    echo $OUTPUT->heading($video->video_title, 2, 'headingblock');

    // Try to embed video on page by calling filter.
    $filtertext = sprintf('{wowza:jw,%s,%s,%d,%d}', 
            'rtmpe://' . get_config('block_ucla_video_reserves', 'wowzaurl'),
            $video->filename, $video->width, $video->height);
    $filter = new filter_oidwowza($context, array());
    $html = $filter->filter($filtertext);
    echo $html;

    echo html_writer::empty_tag('br');
    echo $OUTPUT->container(html_writer::link(
            new moodle_url('/blocks/ucla_video_reserves/index.php',
                array('courseid' => $course->id)),
            get_string('back', 'block_ucla_video_reserves')));

    // Log the video the user is viewing.
    $event = \block_ucla_video_reserves\event\video_viewed::create(array(
        'context' => $context,
        'objectid' => $video->id,
        'other' => array(
            'name' => $video->video_title
        )));
    $event->trigger();

} else {
    echo get_string('guestsarenotallowed', 'error');
}

echo $OUTPUT->footer();
