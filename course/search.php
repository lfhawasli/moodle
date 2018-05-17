<?php
// This file is part of Moodle - http://moodle.org/
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
 * Displays external information about a course
 * @package    core_course
 * @copyright  1999 onwards Martin Dougiamas  http://dougiamas.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../config.php");
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->libdir.'/coursecatlib.php');

$search    = optional_param('search', '', PARAM_RAW);  // search words
$page      = optional_param('page', 0, PARAM_INT);     // which page to show
$perpage   = optional_param('perpage', '', PARAM_RAW); // how many per page, may be integer or 'all'
$blocklist = optional_param('blocklist', 0, PARAM_INT);
$modulelist= optional_param('modulelist', '', PARAM_PLUGIN);
$tagid     = optional_param('tagid', '', PARAM_INT);   // searches for courses tagged with this tag id
// START UCLA MOD: CCLE-3948 - Re-add advanced search.
$collab    = optional_param('collab', 0, PARAM_BOOL);
$course    = optional_param('course', 0, PARAM_BOOL);
// END UCLA MOD: CCLE-3948
// START UCLA MOD: CCLE-4796 - Re-add search filter by title/description
$bytitle    = optional_param('bytitle', 0, PARAM_BOOL);
$bydescription    = optional_param('bydescription', 0, PARAM_BOOL);
// END UCLA MOD: CCLE-4796

// List of minimum capabilities which user need to have for editing/moving course
$capabilities = array('moodle/course:create', 'moodle/category:manage');

// Populate usercatlist with list of category id's with course:create and category:manage capabilities.
$usercatlist = coursecat::make_categories_list($capabilities);

$search = trim(strip_tags($search)); // trim & clean raw searched string
// BEGIN UCLA MOD: CCLE-3948
// Only search for strings of length 2 or more. This is to make the results
// compatible with the manage tool (which uses the same convention).
if ($search) {
    $searchterms = explode(' ', $search);
    foreach ($searchterms as $key => $searchterm) {
        if(strlen($searchterm) < 2) {
            unset($searchterms[$key]);
        }
    }
    $search = trim(implode(' ', $searchterms));
}
// END UCLA MOD: CCLE-3948

$site = get_site();

$searchcriteria = array();
foreach (array('search', 'blocklist', 'modulelist', 'tagid') as $param) {
    if (!empty($$param)) {
        $searchcriteria[$param] = $$param;
    }
}
$urlparams = array();
if ($perpage !== 'all' && !($perpage = (int)$perpage)) {
    // default number of courses per page
    $perpage = $CFG->coursesperpage;
} else {
    $urlparams['perpage'] = $perpage;
}
if (!empty($page)) {
    $urlparams['page'] = $page;
}
// BEGIN UCLA MOD: CCLE-4187 - Add collab and course params to url
//$PAGE->set_url('/course/search.php', $searchcriteria + $urlparams);
$searchtype = array('collab' => $collab, 'course' => $course, 'bytitle' => $bytitle, 'bydescription' => $bydescription);
$PAGE->set_url('/course/search.php', $searchtype + $searchcriteria + $urlparams);
// END UCLA MOD: CCLE-4187
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$courserenderer = $PAGE->get_renderer('core', 'course');

if ($CFG->forcelogin) {
    require_login();
}

$strcourses = new lang_string("courses");
$strsearch = new lang_string("search");
$strsearchresults = new lang_string("searchresults");
$strnovalidcourses = new lang_string('novalidcourses');

$PAGE->navbar->add($strcourses, new moodle_url('/course/index.php'));
$PAGE->navbar->add($strsearch, new moodle_url('/course/search.php'));
if (!empty($search)) {
    $PAGE->navbar->add(s($search));
}

if (empty($searchcriteria)) {
    // no search criteria specified, print page with just search form
    $PAGE->set_title("$site->fullname : $strsearch");
} else {
    // this is search results page
    $PAGE->set_title("$site->fullname : $strsearchresults");
    // Link to manage search results should be visible if user have system or category level capability
    if ((can_edit_in_category() || !empty($usercatlist))) {
        $aurl = new moodle_url('/course/management.php', $searchcriteria);
        $searchform = $OUTPUT->single_button($aurl, get_string('managecourses'), 'get');
    } else {
        $searchform = $courserenderer->course_search_form($search, 'navbar');
    }
    $PAGE->set_button($searchform);

    // Trigger event, courses searched.
    $eventparams = array('context' => $PAGE->context, 'other' => array('query' => $search));
    $event = \core\event\courses_searched::create($eventparams);
    $event->trigger();
}

$PAGE->set_heading($site->fullname);

echo $OUTPUT->header();
// BEGIN UCLA MOD: CCLE-3948
//echo $courserenderer->search_courses($searchcriteria);
$uclasearch = $CFG->dirroot . '/blocks/ucla_search/block_ucla_search.php';
// BEGIN UCLA MOD: CCLE-6540 -  Incorrect results for course list of block
if (file_exists($uclasearch) && empty($modulelist) && empty($blocklist)) {
// END UCLA MOD: CCLE-6540
    // Load and display the advanced search bar.
    require_once($uclasearch);
                       
    echo block_ucla_search::search_form('course-search', $searchcriteria + $searchtype);

    // Get course/collab IDs which meet search criteria.
    $courses = get_courses_search($searchcriteria, "fullname ASC",
                                  $page, $perpage, $totalcount,
                                  array(), $searchtype);

    // If there are no courses which match our search, display appropriate
    // message.
    if (!$totalcount) {
        if (!empty($searchcriteria['search'])) {
            echo $courserenderer->heading(get_string('nocoursesfound', '', $searchcriteria['search']));
        } else {
            echo $courserenderer->heading(get_string('novalidcourses'));
        }
    } else {    
        // Use the renderer to display the appropriate courses/collab sites.
        echo $courserenderer->heading(get_string('searchresults'). ": $totalcount");
        echo $courserenderer->courses_list($courses, true, null, $PAGE->url, $totalcount, $page, $perpage);
    }
} else {
    echo $courserenderer->search_courses($searchcriteria);
}
// END UCLA MOD: CCLE-3948
echo $OUTPUT->footer();
