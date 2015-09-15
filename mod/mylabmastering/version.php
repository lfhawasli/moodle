<?php

/**
 * 
 *
 * @package    mod
 * @subpackage mylabmastering
 * @copyright
 * @author 
 * @license
 */

defined('MOODLE_INTERNAL') || die;

$plugin->version   = 2015081800;
$plugin->requires  = 2013051408;
$module->cron      = 0;
$module->component = 'mod_mylabmastering';
$module->maturity  = MATURITY_STABLE;
$module->release   = '1.0';

$module->dependencies = array(
	'mod_lti' => ANY_VERSION,
	'block_mylabmastering' => 2015081800
);
