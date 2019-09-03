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
 * Admin settings search form
 *
 * @package    admin
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once $CFG->libdir.'/formslib.php';

/**
 * Admin settings search form
 *
 * @package    admin
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class admin_settings_search_form extends moodleform {
    function definition () {
        $mform = $this->_form;

        //$mform->addElement('header', 'settingsheader', get_string('search', 'admin'));
        // START UCLA MOD: CCLE-8504 - Change search fields to look similar.
        // $elements = [];
        // $elements[] = $mform->createElement('text', 'query', get_string('query', 'admin'));
        // $elements[] = $mform->createElement('submit', 'search', get_string('search'));
        // $mform->addGroup($elements);
        // $mform->setType('query', PARAM_RAW);
        // $mform->setDefault('query', optional_param('query', '', PARAM_RAW));
        $searchbar = html_writer::start_div('ucla-search search-wrapper admin-search').
            html_writer::tag('button', null,
                array('type' => 'submit', 'name' => 'search', 'id' => 'id_search', 'class' => 'fa fa-search btn')).
            html_writer::empty_tag('input',
                array('type' => 'text', 'name' => 'query', 'id' => 'id_query', 'value' => optional_param('query', '', PARAM_RAW),
                    'class' => 'form-control ucla-search-input rounded', 'placeholder' => get_string('searchbarplaceholder', 'admin'))).
            html_writer::end_div();
        $mform->addElement('html', $searchbar);
        // END UCLA MOD: CCLE-8504.
    }
}
