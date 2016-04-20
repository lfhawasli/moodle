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
 * TA site renderer.
 *
 * @package   block_ucla_tasites
 * @copyright 2015 UC Regents
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Displays existing TA sites.
 *
 * @package   block_ucla_tasites
 * @copyright 2015 UC Regents
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class existing_tasite implements renderable {
    /**
     * Constructor.
     *
     * @param stdClass $tasite  Contains information for TA site.
     */
    public function __construct($tasite) {
        $this->shortname = $tasite->shortname;
        $this->defaultgroupingname = $tasite->defaultgroupingname;
        $this->courseid = $tasite->enrol->courseid;
        $this->parentcourseid = $tasite->enrol->parentcourseid;
        $this->visible = $tasite->visible;
        $this->secnums = $tasite->enrol->secnums;
    }
}

/**
 * Renderer class.
 *
 * @package   block_ucla_tasites
 * @copyright 2015 UC Regents
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_ucla_tasites_renderer extends plugin_renderer_base {
    /**
     * Displays html to display information for an existing TA site and editing
     * links for managing.
     *
     * @param existing_tasite $tasite
     * @return string
     */
    public function render_existing_tasite(existing_tasite $tasite) {
        // Allow editing if user belongs to TA site or has admin access.
        $allowediting = false;
        $context = context_course::instance($tasite->courseid);
        if (has_capability('moodle/course:manageactivities', $context)) {
            $allowediting = true;
        }

        // Show site accessible grouping and allow TA to change it.
        $accessiblestr = '';
        $defaultgrouping = $tasite->defaultgroupingname;

        // If TA site is using a known grouping, then display friendly text.
        $icon = false;
        $icontext = '';
        if ($defaultgrouping == get_string('publicprivategroupingname', 'local_publicprivate')) {
            $accessiblestr = get_string('listavailablecourse', 'block_ucla_tasites');
            $icon = 't/lock';
            $icontext = get_string('togglegroupingsection', 'block_ucla_tasites');
        } else if ($defaultgrouping == get_string('tasitegroupingname', 'block_ucla_tasites')) {
            $accessiblestr = get_string('listavailablesections', 'block_ucla_tasites', $tasite->secnums);
            $icon = 't/locked';
            $icontext = get_string('togglegroupingcourse', 'block_ucla_tasites');
        } else {
            $accessiblestr = get_string('listgrouping', 'block_ucla_tasites',
                    $tasite->defaultgroupingname);
        }

        // Only allow TA to change grouping if there exists a "TA Section Materials" grouping.
        if ($allowediting && !empty($tasite->secnums) && !empty($icon)) {
            $accessiblestr .= $this->output->action_icon(new moodle_url('/blocks/ucla_tasites/index.php',
                    array('courseid' => $tasite->parentcourseid, 'tasiteaction' => 'togglegrouping',
                        'tasite' => $tasite->courseid)),
                new pix_icon($icon, $icontext),
                null, array('title' => $icontext));
        }
        $lines[] = $accessiblestr;

        // Show visiblity status and allow TA to change it.
        $visibility = $tasite->visible ? get_string('show') :
            get_string('hidden', 'block_ucla_tasites');
        if ($allowediting) {
            $togglestr = $tasite->visible ? 'hide' : 'show';
            $visibility .= $this->output->action_icon(new moodle_url('/blocks/ucla_tasites/index.php',
                    array('courseid' => $tasite->parentcourseid, 'tasiteaction' => 'togglevisiblity',
                        'tasite' => $tasite->courseid)),
                new pix_icon('t/'.$togglestr, get_string($togglestr)),
                null, array('title' => get_string($togglestr)));
        }
        $lines[] = get_string('listvisibility', 'block_ucla_tasites', $visibility);

        // Display in a Bootstrap panel.
        $title = $this->output->heading($tasite->shortname, 3, array('panel-title'));
        $titlelink = html_writer::link(new moodle_url('/course/view.php',
                array('id' => $tasite->courseid)), $title);
        $titlediv = html_writer::div($titlelink, 'panel-heading');
        $contents = html_writer::div(implode('<br />', $lines), 'panel-body');
        $panel = html_writer::div($titlediv . $contents, 'panel panel-default');

        return $panel;
    }

    /**
     * Renders multiple TA sites in a box.
     *
     * @param array $tasites Existing TA sites.
     */
    public function render_tasites($tasites) {
        $retval = '';
        if (empty($tasites)) {
            return $this->output->notification(get_string('noexistingtasites',
                    'block_ucla_tasites'), 'notifymessage');
        }

        $retval .= $this->output->heading(get_string('viewtasites', 'block_ucla_tasites'), 3);

        foreach ($tasites as $tasite) {
            $template = new existing_tasite($tasite);
            $retval .= $this->render($template);
        }

        return $retval;
    }
}