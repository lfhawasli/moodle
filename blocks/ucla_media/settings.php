<?php
// This file is part of the UCLA Media block for Moodle - http://moodle.org/
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
 * Specifies settings for the media block.
 *
 * @package    block_ucla_media
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configtext(
            'block_ucla_media/library_source_url',
            get_string('headerlibraryurl', 'block_ucla_media'),
            get_string('desclibraryurl', 'block_ucla_media'),
            'https://webservices.library.ucla.edu/music/v2/classes',
            PARAM_URL
        ));
    $settings->add(new admin_setting_configtext(
            'block_ucla_media/bruincast_login_url',
            get_string('headerbruincastloginurl', 'block_ucla_media'),
            get_string('descbruincastloginurl', 'block_ucla_media'),
            'https://d7.oid.ucla.edu/api/v1/user/login',
            PARAM_URL
        ));
    $settings->add(new admin_setting_configtext(
            'block_ucla_media/bruincast_url',
            get_string('headerbruincasturl', 'block_ucla_media'),
            get_string('descbruincasturl', 'block_ucla_media'),
            'https://d7.oid.ucla.edu/api/v1/views/ccle_api',
            PARAM_URL
        ));
    $settings->add(new admin_setting_configtext(
            'block_ucla_media/bruincast_http_user',
            get_string('headerbruincasthttpuser', 'block_ucla_media'),
            get_string('descbruincasthttpuser', 'block_ucla_media'),
            '',
            PARAM_TEXT
        ));
    $settings->add(new admin_setting_configpasswordunmask(
            'block_ucla_media/bruincast_http_pass',
            get_string('headerbruincasthttppass', 'block_ucla_media'),
            get_string('descbruincasthttppass', 'block_ucla_media'),
            '',
            PARAM_TEXT
        ));
    $settings->add(new admin_setting_configtext(
            'block_ucla_media/bruincast_user',
            get_string('headerbruincastuser', 'block_ucla_media'),
            get_string('descbruincastuser', 'block_ucla_media'),
            '',
            PARAM_TEXT
        ));
    $settings->add(new admin_setting_configpasswordunmask(
            'block_ucla_media/bruincast_pass',
            get_string('headerbruincastpass', 'block_ucla_media'),
            get_string('descbruincastpass', 'block_ucla_media'),
            '',
            PARAM_TEXT
        ));
    $settings->add(new admin_setting_configtext(
            'block_ucla_media/bruincast_wowza',
            get_string('headerbruincastwowza', 'block_ucla_media'),
            get_string('descbruincastwowza', 'block_ucla_media'),
            '164.67.141.72',
            PARAM_URL
        ));
    $settings->add(new admin_setting_confightmleditor(
            'block_ucla_media/bruincast_notice',
            get_string('bruincastnotice', 'block_ucla_media'),
            get_string('bruincastnoticedesc', 'block_ucla_media'), ''));
    $settings->add(new admin_setting_configtextarea(
            'block_ucla_media/bruincast_crosslists',
            get_string('bruincastcrosslists', 'block_ucla_media'),
            get_string('bruincastcrosslistsdesc', 'block_ucla_media'), ''));
    $settings->add(new admin_setting_configcheckbox(
            'block_ucla_media/media_resource_requests',
            get_string('mrrequest', 'block_ucla_media'),
            get_string('mediarequestdesc', 'block_ucla_media'), 1));
}