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
$albumid = required_param('albumid', PARAM_INT);

if (!$course = get_course($courseid)) {
    print_error('coursemisconf');
}
require_login($course);
$context = context_course::instance($courseid, MUST_EXIST);

init_page($course, $context,
        new moodle_url('/blocks/ucla_media/libalbum.php',
                array('courseid' => $courseid, 'albumid' => $albumid,
                    'title' => $title)), MEDIA_LIBRARYMUSIC, $title);
echo $OUTPUT->header();

// Are we allowed to display this page?
if (is_enrolled($context) || has_capability('moodle/course:view', $context)) {
    $videos = $DB->get_records_select('ucla_library_music_reserves',
            "courseid = ? GROUP BY title", array($courseid));
    $count = count($videos);
    if ($count != 0) {
        print_media_page_tabs(get_string('headerlibres', 'block_ucla_media'), $course->id);
        html_writer::start_tag('div',array('id' => 'anchor'));
        display_page_album($course, $albumid);
        html_writer::end_div('div');

        echo html_writer::empty_tag('br');
        echo $OUTPUT->container(html_writer::link(
                new moodle_url('/blocks/ucla_media/libreserves.php',
                    array('courseid' => $courseid)),
                get_string('back', 'block_ucla_media')));

        $event = \block_ucla_media\event\library_reserves_index_viewed::create(
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
 * Function to display library music reserves for given course.
 *
 * @param object $course
 * @param int $albumid
 */
function display_page_album($course, $albumid) {
    global $DB, $OUTPUT;
    echo html_writer::start_tag('div', array('id' => 'vidreserves-wrapper'));
    $courseid = $course->id;
    
    // Get media with given album title.
    $sql = "SELECT media.*
              FROM {ucla_library_music_reserves} media
              JOIN {ucla_library_music_reserves} album ON (album.albumtitle=media.albumtitle)
             WHERE album.courseid=?
               AND album.id=?
          ORDER BY media.volume, media.disc, media.side, media.tracknumber";
    $reserves = $DB->get_records_sql($sql, array($courseid, $albumid));

    $samplereserve = reset($reserves);
    $title = $samplereserve->albumtitle;
    if ($samplereserve->composer != null) {
        $title = $samplereserve->composer.' '.$title;
    }

    echo $OUTPUT->heading($title, 2, 'headingblock');
    echo $OUTPUT->notification(get_string('intromusic', 'block_ucla_media'), 'info');

    $output = array();
    foreach ($reserves as $reserve) {
        $outputstr = '';
        if (!empty($reserve->embedurl)) {
            $outputstr = $reserve->embedurl;
        } else if ($reserve->isvideo == 0) {
            $outputstr = html_writer::link('#anchor', $reserve->title, array('id' => $reserve->id, 'class' => 'button audio'));
        } else {
            $outputstr = html_writer::link('#anchor', $reserve->title, array('id' => $reserve->id, 'class' => 'button video'));
        }
        $output[] = $outputstr;        
    }
    if (count($output) > 1) {
        echo html_writer::alist($output, array(), 'ul');
    } else {
        echo array_pop($output);
    }
    echo html_writer::end_div('div');
}

