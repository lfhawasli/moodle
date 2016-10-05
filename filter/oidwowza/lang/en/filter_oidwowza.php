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
 *  Language file.
 *
 * @package    filter_oidwowza
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['filtername'] = 'OID Wowza filter';
$string['filter_oidwowza_enable_mp4'] = 'Enable Wowza filter';
$string['filter_oidwowza_hashclientip'] = 'Hash client IP';
$string['filter_oidwowza_hashclientip_desc'] = 'Include client IP when creating secure hash. Would need to disable for local dev testing on VMs, because IPs will not match what Wowza sees.';
$string['filter_oidwowza_minutesexpire'] = 'Expiration';
$string['filter_oidwowza_minutesexpire_desc'] = 'Number of minutes before video link expires';
$string['filter_oidwowza_sharedsecret'] = 'Shared secret';
$string['filter_oidwowza_sharedsecret_desc'] = 'Used to generate SecureToken for Wowza. Must match same token on Wowza server.';
$string['headerwowzaurl'] = 'Source URL';
$string['descwowzaurl'] = 'Source website for wowza server';