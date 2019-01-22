<?php
/**
 * Version info
 *
 * @package    report
 * @subpackage uclastats
 * @copyright  UC Regents
 */

defined('MOODLE_INTERNAL') || die;

$plugin->version   = 2019011600;     // The current plugin version (Date: YYYYMMDDXX)
$plugin->requires  = 2013111800; // Moodle 2.6.
$plugin->component = 'report_uclastats'; // Full name of the plugin (used for diagnostics)

$plugin->dependencies = array(
    'local_ucla' => ANY_VERSION
);