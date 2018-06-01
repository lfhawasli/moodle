<?php

/**
 * Pearson MyLab & Mastering block code.
 *
 * @package    blocks_mylabmastering
 * @copyright  2012-2014 Pearson Education
 * @license    
 */
defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2017052300;
$plugin->requires  = 2013051408;
$plugin->cron      = 0;
$plugin->component = 'block_mylabmastering';
$plugin->maturity  = MATURITY_STABLE;
$plugin->release   = '1.0';

$plugin->dependencies = array(
		'mod_lti' => ANY_VERSION
);
