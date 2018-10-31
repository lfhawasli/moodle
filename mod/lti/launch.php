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

require_once("../../config.php");
require_once($CFG->dirroot.'/mod/lti/lib.php');
require_once($CFG->dirroot.'/mod/lti/locallib.php');

// START UCLA MOD: CCLE-6955 -- Integration point in course menu
// $id = required_param('id', PARAM_INT); // Course Module ID.
$id = optional_param('id', 0, PARAM_INT); // Course Module ID.
// END UCLA MOD: CCLE-6955 - Integration point in course menu
$triggerview = optional_param('triggerview', 1, PARAM_BOOL);
// START UCLA MOD: CCLE-6956 - LTI Apps in Rich Text Editor.
$placement = optional_param('placement', 'activity', PARAM_RAW);
// END UCLA MOD: CCLE-6956.

// START UCLA MOD: CCLE-6955 - Integration point in course menu.
//$cm = get_coursemodule_from_id('lti', $id, 0, false, MUST_EXIST);
//$lti = $DB->get_record('lti', array('id' => $cm->instance), '*', MUST_EXIST);
//$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
//$context = context_module::instance($cm->id);
$courseid = optional_param('courseid', 0, PARAM_INT);
$ltitypeid = optional_param('ltitypeid', 0, PARAM_INT);
$cm = null;
$placement = null;
if ($id) {
    $cm = get_coursemodule_from_id('lti', $id, 0, false, MUST_EXIST);
    $lti = $DB->get_record('lti', array('id' => $cm->instance), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $context = context_module::instance($cm->id);
    $launchparam = 'id=' . $ltitypeid;
    $pageparams = array('id' => $id);
} else {
    $lti = $DB->get_record('lti_types', ['id' => $ltitypeid]);
    $lti->typeid = $ltitypeid;
    $lti->instructorcustomparameters = null;
    $lti->debuglaunch = false;
    $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
    $context = context_course::instance($courseid);
    $pageparams = array('ltitypeid' => $ltitypeid, 'courseid' => $courseid,);
    $launchparam = 'ltitypeid=' . $ltitypeid . '&courseid=' . $courseid;
    $placement = 'menulink';
}
// END UCLA MOD: CCLE-6955.

require_login($course, true, $cm);
require_capability('mod/lti:view', $context);

// START UCLA MOD: CCLE-4863 - Sign FERPA waiver for external LTI
// If true, then user needs to sign waiver.
$url = new moodle_url('/mod/lti/launch.php', $pageparams);
if ($cm && local_ucla_ferpa_waiver::check($context, $USER->id)) {
    $redirecturl = mod_casa_privacy_waiver::get_link($context, $url);
    redirect($redirecturl);
}
// END UCLA MOD: CCLE-4863

// Completion and trigger events.
// START UCLA MOD: CCLE-6955 - Integration point in course menu.
//if ($triggerview) {
//    lti_view($lti, $course, $cm, $context);
//}
//
//$lti->cmid = $cm->id;
if ($triggerview && $cm) {
    lti_view($lti, $course, $cm, $context);
}

if ($cm) {
    $lti->cmid = $cm->id;
}
// END UCLA MOD: CCLE-6955.

// START UCLA MOD: CCLE-6956 - LTI Apps in Rich Text Editor.
//lti_launch_tool($lti);
lti_launch_tool($lti, $placement);
// END UCLA MOD: CCLE-6956.