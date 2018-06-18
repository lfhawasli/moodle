<?php
// This file is part of the UCLA Search block for Moodle - http://moodle.org/
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
 * UCLA search language file.
 *
 * @package     block_ucla_search
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright   2018 UC Regents
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login(null, true);

define('SEARCH_MAX_LIMIT', 20);     // Maximum number of results (arbritrary).
define('SEARCH_SUMMARY_LEN', 75);   // Max length of summary text (arbitrary).

$url = $CFG->wwwroot . '/course/view.php?id=';  // Base course URL.

$query = required_param('q', PARAM_NOTAGS);
$collabparam = optional_param('collab', 0, PARAM_BOOL);
$courseparam = optional_param('course', 0, PARAM_BOOL);
$titleparam = optional_param('bytitle', 0, PARAM_BOOL);
$descparam = optional_param('bydescription', 0, PARAM_BOOL);

$limit = optional_param('limit', SEARCH_MAX_LIMIT, PARAM_INT);

// Ripped right out of the default search.
$search = trim($query); // Trim searched string.
if ($search) {
    $searchterms = explode(' ', $search);    // Search for words independently.
    foreach ($searchterms as $key => $searchterm) {
        if (strlen($searchterm) < 2) {
            unset($searchterms[$key]);
        }
    }
    $search = trim(implode(" ", $searchterms));
}

// Limit the amount of results we can get.
$limit = $limit > SEARCH_MAX_LIMIT ? SEARCH_MAX_LIMIT : $limit;
$params = array('collab' => $collabparam, 'course' => $courseparam,
        'bytitle' => $titleparam, 'bydescription' => $descparam);
$courses = get_courses_search($searchterms, 'fullname ASC', 0, $limit + 1,
        $totalcount, array(), $params);

$totalcount = count($courses);

// Format results.
$results = array();

if (!empty($courses)) {

    // Collect courses.
    foreach ($courses as $course) {
        $item = new stdClass();
        $item->shortname = $course->shortname;
        $item->fullname = $course->fullname;

        // Only display summary for collab sites.
        if (empty($course->collabcheck)) {
            $item->summary = '';
        } else {
            $item->summary = $course->summary;

            // Clean up summary.
            if (!empty($course->summary)) {
                $su = strip_tags($course->summary);
                $su = substr($su, 0, SEARCH_SUMMARY_LEN);
                $item->summary = $su;

                if (strlen($course->summary) > SEARCH_SUMMARY_LEN) {
                    $item->summary .= '...';
                }
            }
        }

        // For highlight results.
        $item->text = $course->fullname;
        // For url click.
        $item->url = $url . $course->id;

        $results[] = $item;
    }

    // Replace the last result with 'View more results...' text.
    if ($totalcount > $limit) {

        $results[$limit]->shortname = '';
        $results[$limit]->text = get_string('more_results', 'block_ucla_search');
        $results[$limit]->summary = '';
        $results[$limit]->url = $CFG->wwwroot . '/course/search.php?search=' .
                $search . '&collab=' . $collabparam . '&course=' . $courseparam .
                '&bytitle=' . $titleparam . '&bydescription=' . $descparam;
    }
} else {
    // We have no results.
    $item = new stdClass();
    $item->shortname = '';
    $item->text = get_string('no_results', 'block_ucla_search');
    $item->summary = '';
    $item->url = '#';

    $results[] = $item;
}

// Adjust total count.
$totalcount = ($totalcount > $limit) ? $limit : $totalcount;

// Format output.
$out = new stdClass();

$out->query = $search;
$out->results = $results;
$out->numresults = $totalcount;

// Return as JSON text.
echo json_encode($out);
