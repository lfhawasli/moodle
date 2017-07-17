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
 * UCLA specific capabilities
 *
 * @package    local_ucla
 * @copyright  2012 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = array(
    'local/ucla:assign_all' => array(

        'riskbitmask' => RISK_SPAM | RISK_PERSONAL | RISK_XSS,

        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'manager' => CAP_ALLOW,
        )
    ),

    'local/ucla:editadvancedcoursesettings' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'manager' => CAP_ALLOW,
        )
    ),

    'local/ucla:deletecoursecontentsandrestore' => array(
        'riskbitmask' => RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'manager' => CAP_ALLOW,
        )
    ),

    'local/ucla:editcoursetheme' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'manager' => CAP_ALLOW,
        )
    ),

    'local/ucla:bulk_users' => array(
        'riskbitmask'   => RISK_DATALOSS,
        'captype'       => 'write',
        'contextlevel'  => CONTEXT_MODULE,
        'archetypes'    => array(
            'manager'  => CAP_ALLOW
        )
    ),

    'local/ucla:browsecourses' => array(
        'captype'       => 'read',
        'contextlevel'  => CONTEXT_SYSTEM,
        'archetypes'    => array(
            'manager'  => CAP_ALLOW
        )
    ),

    'local/ucla:vieweventlist' => array(
        'captype'       => 'read',
        'contextlevel'  => CONTEXT_SYSTEM,
        'archetypes'    => array(
            'manager'  => CAP_ALLOW
        )
    ),

    'local/ucla:viewscheduledtasks' => array(
        'captype'       => 'read',
        'contextlevel'  => CONTEXT_SYSTEM,
        'archetypes'    => array(
            'manager'  => CAP_ALLOW
        )
    ),
);
