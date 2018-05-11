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

$courseid = required_param('courseid', PARAM_INT);
$section = optional_param('section', 0, PARAM_INT);

$PAGE->set_url('/course/format/ucla/admin_panel.php',
        array('courseid' => $courseid, 'section' => $section));

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

require_login($course);
$coursecontext = context_course::instance($course->id);

$PAGE->set_pagelayout('incourse');

$title = get_string('adminpanel', 'format_ucla');
$node = $PAGE->settingsnav->find('courseadmin', navigation_node::TYPE_COURSE);

$PAGE->set_title($title);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();
echo $OUTPUT->heading($title);

if ($node) {
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

    $node->add_node($container);

    // Settings and backup.
    $settings = array(
            'editsettings'     => navigation_node::TYPE_SETTING,
            'coursevisibility' => navigation_node::TYPE_SETTING,
            'import'           => navigation_node::TYPE_SETTING,
            'filters'          => navigation_node::TYPE_SETTING,
            'backup'           => navigation_node::TYPE_SETTING,
            'restore'          => navigation_node::TYPE_SETTING
    );

    $container = navigation_node::create(get_string('settingsandbackup', 'format_ucla'),
            null, navigation_node::TYPE_CONTAINER, null, 'settingsandbackup');

    foreach ($settings as $name => $type) {
        if ($setting = $node->find($name, $type)) {
            $setting->remove();
            $container->add_node($setting);
        }
    }

    $node->add_node($container);

    // MyUCLA.
    if ($container = $node->find('myucla', navigation_node::TYPE_CONTAINER)) {
        $container->remove();
        $node->add_node($container);
    }

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

    if ($setting = $node->find('gradebooksetup', navigation_node::TYPE_SETTING)) {
        $setting->remove();
        $container->add_node($setting);
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

    $node->add_node($container);

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

    $node->add_node($container);

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

    $node->add_node($container);

    // Remove leftover settings.
    $settings = array(
            'turneditingonoff' => navigation_node::TYPE_SETTING,
            'unenrolself'      => navigation_node::TYPE_SETTING,
            'coursereports'    => navigation_node::TYPE_CONTAINER,
            'coursebadges'     => navigation_node::TYPE_CONTAINER,
            'publish'          => navigation_node::TYPE_SETTING,
            'reset'            => navigation_node::TYPE_SETTING,
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

    if ($setting = $node->find('gradebooksetup', navigation_node::TYPE_SETTING)) {
        $setting->text = get_string('gradessetup', 'format_ucla');
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
    echo $OUTPUT->render_from_template('core/settings_link_page', ['node' => $node]);
}

echo $OUTPUT->footer();
