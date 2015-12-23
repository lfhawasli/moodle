<?php
// This file is part of the UCLA theme plugin for Moodle - http://moodle.org/
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
 * Override Moodle's core enrol renderer.
 *
 * @package    theme_uclashared
 * @copyright  UC Regents 2014
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/enrol/renderer.php');

/**
 * Overriding the core enrol render (/enrol/renderer.php).
 *
 * @copyright  UC Regents 2014
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class theme_uclashared_core_enrol_renderer extends core_enrol_renderer {

    /**
     * Prevent users from editing groups.
     *
     * @param int $userid
     * @param array $groups
     * @param array $allgroups
     * @param bool $canmanagegroups
     * @param moodle_url $pageurl
     * @return string
     */
    public function user_groups_and_actions($userid, $groups, $allgroups, $canmanagegroups, $pageurl) {
        // Easiest solution: prevent editing of groups from this UI.
        return parent::user_groups_and_actions($userid, $groups, $allgroups,
                        false, $pageurl);
    }

    /**
     * Renders a course enrolment table
     *
     * Clone of the original that omits handles bulk operations for messaging
     * and enrollment handling.
     *
     * @param course_enrolment_table $table
     * @param moodleform $mform Form that contains filter controls
     * @return string
     */
    public function render_course_enrolment_users_table(course_enrolment_users_table $table,
            moodleform $mform) {
        global $CFG, $COURSE, $OUTPUT, $PAGE;

        $table->initialise_javascript();

        $buttons = $table->get_manual_enrol_buttons();
        $buttonhtml = '';
        if (count($buttons) > 0) {
            $buttonhtml .= html_writer::start_tag('div', array('class' => 'enrol_user_buttons'));
            foreach ($buttons as $button) {
                $buttonhtml .= $this->render($button);
            }
            $buttonhtml .= html_writer::end_tag('div');
        }
        
        $content = '';
        if (!empty($buttonhtml)) {
            $content .= $buttonhtml;
        }
        $content .= $mform->render();

        $content .= $this->output->render($table->get_paging_bar());

        // Check if the table has any bulk operations. If it does we want to wrap the table in a
        // form so that we can capture and perform any required bulk operations.
        if ($table->has_bulk_operations()) {
            // Handle POST actions to two different scripts.
            $enrolaction = $CFG->wwwroot.'/enrol/bulkchange.php"';
            $messageaction = $CFG->wwwroot.'/user/action_redir.php';

            $formattributes = array('action_messaging' => $CFG->wwwroot.'/user/action_redir.php',
                                    'action_enrolment' => $CFG->wwwroot.'/enrol/bulkchange.php',
                                    'id' => 'participantsform',
                                    'name' => 'participantsform', 'method' => 'post');

            $content .= html_writer::start_tag('form', $formattributes);
            $content .= html_writer::empty_tag('div');
            $content .= html_writer::start_tag('input', array('type' => 'hidden',
                                                       'name' => 'sesskey',
                                                       'value' => sesskey()));
            $content .= html_writer::start_tag('input', array('type' => 'hidden',
                                                       'name' => 'returnto',
                                                       'value' => s($PAGE->url->out(false))));

            foreach ($table->get_combined_url_params() as $key => $value) {
                if ($key == 'action') {
                    continue;
                }
                $content .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => $key, 'value' => $value));
            }

            $PAGE->requires->strings_for_js(array('noselectedusers'), 'local_ucla');            
        }    
        
        $content .= html_writer::table($table);

        if ($table->has_bulk_operations()) {
            $content .= html_writer::empty_tag('br');
            $content .= html_writer::start_tag('div', array('class' => 'buttons'));
            $content .= html_writer::empty_tag('input', array('type' => 'button',
                                                       'id' => 'checkall',
                                                       'value' => get_string('selectall')));
            $content .= html_writer::empty_tag('input', array('type' => 'button',
                                                       'id' => 'checknone',
                                                       'value' => get_string('deselectall')));

            $displaylist = array();
            $displaylist2 = array();

            $displaylist['messageselect.php'] = get_string('messageselectadd');
            $frontpagectx = context_course::instance(SITEID);
            $context = context_course::instance($COURSE->id);
            if (!empty($CFG->enablenotes) && has_capability('moodle/notes:manage', $frontpagectx) && $context->id != $frontpagectx->id) {
                $displaylist['addnote.php'] = get_string('addnewnote', 'notes');
                $displaylist['groupaddnote.php'] = get_string('groupaddnewnote', 'notes');
            }
            foreach ($table->get_bulk_user_enrolment_operations() as $operation) {
                $displaylist2[$operation->get_identifier()] = $operation->get_title();
            }

            $list = array(array('Messaging' => $displaylist), array('Enrolment' => $displaylist2));

            $content .= $OUTPUT->help_icon('withselectedusers');
            $content .= html_writer::tag('label', get_string("withselectedusers"), array('for' => 'formactionid'));
            $content .= html_writer::select($list, 'formaction', '', array('' => 'choosedots'), array('id' => 'formactionid'));

            $content .= html_writer::empty_tag('input', array('type' => 'hidden',
                                                       'name' => 'id',
                                                       'value' => $COURSE->id));
            $content .= html_writer::start_tag('noscript', array('style' => 'display:inline')); // hides go button

            $content .= html_writer::start_tag('div');
            $content .= html_writer::empty_tag('input', array('type' => 'submit',
                                                       'value' => get_string('ok')));
            $content .= html_writer::end_tag('div');
            $content .= html_writer::end_tag('noscript');

            $content .= html_writer::end_tag('div');
            $content .= html_writer::end_tag('div');
            $content .= html_writer::end_tag('form');

            $module = array('name' => 'core_user', 'fullpath' => '/user/module.js');
            $PAGE->requires->js_init_call('M.core_user.init_participation', null, false, $module);
        }        
        
        $content .= $this->output->render($table->get_paging_bar());
        if (!empty($buttonhtml)) {
            $content .= $buttonhtml;
        }
        return $content;
    }
}
