<?php
// This file is part of the OID WOWZA plugin for Moodle - http://moodle.org/
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
 *  WOWZA streaming media filter plugin settings.
 *
 * @package    filter_oidwowza
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$settings->add(new admin_setting_configcheckbox('filter_oidwowza_enable_mp4',
        get_string('filter_oidwowza_enable_mp4', 'filter_oidwowza'), '', '0'));

$settings->add(new admin_setting_configcheckbox('filter_oidwowza_hashclientip',
        get_string('filter_oidwowza_hashclientip', 'filter_oidwowza'),
        get_string('filter_oidwowza_hashclientip_desc', 'filter_oidwowza'), '1'));

$settings->add(new admin_setting_configtext('filter_oidwowza_minutesexpire',
        get_string('filter_oidwowza_minutesexpire', 'filter_oidwowza'),
        get_string('filter_oidwowza_minutesexpire_desc', 'filter_oidwowza'), 5, PARAM_INT));

$settings->add(new admin_setting_configtext('filter_oidwowza_sharedsecret',
        get_string('filter_oidwowza_sharedsecret', 'filter_oidwowza'),
        get_string('filter_oidwowza_sharedsecret_desc', 'filter_oidwowza'), '', PARAM_ALPHANUMEXT));

$settings->add(new admin_setting_configtext(
            'filter_oidwowza/video_reserves_url',
            get_string('headerwowzaurl', 'filter_oidwowza'),
            get_string('descwowzaurl', 'filter_oidwowza'),
            'oid-as-wowza.oid.ucla.edu:1935',
            PARAM_URL
        ));