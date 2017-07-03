<?php
// This file is part of the UCLA local help plugin for Moodle - http://moodle.org/
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
 * Sidebar for file page
 *
 * @package    block_ucla_help
 * @author     Rex Lorenzo <rex@seas.ucla.edu>
 * @copyright  2011 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Sidebar for file page
 *
 * @copyright  2011 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sidebar_file implements sidebar_widget {

    /**
     * path to file
     * @var string $path
     */
    private $path;

    /**
     * Constructs your object
     * @param string $path
     */
    public function __construct($path) {
        $this->path = null;

        $file = __DIR__ . '/../../../..' . $path;

        if (file_exists($file)) {
            $this->path = $file;
        }
    }

    /**
     * Requires the file based on the path given
     *
     * @return bool
     */
    public function render() {
        if (!empty($this->path)) {
            return require_once($this->path);
        }
    }

}
