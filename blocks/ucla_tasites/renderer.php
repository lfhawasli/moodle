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
    }
}

/**
 * Renderer class.
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
        // Show default grouping and allow TA to change it.
        $defaultgrouping = $tasite->defaultgroupingname;
        if ($defaultgrouping == get_string('publicprivategroupingname', 'local_publicprivate') ||
                $defaultgrouping == get_string('tasitegroupingname', 'block_ucla_tasites')) {
            $defaultgrouping .= $this->output->action_icon(new moodle_url('/blocks/ucla_tasites/index.php',
                    array('courseid' => $tasite->parentcourseid, 'tasiteaction' => 'togglegrouping',
                        'tasite' => $tasite->courseid)),
                new pix_icon('t/edit', get_string('edit')),
                null, array('title' => get_string('edit')));            
        }
        $lines[] = get_string('listgrouping', 'block_ucla_tasites', $defaultgrouping);

        // Show visiblity status and allow TA to change it.
        $visibility = $tasite->visible ? get_string('visible') :
            get_string('hidden', 'block_ucla_tasites');
        $togglestr = $tasite->visible ? 'hide' : 'show';
        $visibility .= $this->output->action_icon(new moodle_url('/blocks/ucla_tasites/index.php',
                array('courseid' => $tasite->parentcourseid, 'tasiteaction' => 'togglevisiblity',
                    'tasite' => $tasite->courseid)),
            new pix_icon('t/'.$togglestr, get_string($togglestr)),
            null, array('title' => get_string($togglestr)));
        $lines[] = get_string('liststatus', 'block_ucla_tasites', $visibility);

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