<?php
// This file is part of Moodle - http://moodle.org/
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
 * @package    block_ucla_copyright_status
 * @copyright  2012 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Manage copyright';
$string['title'] = 'Manage copyright status';
$string['statistics'] = 'Statistics';
$string['copyrightstatus'] = 'Copyright status';
$string['copyrightstatushelp'] = 'Help with copyright status';
$string['updated_dt'] = 'Updated date';
$string['author'] = 'Author';
$string['permission_not_allow'] = 'You do not have access to this page';
$string['copyright_status'] = 'View material by copyright status';
$string['changessaved'] = 'Sucessfully saved changes.';
$string['no_files'] = 'No materials found with this copyright status';
$string['instruction_text1'] = 'Be sure to click the "Save changes" button at the bottom of the page when your changes are complete';
$string['javascriptdisabled'] = 'Javascript is disabled on your browser. Please enable it to ensure the page function correctly.';
$string['withselected'] = ' With selected: ';

// Strings for alert notice.
$string['alert_msg'] = 'Your site has {$a} piece(s) of content without a copyright status assigned. Would you like to assign copyright status now?';
$string['alert_yes'] = 'Yes';
$string['alert_no'] = 'No, don\'t ask again';
$string['alert_later'] = 'Ask me later';
$string['alert_no_redirect'] = 'You will no longer be prompted to assign copyright status. ' .
        'Use the Manage copyright status link in the Site menu block at the left or the tool in ' .
        'the Control Panel to assign copyright status later.';
$string['alert_later_redirect'] = 'Assign copyright status reminder set.';

// Aria strings.
$string['aria_copyright_badge'] = ' items without copyright status.';

// Strings for events.
$string['eventcopyright_status_updated'] = 'Updated copyright status';

// Admin panel strings.
$string['managecopyright'] = 'Manage copyright';
