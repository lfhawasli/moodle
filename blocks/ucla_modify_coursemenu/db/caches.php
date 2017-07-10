<?php
// This file is part of the UCLA modify course menu block for Moodle - http://moodle.org/
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
 * Contains caching declaration for Landing Page by Dates.
 *
 * @package block_ucla_modify_course_menu
 * @copyright 2017 UC Regent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 defined('MOODLE_INTERNAL') || die();

$definitions = array(
    'landingpagebydatesdb' => array(
        'mode' => cache_store::MODE_APPLICATION,
        'requiredataguarantee' => true,
        'requirelockingwrite' => true,
    ),
    'landingpagebydatesdisplay' => array(
        'mode' => cache_store::MODE_APPLICATION,
    )
);
