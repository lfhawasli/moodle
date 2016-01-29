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
 * Emaillog report renderer.
 *
 * @package    report_log
 * @copyright  2016 UC Regents
 */

defined('MOODLE_INTERNAL') || die;
use core\log\manager;

/**
 * Report emaillog renderable class.
 *
 * @package    report_log
 * @copyright  2016 UC Regents
 */
class report_emaillog_renderable implements renderable {

    /** @var int page number */
    public $page;

    /** @var int perpage records to show */
    public $perpage;

    /** @var stdClass course record */
    public $course;

    /** @var moodle_url url of report page */
    public $url;

    /** @var int selected date from which records should be displayed */
    public $date;

    /** @var int selected user id for which logs are displayed */
    public $userid;

    /** @var bool show courses */
    public $showcourses;

    /** @var bool show users */
    public $showusers;

    /** @var bool show report */
    public $showreport;

    /** @var bool show selector form */
    public $showselectorform;

    /** @var string order to sort */
    public $order;

    /** @var table_log table log which will be used for rendering logs */
    public $tablelog;

    /**
     * Constructor.
     *
     * @param stdClass|int $course (optional) course record or id
     * @param int $userid (optional) id of user to filter records for.
     * @param bool $showcourses (optional) show courses.
     * @param bool $showusers (optional) show users.
     * @param bool $showreport (optional) show report.
     * @param bool $showselectorform (optional) show selector form.
     * @param moodle_url|string $url (optional) page url.
     * @param int $date date (optional) timestamp of start of the day for which logs will be displayed.
     * @param int $page (optional) page number.
     * @param int $perpage (optional) number of records to show per page.
     * @param string $order (optional) sortorder of fetched records
     */

    public function __construct($course = 0, $userid = 0, $showcourses = false,
            $showusers = false, $showreport = true, $showselectorform = true, $url = "", $date = 0,
            $page = 0, $perpage = 100, $order = "timestamp DESC") {

        global $PAGE;

        // Use page url if empty.
        if (empty($url)) {
            $url = new moodle_url($PAGE->url);
        } else {
            $url = new moodle_url($url);
        }

        // Use site course id, if course is empty.
        if (!empty($course) && is_int($course)) {
            $course = get_course($course);
        }
        $this->course = $course;
        $this->userid = $userid;
        $this->date = $date;
        $this->page = $page;
        $this->perpage = $perpage;
        $this->url = $url;
        $this->order = $order;
        $this->showcourses = $showcourses;
        $this->showusers = $showusers;
        $this->showreport = $showreport;
        $this->showselectorform = $showselectorform;
    }

    /**
     * Return selected user fullname.
     *
     * @return string user fullname.
     */
    public function get_selected_user_fullname() {
        $user = core_user::get_user($this->userid);
        return fullname($user);
    }

    /**
     * Return list of courses to show in selector.
     *
     * @return array list of courses the user has posted in.
     */
    public function get_course_list() {
        global $DB, $SITE;

        $courses = array();

        $sitecontext = context_system::instance();
        // First check to see if we can override showcourses and showusers.
        $numcourses = $DB->count_records("course");
        if ($numcourses < COURSE_MAX_COURSES_PER_DROPDOWN && !$this->showcourses) {
            $this->showcourses = 1;
        }

        // Check if course filter should be shown.
        if (has_capability('report/log:view', $sitecontext) && $this->showcourses) {
            if ($courserecords = $DB->get_records("course", null, "fullname", "id,shortname,fullname,category")) {
                foreach ($courserecords as $course) {
                    if ($course->id == SITEID) {
                        $courses[$course->id] = format_string($course->fullname) . ' (' . get_string('site') . ')';
                    } else {
                        $courses[$course->id] = format_string(get_course_display_name_for_list($course));
                    }
                }
            }
            core_collator::asort($courses);
        }
        return $courses;
    }

    /**
     * Return list of users.
     *
     * @return array list of users.
     */
    public function get_user_list() {
        global $CFG, $SITE;

        $courseid = $SITE->id;
        if (!empty($this->course)) {
            $courseid = $this->course->id;
        }
        $context = context_course::instance($courseid);
        $limitfrom = empty($this->showusers) ? 0 : '';
        $limitnum  = empty($this->showusers) ? COURSE_MAX_USERS_PER_DROPDOWN + 1 : '';
        $courseusers = get_enrolled_users($context, '', 0, 'u.id, ' . get_all_user_name_fields(true, 'u'),
                null, $limitfrom, $limitnum);

        if (count($courseusers) < COURSE_MAX_USERS_PER_DROPDOWN && !$this->showusers) {
            $this->showusers = 1;
        }

        $users = array();
        if ($this->showusers) {
            if ($courseusers) {
                foreach ($courseusers as $courseuser) {
                     $users[$courseuser->id] = fullname($courseuser, has_capability('moodle/site:viewfullnames', $context));
                }
            }
            $users[$CFG->siteguest] = get_string('guestuser');
        }
        return $users;
    }

    /**
     * Return list of date options.
     *
     * @return array date options.
     */
    public function get_date_options() {
        global $SITE;

        $strftimedate = get_string("strftimedate");
        $strftimedaydate = get_string("strftimedaydate");

        // Get all the possible dates.
        // Note that we are keeping track of real (GMT) time and user time.
        // User time is only used in displays - all calcs and passing is GMT.
        $timenow = time(); // GMT.

        // What day is it now for the user, and when is midnight that day (in GMT).
        $timemidnight = usergetmidnight($timenow);

        // Put today up the top of the list.
        $dates = array("$timemidnight" => get_string("today").", ".userdate($timenow, $strftimedate) );

        // If course is empty, get it from frontpage.
        $course = $SITE;
        if (!empty($this->course)) {
            $course = $this->course;
        }
        if (!$course->startdate or ($course->startdate > $timenow)) {
            $course->startdate = $course->timecreated;
        }

        // Only show options for last 7 days, as the log is pruned after 7 days.
        $numdates = 1;
        while ($timemidnight > $course->startdate and $numdates < 7) {
            $timemidnight = $timemidnight - 86400;
            $timenow = $timenow - 86400;
            $dates["$timemidnight"] = userdate($timenow, $strftimedaydate);
            $numdates++;
        }
        return $dates;
    }

    /**
     * Setup table log.
     */
    public function setup_table() {

        $filter = new \stdClass();
        if (!empty($this->course)) {
            $filter->courseid = $this->course->id;
        } else {
            $filter->courseid = 0;
        }

        $filter->userid = $this->userid;
        $filter->date = $this->date;
        $filter->orderby = $this->order;

        $this->tablelog = new report_emaillog_table_log('report_emaillog', $filter);
        $this->tablelog->define_baseurl($this->url);
    }
}
