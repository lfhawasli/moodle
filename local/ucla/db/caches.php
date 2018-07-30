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
 * UCLA cache definitions.
 *
 * It contains the components that are using the MUC.
 *
 * @package    local_ucla
 * @category   cache
 * @copyright  2013 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$definitions = array(
    // Store role mappings to avoid repetitive DB queries within one request.
    'rolemappings' => array(
        'mode' => cache_store::MODE_REQUEST,
        'persistent' => true,
    ),
    // Store data used to map ucla_request_classes to Moodle courses.
    'urcmappings' => array(
        'mode' => cache_store::MODE_REQUEST,
        'persistent' => true,
    ),
    // Store user mapping to avoid repetitive DB queries within one request.
    // A user mapping might be keyed by idnumber or username.
    'usermappings' => array(
        'mode' => cache_store::MODE_REQUEST,
        'persistent' => true,
    ),
    // Stores the token to be used in Enterprise Service Bus (ESB) calls.
    'esbtoken' => array(
        'mode' => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => true,
        'staticacceleration' => true,
        'staticaccelerationsize' => 1,
        // Tokens have lifetime of 24 hours, but we are using 23 hours just to
        // be safe that we aren't going to be using an expired token in the next
        // web service call.
        'ttl' => 82800,
    ),
);
