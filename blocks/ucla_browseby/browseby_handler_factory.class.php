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
 * BrowseBy handler class.
 *
 * @package    block_ucla_browseby
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 *  Loads all the handlers and stuff.
 *
 * @package    block_ucla_browseby
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/
class browseby_handler_factory {
    /**
     * @var $loaded Initially not loaded.
     */
    public $loaded = false;

    /**
     *  One place to hold a list of types that should be available,
     *  if possible, make it dynamic?
     **/
    static public function get_available_types() {
        $custom = get_config('block_ucla_browseby', 'available_types');
        if ($custom && is_array($custom)) {
            return $custom;
        }

        return array(
            'subjarea', 'division', 'instructor', 'collab'
        );
    }

    /**
     * Constructor.
     */
    public function __construct() {
        $this->load_types();
    }

    /**
     * Return the type of handler.
     *
     * @param string $type
     * @return boolean|string
     */
    public function get_type_handler($type) {
        $hcn = $type . '_handler';

        if (class_exists($hcn)) {
            $handler = new $hcn();
        } else {
            return false;
        }

        return $handler;
    }

    /**
     * Type loader.
     *
     * @return boolean
     */
    public function load_types() {
        if ($this->loaded) {
            return true;
        }

        $handlerpath = dirname(__FILE__) . '/handlers/';
        if (file_exists($handlerpath)) {
            $files = glob($handlerpath . '/*.class.php');

            foreach ($files as $file) {
                require_once($file);
            }
        } else {
            debugging('could not load handlers');
        }

        $this->loaded = true;
        return true;
    }
}
