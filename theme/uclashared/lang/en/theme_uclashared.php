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

/**
 * Language file.
 *
 * @package    theme_uclashared
 * @copyright  UC Regents 2017
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$string['activitiesresources'] = 'Activities/Resources';
$string['addsections'] = 'Add section';
$string['pluginname'] = 'UCLA theme';
$string['region-side-pre'] = 'Right';   // Boost no longer has left region.
$string['choosereadme'] = 'The theme from University of California, Los Angeles.';

// CCLE-7628 - Add hidden and empty text to sections.
$string['emptysectiontext'] = ' (empty)';
$string['hiddensectiontext'] = ' (hidden)';

// CCLE-7346 - Add More section to nav drawer.
$string['moresection'] = 'More...';

$string['control_panel'] = 'Control Panel';

$string['help_n_feedback'] = 'Need Help';
$string['helprequest'] = 'Submit a help request';
$string['selfhelp'] = 'View self help articles';
$string['tipsupdates'] = 'Read tips & updates';
$string['requestsite'] = 'Request a site';
$string['sitedirectory'] = 'Site Directory';

$string['copyright_information'] = '&copy; {$a} UC Regents';

// Settings titles, descriptions and defaults.
$string['setting_title_footer_links'] = 'Footer links';
$string['setting_desc_footer_links'] = 'This text will be displayed to the right of the set of links in the footer. A separator will be automatically added.';
$string['setting_default_footer_links'] = '';

$string['setting_title_system_name'] = 'System name';
$string['setting_desc_system_name'] = 'The system name that will be displayed in the banner, below the logo.';
$string['setting_default_system_name'] = 'Shared System';

$string['setting_title_system_link'] = 'System link';
$string['setting_desc_system_link'] = 'The link associated with the system name.';
$string['setting_default_system_link'] = 'https://ccle.ucla.edu/course/view/aboutccle?sectionid=924';

// CCLE-6512 - Profile Course details doesn't match My page Class sites.
$string['setting_title_alternative_sharedsystem_name'] = 'Alternative Shared System name';
$string['setting_desc_alternative_sharedsystem_name'] = 'This is an alternative shared system.';
$string['setting_default_alternative_sharedsystem_name'] = 'Social Sciences';

$string['setting_title_alternative_sharedsystem_link'] = 'Alternative Shared System link';
$string['setting_desc_alternative_sharedsystem_link'] = 'The link associated with the alternative shared system name.';
$string['setting_default_alternative_sharedsystem_link'] = 'https://moodle2.sscnet.ucla.edu';

$string['setting_title_running_environment'] = 'Server environment';
$string['setting_desc_running_environment'] = 'This option will determine the color of the header to make it easier distinguish which server environment you are on. Default should be \'Production\'.';
$string['setting_default_running_environment'] = 'prod';
$string['watermark'] = "Owen Weitzel";
$string['env_prod'] = 'Production';
$string['env_stage'] = 'Stage';
$string['env_test'] = 'Test';
$string['env_dev'] = 'Development';

// CCLE-2493 - UCLA Links / CCLE-2827 - Copyright Notice in Footer.
$string['copyright'] = 'CCLE copyright information';
$string['privacy'] = 'CCLE privacy policy';
$string['links'] = 'Useful links for UCLA class sites';
$string['error'] = 'Error';
$string['page_notfound'] = 'The page you requested does not exist';

// Caches.
$string['cachedef_frontpageimage'] = 'Frontpage image';

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
$string['foolin_disability'] = 'http://www.cae.ucla.edu/';
$string['foodis_caps'] = 'Couns/PsychSvc (CAPS)';
$string['foolin_caps'] = 'http://www.counseling.ucla.edu/';
