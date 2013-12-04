<?php
// This file is part of the UCLA local plugin for Moodle - http://moodle.org/
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

/*
 * Command line script to bulk process and update syllabi links for a given
 * course or term.
 *
 * @package    local_ucla
 * @copyright  2013 UCLA regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__.'/../../../config.php');
require_once("$CFG->libdir/clilib.php");
require_once("$CFG->dirroot/local/ucla/classes/local_ucla_regsender.php");
require_once("$CFG->dirroot/local/ucla_syllabus/locallib.php");

// Now get cli options.
list($options, $unrecognized) = cli_get_params(array('help' => false), array('h' => 'help'));

if ($options['help']) {
    $help =
"Updates links to syllabi for given courseid or term.

Options:
-h, --help            Print out this help

Example:
\$sudo -u www-data /usr/bin/php local/ucla/cli/update_srdb_ucla_syllabus [COURSEID|TERM]
";

    echo $help;
    die;
}

// See if user is passing us a courseid or term.
$term = $courseid = null;
if ($unrecognized) {
    foreach ($unrecognized as $index => $param) {
        // Maybe someone is passing us a term to run.
        if (ucla_validator('term', $param)) {
            $term = $param;
            unset($unrecognized[$index]);
        }
        // Maybe someone is passing a courseid.
        if (intval($param) > SITEID) {
            $courseid = intval($param);
            unset($unrecognized[$index]);
        }
    }
    if (!empty($unrecognized)) {
        $unrecognized = implode("\n  ", $unrecognized);
        cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
    }
}

$courseids = null;
if (!empty($term)) {
    $courseids = $DB->get_fieldset_select('ucla_request_classes', 'courseid',
            'term = ?', array($term));
} else {
    $courseids[] = $courseid;    
}

if (empty($courseids)) {
    cli_error('No courses to process.');
}

$trace = new text_progress_trace();

$regsender = new local_ucla_regsender();

foreach ($courseids as $courseid) {
    $trace->output("Processing course id $courseid");

    // Create empty array of syllabus links. Then set them if there is a
    // syllabus for that type.
    $links = array();
    foreach (local_ucla_regsender::$syllabustypes as $type) {
        $links[$type] = '';
    }

    $courselink = (new moodle_url('/local/ucla_syllabus/index.php',
                array('id' => $courseid)))->out();

    // Get syllabi for course. Do not use ucla_syllabus_manager, since it has
    // a lot of overhead.
    $syllabustypes = $DB->get_fieldset_select('ucla_syllabus', 'access_type',
            'courseid = ?', array($courseid));

    $setlinks = array();
    if (!empty($syllabustypes)) {
        foreach ($syllabustypes as $type) {
            switch ($type) {
                case UCLA_SYLLABUS_ACCESS_TYPE_PUBLIC:
                    $links['public'] = $courselink;
                    $setlinks[] = 'public';
                    break;
                case UCLA_SYLLABUS_ACCESS_TYPE_LOGGEDIN:
                    $links['protect'] = $courselink;
                    $setlinks[] = 'protect';
                    break;
                case UCLA_SYLLABUS_ACCESS_TYPE_PRIVATE:
                    $links['private'] = $courselink;
                    $setlinks[] = 'private';
                    break;
            }
        }
    }

    if (empty($setlinks)) {
        $trace->output("No syllabi found, clearing links", 1);
    } else {
        $trace->output(sprintf("Setting links for: %s, clearing others", implode(', ', $setlinks)), 1);
    }

    $result = $regsender->set_syllabus_links($courseid, $links);
    if ($result == local_ucla_regsender::FAILED) {
        cli_error("ERROR! Could not set links for course id $courseid; Aborting", 1);
    } else if ($result == local_ucla_regsender::NOUPDATE) {
        $trace->output("Syllabi links already set, no changes", 1);
    } else if ($result == local_ucla_regsender::PARTIALUPDATE) {
        $trace->output("Some syllabi links already set, some changes", 1);
    } else if ($result == local_ucla_regsender::SUCCESS) {
        $trace->output("All syllabi links set successfully", 1);
    } else {
        cli_error("ERROR! Unknown return code; Aborting", 1);
    }

    unset($courselink);
    unset($links);
    unset($setlinks);
    unset($syllabustypes);
}

$trace->output("DONE!");