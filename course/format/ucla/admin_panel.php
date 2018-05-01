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
$context = context_course::instance($course->id);

$PAGE->set_pagelayout('incourse');

$title = get_string('adminpanel', 'format_ucla');
$node = $PAGE->settingsnav->find('courseadmin', navigation_node::TYPE_COURSE);

$PAGE->set_title($title);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add($title);
echo $OUTPUT->header();
echo $OUTPUT->heading($title);

if ($node) {
    // Delete nodes.
    $nodestoremove = array('otherusers' => navigation_node::TYPE_SETTING,
            'unenrolself' => navigation_node::TYPE_SETTING,
            'publish' => navigation_node::TYPE_SETTING,
            'categories' => navigation_node::TYPE_SETTING,
            'export' => navigation_node::TYPE_SETTING);

    foreach ($nodestoremove as $name => $type) {
        $ntr = $node->find($name, $type);
        $ntr->remove();
    }

    $questionnode = $node->find('questionbank', navigation_node::TYPE_CONTAINER);
    $importnode = $questionnode->find('import', navigation_node::TYPE_SETTING);
    $importnode->remove();

    // Rename "Enrolled users" to "Participants".
    $ntr = $node->find('review', navigation_node::TYPE_SETTING);
    $ntr->text = get_string('participants', 'format_ucla');

    // Rename "Gradebook Setup" to "Grades Setup".
    $ntr = $node->find('gradebooksetup', navigation_node::TYPE_SETTING);
    $ntr->text = get_string('gradessetup', 'format_ucla');

    // Rename "Dates" to "Review/edit dates".
    $ntr = $node->find('editdates', navigation_node::TYPE_SETTING);
    $ntr->text = get_string('revieweditdates', 'format_ucla');

    echo $OUTPUT->render_from_template('core/settings_link_page', ['node' => $node]);
}

echo $OUTPUT->footer();
