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
      print_page_tabs('Bruincast', $course->id);
    display_all($course);

            $event = \block_ucla_media\event\index_viewed::create(
            array('context' => $context, 'other' => array(
                'page' => "Bruincast"
                    )));
            $event->trigger();

} else {
    echo get_string('guestsarenotallowed', 'error');
}

echo $OUTPUT->footer();

function display_all($course) {
    global $OUTPUT;
    global $DB;
    echo html_writer::start_tag('div', array('id' => 'vidreserves-wrapper'));
    $courseid = $course->id;
    echo $OUTPUT->heading(get_string('headerbcast', 'block_ucla_media',
            $course->fullname), 2, 'headingblock');

    echo html_writer::tag('p', get_string('intro', 'block_ucla_media'),
            array('id' => 'videoreserves-intro'));
    echo "<br>";

    for ($i = 1; $i <= 10; $i++) {
        echo html_writer::start_div('Week '.$i);
        if ($DB->record_exists('ucla_bruincast',  array('courseid' => $courseid))) {
            $vids = get_videos($courseid);
            print_bcast($vids, $i);
        }
        echo html_writer::end_div('Week '.$i);
    }

    echo html_writer::end_div('div');
}


function get_videos($courseid) {
    global $DB;

    // Adding GROUP BY to the where clause of the database query ensures no
    // duplicate videos are displayed for crosslisted courses.
    $videos = $DB->get_records_select('ucla_bruincast',
            "courseid = ? ", array($courseid));

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