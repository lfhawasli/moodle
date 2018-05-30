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
 * Local library file.
 *
 * @package    block_ucla_media
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
define('MEDIA_BCAST_VIDEO', 1);
define('MEDIA_BCAST_AUDIO', 4);
define('MEDIA_VIDEORESERVES', 2);
define('MEDIA_LIBRARYMUSIC', 3);

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
 * Given ucla_bruincast record, will return string to be used in OID Wowza
 * filter.
 *
 * @param array $bruincast
 * @param int $mode         Either MEDIA_BCAST_VIDEO or MEDIA_BCAST_AUDIO.
 * @param string $filename  If present, then there is multiple
 * @return string
 */
function get_bruincast_filter_text($bruincast, $mode, $filename = null) {
    $wowzaserver = get_config('block_ucla_media', 'bruincast_wowza');

    // Check if we have a video file.
    $isvideo = false;
    if ($mode == MEDIA_BCAST_VIDEO && !empty($bruincast->video_files)) {
        // Make sure video exists.
        $isvideo = true;
    }

    $content = $isvideo ? $bruincast->video_files : $bruincast->audio_files;
    $contentfiles = explode(',', $content);
    $contentfiles = array_map('trim', $contentfiles);

    // Get filename. If passed in parameters then verify it is valid filename.
    if (!empty($filename)) {
        if (!in_array($filename, $contentfiles)) {
            // Not found so unset it.
            unset($filename);
        }
    }

    // If no filename found, then use first entry from contentfiles.
    if (empty($filename)) {
        $filename = reset($contentfiles);
    }

    // Get application name in format <4 digit year><quarter>-<v(video) or a(audio)>.
    $appname = '20' . substr($bruincast->term, 0, 2) .
            strtolower(substr($bruincast->term, 2, 1)) .'-';
    $extension = '';
    if ($isvideo) {
        $appname .= 'v';
        $extension = 'mp4';
    } else {
        // Audio files can be mp3 or mp4, use file extension.
        $appname .= 'a';
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
    }

    $contentpath = $appname . '/' . $extension . ':' . $filename;

    // Generate SecureToken hash.
    $additionalparams = '';
    $endtime = time() + MINSECS * get_config('', 'filter_oidwowza_minutesexpire');
    $securetoken = filter_oidwowza::generate_securetoken($contentpath, $endtime,
            get_config('', 'filter_oidwowza_bcsharedsecret'));
    if (!empty($securetoken)) {
        $additionalparams = "?wowzatokenendtime=$endtime&wowzatokenhash=$securetoken";
    }

    $httpurl = 'https://' . $wowzaserver . '/' . $contentpath .
            '/playlist.m3u8' . $additionalparams;
    $rtmpurl = 'rtmps://' . $wowzaserver . '/' . $contentpath .
            $additionalparams;

    return sprintf('{bruincast:jw,"%s",%s,%s,%s}', $bruincast->title, $httpurl,
            $rtmpurl, $isvideo);
}

/**
 * Queries for video reserves for a given course.
 *
 * @param int $courseid
 * @return array
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
 * Sorts title.
 *
 * @param object $a
 * @param object $b
 * @return int
 */
function cmp_title($a, $b) {
    // CCLE-5813 - for sorting, ignore "The", "A", and "An" at the beginning
    // of video titles.
    if (preg_match('/^(?:The|A|An)\s(.*)/', $a->video_title, $matches)) {
        $ta = $matches[1];
    } else {
        $ta = $a->video_title;
    }
    if (preg_match('/^(?:The|A|An)\s(.*)/', $b->video_title, $matches)) {
        $tb = $matches[1];
    } else {
        $tb = $b->video_title;
    }
    if ($ta == $tb) {
        return 0;
    }
    return ($ta < $tb) ? -1 : 1;
}

/**
 * Check if a given ip is in the UCLA network.
 *
 * From https://gist.github.com/tott/7684443
 * @param a ip in the form of "x.x.x.x" where x can be numbers between 0 to 255
 * @return boolean true if the ip is a ucla campus ip
 */
function is_on_campus_ip($ip) {
    // List of acceptable ip addresses obtained from https://kb.ucla.edu/articles/list-of-uc-related-ip-addresses.
    // This specifies ip ranges belonging to UCLA.
    $acceptableips = array('128.97.0.0/16', '131.179.0.0/16', '149.142.0.0/16', '164.67.0.0/16', '169.232.0.0/16',
                            '172.16.0.0/12', '192.35.210.0/24', '192.35.225.0/24', '192.154.2.0/24');
    foreach ($acceptableips as $range) {
        if (strpos($range, '/') == false) {
            $range .= '/32';
        }
        // Variable $range is in IP/CIDR format eg 127.0.0.1/24.
        list($range, $netmask) = explode('/', $range, 2);
        $rangedecimal = ip2long($range);
        $ipdecimal = ip2long($ip);
        $wildcarddecimal = pow(2, (32 - $netmask)) - 1;
        $netmaskdecimal = ~ $wildcarddecimal;
        $inrange = (($ipdecimal & $netmaskdecimal) == ($rangedecimal & $netmaskdecimal));
        if ($inrange == true) {
            return true;
        }
    }
    return false;
}

/**
 * Prints out all of the html for displaying the video reserves page contents.
 *
 * @param object $course
 */
function display_video_reserves($course) {
      global $OUTPUT;

    // Adding GROUP BY to the where clause of the database query ensures no
    // duplicate videos are displayed for crosslisted courses.
    $videos = get_video_data($course->id);
    print_media_page_tabs(get_string('headervidres', 'block_ucla_media'), $course->id);

    echo html_writer::start_tag('div', array('id' => 'vidreserves-wrapper'));

    echo $OUTPUT->heading(get_string('headervidres', 'block_ucla_media') .
            ": $course->fullname", 2, 'headingblock');

    echo html_writer::tag('p', get_string('intro', 'block_ucla_media'),
            array('id' => 'videoreserves-intro'));
    $ip = $_SERVER['REMOTE_ADDR'];
    if (is_on_campus_ip($ip) === false) {
        echo $OUTPUT->notification(get_string('videoreservesipwarning', 'block_ucla_media'));
    }
    echo html_writer::start_tag('div', array('id' => 'vidreserves-content'));
    if (!empty($videos['current'])) {
        print_video_list($videos['current'], get_string('currentvideo', 'block_ucla_media'));
    } else {
        echo html_writer::tag('span', get_string('novideo', 'block_ucla_media'));
    }
    if (!empty($videos['future'])) {
        print_video_list($videos['future'], get_string('futurevideo', 'block_ucla_media'));
    }
    if (!empty($videos['past'])) {
        print_video_list($videos['past'], get_string('pastvideo', 'block_ucla_media'));
    }
    echo html_writer::end_tag('div');

    echo html_writer::end_tag('div');
}

/**
 * Initializes all $PAGE variables.
 *
 * @param object $course
 * @param context_course $context
 * @param moodle_url $url
 * @param int $mode         Optional. Add link to index page for given mode.
 * @param string $title     Optional. Add link to breakcrumbs.
 */
function init_page($course, $context, $url, $mode = null, $title = null) {
    global $PAGE;
    $PAGE->set_url($url);

    $pagetitle = $course->shortname . ': ' . get_string('pluginname', 'block_ucla_media');

    $PAGE->set_context($context);
    $PAGE->set_title($pagetitle);

    $PAGE->set_heading($course->fullname);

    $PAGE->set_pagelayout('base');
    $PAGE->set_pagetype('course-view-' . $course->format);

    // Reset breadcrumbs and make it start with course and Media resources.
    $PAGE->navbar->ignore_active();
    $PAGE->navbar->add($course->shortname, new moodle_url('/course/view.php' ,
            array('id' => $course->id)));
    $PAGE->navbar->add(get_string('title', 'block_ucla_media'));

    if (!empty($mode)) {
        $index = '';
        $indextitle = '';
        if ($mode == MEDIA_BCAST_VIDEO || $mode == MEDIA_BCAST_AUDIO) {
            $index = '/blocks/ucla_media/bcast.php';
            $indextitle = get_string('headerbcast', 'block_ucla_media');
        } else if ($mode == MEDIA_VIDEORESERVES) {
            $index = '/blocks/ucla_media/videoreserves.php';
            $indextitle = get_string('headervidres', 'block_ucla_media');
        } else if ($mode == MEDIA_LIBRARYMUSIC) {
            $index = '/blocks/ucla_media/libreserves.php';
            $indextitle = get_string('headerlibres', 'block_ucla_media');
        }
        $indexurl = new moodle_url($index, array('courseid' => $course->id));
        $PAGE->navbar->add($indextitle, $indexurl);
    }

    if (!empty($title)) {
        $PAGE->navbar->add($title, new moodle_url($url));
    }
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
        if ($headertitle == get_string('pastvideo', 'block_ucla_media')) {
            $outputstr = $video->video_title . ' (' .
                    get_string('pastvideo_info', 'block_ucla_media',
                               userdate($video->stop_date, get_string('strftimedatefullshort'))) . ')';
        } else if ($headertitle == get_string('futurevideo', 'block_ucla_media')) {
            $outputstr = $video->video_title . ' (' .
                    get_string('futurevideo_info', 'block_ucla_media',
                               userdate($video->start_date, get_string('strftimedatefullshort'))) . ')';
        } else {
            if (!empty($video->filename)) {
                $outputstr = html_writer::link(
                        new moodle_url('/blocks/ucla_media/view.php',
                                array('id' => $video->id, 'mode' => MEDIA_VIDEORESERVES)), $video->video_title);
            } else {
                $outputstr = html_writer::link(
                        new moodle_url($video->video_url), $video->video_title);
            }
            // Append available dates to each link.
            if ($video->start_date != null && $video->stop_date != null) {
                $start = userdate($video->start_date, get_string('strftimedatefullshort'));
                $stop = userdate($video->stop_date, get_string('strftimedatefullshort'));
                $outputstr .= ' (' .
                        get_string('availability', 'block_ucla_media') .
                        $start . ' - ' . $stop . ')';
            }
        }
        $output[] = $outputstr;
    }
    echo html_writer::alist($output);

    echo html_writer::end_tag('div');
}

/**
 * Prints tabs.
 *
 * @param string $activetab
 * @param int $courseid
 */
function print_media_page_tabs($activetab, $courseid) {
    global $DB;
    $videos = $DB->get_records('ucla_bruincast', array('courseid' => $courseid));
    $count = count($videos);
    if ($count != 0) {
        $tabs[] = new tabobject(get_string('headerbcast', 'block_ucla_media'),
                        new moodle_url('/blocks/ucla_media/bcast.php',
                                array('courseid' => $courseid)),
                                    get_string('bcast_tab', 'block_ucla_media', $count));
    }
    $videos = get_video_data($courseid);
    $count = 0;
    if (!empty($videos['current'])) {
        $count = count($videos['current']);
    }
    if (!empty($videos['past'])) {
        $count = $count + count($videos['past']);
    }
    if (!empty($videos['future'])) {
        $count = $count + count($videos['future']);
    }
    if ($count != 0) {
        $tabs[] = new tabobject(get_string('headervidres', 'block_ucla_media'),
                        new moodle_url('/blocks/ucla_media/videoreserves.php',
                                array('courseid' => $courseid)),
                                    get_string('vidreserves_tab', 'block_ucla_media', $count));
    }
    $videos = $DB->get_records_sql('SELECT DISTINCT albumtitle FROM '
            . '{ucla_library_music_reserves} WHERE courseid=?', array($courseid));
    $count = count($videos);
    if ($count != 0) {
        $tabs[] = new tabobject(get_string('headerlibres', 'block_ucla_media'),
                        new moodle_url('/blocks/ucla_media/libreserves.php',
                                array('courseid' => $courseid)),
                                    get_string('libraryreserves_tab', 'block_ucla_media', $count));
    }
    // Display tabs here.
    print_tabs(array($tabs), $activetab);
}