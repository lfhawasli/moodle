<?php
// This file is part of the UCLA theme plugin for Moodle - http://moodle.org/
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

$string['pluginname'] = 'UCLA theme';
$string['region-side-post'] = 'Right';
$string['region-side-pre'] = 'Left';
$string['choosereadme'] = 'The theme from University of California, Los Angeles.';

// The footer links.
$string['foodis_contact_ccle'] = 'Contact';
$string['foolin_contact_ccle'] = 'https://ccle.ucla.edu/course/view/aboutccle?sectionid=926';

$string['foodis_about_ccle'] = 'About';
$string['foolin_about_ccle'] = 'https://ccle.ucla.edu/course/view/aboutccle?section=0';

$string['foodis_privacy'] = 'Privacy';
$string['foolin_privacy'] = $CFG->wwwroot . '/theme/uclashared/view.php?page=privacy';

$string['foodis_copyright'] = 'Copyright';
$string['foolin_copyright'] = $CFG->wwwroot . '/theme/uclashared/view.php?page=copyright';

$string['foodis_uclalinks'] = 'UCLA links';
$string['foolin_uclalinks'] = 'https://ccle.ucla.edu/course/view/aboutccle?sectionid=6996';

$string['foodis_school'] = 'UCLA';
$string['foolin_school'] = 'http://www.ucla.edu/';

$string['foodis_registrar'] = 'Registrar';
$string['foolin_registrar'] = 'http://www.registrar.ucla.edu/';

$string['foodis_myucla'] = 'MyUCLA';
$string['foolin_myucla'] = 'http://my.ucla.edu/';

$string['foodis_disability'] = 'Disability';
$string['foolin_disability'] = 'http://www.osd.ucla.edu/';

$string['control_panel'] = 'Control Panel';

$string['help_n_feedback'] = 'Help & Feedback';

$string['copyright_information'] = '&copy; {$a} UC Regents';

$string['separator__'] = ' | ';

$string['loginas_as'] = ' as ';

// Settings titles, descriptions and defaults.
$string['setting_title_footer_links'] = 'Footer links';
$string['setting_desc_footer_links'] = 'This text will be displayed to the right of the set of links in the footer. A separator will be automatically added.';
$string['setting_default_footer_links'] = '';

$string['setting_title_logo_sub_text'] = 'Shared server divisions';
$string['setting_desc_logo_sub_text'] = 'Divisions that are displayed in the front layout.';
$string['setting_default_logo_sub_text'] =
'<ul>
    <li><span>Arts & Architecture</span></li>
    <li><span>Chemistry & Biochemistry</span></li>
    <li><span>Computer Science</span></li>
    <li><span>Dentistry</span></li>
    <li><span>Education & Information Studies</span></li>
    <li><span>Engineering</span></li>
    <li><span>Human Genetics</span></li>
    <li><span>Humanities</span></li>
    <li><span>Life Sciences</span></li>
    <li><span>Management</span></li>
    <li><span>Nursing</span></li>
    <li><span>Physical Sciences</span></li>
    <li><span>Physics & Astronomy</span></li>
    <li><span>Public Affairs</span></li>
    <li><span>Public Health</span></li>
</ul>';

$string['setting_title_system_name'] = 'System name';
$string['setting_desc_system_name'] = 'The system name that will be displayed in the banner, below the logo.';
$string['setting_default_system_name'] = 'Shared system';

$string['setting_title_system_link'] = 'System link';
$string['setting_desc_system_link'] = 'The link associated witht he system name.';
$string['setting_default_system_link'] = 'https://ccle.ucla.edu/course/view/aboutccle?sectionid=924';

$string['setting_title_disable_post_blocks'] = 'Disable blocks on right';
$string['setting_desc_disable_post_blocks'] = 'Disable courses from adding blocks onto the right side of the course page. The site page will still have blocks on the right.';

$string['setting_title_running_environment'] = 'Server environment';
$string['setting_desc_running_environment'] = 'This option will determine the color of the header to make it easier distinguish which server environment you are on. Default should be \'Production\'.';
$string['setting_default_running_environment'] = 'prod';
$string['env_prod'] = 'Production';
$string['env_stage'] = 'Stage';
$string['env_test'] = 'Test';
$string['env_dev'] = 'Development';

// CCLE-2862 - Main_site_logo_image_needs_alt_altribute.
$string['UCLA_CCLE_text'] = 'UCLA CCLE Common Collaboration and Learning Environment';

// CCLE-2493 - UCLA Links / CCLE-2827 - Copyright Notice in Footer.
$string['copyright'] = 'CCLE copyright information';
$string['privacy'] = 'CCLE privacy policy';
$string['links'] = 'Useful links for UCLA class sites';
$string['error'] = 'Error';
$string['page_notfound'] = 'The page you requested does not exist';

// CCLE-4445 - Allow calendar to be synchronized with Google Calendar.
$string['calsyncnotice'] = 'Syncing with external calendar applications is not necessarily done in real time and may depend on the calendar application you use.';

// Dropdown menu links
$string['dropdownsubmit'] = 'Submit a help request';
$string['dropdownview'] = 'View self help articles';
$string['dropdownread'] = 'Read release notes';
