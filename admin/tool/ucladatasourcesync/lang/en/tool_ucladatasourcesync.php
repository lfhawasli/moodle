<?php
// This file is part of the UCLA data source sync tool for Moodle - http://moodle.org/
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
 * Strings for the UCLA data source sync scripts.
 *
 * @package    tool_ucladatasourcesync
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'UCLA data source synchronization';

// General strings.
$string['errcannotinsert'] = 'Cannot insert record: {$a}';
$string['successnotice'] = 'Inserted: {$a->inserted}, Updated: {$a->updated}, Deleted: {$a->deleted} records.';
$string['founddupentry'] = 'Found duplicate entry {$a}';

// Event messages.
$string['eventbcread'] = "Bruincast read error.";
$string['eventbcparsing'] = "Bruincast parsing data error.";
$string['eventlrread'] = "Library reserves read error.";
$string['eventlrwrite'] = "Library reserves write error.";
$string['eventlrupdate'] = "Library reserves update error.";
$string['eventvfparsing'] = "Video reserves parsing data error.";
$string['eventvfread'] = "Video reserves read error.";
$string['eventvfwrite'] = "Video reserves write error.";
$string['eventvfupdate'] = "Video reserves update error.";

// Strings for bruincast_dbsync.
// Error messages.
$string['errbcmsglocation'] = "ERROR: No location set for BruinCast data.";
$string['errbcmsgemail'] = "ERROR: No email set for BruinCast error notification.";
$string['errbcmsgquiet'] = "ERROR: Cannot access configuration option quiet_mode.";
$string['errbcinsert'] = "ERROR: Problem writing to the database.";
$string['errbcinvalidrowlen'] = "ERROR: Invalid row length in provided Bruincast data.";
// Notification messages.
$string['bcstartnoti'] = "Starting BruinCast DB update:";
$string['bcnoentries'] = 'No BruinCast entries found. Nothing changed.';
$string['bcinvalidcrosslists'] = 'Invalid crosslists: {$a}';
$string['bccrosslistmedia'] = 'Crosslisting media';
$string['bccrosslistentries'] = 'Crosslisting {$a->course1} with {$a->course2}';
$string['bcfoundupdatedentry'] = 'Already updated {$a}';

// Strings for libraryreserves_dbsync.
// Error messages.
$string['errlrmsglocation'] = "ERROR: No location set for library reserves data.";
$string['errlrfileopen'] = "ERROR: Problem accessing data URL";
$string['warninvalidfields'] = 'WARNING: Found invalid field(s) {$a->fields} in: {$a->data}';
$string['warnnonexistentsrs'] = 'WARNING: Found non-existent srs {$a->term} {$a->srs}';
$string['noticediscussionsrs'] = 'NOTICE: Found discussion srs {$a->srs}, using primary {$a->primary_srs}';
$string['noticefoundaltcourseid'] = 'NOTICE: Found courseid {$a->courseid} by using term/subject/cat_num: {$a->term}-{$a->subject_area}-{$a->cat_num}-{$a->sec_num}';
// Notification messages.
$string['lrstartnoti'] = "Starting library reserves DB update:";
$string['lrnoentries'] = 'No library reserves entries found.';

// Strings for videoreserves_dbsync.
// Error messages.
$string['errvrsourceurl'] = "ERROR: No location set for video reserve data.";
$string['errvrinvalidrowlen'] = 'ERROR: Invalid row length in provided video reserve data. Line: {$a}';
$string['errvrfileopen'] = "ERROR: Problem accessing data URL";
// Notification messages.
$string['vrstartnoti'] = "Starting video reserves DB update:";
$string['vrnoentries'] = 'No video reserve entries found.';
