<?php
/**
 * Version info
 *
 * @package    report
 * @subpackage uclastats
 * @copyright  UC Regents
 */

defined('MOODLE_INTERNAL') || die;

$plugin->version   = 2014082501;     // The current plugin version (Date: YYYYMMDDXX)
$plugin->requires  = 2012061700;     // Requires this Moodle version
$plugin->component = 'report_uclastats'; // Full name of the plugin (used for diagnostics)

$plugin->dependencies = array(
    'local_ucla' => ANY_VERSION
);