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
 * Log report renderer.
 *
 * @package    report_emaillog
 * @copyright  2016 UC Regents
 */
defined('MOODLE_INTERNAL') || die;

/**
 * Report log renderer's for printing reports.
 *
 * @package    report_emaillog
 * @copyright  2016 UC Regents
 */
class report_emaillog_renderer extends plugin_renderer_base {

    /**
     * Render emaillog report page.
     *
     * @param report_emaillog_renderable $reportlog object of report_emaillog.
     */
    public function render_report_emaillog_renderable(report_emaillog_renderable $reportlog) {
        if ($reportlog->showselectorform) {
            $this->report_selector_form($reportlog);
        }

        if ($reportlog->showreport) {
            $reportlog->tablelog->out($reportlog->perpage, true);
        }
    }

    /**
     * This function is used to generate and display selector form
     *
     * @param report_emaillog_renderable $reportlog log report.
     */
    public function report_selector_form(report_emaillog_renderable $reportlog) {
        echo html_writer::start_tag('form', array('class' => 'logselecform', 'action' => $reportlog->url, 'method' => 'get'));
        echo html_writer::start_div();
        echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'chooselog', 'value' => '1'));
        echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'showusers', 'value' => $reportlog->showusers));
        echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'showcourses',
            'value' => $reportlog->showcourses));

        $selectedcourseid = empty($reportlog->course) ? 0 : $reportlog->course->id;

        // Add course selector.
        $sitecontext = context_system::instance();
        $courses = $reportlog->get_course_list();
        if (!empty($courses) && $reportlog->showcourses) {
            echo html_writer::label(get_string('selectacourse'), 'menuid', false, array('class' => 'accesshide'));
            echo html_writer::select($courses, "id", $selectedcourseid, null);
        } else {
            $courses = array();
            $courses[$selectedcourseid] = get_course_display_name_for_list($reportlog->course) . (($selectedcourseid == SITEID) ?
                ' (' . get_string('site') . ') ' : '');
            echo html_writer::label(get_string('selectacourse'), 'menuid', false, array('class' => 'accesshide'));
            echo html_writer::select($courses, "id", $selectedcourseid, false);
            // Check if user is admin and this came because of limitation on number of courses to show in dropdown.
            if (has_capability('report/emaillog:view', $sitecontext)) {
                $a = new stdClass();
                $a->url = new moodle_url('/report/emaillog/index.php', array('chooselog' => 0,
                    'user' => $reportlog->userid,
                    'id' => $selectedcourseid, 'date' => $reportlog->date,
                    'showcourses' => 1, 'showusers' => $reportlog->showusers));
                $a->url = $a->url->out(false);
                print_string('logtoomanycourses', 'moodle', $a);
            }
        }

        // Add user selector.
        $users = $reportlog->get_user_list();

        if ($reportlog->showusers) {
            echo html_writer::label(get_string('selctauser'), 'menuuser', false, array('class' => 'accesshide'));
            echo html_writer::select($users, "user", $reportlog->userid, get_string("allparticipants"));
        } else {
            $users = array();
            if (!empty($reportlog->userid)) {
                $users[$reportlog->userid] = $reportlog->get_selected_user_fullname();
            } else {
                $users[0] = get_string('allparticipants');
            }
            echo html_writer::label(get_string('selctauser'), 'menuuser', false, array('class' => 'accesshide'));
            echo html_writer::select($users, "user", $reportlog->userid, false);
            $a = new stdClass();
            $a->url = new moodle_url('/report/emaillog/index.php', array('chooselog' => 0,
                'user' => $reportlog->userid,
                'id' => $selectedcourseid, 'date' => $reportlog->date,
                'showusers' => 1, 'showcourses' => $reportlog->showcourses));
            $a->url = $a->url->out(false);
            print_string('logtoomanyusers', 'moodle', $a);
        }

        // Add date selector.
        $dates = $reportlog->get_date_options();
        echo html_writer::label(get_string('date'), 'menudate', false, array('class' => 'accesshide'));
        echo html_writer::select($dates, "date", $reportlog->date, get_string("alldays"));

        echo html_writer::empty_tag('input', array('type' => 'submit', 'value' => get_string('gettheselogs')));

        echo html_writer::end_div();
        echo html_writer::end_tag('form');
    }
}

