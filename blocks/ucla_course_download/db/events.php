<?php
/**
 * Database events.
 * 
 * This file contains the event handlers for the Moodle event API.
 * 
 * @package     block
 * @subpackage  block_ucla_course_download
 * @copyright 2014 UC Regents
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$handlers = array (
        'ucla_format_notices' => array(
        'handlerfile'      => '/blocks/ucla_course_download/eventlib.php',
        'handlerfunction'  => 'ucla_course_download_ucla_format_notices',
        'schedule'         => 'instant',    // This is made instant for message passing.
        'internal'         => 1,
    ),
);
