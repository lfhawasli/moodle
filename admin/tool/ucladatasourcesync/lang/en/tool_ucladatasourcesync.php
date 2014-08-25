<?php
/**
* Strings for the bruincast, libraryreserves, and videofurnace _dbsync scripts.
*
* See CCLE-2314 for details.
**/

$string['pluginname'] = 'UCLA data source synchronization';

/** General strings **/
$string['errcannotinsert'] = 'Cannot insert record: {$a}';

/** Strings for bruincast_dbsync **/ 
// Error messages
$string['errbcmsglocation'] = "ERROR: No location set for bruincast data.";
$string['errbcmsgemail'] = "ERROR: No email set for bruincast error notification.";
$string['errbcmsgquiet'] = "ERROR: Cannot access configuration option quiet_mode.";
$string['errbcinsert'] = "ERROR: Problem writing to the database.";
$string['errbcinvalidrowlen'] = "ERROR: Invalid row length in provided bruincast data.";

// Notification messages
$string['bcstartnoti'] = "Starting bruincast DB update:";
$string['bcsuccessnoti'] = '... {$a} records successfully inserted.';
$string['bcnoentries'] = 'No Bruincast entries found.';

/** Strings for libraryreserves_dbsync **/
// Error messages
$string['errlrmsglocation'] = "ERROR: No location set for library reserves data.";
$string['errinvalidrowlen'] = 'ERROR: Invalid row length in line {$a}.';
$string['errlrfileopen'] = "ERROR: Problem accessing data URL";;
$string['warninvalidfields'] = 'WARNING: Found invalid field(s) {$a->fields} in parsed line {$a->line_num}: {$a->data}';
$string['warnnonexistentsrs'] = 'WARNING: Found non-existent srs {$a->term} {$a->srs}';
$string['noticediscussionsrs'] = 'NOTICE: Found discussion srs {$a->srs}, using primary {$a->primary_srs}';
$string['noticefoundaltcourseid'] = 'NOTICE: Found courseid {$a->courseid} by using term/subject/cat_num: {$a->term}-{$a->subject_area}-{$a->cat_num}-{$a->sec_num}';

//Notification messages
$string['lrstartnoti'] = "Starting library reserves DB update:";
$string['lrsuccessnoti'] = '{$a} records successfully inserted.';
$string['lrnoentries'] = 'No library reserve entries found.';

/** Strings for videofurnace_dbsync **/
// Error messages
$string['errvfmsglocation'] = "ERROR: No location set for video furnace data.";
$string['errvfinvalidrowlen'] = "ERROR: Invalid row length in provided video furnace data.";
$string['errvffileopen'] = "ERROR: Problem accessing data URL";
$string['errinvalidtitle'] = 'ERROR: Title has invalid characters in parsed line {$a->line_num}: {$a->data}';

//Notification messages
$string['vfstartnoti'] = "Starting video furnace DB update:";
$string['vfsuccessnoti'] = "records successfully inserted.";

// Event messages.
$string['eventbcread'] = "Bruincast read error.";
$string['eventbcparsing'] = "Bruincast parsing data error.";
$string['eventvfparsing'] = "Video furnace parsing data error.";
$string['eventvfread'] = "Video furnace read error.";
$string['eventvfwrite'] = "Video furnace write error.";
$string['eventvfupdate'] = "Video furnace update error.";
$string['eventlrread'] = "Library reserves read error.";
$string['eventlrwrite'] = "Library reserves write error.";
$string['eventlrupdate'] = "Library reserves update error.";
// EOF
