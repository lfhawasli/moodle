<?php
require_once(dirname(__FILE__) . '/../../config.php');
global $CFG, $PAGE, $USER, $DB;

require_once($CFG->dirroot . '/blocks/moodleblock.class.php');
require_once($CFG->dirroot .
        '/blocks/ucla_office_hours/block_ucla_office_hours.php');
require_once($CFG->dirroot .
        '/blocks/ucla_office_hours/officehours_form.php');
require_once($CFG->dirroot .
        '/blocks/ucla_tasites/block_ucla_tasites.php');
require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/user/lib.php');

$courseid = required_param('courseid', PARAM_INT);
$editid = required_param('editid', PARAM_INT);

if ($courseid == SITEID) {
    print_error('cannoteditsiteform');
}
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
require_login($course, true);

$edituser = $DB->get_record('user', array('id' => $editid), '*', MUST_EXIST);
$editusername = $edituser->firstname . ' ' . $edituser->lastname;

$context = context_course::instance($courseid);
$PAGE->set_context($context);
$PAGE->set_pagelayout('base');
$PAGE->set_url('/blocks/ucla_office_hours/officehours.php',
        array('courseid' => $courseid, 'editid' => $editid));

$pagetitle = get_string('header', 'block_ucla_office_hours', $editusername);
$PAGE->set_title($pagetitle);
$PAGE->set_heading($pagetitle);

set_editing_mode_button();

$PAGE->navbar->add($pagetitle);

// Make sure that entry can be edited.
if (!block_ucla_office_hours::allow_editing($context, $edituser->id)) {
    print_error('cannotedit', 'block_ucla_office_hours');
}

// Get office hours entry, if any.
$officehoursentry = $DB->get_record('ucla_officehours',
        array('courseid' => $courseid, 'userid' => $editid));
$emailsettings = $edituser->maildisplay;

// Get current course name. If it's a TA site, get the parent course.
if (block_ucla_tasites::is_tasite($courseid)) {
    $parentcourseid = block_ucla_tasites::get_tasite_enrol_meta_instance($courseid)->customint1;
    $parentcourse = $DB->get_record('course', array('id' => $parentcourseid));
    $currentcoursename = $parentcourse->shortname;
} else {
    $currentcoursename = $course->shortname;
}

// Get current course term.
$currentcourseterm = $DB->get_field('ucla_request_classes', 'term', array('courseid' => $courseid), IGNORE_MULTIPLE);

// Get user's enrolled courses and their names.
$userenrolments = $DB->get_records('user_enrolments',
        array('userid' => $editid));
$enrolledcourseids = array();
$coursenames = array();

foreach ($userenrolments as $userenrolment) {
    $enrol = $DB->get_record('enrol', array('id' => $userenrolment->enrolid));
    $sql = 'SELECT c.id, c.shortname
              FROM {course} c
              JOIN {ucla_request_classes} urc ON urc.courseid = c.id
             WHERE c.id = :id AND urc.term = :term';
    $enrolledcourse = $DB->get_record_sql($sql, array('id' => $enrol->courseid, 'term' => $currentcourseterm));
    // Don't include TA sites.
    if (!empty($enrolledcourse) && !block_ucla_tasites::is_tasite_enrol_meta_instance($enrol)) {
        $enrolledcourseids[] = $enrolledcourse->id;
        $coursenames[] = $enrolledcourse->shortname;
    }
}

$updateform = new officehours_form(null,
        array('courseid' => $courseid,
              'editid' => $editid,
              'editemail' => $edituser->email,
              'defaults' => $officehoursentry,
              'url' => $edituser->url,
              'emailsettings' => $emailsettings,
              'coursenames' => $coursenames,
              'currentcoursename' => $currentcoursename),
        'post',
        '',
        array('class' => 'officehours_form'));

// If the cancel button is clicked, return to 'Site Info' page.
if ($updateform->is_cancelled()) {
    $url = new moodle_url($CFG->wwwroot . '/course/view.php', array('id' => $courseid, 'topic' => 0));
    redirect($url);
}

echo $OUTPUT->header();
echo $OUTPUT->heading($pagetitle, 2, 'headingblock');

if ($data = $updateform->get_data()) { // Otherwise, process data.

    // Prepare new entry data.
    $newentry = new stdClass();
    $newentry->userid          = $editid;
    $newentry->modifierid      = $USER->id;
    $newentry->timemodified    = time();
    $newentry->officehours     = strip_tags(trim($data->officehours));
    $newentry->officelocation  = $data->office;
    $newentry->email           = $data->email;
    $newentry->phone           = $data->phone;
    $officelocationallcourses  = $data->officelocationallcourses;
    $officehoursallcourses     = $data->officehoursallcourses;

    // To update current course entry, must also update parent course or child TA course.
    $courseids = array($courseid);
    // If TA site, add parent course.
    if (isset($parentcourseid)) {
        $courseids[] = $parentcourseid;
    } else {
        // Check if user has a TA site.
        if (block_ucla_tasites::can_have_tasite($edituser, $courseid)) {
            $tasiteenrols = block_ucla_tasites::get_tasite_enrolments($courseid);
            if ($tasiteenrols[$editid]) {
                $courseids[] = $tasiteenrols[$editid]->courseid;
            }
        }
    }
    foreach ($courseids as $cid) {
        block_ucla_office_hours::update_office_hours($newentry, $cid, $editid);
    }

    // Update office hours and location for each course user is enrolled in.
    if ($officelocationallcourses || $officehoursallcourses) {
        if (!$officelocationallcourses) {
            unset($newentry->officelocation);
        }
        if (!$officehoursallcourses) {
            unset($newentry->officehours);
        }
        foreach ($enrolledcourseids as $enrolledcourseid) {
            $context = context_course::instance($enrolledcourseid);
            // Make sure they are able to have office hours in that course.
            if (block_ucla_office_hours::allow_editing($context, $editid)) {
                block_ucla_office_hours::update_office_hours($newentry, $enrolledcourseid, $editid);
            }
        }
    }

    // Check if editing user's profile needs to change (website or email settings).
    if ($data->website != $edituser->url || $data->emailsettings != $edituser->maildisplay) {
        $edituser->url = $data->website;
        $edituser->maildisplay = $data->emailsettings;
        unset($edituser->password);
        user_update_user($edituser);
    }

    // Display success message.
    echo $OUTPUT->notification(get_string('confirmation_default',
            'block_ucla_office_hours', $coursenames), 'notifysuccess');

    echo $OUTPUT->continue_button(new moodle_url('/course/view.php',
                    array('id' => $courseid, 'section' => 0)));

} else {
    $updateform->display();
}

echo $OUTPUT->footer();
