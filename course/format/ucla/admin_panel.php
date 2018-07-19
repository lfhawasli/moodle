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
 * @package   format_ucla
 * @copyright 2018 UC Regents
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/blocks/ucla_media/locallib.php');

$courseid = required_param('courseid', PARAM_INT);
$section = optional_param('section', 0, PARAM_INT);
$myuclatab = optional_param('tab', 0, PARAM_INT);

$PAGE->set_url('/course/format/ucla/admin_panel.php',
        array('courseid' => $courseid, 'section' => $section, 'tab' => $myuclatab));

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

require_login($course);
$coursecontext = context_course::instance($course->id);
require_capability('format/ucla:viewadminpanel', $coursecontext);

$PAGE->set_pagelayout('base');

// Set editing mode button if user has access to edit on course page.
$PAGE->set_other_editing_capability('moodle/course:manageactivities');
$gobackurl = new moodle_url('/course/view.php', array('id' => $courseid));
$buttons = $OUTPUT->edit_button($gobackurl);
$PAGE->set_button($buttons);

$title = get_string('adminpanel', 'format_ucla');
$node = $PAGE->settingsnav->find('courseadmin', navigation_node::TYPE_COURSE);

$myuclaactive = '';
$adminactive = 'active';
if ($myuclatab) {
    $myuclaactive = 'active';
    $adminactive = '';
}
$node->active = $adminactive;

$PAGE->set_title("$course->shortname: $title");
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();
echo $OUTPUT->heading($title);

if ($node) {
    $tabcontainer = navigation_node::create(get_string('courseadmin', 'format_ucla'), null, navigation_node::TYPE_CONTAINER);
    $tabcontainer->courseadmin = true;
    $tabcontainer->active = $adminactive;
    $rowcontainer = navigation_node::create('row1', null, navigation_node::TYPE_CONTAINER);

    // Manage material.
    $settings = array(
            'modifysections'    => navigation_node::TYPE_SETTING,
            'rearrangematerial' => navigation_node::TYPE_SETTING,
            'managecopyright'   => navigation_node::TYPE_SETTING,
            'managetasites'     => navigation_node::TYPE_SETTING,
            'editdates'         => navigation_node::TYPE_SETTING,
            'coursedownload'    => navigation_node::TYPE_SETTING,
            'tool_recyclebin'   => navigation_node::NODETYPE_LEAF
    );

    $container = navigation_node::create(get_string('managematerial', 'format_ucla'),
            null, navigation_node::TYPE_CONTAINER, null, 'managematerial');

    foreach ($settings as $name => $type) {
        if ($setting = $node->find($name, $type)) {
            $setting->remove();
            $container->add_node($setting);
        }
    }

    if (can_request_media($courseid)) {
        $setting = navigation_node::create(get_string('mrrequest', 'block_ucla_media'),
            new moodle_url('/blocks/ucla_media/bcast.php', array('courseid' => $courseid)));
        $container->add_node($setting, 'tool_recyclebin');
    }

    $rowcontainer->add_node($container);
    // Settings and backup.
    $settings = array(
            'editsettings'     => navigation_node::TYPE_SETTING,
            'coursevisibility' => navigation_node::TYPE_SETTING,
            'import'           => navigation_node::TYPE_SETTING,
            'filters'          => navigation_node::TYPE_SETTING,
            'backup'           => navigation_node::TYPE_SETTING,
            'restore'          => navigation_node::TYPE_SETTING,
            'reset'            => navigation_node::TYPE_SETTING
    );

    $container = navigation_node::create(get_string('settingsandbackup', 'format_ucla'),
            null, navigation_node::TYPE_CONTAINER, null, 'settingsandbackup');

    foreach ($settings as $name => $type) {
        if ($setting = $node->find($name, $type)) {
            $setting->remove();
            $container->add_node($setting);
        }
    }

    $rowcontainer->add_node($container);

    // Users and groups.
    $container = navigation_node::create(get_string('usersandgroups', 'format_ucla'),
            null, navigation_node::TYPE_CONTAINER, null, 'usersandgroups');

    if (has_capability('moodle/course:enrolreview', $coursecontext)) {
        $url = new moodle_url('/user/index.php', array('id'=>$course->id));
        $container->add(get_string('participants', 'format_ucla'), $url, navigation_node::TYPE_SETTING);
    }    

    if (has_capability('enrol/invitation:enrol', $coursecontext)) {
        $manager = new course_enrolment_manager($PAGE, $course);

        foreach ($manager->get_enrolment_instances() as $instance) {
            if ($instance->enrol == 'invitation' && $instance->status == ENROL_INSTANCE_ENABLED) {
                $url = new moodle_url('/enrol/invitation/invitation.php',
                        array('courseid' => $instance->courseid, 'id' => $instance->id));
                $container->add(get_string('inviteusers', 'format_ucla'), $url, navigation_node::TYPE_SETTING);
            }
        }
    }

    if (has_capability('moodle/grade:viewall', $coursecontext)) {
        $formatoptions = course_get_format($courseid)->get_format_options();
        if (empty($formatoptions['myuclagradelinkredirect'])) {
            $url = new moodle_url('/grade/report/index.php', array('id'=>$course->id));
        } else {
            $url = \theme_uclashared\modify_navigation::findgradelink();
        }
        $container->add(get_string('grades'), $url, navigation_node::TYPE_SETTING);
    }

    if (($course->groupmode || !$course->groupmodeforce) && has_capability('moodle/course:managegroups', $coursecontext)) {
        $url = new moodle_url('/group/index.php', array('id'=>$course->id));
        $container->add(get_string('groups'), $url, navigation_node::TYPE_SETTING);
    }

    if (has_capability('moodle/course:enrolconfig', $coursecontext) or has_capability('moodle/course:enrolreview', $coursecontext)) {
        $url = new moodle_url('/enrol/instances.php', array('id'=>$course->id));
        $container->add(get_string('enrollmentmethods', 'format_ucla'), $url, navigation_node::TYPE_SETTING);
    }

    if (has_capability('moodle/role:review', $coursecontext)) {
        $url = new moodle_url('/admin/roles/permissions.php', array('contextid'=>$coursecontext->id));
        $container->add(get_string('permissions', 'role'), $url, navigation_node::TYPE_SETTING);
    }

    $rowcontainer->add_node($container);
    $tabcontainer->add_node($rowcontainer);

    $rowcontainer = navigation_node::create('row2', null, navigation_node::TYPE_CONTAINER);

    // Logs and reports.
    $settings = array(
            'report_outline'       => navigation_node::TYPE_SETTING,
            'report_log'           => navigation_node::TYPE_SETTING,
            'report_loglive'       => navigation_node::TYPE_SETTING,
            'report_emaillog'      => navigation_node::TYPE_SETTING,
            'report_competency'    => navigation_node::TYPE_SETTING,
            'report_participation' => navigation_node::TYPE_SETTING
    );

    $container = navigation_node::create(get_string('logsandreports', 'format_ucla'),
            null, navigation_node::TYPE_CONTAINER, null, 'logsandreports');

    foreach ($settings as $name => $type) {
        if ($setting = $node->find($name, $type)) {
            $setting->remove();
            $container->add_node($setting);
        }
    }

    $rowcontainer->add_node($container);

    // Additional settings.
    $settings = array(
            'questions'                    => navigation_node::TYPE_SETTING,
            'mediasite_course_settings'    => navigation_node::TYPE_SETTING,
            'kalturamediagallery-settings' => navigation_node::NODETYPE_LEAF,
            'repositories'                 => navigation_node::TYPE_SETTING,
            'newbadge'                     => navigation_node::TYPE_SETTING,
            'coursebadges'                 => navigation_node::TYPE_SETTING,
            // Uncategorized settings.
            'coursecompletion'             => navigation_node::TYPE_SETTING,
            'coursetags'                   => navigation_node::TYPE_SETTING,
            'outputs'                      => navigation_node::TYPE_SETTING
    );

    $container = navigation_node::create(get_string('additionalsettings', 'format_ucla'),
            null, navigation_node::TYPE_CONTAINER, null, 'additionalsettings');

    foreach ($settings as $name => $type) {
        if ($setting = $node->find($name, $type)) {
            $setting->remove();
            $container->add_node($setting);
        }
    }

    $rowcontainer->add_node($container);
    $tabcontainer->add_node($rowcontainer);
    $node->add_node($tabcontainer);

    // MyUCLA.
    $tabcontainer = navigation_node::create(get_string('myucla', 'format_ucla'),
            null, navigation_node::TYPE_CONTAINER, null, 'myuclatab');
    $tabcontainer->myuclatab = true;
    $tabcontainer->active = $myuclaactive;
    $tabcontainer->tabtext = get_string('myucla', 'format_ucla');
    $rowcontainer = navigation_node::create('', null, navigation_node::TYPE_CONTAINER);

    if ($container = $node->find('myucla', navigation_node::TYPE_CONTAINER)) {
        $tabcontainer->row = array();
        
        // Check to see if this is a crosslisted course.
        if ($DB->count_records('ucla_request_classes', array('courseid' => $courseid)) > 1) {
            foreach ($container->children as $key => $crosscourse) {
                $tmprow = new stdClass();
                $tmprow->column = array();
                $tmprow->firstcolumn = $crosscourse;
                foreach ($crosscourse->children as $key => $column) {
                    $tmprow->column[] = $column;
                }
                $tabcontainer->row[] = $tmprow;
            }
        } else {
            $tmprow = new stdClass();
            $tmprow->column = array();
            foreach ($container->children as $key => $column) {
                $tmprow->column[] = $column;
            }
            $tabcontainer->row[] = $tmprow;
        }

        $container->remove();
        $rowcontainer->add_node($container);
        $tabcontainer->add_node($rowcontainer);

        $node->add_node($tabcontainer);
    }

    // Support.
    $viewsupport = has_capability('format/ucla:viewsupport', $PAGE->context);
    if ($viewsupport && $courses = $DB->get_records('ucla_request_classes', array('courseid' => $courseid))) {
        $tabcontainer = navigation_node::create(get_string('support', 'format_ucla'),
                null, navigation_node::TYPE_CONTAINER, null, 'supporttab');
        $tabcontainer->tab = true;
        $tabcontainer->tabtext = get_string('support', 'format_ucla');

        $rowcontainer = navigation_node::create('row1', null, navigation_node::TYPE_CONTAINER);

        $container = navigation_node::create(get_string('tools', 'format_ucla'),
                null, navigation_node::TYPE_CONTAINER, null, 'logsandreports');

        $linkarguments = array(
            'console' => 'prepoprun',
            'courseid'    => $courseid
        );
        $url = new moodle_url($CFG->wwwroot. '/admin/tool/uclasupportconsole/index.php',
                $linkarguments);
        $setting = navigation_node::create(get_string('runprepop', 'format_ucla'),
                $url, navigation_node::TYPE_SETTING);
        $container->add_node($setting);

        $linkarguments = array(
            'console' => 'pushgrades',
            'courseid'    => $courseid
        );
        $url = new moodle_url($CFG->wwwroot. '/admin/tool/uclasupportconsole/index.php',
                $linkarguments);
        $setting = navigation_node::create(get_string('pushgrades', 'format_ucla'),
                $url, navigation_node::TYPE_SETTING);
        $container->add_node($setting);

        $tabcontainer->add_node($rowcontainer);

        $rowcontainer->add_node($container);

        foreach ($courses as $course) {
            $coursecode = $course->department . " " . $course->course;
            $courseterm = $course->term;
            $coursesrs = $course->srs;

            $rowcontainer = navigation_node::create('row2', null, navigation_node::TYPE_CONTAINER);

            $container = navigation_node::create($coursecode, null, navigation_node::TYPE_CONTAINER, null);

            $linkarguments = array(
                'console' => 'ccle_courseinstructorsget',
                'term'    => $courseterm,
                'srs'     => $coursesrs
            );
            $url = new moodle_url($CFG->wwwroot. '/admin/tool/uclasupportconsole/index.php',
                    $linkarguments);
            $setting = navigation_node::create(get_string('getinsts', 'format_ucla'),
                    $url, navigation_node::TYPE_SETTING);
            $container->add_node($setting);

            $linkarguments = array(
                'console' => 'ccle_roster_class',
                'term'    => $courseterm,
                'srs'     => $coursesrs
            );
            $url = new moodle_url($CFG->wwwroot. '/admin/tool/uclasupportconsole/index.php',
                    $linkarguments);
            $setting = navigation_node::create(get_string('getstdroster', 'format_ucla'),
                    $url, navigation_node::TYPE_SETTING);
            $container->add_node($setting);

            $rowcontainer->add_node($container);

            $tabcontainer->add_node($rowcontainer);
        }
        $node->add_node($tabcontainer);
    }

    // Remove leftover settings.
    $settings = array(
            'turneditingonoff' => navigation_node::TYPE_SETTING,
            'unenrolself'      => navigation_node::TYPE_SETTING,
            'coursereports'    => navigation_node::TYPE_CONTAINER,
            'gradebooksetup'   => navigation_node::TYPE_SETTING,
            'coursebadges'     => navigation_node::TYPE_CONTAINER,
            'publish'          => navigation_node::TYPE_SETTING,
            'questionbank'     => navigation_node::TYPE_CONTAINER,
            'users'            => navigation_node::TYPE_CONTAINER
    );

    foreach ($settings as $name => $type) {
        if ($setting = $node->find($name, $type)) {
            $setting->remove();
        }
    }

    // Rename settings.
    if ($setting = $node->find('editdates', navigation_node::TYPE_SETTING)) {
        $setting->text = get_string('revieweditdates', 'format_ucla');
    }

    if ($setting = $node->find('report_competency', navigation_node::TYPE_SETTING)) {
        $setting->text = get_string('competency', 'format_ucla');
    }

    if ($setting = $node->find('questions', navigation_node::TYPE_SETTING)) {
        $setting->text = get_string('questionbank', 'format_ucla');
    }

    if ($setting = $node->find('mediasite_course_settings', navigation_node::TYPE_SETTING)) {
        $setting->text = get_string('mediasitesettings', 'format_ucla');
    }

    if ($setting = $node->find('kalturamediagallery-settings', navigation_node::NODETYPE_LEAF)) {
        $setting->text = get_string('mediagallery', 'format_ucla');
    }

    if ($setting = $node->find('newbadge', navigation_node::TYPE_SETTING)) {
        $setting->text = get_string('newbadge', 'format_ucla');
    }

    // Render admin panel.
    echo $OUTPUT->render_from_template('format_ucla/admin_panel', ['node' => $node]);
}

echo $OUTPUT->footer();
