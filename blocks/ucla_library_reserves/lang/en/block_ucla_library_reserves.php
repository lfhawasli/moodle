<?php
// This file is part of the UCLA Library Reserves block for Moodle - http://moodle.org/
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
 * Language file.
 *
 * @package    block_ucla_library_reserves
 * @copyright  2019 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['cachedef_hostcourseurl'] = 'Request cache that stores the url for the iframe in course reserves.';

$string['desclibraryreservesurl'] = 'Base URL for library reserves web service';
$string['headerlibraryreservesurl'] = 'Source URL';
$string['pluginname'] = 'UCLA library reserves';
$string['taskupdate'] = 'Updates library reserves';
$string['title'] = 'Library resources';

$string['researchguide'] = 'Research guide';
$string['coursereserves'] = 'Course reserves';
$string['iframecoursereserves'] = 'Course reserves for {$a}';
$string['iframeresearchguide'] = 'Research guide for {$a}';
// Event names for logging.
$string['eventresearchguideindexviewed'] = 'Research guide index viewed';
$string['eventcoursereservesindexviewed'] = 'Course reserves index viewed';
// Library reserves LTI tool name.
$string['ltinamelibraryreserves'] = 'Tool name';
$string['ltinamelibraryreserveshelp'] = 'The tool name is used to identify the tool provider within Moodle. The name entered will be visible to teachers when adding external tools within courses.';
$string['ltinamelibraryreservesdefault'] = 'UCLA_library_reserves';
// Library reserves LTI tool URL.
$string['ltiURLlibraryreserves'] = 'Tool URL';
$string['ltiURLlibraryreserveshelp'] = 'The tool URL is used to match tool URLs to the correct tool configuration.';
$string['ltiURLlibraryreservesdefault'] = 'https://ucla.libapps.com/libapps/lti_launch_automagic.php?id=10114';
// Library reserves LTI consumer key.
$string['lticonsumerkeylibraryreserves'] = 'Consumer key';
$string['lticonsumerkeylibraryreserveshelp'] = 'The consumer key can be thought of as a username used to authenticate access to the tool.

It can be used by the tool provider to uniquely identify the Moodle site from which users launch into the tool.';
$string['lticonsumerkeylibraryreservesdefault'] = 'ucla.libapps.com';
// Library reserves LTI shared secret.
$string['ltisecretlibraryreserves'] = 'Shared secret';
$string['ltisecretlibraryreserveshelp'] = 'The shared secret can be thought of as a password used to authenticate access to the tool. 

It should be provided along with the consumer key from the tool provider.';

// Library resources feedback.
$string['libraryfeedback'] = 'Library resources feedback';

// Course reserves request reserves.
$string['courseresnotavailable'] = "There are no library reserves for this course.";
$string['lcrequest'] = 'Request library reserves';
$string['nopermissionmsg'] = 'You do not have permission to access this tab.';
// Research guide explanations.
$string['researchguidegeneral'] = 'Research guides compile useful databases, digital library collections, and research strategies.';
$string['reserachguidecrosslistedcourse'] = 'Cross-listed courses are offered by more than one department. Check out the tabs below for resources related to each cross-listed department.';
