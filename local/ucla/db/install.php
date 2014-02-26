<?php
// This file is part of the UCLA local plugin for Moodle - http://moodle.org/
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

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/../lib.php');

/**
 * Runs extra commands when installing.
 */
function xmldb_local_ucla_install() {
    global $CFG, $DB;

    require_once($CFG->libdir . '/licenselib.php');

    // Add/set default license.
    $license = new stdClass();
    $license->shortname = 'tbd';
    $license->fullname = 'Copyright status not yet identified';
    $license->source = null;
    $license->enabled = true;
    $license->version = '2012060400';
    license_manager::add($license);
    license_manager::enable($license->shortname);
    $default = get_config('', 'sitedefaultlicense');
    if (empty($default)) {
        set_config('sitedefaultlicense', 'tbd');
    }

    // Disable existing licenses.
    license_manager::disable('allrightsreserved');
    license_manager::disable('cc');
    license_manager::disable('cc-nc');
    license_manager::disable('cc-nc-nd');
    license_manager::disable('cc-nc-sa');
    license_manager::disable('cc-nd');
    license_manager::disable('cc-sa');
    license_manager::disable('public');
    license_manager::disable('unknown');

    // Add new licenses.
    $license->shortname = 'iown';
    $license->fullname = 'I own the copyright';
    $license->source = null;
    $license->enabled = true;
    $license->version = '2012032200';
    license_manager::add($license);
    license_manager::enable($license->shortname);

    $license->shortname = 'ucown';
    $license->fullname = 'The UC Regents own the copyright';
    $license->source = null;
    $license->enabled = true;
    $license->version = '2012032200';
    license_manager::add($license);
    license_manager::enable($license->shortname);

    $license->shortname = 'lib';
    $license->fullname = 'Item is licensed by the UCLA Library';
    $license->source = null;
    $license->enabled = true;
    $license->version = '2012032200';
    license_manager::add($license);
    license_manager::enable($license->shortname);

    $license->shortname = 'public1';
    $license->fullname = 'Item is in the public domain';
    $license->source = 'http://creativecommons.org/licenses/publicdomain/';
    $license->enabled = true;
    $license->version = '2012032200';
    license_manager::add($license);
    license_manager::enable($license->shortname);

    $license->shortname = 'cc1';
    $license->fullname = 'Item is available for this use via Creative Commons license';
    $license->source = 'http://creativecommons.org/licenses/by/3.0/';
    $license->enabled = true;
    $license->version = '2012032200';
    license_manager::add($license);
    license_manager::enable($license->shortname);

    $license->shortname = 'obtained';
    $license->fullname = 'I have obtained written permission from the copyright holder';
    $license->source = null;
    $license->enabled = true;
    $license->version = '2012032200';
    license_manager::add($license);
    license_manager::enable($license->shortname);

    $license->shortname = 'fairuse';
    $license->fullname = 'I am using this item under fair use';
    $license->source = null;
    $license->enabled = true;
    $license->version = '2012032200';
    license_manager::add($license);
    license_manager::enable($license->shortname);

    // Setup the divisions.
    $divisions = array();
    $divisions[] = array('code' => 'AA', 'fullname' => 'ARTS AND ARCHITECTURE');
    $divisions[] = array('code' => 'BB', 'fullname' => 'BASIC BIOMEDICAL SCIENCES');
    $divisions[] = array('code' => 'CC', 'fullname' => 'CROSS-CAREER');
    $divisions[] = array('code' => 'DN', 'fullname' => 'DENTISTRY');
    $divisions[] = array('code' => 'EI', 'fullname' => 'EDUCATION AND INFORMATION STUDIES');
    $divisions[] = array('code' => 'EN', 'fullname' => 'ENGINEERING AND APPLIED SCIENCE');
    $divisions[] = array('code' => 'GI', 'fullname' => 'GENERAL CAMPUS-INTERDISCIPLINARY');
    $divisions[] = array('code' => 'GS', 'fullname' => 'LETTERS AND SCIENCE-INTERDISCIPLINARY');
    $divisions[] = array('code' => 'HU', 'fullname' => 'HUMANITIES');
    $divisions[] = array('code' => 'IE', 'fullname' => 'INTERNATIONAL EDUCATION');
    $divisions[] = array('code' => 'IS', 'fullname' => 'INTERNATIONAL INSTITUTE');
    $divisions[] = array('code' => 'LF', 'fullname' => 'LIFE SCIENCE');
    $divisions[] = array('code' => 'LW', 'fullname' => 'LAW');
    $divisions[] = array('code' => 'MG', 'fullname' => 'MANAGEMENT');
    $divisions[] = array('code' => 'MN', 'fullname' => 'MEDICINE');
    $divisions[] = array('code' => 'NS', 'fullname' => 'NURSING');
    $divisions[] = array('code' => 'PA', 'fullname' => 'PUBLIC AFFAIRS');
    $divisions[] = array('code' => 'PH', 'fullname' => 'PUBLIC HEALTH');
    $divisions[] = array('code' => 'PS', 'fullname' => 'PHYSICAL SCIENCE');
    $divisions[] = array('code' => 'SM', 'fullname' => 'SUMMER SESSION');
    $divisions[] = array('code' => 'SS', 'fullname' => 'SOCIAL SCIENCE');
    $divisions[] = array('code' => 'TF', 'fullname' => 'THEATER, FILM, AND TELEVISION');

    foreach ($divisions as $division) {
        $DB->insert_record('ucla_reg_division', $division);
    }

    return true;
}

/**
 * Runs commands to recover a halted installation.
 */
function xmldb_local_ucla_install_recovery() {
    return true;
}
