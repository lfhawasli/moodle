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
 * Class file to handle Browse-By settings.
 *
 * @package    block_ucla_browseby
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/ucla_browseby/'
    . 'browseby_handler_factory.class.php');

$types = browseby_handler_factory::get_available_types();

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configcheckbox(
        'block_ucla_browseby/syncallterms',
         get_string('title_syncallterms', 'block_ucla_browseby'),
         get_string('desc_syncallterms', 'block_ucla_browseby'), 0));

    foreach ($types as $type) {
        $settings->add(new admin_setting_configcheckbox(
            'block_ucla_browseby/disable_' . $type,
            get_string('title_' . $type, 'block_ucla_browseby'),
            get_string('desc_' . $type, 'block_ucla_browseby'),
            0));
    }

    $settings->add(new admin_setting_configcheckbox(
        'block_ucla_browseby/use_local_courses',
         get_string('title_use_local_courses', 'block_ucla_browseby'),
         get_string('desc_use_local_courses', 'block_ucla_browseby'), 0));

    $settings->add(new admin_setting_configtext(
        'block_ucla_browseby/ignore_coursenum',
        get_string('title_ignore_coursenum', 'block_ucla_browseby'),
        get_string('desc_ignore_coursenum', 'block_ucla_browseby'),
        '194,295,296,375'));

    $settings->add(new admin_setting_configtext(
        'block_ucla_browseby/allow_acttypes',
        get_string('title_allow_acttypes', 'block_ucla_browseby'),
        get_string('desc_allow_acttypes', 'block_ucla_browseby'),
        'LEC,SEM'));
}
