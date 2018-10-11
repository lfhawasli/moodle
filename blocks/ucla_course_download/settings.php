<?php
// This file is part of the UCLA course download plugin for Moodle - http://moodle.org/
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
 * Generates the settings form for the UCLA course download block.
 *
 * @package     block_ucla_course_download
 * @copyright   2014 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if($hassiteconfig && $ADMIN->fulltree) {

    $settings->add(new admin_setting_configcheckbox(
                'block_ucla_course_download/allowstudentaccess',
                new lang_string('allowstudentaccess','block_ucla_course_download'),
                new lang_string('allowstudentaccess_desc','block_ucla_course_download'),
                1
            ));

    $days = array(
        1   => new lang_string('numdays', '', 1),
        2   => new lang_string('numdays', '', 2),
        3   => new lang_string('numdays', '', 3),
        5   => new lang_string('numdays', '', 5),
        7   => new lang_string('numdays', '', 7),
        10  => new lang_string('numdays', '', 10),
        14  => new lang_string('numdays', '', 14),
        20  => new lang_string('numdays', '', 20),
        30  => new lang_string('numdays', '', 30)
    );
    $settings->add(new admin_setting_configselect(
                'block_ucla_course_download/ziplifetime',
                new lang_string('ziplifetime','block_ucla_course_download'),
                new lang_string('ziplifetime_desc','block_ucla_course_download'),
                7,
                $days
            ));
    $settings->add(new admin_setting_configtext(
                'block_ucla_course_download/maxfilesize',
                new lang_string('maxfilesize','block_ucla_course_download'),
                new lang_string('maxfilesize_desc','block_ucla_course_download'),
                250,
                PARAM_INT,
                4
            ));
    $settings->add(new admin_setting_configcheckbox(
        'block_ucla_course_download/coursedownloaddefault',
        get_string('coursedownloaddefault', 'block_ucla_course_download'),
        get_string('coursedownloaddefault_desc', 'block_ucla_course_download'),
        1));
}
