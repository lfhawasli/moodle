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
 * Displays video reserves video links (chronologically).
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
        new moodle_url('/blocks/ucla_media/videoreserves.php',
                array('courseid' => $courseid)), MEDIA_VIDEORESERVES);
echo $OUTPUT->header();

// Are we allowed to display this page?
if (is_enrolled($context) || has_capability('moodle/course:view', $context)) {
    $videos = $DB->get_records('ucla_video_reserves', array('courseid' => $courseid));
    if (!empty($videos)) {
        display_video_reserves($course);

        // Log that user viewed index.
        $event = \block_ucla_media\event\video_reserves_index_viewed::create(
                array('context' => $context,
                    'other' => array(
                    'page' => get_string('headervidres', 'block_ucla_media')
                        )));
        $event->trigger();
    } else {
        // Display request link.
        if (can_request_media($courseid)) {
            print_media_page_tabs(get_string('headervidres', 'block_ucla_media'), $course->id);
            echo get_string('vresourcesnotavailable', 'block_ucla_media');
            echo html_writer::empty_tag('br');
            echo html_writer::link('https://oid.ucla.edu/imcs/requesting-access-online-materials',
                get_string('vrrequest', 'block_ucla_media'));
        } else {
            echo get_string('vresourcesnotavailable', 'block_ucla_media');
        }
    }
} else {
    echo get_string('guestsarenotallowed', 'error');
}

echo $OUTPUT->footer();
