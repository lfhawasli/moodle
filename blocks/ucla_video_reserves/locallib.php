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
 * Local library file.
 *
 * @package    block_ucla_video_reserves
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Sorts start date from least recent to most recent.
 *
 * @param object $a
 * @param object $b
 * @return int
 */
function cmp_start_date($a, $b) {
    if ($a->start_date == $b->start_date) {
        return 0;
    }
    return ($a->start_date < $b->start_date) ? -1 : 1;
}

/**
 * Sorts end date from most recent to least recent.
 *
 * @param object $a
 * @param object $b
 * @return int
 */
function cmp_stop_date($a, $b) {
    if ($a->stop_date == $b->stop_date) {
        return 0;
    }
    return ($a->stop_date < $b->stop_date) ? 1 : -1;
}

/**
 * Sorts title.
 *
 * @param object $a
 * @param object $b
 * @return int
 */
function cmp_title($a, $b) {
    if ($a->video_title == $b->video_title) {
        return 0;
    }
    return ($a->video_title < $b->video_title) ? -1 : 1;
}

/**
 * Prints out all of the html for displaying the video reserves page contents.
 *
 * @param object $course
 */
function display_video_reserves_contents($course) {
    global $OUTPUT;

    echo html_writer::start_tag('div', array('id' => 'vidreserves-wrapper'));

    echo $OUTPUT->heading(get_string('header', 'block_ucla_video_reserves',
            $course->fullname), 2, 'headingblock');

    echo html_writer::tag('p', get_string('intro', 'block_ucla_video_reserves'),
            array('id' => 'videoreserves-intro'));

    $videos = get_video_data($course->id);

    echo html_writer::start_tag('div', array('id' => 'vidreserves-content'));
    if (!empty($videos['current'])) {
        print_video_list($videos['current'], get_string('currentvideo', 'block_ucla_video_reserves'));
    } else {
        echo html_writer::tag('span', get_string('novideo', 'block_ucla_video_reserves'));
    }
    if (!empty($videos['future'])) {
        print_video_list($videos['future'], get_string('futurevideo', 'block_ucla_video_reserves'));
    }
    if (!empty($videos['past'])) {
        print_video_list($videos['past'], get_string('pastvideo', 'block_ucla_video_reserves'));
    }
    echo html_writer::end_tag('div');

    echo html_writer::end_tag('div');
}

/**
 * Obtains raw video data from the db, and returns a sorted version of that data based on
 * the current system time.
 *
 * @param int $courseid - the course info of the course that the video data is from.
 * courseinfo is array of courses array(0=>array(term, srs), 1=>array(term, srs))
 *
 * @return An array of arrays of the current, future, and past videos relative
 * to the system date, sorted chronologically.
 */
function get_video_data($courseid) {
    global $DB;

    $currentvideos = array();
    $futurevideos = array();
    $pastvideos = array();
    $currentdate = time();

    // Adding GROUP BY to the where clause of the database query ensures no
    // duplicate videos are displayed for crosslisted courses.
    $videos = $DB->get_records_select('ucla_video_reserves',
            "courseid = ? GROUP BY video_title", array($courseid));

    // Sort the data chronologically.
    foreach ($videos as $video) {
        if ($currentdate >= $video->start_date && $currentdate <= $video->stop_date) {
            $currentvideos[] = $video;
        } else if ($currentdate < $video->start_date) {
            $futurevideos[] = $video;
        } else if ($currentdate > $video->stop_date) {
            $pastvideos[] = $video;
        }
    }
    // Sort the different videos depending on their current status.
    if (!empty($currentvideos)) {
        usort($currentvideos, 'cmp_title');
    }
    if (!empty($futurevideos)) {
        usort($futurevideos, 'cmp_start_date');
    }
    if (!empty($pastvideos)) {
        usort($pastvideos, 'cmp_stop_date');
    }
    return array('current' => $currentvideos, 'future' => $futurevideos, 'past' => $pastvideos);
}

/**
 * Initializes all $PAGE variables.
 *
 * @param object $course
 * @param context_course $context
 * @param moodle_url $url
 */
function init_page($course, $context, $url) {
    global $PAGE;
    $PAGE->set_url($url);

    $pagetitle = $course->shortname . ': ' . get_string('pluginname', 'block_ucla_video_reserves');

    $PAGE->set_context($context);
    $PAGE->set_title($pagetitle);

    $PAGE->set_heading($course->fullname);

    $PAGE->set_pagelayout('incourse');
    $PAGE->set_pagetype('course-view-' . $course->format);
}

/**
 * Prints all of the html associated with a particular video list.
 *
 * @param array $videolist      A list of videos to be displayed. Meant to be
 *                              used with data obtained from get_video_data.
 * @param string $headertitle   The header title of the list to be displayed.
 */
function print_video_list($videolist, $headertitle) {

    echo html_writer::tag('h3', $headertitle);
    echo html_writer::start_tag('div');

    $output = array();
    foreach ($videolist as $video) {
        $outputstr = '';
        if ($headertitle == get_string('pastvideo', 'block_ucla_video_reserves')) {
            $outputstr = $video->video_title . ' (' .
                    get_string('pastvideo_info', 'block_ucla_video_reserves',
                               userdate($video->stop_date, get_string('strftimedatefullshort'))) . ')';
        } else if ($headertitle == get_string('futurevideo', 'block_ucla_video_reserves')) {
            $outputstr = $video->video_title . ' (' .
                    get_string('futurevideo_info', 'block_ucla_video_reserves',
                               userdate($video->start_date, get_string('strftimedatefullshort'))) . ')';
        } else {
            $outputstr = html_writer::link(
                    new moodle_url('/blocks/ucla_video_reserves/view.php',
                            array('id' => $video->id)), $video->video_title);
            // Append available dates to each link.
            if ($video->start_date != null && $video->stop_date != null) {
                $start = userdate($video->start_date, get_string('strftimedatefullshort'));
                $stop = userdate($video->stop_date, get_string('strftimedatefullshort'));
                $outputstr .= ' (' .
                        get_string('availability', 'block_ucla_video_reserves') .
                        $start . ' - ' . $stop . ')';
            }
        }
        $output[] = $outputstr;
    }
    echo html_writer::alist($output);

    echo html_writer::end_tag('div');
}
