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
 * Table log for displaying logs.
 *
 * @package    report_emaillog
 * @copyright  2016 UC Regents
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Table log class for displaying logs.
 *
 * @package    report_emaillog
 * @copyright  2016 UC Regents
 */
class report_emaillog_table_log extends table_sql {

    /** @var array list of user fullnames shown in report */
    private $userfullnames = array();

    /** @var array list of course short names shown in report */
    private $courseshortnames = array();

    /** @var stdClass filters parameters */
    private $filterparams;

    /**
     * Sets up the table_log parameters.
     *
     * @param string $uniqueid unique id of form.
     * @param stdClass $filterparams (optional) filter params.
     *     - int courseid: id of course
     *     - int date: Date from which logs to be viewed.
     */
    public function __construct($uniqueid, $filterparams = null) {
        parent::__construct($uniqueid);

        $this->set_attribute('class', 'reportemaillog generaltable generalbox');
        $this->filterparams = $filterparams;

        $this->define_columns(array('time', 'sender', 'recipient',  'recipient_email',
                'course', 'subject', 'message'));
        $this->define_headers(array(
                get_string('time'),
                get_string('sender', 'report_emaillog'),
                get_string('recipient', 'report_emaillog'),
                get_string('recipient_email', 'report_emaillog'),
                get_string('course'),
                get_string('subject', 'report_emaillog'),
                get_string('message', 'report_emaillog'),
                )
            );
        $this->collapsible(false);
        $this->sortable(false);
        $this->pageable(true);
    }

    /**
     * Generate the time column.
     *
     * @param stdClass $log emaillog data.
     * @return string HTML for the time column
     */
    public function col_time($log) {
        $recenttimestr = get_string('strftimerecent', 'core_langconfig');
        return userdate($log->timestamp, $recenttimestr);
    }

    /**
     * Generate the sender column.
     *
     * @param stdClass $log event data.
     * @return string HTML for the username column
     */
    public function col_sender($log) {
        if (!empty($log->sender) && !empty($this->userfullnames[$log->sender])) {
            $params = array('id' => $log->sender);
            if ($log->course) {
                $params['course'] = $log->course;
            }
            $username = html_writer::link(new moodle_url('/user/view.php', $params), $this->userfullnames[$log->sender]);
        } else {
            $username = '-';
        }
        return $username;
    }

    /**
     * Generate the recipient column.
     *
     * @param stdClass $log emaillog data.
     * @return string HTML for the recipient column
     */
    public function col_recipient($log) {
        if (!empty($log->recipient) && !empty($this->userfullnames[$log->recipient])) {
            $params = array('id' => $log->recipient);
            if ($log->course) {
                $params['course'] = $log->course;
            }
            $username = html_writer::link(new moodle_url('/user/view.php', $params), $this->userfullnames[$log->recipient]);
        } else {
            $username = '-';
        }
        return $username;
    }

    /**
     * Generate the course column.
     *
     * @param stdClass $log emaillog data.
     * @return string HTML for the course column.
     */
    public function col_course($log) {
        if (empty($log->course) || empty($this->courseshortnames[$log->course])) {
            return '-';
        } else {
            return $this->courseshortnames[$log->course];
        }
    }

    /**
     * Query the database. Store results in the object for use by build_table.
     *
     * @param int $pagesize size of page for paginated displayed table.
     * @param bool $useinitialsbar do you want to use the initials bar.
     */
    public function query_db($pagesize, $useinitialsbar = true) {
        global $DB;

        // Filter options.
        $joins = array();
        $params = array();

        if (!empty($this->filterparams->courseid) && $this->filterparams->courseid != SITEID) {
            $joins[] = "course = :course";
            $params['course'] = $this->filterparams->courseid;
        }

        if (!empty($this->filterparams->date)) {
            $joins[] = "timestamp > :date AND timestamp < :enddate";
            $params['date'] = $this->filterparams->date;
            $params['enddate'] = $this->filterparams->date + DAYSECS; // Show logs only for the selected date.
        }

        $where = implode(' AND ', $joins);
        $order = $this->filterparams->orderby;
        $sql = "SELECT emaillog.id, timestamp, course, post, posts.userid AS sender,
                        recipient_id AS recipient, recipient_email, subject, message
                  FROM {report_emaillog} emaillog
                  JOIN {forum_posts} posts ON emaillog.post = posts.id
                  JOIN {forum_discussions} discussions ON posts.discussion = discussions.id
                 WHERE $where
              ORDER BY $order";

        // Select emaillog data as an array of objects (each object is one log entry).
        $this->rawdata = $DB->get_records_sql($sql, $params, $this->get_page_start(), $this->get_page_size());

        $total = $DB->count_records('report_emaillog');
        $this->pagesize($pagesize, $total);

        // Set initial bars.
        if ($useinitialsbar && !$this->is_downloading()) {
            $this->initialbars($total > $pagesize);
        }

        // Update list of users and courses list which will be displayed on log page.
        $this->update_users_and_courses_used();
    }

    /**
     * Helper function to create list of course shortname and user fullname shown in log report.
     * This will update $this->userfullnames and $this->courseshortnames array with userfullname and courseshortname (with link),
     * which will be used to render logs in table.
     */
    public function update_users_and_courses_used() {
        global $SITE, $DB;

        $this->userfullnames = array();
        $this->courseshortnames = array($SITE->id => $SITE->shortname);
        $userids = array();
        $courseids = array();
        // For each log cache full username and course.
        // Get list of userids and courseids which will be shown in log report.
        foreach ($this->rawdata as $log) {
            if (!empty($log->sender) && !in_array($log->sender, $userids)) {
                $userids[] = $log->sender;
            }

            if (!empty($log->recipient) && !in_array($log->recipient, $userids)) {
                $userids[] = $log->recipient;
            }

            if (!empty($log->course) && ($log->course != $SITE->id) && !in_array($log->course, $courseids)) {
                $courseids[] = $log->course;
            }
        }

        // Get user fullname and put that in return list.
        if (!empty($userids)) {
            list($usql, $uparams) = $DB->get_in_or_equal($userids);
            $users = $DB->get_records_sql("SELECT id," . get_all_user_name_fields(true) . " FROM {user} WHERE id " . $usql,
                    $uparams);
            foreach ($users as $userid => $user) {
                $this->userfullnames[$userid] = fullname($user);
            }
        }

        // Get course shortname and put that in return list.
        if (!empty($courseids)) { // If all logs don't belog to site level then get course info.
            list($coursesql, $courseparams) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);
            $ccselect = ', ' . context_helper::get_preload_record_columns_sql('ctx');
            $ccjoin = "LEFT JOIN {context} ctx ON (ctx.instanceid = c.id AND ctx.contextlevel = :contextlevel)";
            $courseparams['contextlevel'] = CONTEXT_COURSE;
            $sql = "SELECT c.id,c.shortname $ccselect FROM {course} c
                   $ccjoin
                     WHERE c.id " . $coursesql;

            $courses = $DB->get_records_sql($sql, $courseparams);
            foreach ($courses as $courseid => $course) {
                $url = new moodle_url("/course/view.php", array('id' => $courseid));
                context_helper::preload_from_record($course);
                $context = context_course::instance($courseid, IGNORE_MISSING);
                // Method format_string() takes care of missing contexts.
                $this->courseshortnames[$courseid] = html_writer::link($url, format_string($course->shortname, true,
                        array('context' => $context)));
            }
        }
    }
}
