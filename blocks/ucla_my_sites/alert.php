<?php
// This file is part of Moodle - http://moodle.org/
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
 * Alert file.
 *
 * Responds to my_sites alert form. Handles setting of user preferences and
 * redirecting.
 *
 * @package     block_ucla_my_sites
 * @copyright   2012 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/blocks/ucla_my_sites/alert_form.php');

require_login();

$PAGE->set_context(context_system::instance());

$alertform = new my_sites_form();
$data = $alertform->get_data();
if (!empty($data) && confirm_sesskey()) {
    if (isset($data->dismissbutton)) {
        set_user_preference('ucla_altemail_noprompt_' . $USER->id, 1);
        $successmsg = get_string('dismissed', 'block_ucla_my_sites');
    }
    redirect(new moodle_url('/my'), $successmsg);
}
