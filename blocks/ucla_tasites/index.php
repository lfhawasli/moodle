<?php
// This file is part of the UCLA TA sites block for Moodle - http://moodle.org/
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
 * Index page.
 *
 * @package    block_ucla_tasites
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require(dirname(__FILE__) . '/../../config.php');

require_oncE($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/blocks/ucla_tasites/block_ucla_tasites.php');
require_once($CFG->dirroot . '/blocks/ucla_tasites/tasites_form.php');
require_once($CFG->dirroot . '/local/ucla/lib.php');

// Setup parameters.
$courseid = required_param('courseid', PARAM_INT);
$formaction = optional_param('tasiteaction', null, PARAM_ALPHA);
$course = get_course($courseid);

// Check access.
require_login($courseid);
block_ucla_tasites::check_access($courseid);
if (block_ucla_tasites::is_tasite($courseid)) {
    throw new block_ucla_tasites_exception('erristasite');
}

// Setup page.
$PAGE->set_url(new moodle_url(
        '/blocks/ucla_tasites/index.php',
        array('courseid' => $courseid)
    ));
$PAGE->set_course($course);
$PAGE->set_title("$course->shortname: ".get_string('pluginname', 'block_ucla_tasites'));
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('base');
$PAGE->requires->js('/blocks/ucla_tasites/tasites_form.js');
$PAGE->requires->jquery();

// Get TA mappings.
$mapping = block_ucla_tasites::get_tasection_mapping($courseid);

// Setup form.
$formdata = array(
    'course' => $course,
    'mapping' => $mapping
);
$tasitesform = new tasites_form(null, $formdata, 'post', '', array('class' => 'tasites_form'));

// Link source to control panel on cancel.
$cpurl = new moodle_url('/blocks/ucla_control_panel/view.php',
        array('course_id' => $courseid));

$pagebody = '';

if ($tasitesform->is_cancelled()) {
    redirect($cpurl);
} else if ($formaction == 'create') {
    $typeinfo = array();
    $newtasite = null;
    // User wants to create TA site.
    if (($params = $tasitesform->get_data()) && confirm_sesskey()) {

        // What type of TA site does user want?

        // If course doesn't have section, then just create the TA site for
        // a given TA.
        $restrictgrouping = true;   // Default to creating restricted TA sites.
        if (!empty($mapping['bysection']['all'])) {
            $taidfound = false;
            foreach ($mapping['byta'] as $name => $uid) {
                if ($params->byta == $uid['ucla_id']) {
                    $taidfound = true;
                    $typeinfo['byta'][$name]['ucla_id'] = $uid['ucla_id'];
                }
            }
            if (!$taidfound) {
                throw new block_ucla_tasites_exception('errcantcreatetasite');
            }

        } else if (isset($params->bysection)) {
            // What section is user building?
            foreach ($params->bysection as $secnum => $val) {
                $typeinfo['bysection'][$secnum] = $mapping['bysection'][$secnum];
            }
        } else if (isset($params->byta)) {
            // Get TA to create TA site for.
            $taidnumber = str_pad($params->byta, 9, "0", STR_PAD_LEFT);
            if (empty($taidnumber)) {
                throw new block_ucla_tasites_exception('errcantcreatetasite');
            }
            $tauser = $DB->get_record('user', array('idnumber' => $taidnumber));
            if (empty($tauser)) {
                throw new block_ucla_tasites_exception('errcantcreatetasite');
            }
            $tafullname = fullname($tauser);
            $tasectionchoice = isset($params->tasectionchoice) ? $params->tasectionchoice : '';

            // Get TA info from mapping.
            $typeinfo['byta'][$tafullname] = $mapping['byta'][$tafullname];

            // Create TA site for entire course.
            if ($tasectionchoice == 'all' || isset($params->tasectionchoiceentire)) {
                $restrictgrouping = false;
            }
        }

        if (!empty($typeinfo)) {
            $newtasite = block_ucla_tasites::create_tasite($course, $typeinfo, $restrictgrouping);
        }
    }

    // If new TA site was created, then display success message.
    if (!empty($newtasite)) {
        $message = get_string('succreatesite', 'block_ucla_tasites', $newtasite->shortname);
        $redirect = new moodle_url('/blocks/ucla_tasites/index.php',
                array('courseid' => $courseid));
        flash_redirect($redirect, $message);
    } else {
         // Display form to process.
        ob_start();
        $tasitesform->display();
        $pagebody = ob_get_contents();
        ob_end_clean();
    }

} else if ($formaction == 'togglevisiblity') {
    // Show or hide given TA site.
    $tasiteid = required_param('tasite', PARAM_INT);
    $visiblity = block_ucla_tasites::toggle_visiblity($tasiteid);
    $tasite = get_course($tasiteid);

    $redirect = $url = new moodle_url('/blocks/ucla_tasites/index.php',
            array('courseid' => $courseid));

    if ($visiblity) {
        $message = get_string('sucshowsite', 'block_ucla_tasites', $tasite->shortname);
    } else {
        $message = get_string('suchidsite', 'block_ucla_tasites', $tasite->shortname);
    }

    flash_redirect($redirect, $message);

} else if ($formaction == 'togglegrouping') {
    // Change the default grouping for a TA site to be either
    // "Private Course Material" or "TA Section Materials".
    $tasiteid = required_param('tasite', PARAM_INT);

    // Get groupings for course.
    $tasite = get_course($tasiteid);
    $privatematerialsgrouping = $tasite->groupingpublicprivate;
    $tasitegrouping = $DB->get_field('groupings', 'id',
            array('courseid' => $tasite->id, 'idnumber' => block_ucla_tasites::GROUPINGID));

    $newgrouping = null;
    if ($tasite->defaultgroupingid == $privatematerialsgrouping) {
        $newgrouping = $tasitegrouping;
        $message = get_string('succhangedgroupingta', 'block_ucla_tasites', $tasite->shortname);
    } else if ($tasite->defaultgroupingid == $tasitegrouping) {
        $newgrouping = $privatematerialsgrouping;
        $message = get_string('succhangedgroupingpp', 'block_ucla_tasites', $tasite->shortname);
    }

    if (!empty($newgrouping)) {
        block_ucla_tasites::change_default_grouping($tasite->id, $newgrouping);
        $redirect = $url = new moodle_url('/blocks/ucla_tasites/index.php',
                array('courseid' => $courseid));
        flash_redirect($redirect, $message);
    } else {
        // No grouping to change, give error.
        print_error('errtogglegrouping', 'block_ucla_tasites');
    }

} else {
    ob_start();
    // Display any messages, if any.
    flash_display();

    // Show existing TA sites.
    $tasites = block_ucla_tasites::get_tasites($courseid);

    // If user can have a TA site, only show their TA site, if any.
    $ista = false;
    if (block_ucla_tasites::can_have_tasite($USER, $courseid)) {
        $ista = true;
        foreach ($tasites as $index => $tasite) {
            if (isset($tasite->enrol->ta_uclaids) &&
                    strpos($tasite->enrol->ta_uclaids, $USER->idnumber) === false) {
                unset($tasites[$index]);
            }
        }
    }

    $output = $PAGE->get_renderer('block_ucla_tasites');
    echo $output->render_tasites($tasites);

    if (block_ucla_tasites::can_make_tasite($USER, $course->id)) {
        // Display form to process.
        $tasitesform->display();
    }

    $pagebody = ob_get_contents();
    ob_end_clean();
}

// Display page.
echo $OUTPUT->header();
echo $OUTPUT->heading($PAGE->title);
echo $pagebody;
echo $OUTPUT->footer();
