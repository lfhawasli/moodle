<?php
// This file is part of the UCLA course menu block for Moodle - http://moodle.org/
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
 * Edit form class.
 *
 * @package block_ucla_course_menu
 * @copyright 2016 UC Regent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Block course menu form class.
 * @copyright 2016 UC Regent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_ucla_course_menu_edit_form extends block_edit_form {

    /**
     * Fill specific definition for form.
     * @param form $mform
     */
    protected function specific_definition($mform) {
        global $CFG;
        $mform->addElement('header', 'configheader',
            get_string('blockgeneralsettings', $this->block->blockname));

        $options = array(block_ucla_course_menu::TRIM_RIGHT => get_string('trimmoderight', $this->block->blockname),
        block_ucla_course_menu::TRIM_LEFT => get_string('trimmodeleft',
        $this->block->blockname), block_ucla_course_menu::TRIM_CENTER => get_string('trimmodecenter', $this->block->blockname));
        $mform->addElement('select', 'config_trimmode', get_string('trimmode', $this->block->blockname), $options);
        $mform->setType('config_trimmode', PARAM_INT);
        $mform->addElement('text', 'config_trimlength', get_string('trimlength', $this->block->blockname));
        $mform->setDefault('config_trimlength', get_config('block_ucla_course_menu', 'trimlength'));
        $mform->setType('config_trimlength', PARAM_INT);
    }
}
