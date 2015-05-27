<?php
/**
 * UCLA specific capabilities
 *
 * @package    local
 * @subpackage ucla
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
