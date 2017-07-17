<?php
// This file is part of the UCLA local_ucla plugin for Moodle - http://moodle.org/
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
 * Script to show end-of-course survey on alert block. 
 * 
 * For the school of public health courses for Fall 2012. See CCLE-3679.
 *
 * @copyright 2012 UC Regents
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package local_ucla
 */

define('CLI_SCRIPT', true);

require_once(dirname(__FILE__).'/../../../config.php');
require_once($CFG->dirroot . '/blocks/ucla_alert/locallib.php');

echo "Adding alert notice to Public Health Fall 2012\n";

// Select all the course IDs where block will be installed.
$sql = "SELECT c.id
          FROM {course} c
          JOIN {ucla_request_classes} urc ON urc.courseid = c.id
          JOIN {ucla_reg_classinfo} rci ON rci.srs = urc.srs AND rci.term = urc.term
         WHERE rci.term = :term
           AND rci.division = :division
           AND urc.hostcourse = 1";

// Get records.
$records = $DB->get_records_sql($sql, array('term' => '12F', 'division' => 'PH'));


// Put the IDs in an array.
$courseidarray = array();

foreach ($records as $r) {
    $courseidarray[] = $r->id;
}

// Message we want to display.
$text = "# Course Evaulation
As part of our transition to a competencies-based curriculum, we are implementing
an online course assessment system called <strong>SPHweb</strong> to replace the
scantron course evaluations.

For Fall 2012, we are asking you to complete end of quarter courses evaluations
<strong>by Dec. 9</strong>.  Please login with your UCLA LogonID using the link
below, then click [MyHome].
>{http://portal.ph.ucla.edu/sphweb/} Survey link";

// Only apply if we have any records to display.
if (!empty($courseidarray)) {
    // Create event data.
    $data = array(
        'courses' => $courseidarray,
        'entity' => ucla_alert::ENTITY_ITEM,
        'starts' => 'Dec 01 2012 12:00',
        'expires' => 'Dec 09 2012 20:10',
        'text' => $text
    );

    // Call function directly.
    ucla_alert::handle_alert_post($data);

    // Display count and a list of course ids.
    $n = count($courseidarray);

    echo "There were $n courses alerted.\n";
    echo "IDs: [ ". implode(', ', $courseidarray) . " ]\n";
}

echo "...done\n";
