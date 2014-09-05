<?php
// This file is part of the UCLA gradebook customizations plugin for Moodle - http://moodle.org/
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
 * Settings.
 *
 * @package    local_gradebook
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) { // Speedup for non-admins, add all caps used on this page.
    // Inject gradebook on/off switch.
    // Site administration > Development > Experimental > Experimental settings.
    $temp = $ADMIN->locate('experimentalsettings');
    $temp->add(new admin_setting_configcheckbox('gradebook_send_updates',
            get_string('settingsendupdates', 'local_gradebook'),
            get_string('settingsendupdateshelp', 'local_gradebook'), 0));
}
