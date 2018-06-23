<?php
// This file is part of the UCLA shared theme for Moodle - http://moodle.org/
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
 * New action menu item of type header.
 *
 * @package   theme_uclashared
 * @copyright 2018 UC Regents
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_uclashared;
defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/lib/outputcomponents.php');

/**
 * An action menu header.
 *
 * @package   theme_uclashared
 * @copyright 2018 UC Regents
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class action_menu_header extends \action_link implements \renderable {

    /**
     * True if this is a primary action. False if not.
     * @var bool
     */
    public $primary = false;

    /**
     * Constructs the object.
     *
     * @param string $text The text to represent the action.
     */
    public function __construct($text) {
        parent::__construct(new \moodle_url(''), $text, null, array(), null);
        $this->add_class('menu-action-header');
        $this->attributes['role'] = 'menuheader';
    }
}