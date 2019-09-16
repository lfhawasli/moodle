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
//
// This file is part of BasicLTI4Moodle
//
// BasicLTI4Moodle is an IMS BasicLTI (Basic Learning Tools for Interoperability)
// consumer for Moodle 1.9 and Moodle 2.0. BasicLTI is a IMS Standard that allows web
// based learning tools to be easily integrated in LMS as native ones. The IMS BasicLTI
// specification is part of the IMS standard Common Cartridge 1.1 Sakai and other main LMS
// are already supporting or going to support BasicLTI. This project Implements the consumer
// for Moodle. Moodle is a Free Open source Learning Management System by Martin Dougiamas.
// BasicLTI4Moodle is a project iniciated and leaded by Ludo(Marc Alier) and Jordi Piguillem
// at the GESSI research group at UPC.
// SimpleLTI consumer for Moodle is an implementation of the early specification of LTI
// by Charles Severance (Dr Chuck) htp://dr-chuck.com , developed by Jordi Piguillem in a
// Google Summer of Code 2008 project co-mentored by Charles Severance and Marc Alier.
//
// BasicLTI4Moodle is copyright 2009 by Marc Alier Forment, Jordi Piguillem and Nikolas Galanis
// of the Universitat Politecnica de Catalunya http://www.upc.edu
// Contact info: Marc Alier Forment granludo @ gmail.com or marc.alier @ upc.edu.

/**
 * This file contains all necessary code to view a lti activity instance
 *
 * @package mod_lti
 * @copyright  2009 Marc Alier, Jordi Piguillem, Nikolas Galanis
 *  marc.alier@upc.edu
 * @copyright  2009 Universitat Politecnica de Catalunya http://www.upc.edu
 * @author     Marc Alier
 * @author     Jordi Piguillem
 * @author     Nikolas Galanis
 * @author     Chris Scribner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir.'/completionlib.php');
require_once($CFG->dirroot.'/mod/lti/lib.php');
require_once($CFG->dirroot.'/mod/lti/locallib.php');

$id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
$l  = optional_param('l', 0, PARAM_INT);  // lti ID.
$forceview = optional_param('forceview', 0, PARAM_BOOL);

$ltitypeid = optional_param('ltitypeid', 0, PARAM_INT);
$courseid = optional_param('course', 0, PARAM_INT);


$cm = null;
$pageparams = array();
if ($ltitypeid && $courseid) {
    $lti = $DB->get_record('lti_types', ['id' => $ltitypeid]);
    $lti->typeid = $ltitypeid;
    $lti->showtitlelaunch = false;
    $lti->showdescriptionlaunch = false;
    $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
    $context = context_course::instance($courseid);
    $pageparams = array('ltitypeid' => $ltitypeid, 'courseid' => $courseid,);
    $launchparam = 'ltitypeid=' . $ltitypeid . '&courseid=' . $courseid;
} else {
    if ($l) {  // Two ways to specify the module.
        $lti = $DB->get_record('lti', array('id' => $l), '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('lti', $lti->id, $lti->course, false, MUST_EXIST);
    } else {
        $cm = get_coursemodule_from_id('lti', $id, 0, false, MUST_EXIST);
        $lti = $DB->get_record('lti', array('id' => $cm->instance), '*', MUST_EXIST);
    }
    
    $pageparams = array('id' => $cm->id);
    $launchparam = 'id=' . $cm->id;
}

if (!empty($lti->typeid)) {
    $toolconfig = lti_get_type_config($lti->typeid);
} else if ($tool = lti_get_tool_by_url_match($lti->toolurl)) {
    $toolconfig = lti_get_type_config($tool->id);
} else {
    $toolconfig = array();
}

if ($cm) {
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

    $PAGE->set_cm($cm, $course); // Set's up global $COURSE.
    $context = context_module::instance($cm->id);
    $PAGE->set_context($context);
}

require_login($course, true, $cm);
require_capability('mod/lti:view', $context);

$url = new moodle_url('/mod/lti/view.php', $pageparams);
$PAGE->set_url($url);

// START UCLA MOD: CCLE-4863 - Sign FERPA waiver for external LTI
// If true, then user needs to sign waiver.

// TODO: figure out what happens with FERPA when a tool is launched from the course menu
// START UCLA MOD: CCLE-6954 - LTI tools in course menu
// if (local_ucla_ferpa_waiver::check($context, $USER->id)) {
if ($id && local_ucla_ferpa_waiver::check($context, $USER->id)) {
// END UCLA MOD: CCLE-6954 - LTI tools in course menu
    $redirecturl = mod_casa_privacy_waiver::get_link($context, $url);
    redirect($redirecturl, get_string('privacywaiverrequired', 'mod_casa'), 0);
}
// END UCLA MOD: CCLE-4863

$launchcontainer = lti_get_launch_container($lti, $toolconfig);

if ($launchcontainer == LTI_LAUNCH_CONTAINER_EMBED_NO_BLOCKS) {
    $PAGE->set_pagelayout('incourse');
    $PAGE->blocks->show_only_fake_blocks(); // Disable blocks for layouts which do include pre-post blocks.
} else if ($launchcontainer == LTI_LAUNCH_CONTAINER_REPLACE_MOODLE_WINDOW) {
    if (!$forceview) {
        $url = new moodle_url('/mod/lti/launch.php', $pageparams);
        redirect($url);
    }
} else { // Handles LTI_LAUNCH_CONTAINER_DEFAULT, LTI_LAUNCH_CONTAINER_EMBED, LTI_LAUNCH_CONTAINER_WINDOW.
    $PAGE->set_pagelayout('incourse');
}

if ($cm) {
    lti_view($lti, $course, $cm, $context);
}

$pagetitle = strip_tags($course->shortname.': '.format_string($lti->name));
$PAGE->set_title($pagetitle);
$PAGE->set_heading($course->fullname);

// Print the page header.
echo $OUTPUT->header();

if ($lti->showtitlelaunch) {
    // Print the main part of the page.
    echo $OUTPUT->heading(format_string($lti->name, true, array('context' => $context)));
}

if ($lti->showdescriptionlaunch && $lti->intro) {
    echo $OUTPUT->box(format_module_intro('lti', $lti, $cm->id), 'generalbox description', 'intro');
}

if ( $launchcontainer == LTI_LAUNCH_CONTAINER_WINDOW ) {
    $windowtitle = 'lti-type-' . $lti->typeid;
    if ($cm) {
        $windowtitle = 'lti-' . $cm->id;
    }
    if (!$forceview) {
        echo "<script language=\"javascript\">//<![CDATA[\n";
        echo "window.open('launch.php?" . $launchparam . "&triggerview=0','" . $windowtitle . "');";
        echo "//]]\n";
        echo "</script>\n";
        echo "<p>".get_string("basiclti_in_new_window", "lti")."</p>\n";
    }
    $url = new moodle_url('/mod/lti/launch.php', $pageparams);
    echo html_writer::start_tag('p');
    echo html_writer::link($url, get_string("basiclti_in_new_window_open", "lti"), array('target' => '_blank'));
    echo html_writer::end_tag('p');
} else {
    // Request the launch content with an iframe tag.
    echo '<iframe id="contentframe" height="600px" width="100%" src="launch.php?'.$launchparam.'&triggerview=0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>';

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
                    var viewportHeight = doc.get("winHeight");
                    if(lastHeight !== Math.min(doc.get("docHeight"), viewportHeight)){
                        frame.setStyle("height", viewportHeight - frame.getY() - padding + "px");
                        lastHeight = Math.min(doc.get("docHeight"), doc.get("winHeight"));
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

// Finish the page.
echo $OUTPUT->footer();
