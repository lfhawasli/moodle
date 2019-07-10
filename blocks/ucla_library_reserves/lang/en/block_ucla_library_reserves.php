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

$string['desclibraryreservesurl'] = 'Base URL for library reserves web service';
$string['headerlibraryreservesurl'] = 'Source URL';
$string['pluginname'] = 'UCLA library reserves';
$string['taskupdate'] = 'Updates library reserves';
$string['title'] = 'Library reserves';

$string['researchguide'] = 'Research guide';
$string['coursereserves'] = 'Course reserves';
// Library reserves LTI tool name.
$string['ltinamelibraryreserves'] = 'Tool name';
$string['ltinamelibraryreserveshelp'] = 'The tool name is used to identify the tool provider within Moodle. The name entered will be visible to teachers when adding external tools within courses.';
$string['ltinamelibraryreservesdefault'] = 'UCLA_library_reserves';
// Library reserves LTI tool URL.
$string['ltiURLlibraryreserves'] = 'Tool URL';
$string['ltiURLlibraryreserveshelp'] = 'The tool URL is used to match tool URLs to the correct tool configuration. Prefixing the URL with http(s) is optional.

Additionally, the base URL is used as the tool URL if a tool URL is not specified in the external tool instance.

For example, a base URL of *tool.com* would match the following:

* tool.com
* tool.com/quizzes
* tool.com/quizzes/quiz.php?id=10
* www.tool.com/quizzes

A base URL of *www.tool.com/quizzes* would match the following:

* www.tool.com/quizzes
* tool.com/quizzes
* tool.com/quizzes/take.php?id=10

A base URL of *quiz.tool.com* would match the following:

* quiz.tool.com
* quiz.tool.com/take.php?id=10

If two different tool configurations are for the same domain, the most specific match will be used.

You can also insert a cartridge URL if you have one and the details for the tool will be automatically filled.';
$string['ltiURLlibraryreservesdefault'] = 'https://ucla.libapps.com/libapps/lti_launch_automagic.php?id=10114';
// Library reserves LTI consumer key.
$string['lticonsumerkeylibraryreserves'] = 'Consumer key';
$string['lticonsumerkeylibraryreserveshelp'] = 'For pre-configured tools, it is not necessary to enter a shared secret here, as the shared secret will be
provided as part of the configuration process.

This field should be entered if creating a link to a tool provider which is not already configured.
If the tool provider is to be used more than once in this course, adding a course tool configuration is a good idea.

The shared secret can be thought of as a password used to authenticate access to the tool. It should be provided
along with the consumer key from the tool provider.

Tools which do not require secure communication from Moodle and do not provide additional services (such as grade reporting)
may not require a shared secret.';
$string['lticonsumerkeylibraryreservesdefault'] = 'ucla.libapps.com';
// Library reserves LTI shared secret.
$string['ltisecretlibraryreserves'] = 'Shared secret';
$string['ltisecretlibraryreserveshelp'] = 'The shared secret can be thought of as a password used to authenticate access to the tool. It should be provided
along with the consumer key from the tool provider.

Tools which do not require secure communication from Moodle and do not provide additional services (such as grade reporting)
may not require a shared secret.';
// Costumized messages for missing parameters.
$string['missingtoolname'] = 'Tool name for library reserve LTI is not set.';
$string['missingtoolURL'] = 'Tool URL for library reserve LTI is not set.';
$string['missingconsumerkey'] = 'Consumer key for library reserve LTI is not set.';
$string['missingsecret'] = 'Shared secret for library reserve LTI is not set.';
