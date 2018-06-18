<?php

/**
 * Version details.
 *
 * @package    local_mylabmastering
 * @copyright  
 * @license    
 */

defined('MOODLE_INTERNAL') || die;

$plugin->version   = 2017052300;
$plugin->requires  = 2013051408;
$plugin->cron      = 0;
$plugin->component = 'local_mylabmastering';
$plugin->maturity  = MATURITY_STABLE;
$plugin->release   = '1.0';

$plugin->dependencies = array(
		'mod_lti' => ANY_VERSION
);
