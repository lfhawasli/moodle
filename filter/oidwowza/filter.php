<?php
// This file is part of the SSC WOWZA plugin for Moodle - http://moodle.org/
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
 *  WOWZA streaming media filter plugin.
 *
 *  This filter will replace any wowza links to a media file with
 *  a media plugin that plays that media inline.
 *
 * @package    filter_oidwowza
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->libdir . '/filelib.php');

/**
 * Filter class file.
 *
 * @package    filter_oidwowza
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filter_oidwowza extends moodle_text_filter {

    /**
     * Returns filtered text that may have WOWZA server links converted to
     * players.
     *
     * @param string $text
     * @param array $options    Unused.
     * @return string
     */
    public function filter($text, array $options = array()) {
        global $CFG;

        if (!is_string($text)) {
            // Non-string data can not be filtered anyway.
            return $text;
        }
        $newtext = $text;

        if ($CFG->filter_oidwowza_enable_mp4) {
            $search = '/\{wowza:(.*?),(.*?),(.*?),(.*?),(.*?)\}/';
            $newtext = preg_replace_callback($search, 'oidwowza_filter_mp4_callback', $newtext);
        }

        if (empty($CFG->filter_oidwowza_enable_mp4) || is_null($newtext) ||
                $newtext === $text) {
            // Error or not filtered.
            return $text;
        }

        // Prefix the jwplayer.
        $jwplayerpath = $CFG->wwwroot . '/filter/oidwowza/jwplayer/jwplayer.js';
        $newtext = '<script type="text/javascript" src="' . $jwplayerpath . '"></script>'
                . $newtext;

        return $newtext;
    }

}

/**
 * Replaces WOWZA link with media player.
 *
 * @param array $link   Consisting of type, url, file, width, and height.
 * @param boolean $autostart    Unused.
 * @return string       HTML fragment to display video player.
 */
function oidwowza_filter_mp4_callback($link, $autostart = false) {
    global $CFG, $COURSE, $USER;

    // Clean url and get variables.
    $type   = clean_param($link[1], PARAM_NOTAGS);
    $url    = clean_param($link[2], PARAM_NOTAGS);
    $file   = clean_param($link[3], PARAM_NOTAGS);
    $width  = clean_param($link[4], PARAM_INT);
    $height = clean_param($link[5], PARAM_INT);

    $app = $COURSE->shortname; // Need to store videos by shortname.

    // Handle VOD and Live streams.
    $timeline = '';
    if (strpos($file, '*') !== false) {
        $files = explode('*', $file);
        $file = $files[0];
        $srt = $files[1];
    } else {
        $srt = '';
    }

    $format = strtolower(substr($file, -3));
    switch ($format) {
        case 'mov':
        case 'm4v':
        case 'm4a':
            $format = 'mp4';
            break;
    }
    $format = $format . ":";
    $title = substr($file, 0, -4);
    $extension = substr($file, -3);
    $parseurl = parse_url($url);

    // Handle prefixes.
    $mbrjs = '';
    switch ($type) {
        case "mbr":
            $file = $title . '-low.' . $extension;
            $filehd = $title . '.' . $extension;
            $mbrjs = '"hd-2":{
                "file" : "' . $format . $filehd . '",
                "state" : "true"
            },';
            break;
        case "live":
            $format = "";
            $app = $app . "-live";
            break;
    }

    // Set server variables.
    $USER->oid_video_allowed = true;

    // Streaming paths.
    $srtpath = 'http://' . $parseurl['host'] . ':8080/' . $app . '/' . $srt;
    $html5path = 'http://' . $parseurl['host'] . ':' . $parseurl['port'] . '/' . $app . '/' . $format . $file . '/playlist.m3u8';

    $rtmppath = $url . '/' . $app . '/' . $format . $file;

    // Set playerid, so that we can support multiple video embeds.
    $playerid = uniqid();

    if ($srt != '') {
        // Interactive timeline.
        $srtfile = file($srtpath);
        $lines = array();
        foreach ($srtfile as $line) {
            $cleanline = trim($line);

            if (!is_numeric($cleanline)) {
                array_push($lines, $line);
            }
        }

        $timecodes = array();
        $text = array();
        $index = 0;
        $paragraph = '';

        foreach ($lines as $line) {
            $line = trim($line);
            if (preg_match('/^[0-9]{2}:[0-9]{2}/', $line)) {
                array_push($timecodes, $line);
                $index++;
            } else {
                if ($index < count($lines) - 1) {
                    $next = $lines[$index + 1];
                } else {
                    $next = '';
                }
                $index++;
                if (preg_match('/^[0-9]{2}:[0-9]{2}/', $next)) {
                    $paragraph = $paragraph . ' ' . $line;
                    array_push($text, trim($paragraph));
                    $paragraph = '';
                } else {
                    $paragraph = $paragraph . ' ' . $line;
                    if ($next == '') {
                        array_push($text, trim($paragraph));
                    }
                }
            }
        }
        $starts = array();
        $ends = array();
        $times = array();
        foreach ($timecodes as $timecode) {
            // Calculate start times.
            $parts = preg_split(':', $timecode);
            $hours = $parts[0];
            $minutes = $parts[1];
            $seconds = substr($parts[2], 0, 2);
            $timecodeinseconds = ((int) $hours * 3600) + ((int) $minutes * 60) + ((int) $seconds);
            $timecodestandard = $hours . ':' . $minutes . ':' . $seconds;
            array_push($starts, $timecodeinseconds);
            array_push($times, $timecodestandard);

            // Calculate end times.
            $end = preg_split('-->', $timecode);
            $parts = preg_split(':', $end[1]);
            $hours = $parts[0];
            $minutes = $parts[1];
            $seconds = substr($parts[2], 0, 2);
            $timecodeinseconds = ((int) $hours * 3600) + ((int) $minutes * 60) + ((int) $seconds);
            $timecodestandard = $hours . ':' . $minutes . ':' . $seconds;
            array_push($ends, $timecodeinseconds);
        }

        $index = 0;
        $timeline = '<div class="oidwowzafilter-timeline" style="width:' . $width . 'px;">';
        foreach ($text as $line) {
            $timeline .= '<a onclick="jwplayer("player-' . $playerid . '").seek(' . $starts[$index] . ')" href="javascript:void(0)" id="t' . $starts[$index] . '">' . $times[$index] . '</a> - ' . $line . '<br/>';
            $index++;
        }
        $timeline .= '</div>';
    }

    if ($srt != '') {
        $srtjs = '"captions-2":{
                                "file":"' . $srtpath . '",
                                "back": "true"
				},';
    } else {
        $srtjs = "";
    }

    // Print out the player output.
    return "
        <div id='player-$playerid'></div>
	<script type='text/javascript'>
	jwplayer('player-$playerid').setup({
		width: $width,
		height: $height,
		plugins: {
                    $srtjs
                    $mbrjs
                    },
		playlist: [{
                    sources :
                        [
                            {file: '$html5path'},
                            {file: '$rtmppath'}
                        ]
                        }],
                primary: 'html5'
		});
	</script>" . $timeline;
}
