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

$courseid = required_param('courseid', PARAM_INT);

if (!$course = get_course($courseid)) {
    print_error('coursemisconf');
}
require_login($course);
$context = context_course::instance($courseid, MUST_EXIST);

init_page($course, $context,
        new moodle_url('/blocks/ucla_media/bcast.php',
                array('courseid' => $courseid)));
echo $OUTPUT->header();

// Are we allowed to display this page?
if (is_enrolled($context) || has_capability('moodle/course:view', $context)) {
    $videos = $DB->get_records('ucla_bruincast', array('courseid' => $courseid));
    $count = count($videos);
    if ($count != 0) {
        print_page_tabs(get_string('headerbcast', 'block_ucla_media'), $course->id);
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
    $content = get_videos($course->id);
    foreach ($content as $link) {
        echo $link . '<br>';
    }

    echo html_writer::end_div('div');
}

/**
 * A course might have more than 1 Bruincast link. Some possible reasons are if
 * a course is cross-listed or if there are multiple restriction types, or both.
 *
 * Logic to decide how to display links in these different scenarios:
 *
 * 1) If links all have same restriction, then get last part of url, which will
 *    be the course name and display it as: Bruincast (<course title>)
 * 2) If links have different restrictions, then display as:
 *    Bruincast (<restriction type>) 
 * 3) If links have different restrictions and different course titles, then
 *    display as: Bruincast (<course title>/<restriction type>)
 * 4) If there is only 1 url, then display as: Bruincast (<restriction type>)
 *
 * This will be replaced later when the new web service that we use to get
 * Bruincast videos is done via CCLE-6263.
 */
function get_videos($courseid) {
    global $DB;

    $videos = array();

    // Links will be indexed as: [coursetitle][restriction] => url.
    $links = array();

    if ($matchingcourses = $DB->get_records('ucla_bruincast',
            array('courseid' => $courseid))) {
        $titlesused = array();
        $restrictionsused = array();
        foreach ($matchingcourses as $matchingcourse) {
            if (empty($matchingcourse->bruincast_url)) {
                continue;
            }

            $title = basename($matchingcourse->bruincast_url);
            $title = core_text::strtoupper($title);

            $restriction = 'node_' . core_text::strtolower($matchingcourse->restricted);
            $restriction = str_replace(' ', '_', $restriction);

            $links[$title][$restriction] = $matchingcourse->bruincast_url;

            $titlesused[] = $title;
            $restrictionsused[] = $restriction;
        }

        // See what type of display scenario we are going to use.
        $multipletitles = false;
        $multiplerestrictions = false;
        if (count(array_unique($titlesused)) > 1) {
            $multipletitles = true;
        }
        if (count(array_unique($restrictionsused)) > 1) {
            $multiplerestrictions = true;
        }

        foreach ($links as $title => $restrictions) {
            foreach ($restrictions as $restriction => $url) {
                if ($multipletitles && !$multiplerestrictions) {
                    // 1) If links all have same restriction, then get last
                    //    part of url, which will be the course name and
                    //    display it as:
                    //      Bruincast (<course title>)
                    $videos[] = html_writer::link($url, sprintf('%s (%s)',
                            get_string('titlebcast', 'block_ucla_media'), $title));
                } else if (!$multipletitles && $multiplerestrictions) {
                    // 2) If links have different restrictions, then display
                    //    as:
                    //      Bruincast (<restriction type>)
                    $videos[] = html_writer::link($url, sprintf('%s (%s)',
                            get_string('titlebcast', 'block_ucla_media'),
                            get_string($restriction, 'block_ucla_media')));
                } else if ($multipletitles && $multiplerestrictions) {
                    // 3) If links have different restrictions and different
                    //    course titles, then display as:
                    //     Bruincast (<course title>/<restriction type>)
                    $videos[] = html_writer::link($url, sprintf('%s (%s/%s)',
                            get_string('titlebcast', 'block_ucla_media'),
                            $title,
                            get_string($restriction, 'block_ucla_media')));
                } else if (!$multipletitles && !$multiplerestrictions) {
                    // 4) If there is only 1 url, then display as:
                    //     Bruincast (<restriction type>)
                    $type = '';
                    if ($restriction != 'node_open') {
                        // Don't add restriction type text for open.
                        $type = sprintf(' (%s)', get_string($restriction, 'block_ucla_media'));
                    }
                    $videos[] = html_writer::link($url,
                            get_string('titlebcast', 'block_ucla_media') . $type);
                }
            }
        }
    }
    return $videos;
}

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
                array('id' => $video->id, 'mode' => 1)), "Video");
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