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
 * Past course quiz notice form definition.
 *
 * @package    local_ucla
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');

/**
 * Past course quiz notice form class.
 *
 * Used to create a form for notifying instructors about visible quizzes in past courses.
 *
 * @copyright   2016 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class past_course_visible_quiz_notice_form extends moodleform {
    /**
     * Generates quiz notice form.
     */
    public function definition() {
        $mform = $this->_form;
        $quizzes = $this->_customdata;

        // Display the regular syllabus alert.
        $mform->addElement('html', html_writer::tag('div', get_string('notice_quizhidden', 'local_ucla')));

        // Display a list of the visible quizzes.
        if (is_array($quizzes) || is_object($quizzes))
        {
            $listquizzes = array();
            foreach ($quizzes as $quizid => $quiz) {
                $url = new moodle_url('/mod/quiz/view.php', array('id' => $quizid));
                $listquizzes[] = html_writer::link($url, $quiz->name);
            }
            $mform->addElement('html', html_writer::alist($listquizzes));
        }
        // Display the prompt that asks users what they want to do with their quizzes.
        $mform->addElement('html', html_writer::tag('div', get_string('notice_quizhidden_ask', 'local_ucla')));

        // Create and display the submit buttons.
        $noticebuttons = array();
        $noticebuttons[] = $mform->createElement('submit', 'yesbutton',
            get_string('notice_quizhidden_yes', 'local_ucla'));
        $noticebuttons[] = $mform->createElement('submit', 'nobutton',
            get_string('notice_quizhidden_no', 'local_ucla'));
        $noticebuttons[] = $mform->createElement('submit', 'laterbutton',
            get_string('notice_quizhidden_later', 'local_ucla'));
        $mform->addGroup($noticebuttons, 'alert_buttons', '', array(' '), false);

        $mform->closeHeaderBefore('alert_buttons');
    }
}
