<?php
// This file is part of the UCLA Library Reserves block for Moodle - http://moodle.org/
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
 * Local library file.
 *
 * @package    block_ucla_library_reserves
 * @copyright  2019 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
define('BLOCK_UCLA_LIBRARY_RESERVES_LIB_GUIDE', 1);
define('BLOCK_UCLA_LIBRARY_RESERVES_LIB_RESERVES', 2);
require_once($CFG->dirroot.'/local/ucla/lib.php');
require_once($CFG->dirroot.'/admin/tool/uclacoursecreator/uclacoursecreator.class.php');

/**
 * Initializes all $PAGE variables.
 *
 * @param object $course
 * @param context_course $context
 * @param moodle_url $url
 * @param int $mode         Optional. Add link to index page for given mode.
 * @param string $title     Optional. Add link to breakcrumbs.
 */
function init_pagex($course, $context, $url, $mode = null, $title = null) {
    global $PAGE;

    $PAGE->set_pagetype('course-view-' . $course->format);
    $PAGE->set_context($context);
    $PAGE->set_pagelayout('base');
    $PAGE->set_title($course->shortname . ': ' . get_string('pluginname', 'block_ucla_library_reserves'));
    $PAGE->set_heading($course->fullname);
    $PAGE->set_url($url);
    require_login($course);

    // Reset breadcrumbs and make it start with course and Library reserves.
    $PAGE->navbar->ignore_active();
    $PAGE->navbar->add($course->shortname, new moodle_url('/course/view.php' ,
            array('id' => $course->id)));
    $PAGE->navbar->add(get_string('title', 'block_ucla_library_reserves'));

    if (!empty($mode)) {
        $index = '';
        $indextitle = '';
        if ($mode == BLOCK_UCLA_LIBRARY_RESERVES_LIB_GUIDE) {
            $index = '/blocks/ucla_library_reserves/index.php';
            $indextitle = get_string('researchguide', 'block_ucla_library_reserves');
        } else if ($mode == BLOCK_UCLA_LIBRARY_RESERVES_LIB_RESERVES) {
            $index = '/blocks/ucla_library_reserves/course_reserves.php';
            $indextitle = get_string('coursereserves', 'block_ucla_library_reserves');
        }
        $indexurl = new moodle_url($index, array('courseid' => $course->id));
        $PAGE->navbar->add($indextitle, $indexurl);
    }

    if (!empty($title)) {
        $PAGE->navbar->add($title, new moodle_url($url));
    }
}

/**
 * Prints tabs for course library content.
 *
 * @param string $activetab
 * @param int $courseid courseid of the active tab
 * @param boolean $printreserves courseid of the active tab
 */
function print_library_tabs($activetab, $courseid, $printreserves = true) {
    $tabs = [];
    $tabs[] = new tabobject(get_string('researchguide', 'block_ucla_library_reserves'),
        new moodle_url('/blocks/ucla_library_reserves/index.php',
            array('courseid' => $courseid)),
            get_string('researchguide', 'block_ucla_library_reserves'));
    if ($printreserves) {
        $tabs[] = new tabobject(get_string('coursereserves', 'block_ucla_library_reserves'),
        new moodle_url('/blocks/ucla_library_reserves/course_reserves.php',
            array('courseid' => $courseid)),
            get_string('coursereserves', 'block_ucla_library_reserves'));
    }
    print_tabs(array($tabs), $activetab);
}

/**
 * Creates the object that contains the necessary information to call the LTI tool.
 * @return stdObject contains information to pass into the function that calls the LTI tool.
 */
function construct_lti_config() {
    $toolconfig = new stdClass();
    // Add config info.
    $toolconfig->name = get_config('block_ucla_library_reserves', 'lti_tool_name');
    $toolconfig->toolurl = get_config('block_ucla_library_reserves', 'lti_tool_url');
    $toolconfig->resourcekey = get_config('block_ucla_library_reserves', 'consumer_key');
    $toolconfig->password = get_config('block_ucla_library_reserves', 'lti_sharedsecret');
    // Default info.
    $toolconfig->instructorchoicesendname = 0; // Do not send name.
    $toolconfig->instructorchoicesendemailaddr = 0; // Do not send email address.
    $toolconfig->instructorchoiceacceptgrades = 0; // Do not accept grade from the LTI tool.
    $toolconfig->instructorcustomparameters = ''; // No custom parameters.
    $toolconfig->instructorchoiceallowroster = null;
    $toolconfig->securetoolurl = '';
    $toolconfig->debuglaunch = 0; // No debuglaunch.
    $toolconfig->id = 0; // Property $toolconfig->id is an optional param in mod/lti/return.php, default to 0.
    return $toolconfig;
}

/**
 * Checks if user can access course reserves.
 *
 * @param int $courseid
 * @return bool
 */
function can_request_course_reserves(int $courseid) {
    $caneditcourse = has_capability('format/ucla:viewadminpanel',
        context_course::instance($courseid, MUST_EXIST));
    return $caneditcourse;
}
/**
 * Print the tabs under "reserach guide" tab for crosslisted courses.
 * @param array   $courseinfo An array of stdClass object containing course info such as subject area/course number.
 * @param int $courseid The course id of the given course context.
 * @param string $activetab The string serves as id of the crossslisted course tab being clicked on.
 */
function print_research_tabs($courseinfo, $courseid, $activetab) {
    $tabs = array();
    foreach ($courseinfo as $course) {
        $coursenamedisplay = $course->subj_area.' '.$course->coursenum;
        $courseshortname = uclacoursecreator::make_course_shortname($course);
        if ($course->hostcourse == 1 && $activetab == '') {
            $activetab = $courseshortname;
        }
        // Here, $courseshortname serves as the id of a tab.
        $tabs[] = new tabobject($courseshortname,
        new moodle_url('/blocks/ucla_library_reserves/index.php',
        array('courseid' => $courseid, 'courseshortname' => $courseshortname)),
        $coursenamedisplay);
    }
    print_tabs(array($tabs), $activetab);
}

/**
 * Display some information about research guide and crosslisted course between "Research guide" tab and crosslisted tabs.
 * @param bool   $iscrosslisted A boolean . If the current course is a crosslisted course.
 */
function info_display($iscrosslisted) {
    $infodisplay = html_writer::start_tag('div', array('class' => 'alert alert-info'));
    $infodisplay .= html_writer::div(get_string('researchguidegeneral', 'block_ucla_library_reserves'));
    if ($iscrosslisted) {
        $infodisplay .= html_writer::tag('hr', '');
        $infodisplay .= html_writer::div(get_string('reserachguidecrosslistedcourse', 'block_ucla_library_reserves'));
    }
    $infodisplay .= html_writer::end_tag('div');
    echo $infodisplay;
}

/**
 * From the given coursesectioninfo array, remove courses with the same subject area/course number.
 * @param array   $coursesectioninfo An array of stdClass object containing all course sections getting from a particular course id.
 * @return bool An array of stdClass object containing course info with nonduplicated subject area/course number.
 */
function get_unique_course($coursesectioninfo) {
    $coursenamecache = array();
    foreach ($coursesectioninfo as $course) {
        $coursename = $course->subj_area.' '.$course->coursenum;
        if (in_array($coursename, $coursenamecache)) {
            unset($coursesectioninfo[array_search($course, $coursesectioninfo)]);
        } else {
            array_push($coursenamecache, $coursename);
        }
    }
    return $coursesectioninfo;
}

/**
 * Show the research guide under library reserves section.
 * @param int   $courseid An integer representing the course id of the current course.
 * @param string $activetab The string serves as id of the crossslisted course tab being clicked on..
 */
function show_research_guide($courseid, $activetab) {
    global $OUTPUT;
    $coursesectioninfo = ucla_get_course_info($courseid);
    $courseinfo = get_unique_course($coursesectioninfo);
    $crosslistedtab = (count($courseinfo) > 1);
    info_display($crosslistedtab);
    if ($crosslistedtab) {
        print_research_tabs($courseinfo, $courseid, $activetab);
    }
    get_iframe('launch.php?shortname='.$activetab.'&id='.$courseid);
}

/**
 * Generate an iframe that displays the sent URL.
 *
 * @param string $url The URL to be displayed.
 */
function get_iframe($url) {
    $decodedurl = urldecode ( $url );
    $courseid = strpos($decodedurl, 'courseId');
    $decodedurl = substr(
        $decodedurl, $courseid
        ? $courseid + strlen('courseId')
        : strpos($decodedurl, 'shortname') + strlen('shortname') + 1
    );
    $courseshortname = substr($decodedurl, 0, strpos($decodedurl, '&'));
    $title = '';
    if ( $courseid ) {
        $courseshortname = substr($courseshortname, strpos($courseshortname, '|') + 1);
        $courseshortname = str_replace ( ':', ' ', $courseshortname);
        $title = get_string('iframecoursereserves', 'block_ucla_library_reserves', $courseshortname);
    } else {
        $title = get_string('iframeresearchguide', 'block_ucla_library_reserves', $courseshortname);
    }
    echo '<iframe id="contentframe" title="'.$title.'" height="600px" width="100%" src="' .
        $url . '"webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>';
    // Output script to make the iframe tag be as large as possible.
    $resize = '
        <script type="text/javascript">
        //<![CDATA[
            YUI().use("node", "event", function(Y) {
                var doc = Y.one("body");
                var frame = Y.one("#contentframe");
                var padding = 15; //The bottom of the iframe wasn\'t visible on some themes. Probably because of border widths, etc.
                var lastHeight;
                var resize = function(e) {
                    if(lastHeight !== doc.get("docHeight")*3/4){
                        frame.setStyle("height", doc.get("docHeight")*3/4- frame.getY() - padding + "px");
                        lastHeight = doc.get("docHeight")*3/4;
                    }
                };

                resize();

                Y.on("windowresize", resize);
            });
        //]]
        </script>
';
    echo $resize;
}

/**
 * Find the hostcourse URL if it exists.
 *
 * @param int   $courseid An integer representing the course id of the current course.
 * @return string Returns null if no URL exists.
 */
function get_hostcourseurl($courseid) {
    global $DB;
    $cache = cache::make('block_ucla_library_reserves', 'hostcourseurl');
    $courseurl = $cache->get($courseid);
    if ($courseurl === false) {
        $courseurl = $DB->get_record_sql("
            SELECT url
              FROM {ucla_library_reserves} reserves
              JOIN {ucla_request_classes} classes ON reserves.srs = classes.srs
                   AND reserves.quarter = classes.term
             WHERE classes.hostcourse = 1
                   AND classes.courseid = :id", array('id' => $courseid), IGNORE_MULTIPLE);
        if ($courseurl) {
            $courseurl = $courseurl->url;
        }
        // If link is not https, convert it to https.
        if (!(strpos($courseurl, 'https') !== false)) {
            $courseurl = preg_replace('|http://|i', 'https://', $courseurl, 1);
        }
        if (!$courseurl) {
            $courseurl = null;
        }
        $cache->set($courseid, $courseurl);
    }
    return $courseurl;
}
