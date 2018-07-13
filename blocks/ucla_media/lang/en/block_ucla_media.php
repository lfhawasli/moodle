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
$string['availability'] = 'Available: ';
$string['back'] = 'Back to listing';
$string['currentvideo'] = 'Current videos';
$string['errorinvalidvideo'] = 'Cannot find given video.';
$string['errorpermission'] = 'You do not have permission to view this media.';
$string['eventindexviewed'] = 'Index viewed';
$string['eventvideoviewed'] = 'Video viewed';
$string['eventbruincastindexviewed'] = 'BruinCast index viewed';
$string['eventbruincastviewed'] = 'BruinCast viewed';
$string['eventlibreserveindexviewed'] = 'Digital audio reserve index viewed';
$string['eventlibreserveviewed'] = 'Digital audio reserve viewed';
$string['eventvidreserveindexviewed'] = 'Video reserve index viewed';
$string['eventvidreserveviewed'] = 'Video reserve viewed';
$string['fallbackurl'] = 'Click here if experiencing video playback issues';
$string['futurevideo'] = 'Future videos';
$string['futurevideo_info'] = 'Will be available on {$a}';
$string['intro'] = 'Please note that this media is intended for on-campus use only.
    Off-campus use is possible in certain circumstances, but it is not supported, dependable, or
    recommended.';

$string['novideo'] = 'No videos available';
$string['pastvideo'] = 'Past videos';
$string['pastvideo_info'] = 'No longer available as of {$a}';
$string['pluginname'] = 'UCLA media';
$string['sourceurl'] = 'Source URL';
$string['sourceurl_desc'] = 'TSV source URL for video reserves data';
$string['taskupdatebcast'] = 'Update Bruincast';
$string['taskupdatevidreserves'] = 'Update video reserves';
$string['taskupdatelibmusic'] = 'Update digital audio reserves';
$string['wowzaurl'] = 'Wowza server';
$string['wowzaurl_desc'] = 'The address and port for the Wowza streaming server. Do not include a protocol prefix.';
$string['bcast_tab'] = 'Bruincast ({$a})';
$string['vidreserves_tab'] = 'Video reserves ({$a})';
$string['libraryreserves_tab'] = 'Digital audio reserves ({$a})';
$string['bcnotavailable'] = "There are no BruinCast recordings for this course.";
$string['vresourcesnotavailable'] = "There are no Video reserves for this course.";
$string['mlreservesnotavailable'] = "There are no Digital audio reserves for this course.";

$string['titlebcast'] = 'Bruincast';
$string['headerbruincastloginurl'] = 'Bruincast Login Source URL';
$string['descbruincastloginurl'] = 'Bruincast login URL to obtain a session from Bruincast datasource';
$string['headerbruincastuser'] = 'Bruincast access Username';
$string['descbruincastuser'] = 'Bruincast access Username to obtain data';
$string['headerbruincastpass'] = 'Bruincast access Password';
$string['descbruincastpass'] = 'Bruincast access Password to obtain data';
$string['headerbruincasturl'] = 'Bruincast Datasource URL';
$string['headerbruincasthttpuser'] = 'Bruincast HTTP access Username';
$string['descbruincasthttpuser'] = 'Bruincast HTTP access Username to obtain a HTTP session';
$string['headerbruincasthttppass'] = 'Bruincast HTTP access Password';
$string['descbruincasthttppass'] = 'Bruincast HTTP access Password to obtain a HTTP session';
$string['headerbruincasturl'] = 'Bruincast Datasource URL';
$string['descbruincasturl'] = 'Bruincast URL to obtain data from Bruincast datasource API';
$string['headerbruincastwowza'] = 'Bruincast Wowza URL';
$string['descbruincastwowza'] = 'Server location for Bruincast Wowza.';
$string['bruincastnotice'] = 'Bruincast notice';
$string['bruincastnoticedesc'] = 'If set, will display notice on all Bruincast pages.';
$string['bruincastcrosslists'] = 'Bruincast crosslists';
$string['bruincastcrosslistsdesc'] = 'Each line is a pairing of shortnames that should share BruinCast content in the following format: 17F-CHEM153A-2=17F-CHEM153A-3';
$string['erraddingindex'] = 'Error adding unique_recording index, truncating table. Run update script after upgrading.';

// Strings for Bruincast display.
$string['bccoursedate'] = 'Course date';
$string['bcmedia'] = 'Media';
$string['bctitle'] = 'Title';
$string['bccomments'] = 'Comments';
$string['bcvideo'] = 'Video';
$string['bcaudio'] = 'Audio';
$string['bchelp'] = 'Lecture recordings may take up to 24 hours before they are '
        . 'available. For help regarding BruinCast content please read our '
        . '<a href="https://docs.ccle.ucla.edu/w/BruinCast">help document</a>.';

$string['videoreservesipwarning'] = 'You are accessing this content from off-campus. If the content does not load, you will need to use the UCLA VPN to obtain an on-campus internet address.</br>
BOL VPN instructions: <a target="_blank" href="https://www.it.ucla.edu/bol/services/virtual-private-network-vpn-clients">https://www.it.ucla.edu/bol/services/virtual-private-network-vpn-clients</a>.';

// Strings for Media resources requests.
$string['mediarequestdesc'] = 'Display media resource request links';
$string['mrrequest'] = 'Request media resources';
$string['bcrequest'] = 'Request BruinCast';
$string['vrrequest'] = 'Request Video reserves';
$string['mlrequest'] = 'Request Digital audio reserves';