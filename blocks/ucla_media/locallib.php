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
    // duplicate videos are displayed for crosslisted courses if they have the 
    // same term.
    $videos = $DB->get_records_select('ucla_video_reserves',
            "courseid = ? GROUP BY video_title, term", array($courseid));

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
    global $USER, $PAGE;

    echo html_writer::tag('h3', $headertitle);
    echo html_writer::start_tag('div');
    
    $table = new html_table();

    // Video reserves table setup.
    if ($headertitle === get_string('currentvideo', 'block_ucla_media')) {
        $table->head = array(get_string('vrtitle', 'block_ucla_media'), get_string('vrlinks', 'block_ucla_media'), 
            get_string('vravailability', 'block_ucla_media'));
        $table->size = array('25%', '50%', '25%');
    } else {
        $table->size = array('50%', '50%');
        if ($headertitle === get_string('pastvideo', 'block_ucla_media')) {
            $table->head = array(get_string('vrtitle', 'block_ucla_media'), 
                    get_string('vrnolongeravailable', 'block_ucla_media'));
        } else {
            $table->head = array(get_string('vrtitle', 'block_ucla_media'), 
                    get_string('vrwillbeavailable', 'block_ucla_media'));
        }
    }

    $textplay = get_string('bcmediaplay', 'block_ucla_media');
    $textresume = get_string('bcmediaresume', 'block_ucla_media');
    $timeformat = get_string('strftimedatefullshort', 'tool_ucladatasourcesync');
    foreach ($videolist as $video) {
        $titlecell = $video->video_title;
        
        $mediacell = '';
        if ($headertitle === get_string('currentvideo', 'block_ucla_media')) {
            $start = userdate($video->start_date, $timeformat);
            $stop = userdate($video->stop_date, $timeformat);
            $availcell = $start . ' - ' . $stop;

            $buttontextplay = '<button type="button" class="btn btn-primary">' .
                    '<i class="fa fa-video-camera" aria-hidden="true"></i> ' .
                    $textplay . '</button>';
            $videolinkplay = html_writer::link(new moodle_url('/blocks/ucla_media/view.php#top',
                    array('mode' => MEDIA_VIDEORESERVES, 'id' => $video->id)), 
                    $buttontextplay);
            $mediacell .= $videolinkplay . ' ';

            $jwtimestamp = get_user_preferences('jwtimestamp_'.strtoupper($video->video_title), null, $USER);
            if ($jwtimestamp !== null) {
                if ($jwtimestamp === 'FINISHED') {
                    $buttontextresume = '<i class="fa fa-check-circle" aria-hidden="true"></i> '.
                            get_string('bcmediafinished', 'block_ucla_media');
                    $jwtimestamp = 0;
                } else {
                    $buttontextresume = $textresume . gmdate('H:i:s', $jwtimestamp);
                }
                $buttontextresume = '<button type="button" class="btn btn-default">' .
                        $buttontextresume . '</button>';
                $videolinkresume = html_writer::link(new moodle_url('/blocks/ucla_media/view.php#top',
                        array('mode' => MEDIA_VIDEORESERVES, 'id' => $video->id,
                        'offset' =>  $jwtimestamp)), $buttontextresume);
                $mediacell .= $videolinkresume;
            }

            $cells = array($titlecell, $mediacell, $availcell); 
        } else {
            if ($headertitle === get_string('pastvideo', 'block_ucla_media')) {
                $availcell = userdate($video->stop_date, $timeformat);
            } else {
                $availcell = userdate($video->start_date, $timeformat);
            }
            $cells = array($titlecell, $availcell); 
        }

        $row = new html_table_row($cells);
        $table->data[] = $row;
    }
    echo html_writer::table($table);
    echo html_writer::end_div('div');
}

/**
 * Prints tabs.
 *
 * @param string $activetab
 * @param int $courseid
 */
function print_media_page_tabs($activetab, $courseid) {
    global $DB;
    $tabs = [];
    $can_request = can_request_media($courseid);
    $videos = $DB->count_records('ucla_bruincast', array('courseid' => $courseid));
    $xvideos = $DB->count_records('ucla_bruincast_crosslist', array('courseid' => $courseid));
    if ($videos != 0 || $xvideos != 0 || $can_request) {
        $tabs[] = new tabobject(get_string('headerbcast', 'block_ucla_media'),
                        new moodle_url('/blocks/ucla_media/bcast.php',
                                array('courseid' => $courseid)),
                                    get_string('bcast_tab', 'block_ucla_media', $videos + $xvideos));
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
    if ($count != 0 || $can_request) {
        $tabs[] = new tabobject(get_string('headervidres', 'block_ucla_media'),
                        new moodle_url('/blocks/ucla_media/videoreserves.php',
                                array('courseid' => $courseid)),
                                    get_string('vidreserves_tab', 'block_ucla_media', $count));
    }
    $videos = $DB->get_records_sql('SELECT DISTINCT albumtitle FROM '
            . '{ucla_library_music_reserves} WHERE courseid=?', array($courseid));
    $count = count($videos);
    if ($count != 0 || $can_request) {
        $tabs[] = new tabobject(get_string('headerlibres', 'block_ucla_media'),
                        new moodle_url('/blocks/ucla_media/libreserves.php',
                                array('courseid' => $courseid)),
                                    get_string('libraryreserves_tab', 'block_ucla_media', $count));
    }
    // Commenting out for CCLE-8102 - Turn off current Kaltura Media Gallery.
    // Change when issues with the Kaltura Media Gallery are resolved with:
    // CCLE-8101 - Create new Kaltura Media Gallery or
    // CCLE-8100 - Kaltura upload needs Auto Publish.
//    $modinfo = get_fast_modinfo($courseid);
//    if (!empty($modinfo->get_instances_of('kalvidres'))) {
//        $tabs[] = new tabobject(get_string('title','block_ucla_media'),
//                    new moodle_url('/blocks/ucla_media/kalvidres.php',
//                        array('courseid' => $courseid)),
//                            get_string('kalvidres_tab', 'block_ucla_media', $count));
//    }
    // Display tabs here.
    print_tabs(array($tabs), $activetab);
}

/**
 * Determines if course can request media resources.
 *
 * @param int $courseid
 * @return bool
 */
function can_request_media(int $courseid) {
    global $CFG;

    // Config setting to show media resource requests.
    $setting = get_config('block_ucla_media', 'media_resource_requests');
    if (!$setting) {
        return false;
    }
    // User has instructor privileges. 
    $context = context_course::instance($courseid, MUST_EXIST);
    $isinstructor = has_capability('format/ucla:viewadminpanel', $context);
    if (!$isinstructor) {
        return false;
    }
    // Course has a srs number.
    $courseinfo = ucla_get_course_info($courseid);
    $hassrs = !empty($courseinfo[0]->srs);
    if (!$hassrs) {
        return false;
    }
    // Course is for a current or future term.
    $courseterm = $courseinfo ? $courseinfo[0]->term : false;
    if (term_cmp_fn($courseterm, $CFG->currentterm) < 0) {
        return false;
    }

    return true;
}

/** 
 * Returns crosslisted courses with BruinCast content for a particular course.
 * 
 * @param int $courseid
 * @return array
 */
function get_bccrosslisted_courses($courseid) {
    global $DB;
    $sql = 'SELECT DISTINCT bc.courseid
                       FROM {ucla_bruincast} AS bc, {ucla_bruincast_crosslist} AS xlist 
                      WHERE bc.id = xlist.contentid 
                        AND xlist.courseid = :courseid';
    return $DB->get_records_sql($sql, array('courseid'=>$courseid));
}

/**
 * Deletes media timestamps from user preferences.
 * 
 * @param array $prefnames  Array of 'jwtimestamp_[courseid if bruincast]'
 *                          strings to pre-pend to filenames.
 * @param array $media      Array of media filenames to be deleted.
 */
function delete_timestamps($prefnames, $media) {
    global $DB;

    foreach ($media as $filename) {
        if (empty($filename)) {
            continue;
        }

        foreach ($prefnames as $prefname) {
            $records = $DB->get_records('user_preferences', 
                    array('name' => $prefname.'_'.$filename), '', 'userid');
            foreach ($records as $record) {
                unset_user_preference($prefname.'_'.$filename, $record->userid);
            }
        }
    }
}