<?php
// This file is part of the UCLA video reserve block for Moodle - http://moodle.org/
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
 * UCLA video reserves block settings.
 *
 * @package    block_ucla_video_reserves
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configtext(
            'block_ucla_video_reserves/sourceurl',
            get_string('sourceurl', 'block_ucla_video_reserves'),
            get_string('sourceurl_desc', 'block_ucla_video_reserves'),
            '',
            PARAM_URL
        ));
    $settings->add(new admin_setting_configtext(
            'block_ucla_video_reserves/wowzaurl',
            get_string('wowzaurl', 'block_ucla_video_reserves'),
            get_string('wowzaurl_desc', 'block_ucla_video_reserves'),
            'wowza.oid.ucla.edu:1935',
            PARAM_URL
        ));
}