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
 * @package    filter_sscwowza
 * @copyright  2019 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Filter class file.
 *
 * @package    filter_sscwowza
 * @copyright  2019 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filter_sscwowza extends moodle_text_filter {

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

        if ($CFG->filter_sscwowza_enable_mp4) {
            $search = '/\{wowza:(.*?),(.*?),(.*?)\}/';
            $newtext = preg_replace_callback($search, 'sscwowza_filter_mp4_callback', $newtext);
        }

        if (empty($CFG->filter_sscwowza_enable_mp4) || is_null($newtext) ||
                $newtext === $text) {
            // Error or not filtered.
            return $text;
        }

        // Prefix the jwplayer.
        $jwplayerpath = 'https://content.jwplatform.com/libraries/q3GUgsN9.js';
        $newtext = '<script type="text/javascript" src="' . $jwplayerpath . '"></script>'
                . $newtext;

        return $newtext;
    }

    /**
     * Returns given timecode formatted as time in seconds and HH:MM:SS.
     *
     * @param string $timecode  Expecting timecode format like: 00:01:11,736
     * @return array            Returns array of time in seconds and string.
     */
    public static function parse_timecode($timecode) {
        $parts   = explode(':', $timecode);
        $hours   = $parts[0];
        $minutes = $parts[1];
        $seconds = substr($parts[2], 0, 2);
        $timecodeinseconds = ($hours * 3600) + ($minutes * 60) + ($seconds);
        $timecodestandard  = $hours . ':' . $minutes . ':' . $seconds;
        return array($timecodeinseconds, $timecodestandard);
    }

}

/**
 * Replaces WOWZA link with media player.
 *
 * @param array $link   Consisting of type, url, and file.
 * @param boolean $autostart    Unused.
 * @return string       HTML fragment to display video player.
 */
function sscwowza_filter_mp4_callback($link, $autostart = false) {
    global $COURSE, $USER;

    // Clean url and get variables.
    $type = clean_param($link[1], PARAM_NOTAGS);
    $url  = clean_param($link[2], PARAM_NOTAGS);
    $file = clean_param($link[3], PARAM_NOTAGS);
    $app = $COURSE->id;
    // Handle VOD and Live streams.
    $timeline = '';
    $srtjs = '';
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
    $USER->ssc_video_allowed = 'true';

    // Streaming paths.
    $srtpath = 'https://' . $parseurl['host'] . '/' . $app . '/' . $srt;
    $html5path = 'https://' . $parseurl['host'] . '/' . $app . '/' . $format . $file . '/playlist.m3u8';

    $rtmppath = $url . '/' . $app;

    // Set playerid, so that we can support multiple video embeds.
    $playerid = uniqid();

    $lines = array();
    if ($srt != '') {
        // Interactive timeline.
        $srtfile = file($srtpath);
        if ($srtfile !== false) {
            foreach ($srtfile as $line) {
                $cleanline = trim($line);
                if (!is_numeric($cleanline)) {
                    array_push($lines, $cleanline);
                }
            }
        } else {
            // Cannot ready subtitle file, so ignore it.
            $srt = '';
        }
    }

    if (!empty($lines)) {
        // Build arrays for timecodes and text.
        $timecodes = array();
        $text = array();
        $index = 0;
        $paragraph = '';
        $numlines = count($lines) - 1;
        foreach ($lines as $line) {
            if (preg_match('/^[0-9]{2}:[0-9]{2}/', $line)) {
                // Found timecode.
                array_push($timecodes, $line);
            } else {
                // Else we found a start of a paragraph.
                $paragraph = $paragraph . ' ' . $line;
                
                // Check if next line is a timecode, meaning paragraph ended.
                if ($index < $numlines) {
                    $next = $lines[$index + 1];    
                } else {
                    $next = null;
                }
                if (preg_match('/^[0-9]{2}:[0-9]{2}/', $next) || is_null($next)) {
                    // Next line is timecode or reached end of file.
                    $paragraph = trim($paragraph . ' ' . $line);
                    array_push($text, $paragraph);
                    $paragraph = '';
                }
            }
            $index++;
        }

        $starts = array();
        $ends = array();
        $times = array();
        foreach ($timecodes as $timecode) {
            // Start and end times are delineated by "-->".
            $timestoparse = explode('-->', $timecode);

            // Calculate start times.
            $result = filter_sscwowza::parse_timecode($timestoparse[0]);
            array_push($starts, $result[0]);
            array_push($times, $result[1]);

            // Calculate end times.
            $result = filter_sscwowza::parse_timecode($timestoparse[1]);
            array_push($ends, $result[0]);
        }

        $index = 0;
        $timeline = '<div class="sscwowzafilter-timeline" style="width:' . $width . 'px;">';
        foreach ($text as $line) {
            if (!empty($line)) {
                $timeline .= '<a onclick="jwplayer(\'player-' . $playerid . '\').seek(' .
                        $starts[$index] . ')" href="javascript:void(0)" id="t' .
                        $starts[$index] . '">' . $times[$index] . '</a> - ' . $line . '<br/>';
            }
            $index++;
        }
        $timeline .= '</div>';

        $srtjs = ",tracks: [{
                    file: '$srtpath',
                    kind: 'captions',
                    'default': true
                }]";
    }

    // Print out the player output.
    return "
        <div id='player-$playerid'></div>
        <script type='text/javascript'>
        jwplayer('player-$playerid').setup({
            autostart: true,
            width: '100%',
            aspectratio: '3:2',
            plugins: {
                $mbrjs
                },
            playlist: [{
                sources:
                    [
                        {file: '$html5path'},
                        {file: '$rtmppath'}
                    ]
                $srtjs
                }],
            primary: 'html5'
        });
        </script>" . $timeline;
}
