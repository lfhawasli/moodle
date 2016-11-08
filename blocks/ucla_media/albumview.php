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
require_once($CFG->dirroot . '/filter/oidwowza/filter.php');

$id = required_param('id', PARAM_TEXT);
$video = $DB->get_record_sql('SELECT * FROM {ucla_library_music_reserves} WHERE id=?', array($id));
$courseid = $video->courseid;

if (!$course = get_course($courseid)) {
    print_error('coursemisconf');
}
require_login($course);
$context = context_course::instance($courseid, MUST_EXIST);
init_page($course, $context,
        new moodle_url('/blocks/ucla_media/albumview.php',
                array('id' => $id)));
// Are we allowed to display this page?
if (is_enrolled($context) || has_capability('moodle/course:view', $context)) {
    $filtertext = sprintf('{lib:jw,"%s",%s,%s,%s}',
                addslashes($video->title), urlencode($video->httpurl),
                urlencode($video->rtmpurl), $video->isvideo);
    $filter = new filter_oidwowza($context, array());
    $html = $filter->filter($filtertext);
    echo $html;
    echo html_writer::empty_tag('br');
       
    // Log the video the user is viewing.
    $event = \block_ucla_media\event\video_viewed::create(array(
        'context' => $context,
        'objectid' => $video->id,
        'other' => array(
            'name' => $video->title,
            'type' => get_string('headerlibres', 'block_ucla_media')
        )));
    $event->trigger();
} else {
    echo get_string('guestsarenotallowed', 'error');
}
