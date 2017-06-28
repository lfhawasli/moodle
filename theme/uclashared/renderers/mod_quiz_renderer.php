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
 * Override Moodle's mod quiz renderer.
 *
 * @package    theme_uclashared
 * @copyright  UC Regents 2015
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/mod/quiz/renderer.php');

/**
 * Overriding the mod quiz render (/mod/quiz/renderer.php).
 *
 * @copyright  UC Regents 2015
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class theme_uclashared_mod_quiz_renderer extends mod_quiz_renderer {

    /**
     * Attempt Page
     *
     * @param quiz_attempt $attemptobj Instance of quiz_attempt
     * @param int $page Current page number
     * @param quiz_access_manager $accessmanager Instance of quiz_access_manager
     * @param array $messages An array of messages
     * @param array $slots Contains an array of integers that relate to questions
     * @param int $id The ID of an attempt
     * @param int $nextpage The number of the next page
     */
    public function attempt_page($attemptobj, $page, $accessmanager, $messages, $slots, $id,
            $nextpage) {
        $output = '';
        $output .= $this->header();
        $output .= $this->quiz_notices($messages);
        if ($attemptobj->get_quiz()->timelimit) {
            $output .= $this->quiz_warnings($attemptobj->get_quiz()->overduehandling);
        }
        $output .= $this->attempt_form($attemptobj, $page, $slots, $id, $nextpage);
        $output .= $this->footer();
        return $output;
    }

    /**
     * Returns any warnings.
     *
     * @param string $quiztype
     */
    public function quiz_warnings($quiztype) {
        if ($quiztype == 'autoabandon') {
            $warningheader = html_writer::tag('span', get_string('confirmstartwarningheader', 'local_ucla'),
                                     array('style' => 'color: red;'));
            $warningmsg = get_string('persistentquizwarningmessage', 'local_ucla');
            return $this->box($this->heading($warningheader . $warningmsg, 5));
        } else {
            return '';
        }
    }

    /*
     * Summary Page
     */
    /**
     * Create the summary page
     *
     * @param quiz_attempt $attemptobj
     * @param mod_quiz_display_options $displayoptions
     */
    public function summary_page($attemptobj, $displayoptions) {
        $output = '';
        $output .= $this->header();
        if ($attemptobj->get_quiz()->timelimit &&
                $attemptobj->get_state() != quiz_attempt::ABANDONED) {
            $output .= $this->quiz_warnings($attemptobj->get_quiz()->overduehandling);
        }
        $output .= $this->heading(format_string($attemptobj->get_quiz_name()));
        $output .= $this->heading(get_string('summaryofattempt', 'quiz'), 3);
        $output .= $this->summary_table($attemptobj, $displayoptions);
        $output .= $this->summary_page_controls($attemptobj);
        $output .= $this->footer();
        return $output;
    }

    /**
     * Creates any controls a the page should have.
     *
     * @param quiz_attempt $attemptobj
     */
    public function summary_page_controls($attemptobj) {
        $output = '';

        // Return to place button.
        if ($attemptobj->get_state() == quiz_attempt::IN_PROGRESS) {
            $button = new single_button(
                    new moodle_url($attemptobj->attempt_url(null, $attemptobj->get_currentpage())),
                    get_string('returnattempt', 'quiz'));
            $output .= $this->container($this->container($this->render($button),
                    'controls'), 'submitbtns mdl-align');
        }

        // Finish attempt button.
        $options = array(
            'attempt' => $attemptobj->get_attemptid(),
            'finishattempt' => 1,
            'timeup' => 0,
            'slots' => '',
            'sesskey' => sesskey(),
        );

        if ($attemptobj->get_state() == quiz_attempt::ABANDONED) {
            $message = $this->container(get_string('quizattemptabandoned', 'local_ucla'),
                    'alert alert-danger');
            $button = new single_button(
                    new moodle_url($attemptobj->view_url(), $options),
                    get_string('finishsummary', 'local_ucla'));
        } else {
            $button = new single_button(
                    new moodle_url($attemptobj->processattempt_url(), $options),
                    get_string('submitallandfinish', 'quiz'));
            $button->id = 'responseform';
            if ($attemptobj->get_state() == quiz_attempt::IN_PROGRESS) {
                $button->add_action(new confirm_action(get_string('confirmclose', 'quiz'), null,
                        get_string('submitallandfinish', 'quiz')));
            }

            $duedate = $attemptobj->get_due_date();
            $message = '';
            if ($attemptobj->get_state() == quiz_attempt::OVERDUE) {
                $message = get_string('overduemustbesubmittedby', 'quiz', userdate($duedate));

            } else if ($duedate) {
                $message = get_string('mustbesubmittedby', 'quiz', userdate($duedate));
            }
        }

        $output .= $this->countdown_timer($attemptobj, time());
        $output .= $this->container($message . $this->container(
                $this->render($button), 'controls'), 'submitbtns mdl-align');

        return $output;
    }

}