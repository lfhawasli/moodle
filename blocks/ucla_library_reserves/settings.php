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
 * Generates the settings form for the Library reserves Block.
 *
 * @package    block_ucla_library_reserves
 * @copyright  2019 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;
require_once($CFG->dirroot.'/mod/lti/edit_form.php');
require_once($CFG->dirroot.'/mod/lti/locallib.php');

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configtext(
        'block_ucla_library_reserves/source_url',
        get_string('headerlibraryreservesurl', 'block_ucla_library_reserves'),
        get_string('desclibraryreservesurl', 'block_ucla_library_reserves'),
        'https://webservices.library.ucla.edu/reserves',
        PARAM_URL
        ));
    $settings->add(new admin_setting_configtext(
        'block_ucla_library_reserves/lti_tool_name',
        get_string('ltinamelibraryreserves', 'block_ucla_library_reserves'),
        get_string('ltinamelibraryreserveshelp', 'block_ucla_library_reserves'),
        get_string('ltinamelibraryreservesdefault', 'block_ucla_library_reserves')
        ));
    $settings->add(new admin_setting_configtext(
        'block_ucla_library_reserves/lti_tool_url',
        get_string('ltiURLlibraryreserves', 'block_ucla_library_reserves'),
        get_string('ltiURLlibraryreserveshelp', 'block_ucla_library_reserves'),
        get_string('ltiURLlibraryreservesdefault', 'block_ucla_library_reserves'),
        PARAM_URL
        ));
    $settings->add(new admin_setting_configtext(
        'block_ucla_library_reserves/consumer_key',
        get_string('lticonsumerkeylibraryreserves', 'block_ucla_library_reserves'),
        get_string('lticonsumerkeylibraryreserveshelp', 'block_ucla_library_reserves'),
        get_string('lticonsumerkeylibraryreservesdefault', 'block_ucla_library_reserves'),
        PARAM_URL
        ));
    $settings->add(new admin_setting_configpasswordunmask(
        'block_ucla_library_reserves/lti_sharedsecret',
        get_string("ltisecretlibraryreserves", "block_ucla_library_reserves"),
        get_string("ltisecretlibraryreserveshelp", "block_ucla_library_reserves"),
        '',
        ''
        ));
}