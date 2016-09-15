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
 * Lang strings for the UCLA Media Block
 *
 * @package    block_ucla_media
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['headerbcast'] = 'Bruincast';
$string['headervidres'] = 'Video reserves';
$string['headerlibres'] = 'Digital audio reserves';
$string['intromusic'] = 'Notice: The U.S. Copyright Law (Title 17 U.S. Code) governs the making of photocopies or other reproductions of copyrighted materials. These sound files have been prepared to provide instructional support for students enrolled in UCLA classes. Copying, downloading, redistribution, modification and/or archiving of these files is prohibited.<br>
Questions? Contact <a href="mailto:music-circ@library.ucla.edu">Music Circulation.</a>';
$string['headerlibraryurl'] = 'Source URL';
$string['desclibraryurl'] = 'JSON source URL for library music reserves data';
$string['title'] = 'Media resources';
$string['intro'] = 'Please note that this media is intended for on-campus use only. Off-campus use is possible in certain circumstances, but it is not supported, dependable, or recommended. In order to view the video streams, you may need to download and install the Silverlight plugin.';
$string['availability'] = 'Available: ';
$string['back'] = 'Back to listing';
$string['currentvideo'] = 'Current videos';
$string['errorinvalidvideo'] = 'Cannot find given video.';
$string['errorpermission'] = 'You do not have permission to view this media.';
$string['eventindexviewed'] = 'Index viewed';
$string['eventvideoviewed'] = 'Video viewed';
$string['fallbackurl'] = 'Click here if experiencing video playback issues';
$string['futurevideo'] = 'Future videos';
$string['futurevideo_info'] = 'Will be available on {$a}';
$string['intro'] = 'Please note that this media is intended for on-campus use only.
    Off-campus use is possible in certain circumstances, but it is not supported, dependable, or
    recommended. In order to view the video streams, you may need to download and install the
    <a href="http://www.microsoft.com/silverlight/get-started/install/default.aspx" target="_blank">Silverlight plugin</a>.';

$string['novideo'] = 'No videos available';
$string['pastvideo'] = 'Past videos';
$string['pastvideo_info'] = 'No longer available as of {$a}';
$string['pluginname'] = 'UCLA media';
$string['sourceurl'] = 'Source URL';
$string['sourceurl_desc'] = 'TSV source URL for video reserves data';
$string['taskupdatebcast'] = 'Update Bruincast';
$string['taskupdatevidreserves'] = 'Update digital audio reserves';
$string['taskupdatelibmusic'] = 'Update library music reserves';
$string['wowzaurl'] = 'Wowza server';
$string['wowzaurl_desc'] = 'The address and port for the Wowza streaming server. Do not include a protocol prefix.';
$string['bcast_tab'] = 'Bruincast ({$a})';
$string['vidreserves_tab'] = 'Video reserves ({$a})';
$string['libraryreserves_tab'] = 'Digital audio reserves ({$a})';
$string['mediaresnotavailable'] = "There are no media resources for this course.";

// Types of bruincasts. To be removed once new web service is done: CCLE-6263.
$string['titlebcast'] = 'Bruincast';
$string['node_restricted'] = 'Private';
$string['node_open'] = 'Open';
$string['node_see_instructor'] = 'See Instructor';
$string['node_online'] = 'E-Lecture';