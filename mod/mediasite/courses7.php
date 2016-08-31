<?php

// Setup the page to view Mediasite Courses. 
// Ideally we'll load the catalog in an iFrame on a course specific page

require_once("../../config.php");
require_once($CFG->dirroot.'/mod/mediasite/basiclti_lib.php');
require_once($CFG->dirroot.'/mod/mediasite/basiclti_locallib.php');
require_once($CFG->dirroot.'/mod/mediasite/basiclti_mediasite_lib.php');

global $DB, $PAGE, $OUTPUT;

$id = required_param('id', PARAM_INT); // Course ID
$siteid = required_param('siteid', PARAM_INT); // Site ID
$inpopup = optional_param('inpopup', 0, PARAM_BOOL);

$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);

$context = context_course::instance($id);
$PAGE->set_context($context);

require_login($course, true);
require_capability('mod/mediasite:courses7', $context);

$url = new moodle_url('/mod/mediasite/courses7.php', array('id' => $id, 'siteid' => $siteid));

if ($inpopup) {
    // LTI post full screen to the destination
    // Request the launch content with an iframe tag.
    $launchUrl = new moodle_url('/mod/mediasite/courses7_launch.php', array('id' => $id, 'siteid' => $siteid, 'inpopup' => $inpopup));
    redirect($launchUrl);    
}

$PAGE->set_url($url);

$typeconfig = basiclti_get_type_config($siteid);

$PAGE->set_pagelayout('incourse');

$pagetitle = strip_tags($course->shortname.': '.format_string($typeconfig->integration_catalog_title));
$PAGE->set_title($pagetitle);
$PAGE->set_heading($course->fullname);

// Start the page.
echo $OUTPUT->header();

if ($typeconfig->openpopup_integration_catalog == '1' and !$inpopup) {
    //create the popup window if the content should be a popup    

    echo "\n<script type=\"text/javascript\">";
    echo "\n<!--\n";
    echo 'openpopup(null, {"url":"/mod/mediasite/courses7_launch.php?id=' . $id . '&siteid='.$siteid.'&inpopup=true", ' . '"name":"mediasiteCourses' . $id . '", ' . '"options":"resizable=1,scrollbars=1,directories=1,location=1,menubar=1,toolbar=1,status=1"});';
    echo "\n-->\n";
    echo '</script>';

    $link = "<a href=\"$CFG->wwwroot/mod/mediasite/courses7_launch.php?inpopup=true&amp;id={$id}&amp;siteid={$siteid}\" "
          . "onclick=\"this.target='mediasiteCourses{$id}'; return openpopup('/mod/mediasite/courses7_launch.php?inpopup=true&amp;id={$id}', "
          . "'mediasiteCourses{$id}','resizable=1,scrollbars=1,directories=1,location=1,menubar=1,toolbar=1,status=1');\">".format_string($typeconfig->integration_catalog_title,true)."</a>";

    echo '<div class="popupnotice">';
    print_string('popupresource', 'mediasite');
    echo '<br />';
    print_string('popupresourcelink', 'mediasite', $link);
    echo '</div>';

} else {
    // for now, let's assume every instance is an iFrame
    // Request the launch content with an iframe tag.
    $launchUrl = new moodle_url('/mod/mediasite/courses7_launch.php', array('id' => $id, 'siteid' => $siteid));

    echo '<iframe id="contentframe" class="mediasite_lti_courses_iframe" src="'.$launchUrl.'"></iframe>';
}

// Finish the page.
echo $OUTPUT->footer();
