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
 * Displays Library music reserves
 *
 * @package    block_ucla_media
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/../../config.php');
require_once($CFG->dirroot . '/blocks/ucla_media/locallib.php');
$PAGE->requires->js('/blocks/ucla_media/display.js');
$PAGE->requires->jquery();

$courseid= required_param('courseid', PARAM_INT);
$title = required_param('title', PARAM_TEXT);

if (!$course = get_course($courseid)) {
    print_error('coursemisconf');
}
require_login($course);
$context = context_course::instance($courseid, MUST_EXIST);

init_page($course, $context,
        new moodle_url('/blocks/ucla_media/libalbum.php',
                array('title' => $title)));
echo $OUTPUT->header();

// Are we allowed to display this page?
if (is_enrolled($context) || has_capability('moodle/course:view', $context)) {
    $videos = $DB->get_records('ucla_library_music_reserves', array('courseid' => $courseid));
    $count = count($videos);
    if ($count != 0) {
        print_page_tabs(get_string('headerlibres', 'block_ucla_media'), $course->id);
        html_writer::start_tag('div',array('id' => 'anchor'));
        display_page_album($course, $title);
        html_writer::end_div('div');
        echo $OUTPUT->single_button(new moodle_url('/blocks/ucla_media/libreserves.php',
                array('courseid' => $courseid)), get_string('returntomedia', 'block_ucla_media'), 'get');
        $event = \block_ucla_media\event\index_viewed::create(
            array('context' => $context, 'other' => array(
                'page' => get_string('headerlibres', 'block_ucla_media')
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
 * Function to display library music reserves for given course
 *
 * @param course object
 */
function display_page_album($course,$title) {
    global $OUTPUT;
    global $DB;
    echo html_writer::start_tag('div', array('id' => 'vidreserves-wrapper'));
    $courseid = $course->id;
    echo $OUTPUT->heading(get_string('headerlibres', 'block_ucla_media') .
            ": $course->fullname", 2, 'headingblock');
    echo html_writer::tag('p', get_string('intromusic', 'block_ucla_media'),
            array('id' => 'videoreserves-intro'));
    echo "<br>";
    $videos = $DB->get_records_sql('SELECT * FROM {ucla_library_music_reserves} WHERE courseid=? AND albumtitle=?', array($courseid,$title));
    
    $samplevideo = reset($videos);
    if ($samplevideo->composer != NULL) {
        $title = $samplevideo->composer.' '.$title;
    }
    echo html_writer::tag('h3', 'Track Listing: '.$title);
    $output = array();
    foreach ($videos as $video) {
        $outputstr = '';
        if ($video->isvideo == 0) {
            $outputstr = html_writer::link('#anchor', $video->title, array('id' => $video->id, 'class' => 'button audio'));
        } else {
            $outputstr = html_writer::link('#anchor', $video->title, array('id' => $video->id, 'class' => 'button video'));
        }
        $output[] = $outputstr;
        
    }
    echo html_writer::alist($output, array(), 'ol');
    echo html_writer::end_div('div');
}

