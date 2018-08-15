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
$PAGE->requires->js('/blocks/ucla_media/bcast_table.js', true);

$pageparams = array();
$courseid = required_param('courseid', PARAM_INT);
$sort = optional_param('sort', 0, PARAM_INT);
$tab = optional_param('tab', $courseid, PARAM_INT);

if (!$course = get_course($courseid)) {
    print_error('coursemisconf');
}
require_login($course);
$context = context_course::instance($courseid, MUST_EXIST);
$pageparams['courseid'] = $courseid;

// See if user wants to view a particular video.
$mediaid = optional_param('videoid', null, PARAM_INT);
if (!empty($media)) {
    $pageparams['video'] = $mediaid;
}

init_page($course, $context,
        new moodle_url('/blocks/ucla_media/bcast.php', $pageparams), MEDIA_BCAST_VIDEO);
echo $OUTPUT->header();

// Are we allowed to display this page?
if (is_enrolled($context) || has_capability('moodle/course:view', $context)) {
    $videos = $DB->count_records('ucla_bruincast', array('courseid' => $courseid));
    $xvideos = $DB->count_records('ucla_bruincast_crosslist', array('courseid' => $courseid));
    if ($videos != 0 || $xvideos != 0) {
        print_media_page_tabs(get_string('headerbcast', 'block_ucla_media'), $course->id);

        // Show all videos.
        display_all($course, $sort, $tab, $pageparams);

        $event = \block_ucla_media\event\bruincast_index_viewed::create(
            array('context' => $context, 'other' => array(
                'page' => get_string('headerbcast', 'block_ucla_media')
                    )));
        $event->trigger();
    } else {
        // Display request link.
        if (can_request_media($courseid)) {
            print_media_page_tabs(get_string('headerbcast', 'block_ucla_media'), $course->id);
            echo get_string('bcnotavailable', 'block_ucla_media');
            echo html_writer::empty_tag('br');
            echo html_writer::link('https://d7.oid.ucla.edu/request-media-services',
                get_string('bcrequest', 'block_ucla_media'));
        } else {
            echo get_string('bcnotavailable', 'block_ucla_media');
        }
    }
} else {
    echo get_string('guestsarenotallowed', 'error');
}

echo $OUTPUT->footer();

/**
 * Outputs Bruincast content for given course.
 *
 * @param object $course
 * @param string $sort by course date
 * @param int    $tab 
 * @param array $pageparams
 */
function display_all($course, $sort, $tab, $pageparams) {
    global $OUTPUT, $USER, $DB;

    $pageparams['sort'] = !$sort;
    $pageparams['tab'] = $tab;

    echo html_writer::start_tag('div', array('id' => 'vidreserves-wrapper'));
    echo $OUTPUT->heading(get_string('headerbcast', 'block_ucla_media') .
            ": $course->fullname", 2, 'headingblock');

    $notice = get_config('block_ucla_media', 'bruincast_notice');
    if (!empty($notice)) {
        echo $OUTPUT->notification($notice);
    }

    echo html_writer::tag('p', get_string('bchelp', 'block_ucla_media'));

    // Print BruinCast tabs.
    $courseinfo = get_course($tab);
    $shortname = $courseinfo->shortname;
    $firstxtab = print_bcast_tabs($shortname, $course->id);

    // Retrieve tab content.
    // Original course content tab.
    if ($tab == $course->id && !$firstxtab) {
        $bccontent = $sort ? get_bccontent($course->id, 'DESC') : get_bccontent($course->id);
    // No original course content - use first crosslisted content.
    } else if ($firstxtab) {
        $bccontent = $sort ? get_bccrosslistcontent($course->id, $firstxtab, 'DESC') : 
                get_bccrosslistcontent($course->id, $firstxtab);
    // Use specified crosslisted course content.
    } else {
        $bccontent = $sort ? get_bccrosslistcontent($course->id, $tab, 'DESC') : 
                get_bccrosslistcontent($course->id, $tab);
    }

    $table = new html_table();
    $sorticon = $sort ? 'fa fa-sort-desc' : 'fa fa-sort-asc';
    $icontag = html_writer::empty_tag('i', array('class' => $sorticon));
    $bcdatecell = new html_table_cell(html_writer::tag('a', get_string('bccoursedate', 'block_ucla_media').$icontag,
            array('href' => new moodle_url('/blocks/ucla_media/bcast.php', $pageparams))));
    $table->head = array($bcdatecell, new html_table_cell(get_string('bcmedia', 'block_ucla_media')), 
            new html_table_cell(get_string('bcmetadata', 'block_ucla_media')));
    $table->size = array('0%', '0%', '100%');
    $table->id = 'bruincast-content-table';

    // Check if the course has any media with Titles or Comments.
    $hasmetadata = false;

    $prevweek = null;
    foreach ($bccontent as $media) {
        // Get data for the media content.
        $courseinfo = ucla_get_course_info($media->courseid);

        // Each video entry will have two rows. One row for Course date and
        // Media, then another row for Title and Comments.

        // Create Course date and Media row.
        $datecell = date('D, m/d/Y', $media->date);
        $week = $media->week;

        // Display the week for the BruinCast content if it is not a summer session.
        if ($courseinfo[0]->session == 'RG' && $week != $prevweek) {
            $prevweek = $week;
            $icon = html_writer::tag('i','',array('class' => 'fa fa-chevron-up'));
            if ($week == 11) {
                $row = new html_table_row(
                        [$icon.html_writer::tag('b', get_string('finals_week', 'block_ucla_weeksdisplay')), "", ""]);
            } else if ($week > -1) {
                $row = new html_table_row(
                        [$icon.html_writer::tag('b', get_string('week', 'block_ucla_weeksdisplay', $week)), "", ""]);
            } else {
                $row = new html_table_row(
                        [$icon.html_writer::tag('b', get_string('bcother', 'block_ucla_media')), "", ""]);
            }

            // Count the number of rows to collapse for week.
            $row->attributes['num'] = 
                    $DB->count_records('ucla_bruincast', array('courseid'=> $media->courseid, 'week' => $week));
            $row->attributes['class'] = "week-row";
            $table->data[] = $row;
        }
        
        $mediacell = '';

        $hasmultivideos = false;
        $textplay = get_string('bcmediaplay', 'block_ucla_media');
        $textresume = get_string('bcmediaresume', 'block_ucla_media');
        if (!empty($media->video_files)) {
            // There might be multiple video files, separated by comma.
            $videos = explode(',', $media->video_files);
            $videos = array_map('trim', $videos);

            $hasmultivideos = count($videos) > 1;
            foreach ($videos as $index => $filename) {
                $buttontextplay = $textplay;
                $buttontextresume = $textresume;
                if ($hasmultivideos) {
                    // If there are multiple videos, then append number.
                    $buttontextplay .= ' ' . ($index + 1);
                    $buttontextresume .= ' ' . ($index + 1);
                }

                $buttontextplay = '<button type="button" class="btn btn-primary">' .
                        '<i class="fa fa-video-camera" aria-hidden="true"></i> ' .
                        $buttontextplay . '</button>';
                $videolinkplay = html_writer::link(new moodle_url('/blocks/ucla_media/view.php#top',
                        array('mode' => MEDIA_BCAST_VIDEO, 'id' => $media->id, 'courseid' => $course->id, 
                        'filename' => $filename)), $buttontextplay);
                $mediacell .= $videolinkplay . ' ';

                $jwtimestamp = get_user_preferences('jwtimestamp_'.$course->id.'_'.$filename, NULL, $USER);
                if ($jwtimestamp !== NULL) {
                    if ($jwtimestamp === 'FINISHED') {
                        $buttontextresume = '<i class="fa fa-check-circle" aria-hidden="true"></i> Finished';
                        $jwtimestamp = 0;
                    } else {
                        $buttontextresume .= ' from ' . gmdate('H:i:s', $jwtimestamp);
                    }
                    $buttontextresume = '<button type="button" class="btn btn-default">' .
                            $buttontextresume . '</button>';
                    $videolinkresume = html_writer::link(new moodle_url('/blocks/ucla_media/view.php#top',
                            array('mode' => MEDIA_BCAST_VIDEO, 'id' => $media->id, 'courseid' => $course->id,
                            'filename' => $filename, 'offset' =>  $jwtimestamp)), $buttontextresume);
                    $mediacell .= $videolinkresume . ' ';
                }
            }
        }

        if (!empty($media->audio_files)) {
            // If there are multiple videos and audio, put a new line.
            if ($hasmultivideos) {
                $mediacell .= '<br /><br />';
            }

            // There might be multiple audio files, separated by comma.
            $audio = explode(',', $media->audio_files);
            $audio = array_map('trim', $audio);

            $hasmultiaudio = count($audio) > 1;
            foreach ($audio as $index => $filename) {
                $buttontextplay = $textplay;
                $buttontextresume = $textresume;
                if ($hasmultiaudio) {
                    // If there are multiple audio, then append number.
                    $buttontextplay .= ' ' . ($index + 1);
                    $buttontextresume .= ' ' . ($index + 1);
                }

                $buttontextplay = ' <button type="button" class="btn btn-primary">' .
                        '<i class="fa fa-microphone" aria-hidden="true"></i> ' .
                        $buttontextplay . '</button>';
                $audiolinkplay = html_writer::link(new moodle_url('/blocks/ucla_media/view.php',
                        array('mode' => MEDIA_BCAST_AUDIO, 'id' => $media->id, 'courseid' => $course->id,
                        'filename' => $filename, 'offset' => 0)), $buttontextplay);
                $mediacell .= $audiolinkplay . ' ';

                $jwtimestamp = get_user_preferences('jwtimestamp_'.$course->id.'_'.$filename, NULL, $USER);
                if ($jwtimestamp !== NULL) {
                    if ($jwtimestamp === 'FINISHED') {
                        $buttontextresume = '<i class="fa fa-check-circle" aria-hidden="true"></i> Finished';
                        $jwtimestamp = 0;
                    } else {
                        $buttontextresume .= ' from ' . gmdate('H:i:s', $jwtimestamp);
                    }
                    $buttontextresume = '<button type="button" class="btn btn-default">' .
                            $buttontextresume . '</button>';
                    $audiolinkresume = html_writer::link(new moodle_url('/blocks/ucla_media/view.php',
                            array('mode' => MEDIA_BCAST_AUDIO, 'id' => $media->id, 'courseid' => $course->id,
                            'filename' => $filename, 'offset' =>  $jwtimestamp)), $buttontextresume);
                    $mediacell .= $audiolinkresume . ' ';
                }
            }
        }

        // Create Metadata column.
        $metadatacell = '';
        if (!empty($media->title)) {
            $metadatacell .= html_writer::tag('strong',
                    get_string('bctitle', 'block_ucla_media') . ':') . ' ' .
                    $media->title . '<br />';
                    $hasmetadata = true;
        }
        if (!empty($media->comments)) {
            $metadatacell .= html_writer::tag('strong',
                    get_string('bccomments', 'block_ucla_media') . ':') . ' ' .
                    strip_tags(format_string($media->comments, FORMAT_PLAIN));
                    $hasmetadata = true;
        }

        $cells = array($datecell, $mediacell, $metadatacell);
        $row = new html_table_row($cells);
        $row->attributes['class'] = 'fold open';
        $table->data[] = $row;
    }

    // Only have two columns if there is no metadata.
    if (!$hasmetadata) {
        $table->head = array($bcdatecell, new html_table_cell(get_string('bcmedia', 'block_ucla_media')));
        $table->size = array('0%', '100%');
    }

    echo html_writer::table($table);
    echo html_writer::end_div('div');
}

/**
 * Returns Bruincast content for course.
 *
 * @param int $courseid
 * @param string $sort by course date
 * @return array
 */
function get_bccontent($courseid, $sort = 'ASC') {
    global $DB;
    return $DB->get_records('ucla_bruincast', array('courseid' => $courseid), 'date '.$sort);
}

/**
 * Returns crosslisted Bruincast content from course2 for course1.
 *
 * @param int $courseid1
 * @param int $courseid2
 * @param string $sort by course date
 * @return array
 */
function get_bccrosslistcontent($courseid1, $courseid2, $sort = 'ASC') {
    global $DB;
    $sql = 'SELECT bc.id, bc.courseid, term, srs, video_files, audio_files, date, title, comments, week
              FROM {ucla_bruincast} AS bc, {ucla_bruincast_crosslist} AS xlist 
             WHERE bc.id = xlist.contentid 
                   AND xlist.courseid = :courseid1 
                   AND bc.courseid = :courseid2
          ORDER BY date '.$sort;
    return $DB->get_records_sql($sql, array('courseid1'=>$courseid1, 'courseid2'=>$courseid2));
}

/**
 * Prints tabs for course Bruincast content.
 * 
 * @param string $activetab
 * @param int $courseid
 * @return int courseid of the active tab
 */
function print_bcast_tabs($activetab, $courseid) {
    // Print out the primary course tab.
    $courseinfo = get_course($courseid);
    $bcshortname = $courseinfo->shortname;
    $bccount = count(get_bccontent($courseid));
    if ($bccount != 0) {
        $tabs[] = new tabobject($bcshortname,new moodle_url('/blocks/ucla_media/bcast.php', 
                array('courseid' => $courseid)), $bcshortname.' ('.$bccount.')');
    }

    // Print out the secondary course tabs.
    // Get crosslisted courses.
    $firstxtab = NULL;
    $crosslisted = get_bccrosslisted_courses($courseid);
    if (!empty($crosslisted)) {
        foreach ($crosslisted as $xcourse) {
            $courseinfo = get_course($xcourse->courseid);
            $shortname = $courseinfo->shortname;
            $xcount = count(get_bccrosslistcontent($courseid, $xcourse->courseid));
            if ($xcount != 0) {
                $tabs[] = new tabobject($shortname,new moodle_url('/blocks/ucla_media/bcast.php', 
                        array('courseid' => $courseid, 'tab'=> $xcourse->courseid)), $shortname.' ('.$xcount.')');
            }
            // Make the first crosslisted tab the landing page if there's no original content.
            if ($bccount == 0 && $activetab == $bcshortname) {
                $activetab = $shortname;
                $firstxtab = $xcourse->courseid;
            }
        }
        print_tabs(array($tabs), $activetab);
        return $firstxtab;
    }
}