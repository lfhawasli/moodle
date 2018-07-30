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

$courseid = required_param('courseid', PARAM_INT);

if (!$course = get_course($courseid)) {
    print_error('coursemisconf');
}
require_login($course);
$context = context_course::instance($courseid, MUST_EXIST);

init_page($course, $context,
        new moodle_url('/blocks/ucla_media/libreserves.php',
                array('courseid' => $courseid)), MEDIA_LIBRARYMUSIC);
echo $OUTPUT->header();

// Are we allowed to display this page?
if (is_enrolled($context) || has_capability('moodle/course:view', $context)) {
    $videos = $DB->get_records('ucla_library_music_reserves', array('courseid' => $courseid));
    $count = count($videos);
    if ($count != 0) {
        print_media_page_tabs(get_string('headerlibres', 'block_ucla_media'), $course->id);
        display_page($course);

        $event = \block_ucla_media\event\library_reserves_index_viewed::create(
            array('context' => $context, 'other' => array(
                'page' => get_string('headerlibres', 'block_ucla_media')
                    )));
        $event->trigger();
    } else {
        // Display request link.
        if (can_request_media($courseid)) {
            print_media_page_tabs(get_string('headerlibres', 'block_ucla_media'), $course->id);
            echo get_string('mlreservesnotavailable', 'block_ucla_media');
            echo html_writer::empty_tag('br');
            echo html_writer::link('mailto:lib_mus-circ@library.ucla.edu?subject=Requesting Digital music library reserves for '.
                $course->shortname, get_string('mlrequest', 'block_ucla_media'));
        } else {
            echo get_string('mlreservesnotavailable', 'block_ucla_media');
        }
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
function display_page($course) {
    global $OUTPUT;
    global $DB;
    echo html_writer::start_tag('div', array('id' => 'vidreserves-wrapper'));
    $courseid = $course->id;
    echo $OUTPUT->heading(get_string('headerlibres', 'block_ucla_media') .
            ": $course->fullname", 2, 'headingblock');
    echo $OUTPUT->notification(get_string('intromusic', 'block_ucla_media'), 'info');

    echo "<br>";
    $reserves = $DB->get_records_sql('SELECT * FROM {ucla_library_music_reserves} WHERE courseid=? GROUP BY albumtitle', array($courseid));
    $titles = array();
    $output = array();
    foreach ($reserves as $reserve) {
        if ($reserve->composer != '') {
            $title = $reserve->composer.' - '.$reserve->albumtitle;
        } else {
            $title = $reserve->albumtitle;
        }
        $titles[$reserve->id] = $title;
    }
    natcasesort($titles);    
    foreach ($titles as $id => $title) {
        $outputstr = html_writer::link(
                        new moodle_url('/blocks/ucla_media/libalbum.php',
                        array('courseid'=> $courseid, 'albumid' => $id, 
                              'title' => $title)), $title); 
        $output[] = $outputstr;
    }
    echo html_writer::alist($output);
    echo html_writer::end_div('div');
}
