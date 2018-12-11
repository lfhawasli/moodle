<?php
// This file is part of the UCLA browse-by plugin for Moodle - http://moodle.org/
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
 * Class file to handle Browse-By course listings.
 *
 * @package    block_ucla_browseby
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class definition for browsing by course.
 *
 * @package    block_ucla_browseby
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_handler extends browseby_handler {
    /** @var string SQL fragment for SELECT. */
    const BROWSEALL_SQL_HELPER = "
        SELECT
            CONCAT(
                ubci.term, '-',
                ubci.srs, '-',
                ubii.uid
            ) AS 'recordsetid',
            ubci.section AS 'sectnum',
            ubci.course AS 'coursenum',
            ubci.activitytype,
            ubci.subjarea AS 'subj_area',
            ubci.url,
            ubci.term,
            ubci.srs,
            ubci.ses_grp_cd AS session_group,
            ubci.session AS session_code,
            ubci.coursetitlelong AS course_title,
            ubci.sectiontitle AS section_title,
            ubci.sect_enrl_stat_cd AS enrolstat,
            ubci.catlg_no AS course_code,
            ubci.activitytype,
            urc.courseid,
            COALESCE(user.id, user.idnumber, ubii.uid) AS userid,
            COALESCE(user.firstname, ubii.firstname) AS firstname,
            COALESCE(user.lastname, ubii.lastname) AS lastname,
            ubii.profcode,
            user.url AS userlink,
            user.alternatename,
            user.firstnamephonetic,
            user.lastnamephonetic,
            user.middlename,
            mco.shortname AS shortname,
            mco.idnumber AS idnumber,
            public_world_syllabus.id>0 AS has_public_world_syllabus,
            public_ucla_syllabus.id>0 AS has_public_ucla_syllabus,
            private_syllabus.id>0 AS has_private_syllabus
    ";

    /** @var string SQL fragment for ORDER BY. */
    const BROWSEALL_ORDER_HELPER = "
        ORDER BY session_group, subj_area, course_code, ubci.sect_no,
                 ubii.profcode, lastname, firstname
    ";

    /** @var string SQL fragment for syllabi information. */
    const BROWSEALL_SYLLABUS_HELPER = "
        LEFT JOIN {ucla_syllabus} public_world_syllabus
            ON  (urc.courseid = public_world_syllabus.courseid AND
                 public_world_syllabus.access_type=1)
        LEFT JOIN {ucla_syllabus} public_ucla_syllabus
            ON  (urc.courseid = public_ucla_syllabus.courseid AND
                 public_ucla_syllabus.access_type=2)
        LEFT JOIN {ucla_syllabus} private_syllabus
            ON  (urc.courseid = private_syllabus.courseid AND
                 private_syllabus.access_type=3)";

    /**
     * Returns what parameters are required for this handler.
     *
     * @return array
     */
    public function get_params() {
        // This uses division in breadcrumbs.
        return array('subjarea', 'user', 'division', 'alpha');
    }

    /**
     * Displays the course listing.
     *
     * @param array $args
     * @return array        Returns array of title and contents.
     */
    public function handle($args) {
        global $OUTPUT, $PAGE;

        $subjarea = null;
        $instructor = null;

        $t = false;
        $s = '';

        $termsselectwhere = '';
        $termsselectparam = null;

        // This is the parameters for one of the two possible query
        // types in this function.
        $param = array();

        $fullcourselist = array();

        if (isset($args['term'])) {
            $term = $args['term'];
            $termwhere = ' AND ubci.term = :term ';
            $param['term'] = $args['term'];
        } else {
            $termwhere = '';
        }

        $issummer = false;
        if (is_summer_term($term)) {
            $issummer = true;
        }

        if (isset($args['subjarea'])) {
            $subjarea = $args['subjarea'];

            // These are saved for way later.
            $termsselectwhere = 'subj_area = ?';
            $termsselectparam = array($subjarea);

            $subjareapretty = ucla_format_name(
                $this->get_pretty_subjarea($subjarea));

            $t = $subjareapretty;

            // Get all courses in this subject area from our browseall tables.
            $sql = self::BROWSEALL_SQL_HELPER . "
                FROM {ucla_browseall_classinfo} ubci
                INNER JOIN {ucla_browseall_instrinfo} ubii
                    USING(term, srs)
                LEFT JOIN {ucla_request_classes} urc
                    USING(term, srs)
                LEFT JOIN {user} user
                    ON ubii.uid = user.idnumber
                LEFT JOIN {course} mco
                    ON urc.courseid = mco.id
            " .
            self::BROWSEALL_SYLLABUS_HELPER .
            "   WHERE ubci.subjarea = :subjarea
                $termwhere
            " . self::BROWSEALL_ORDER_HELPER;

            $param['subjarea'] = $subjarea;
            $courseslist = $this->get_records_sql($sql, $param);

            // We came here from subjarea, so add some stuff.
            if (!empty($args['division'])) {
                // Add the generic division.
                subjarea_handler::alter_navbar();

                // Display the specific division's subjareas link.
                $navbarstr = get_string(
                    'subjarea_title',
                    'block_ucla_browseby',
                    $this->get_division($args['division'])
                );
            } else {
                // Came from all subjareas.
                $navbarstr = get_string('all_subjareas',
                    'block_ucla_browseby');
            }

            $urlobj = clone($PAGE->url);
            $urlobj->remove_params('subjarea');
            $urlobj->params(array('type' => 'subjarea'));
            $PAGE->navbar->add($navbarstr, $urlobj);

            $sql = "SELECT DISTINCT term
                        FROM {ucla_browseall_classinfo} ubci
                    WHERE ubci.subjarea = :subjarea";

        } else if (isset($args['user'])) {
            ucla_require_db_helper();

            // This is the local-system specific instructor's courses view.
            $instructor = $args['user'];

            $sqlhelp = instructor_handler::combined_select_sql_helper();

            // Query that selects courses for selected term only.
            // This will not include people enrolled only locally.
            $sql = self::BROWSEALL_SQL_HELPER . "
                FROM {ucla_browseall_classinfo} ubci
                LEFT JOIN {ucla_browseall_instrinfo} ubii
                    USING (term, srs)
                LEFT JOIN {ucla_request_classes} urc
                    USING (term, srs)
                LEFT JOIN {user} user
                    ON ubii.uid = user.idnumber
                LEFT JOIN {course} mco
                    ON urc.courseid = mco.id
                 " .
            self::BROWSEALL_SYLLABUS_HELPER .
            "   WHERE user.id = :user
                AND ubii.profcode != '03'
                    $termwhere
            " . self::BROWSEALL_ORDER_HELPER;

            $param['user'] = $instructor;

            $courseslist = $this->get_records_sql($sql, $param);

            // Get the actual user information from courses.
            $instruser = false;
            foreach ($courseslist as $course) {
                if ($instruser == false && $course->userid == $instructor) {
                    $instruser = $course;
                }
            }

            if (!$instruser) {
                print_error('noinstructorfound');
            } else {
                // Get names.
                $instruser->firstname = ucla_format_name($instruser->firstname);
                $instruser->lastname = ucla_format_name($instruser->lastname);

                $t = get_string('coursesbyinstr', 'block_ucla_browseby',
                    fullname($instruser));
            }

            if (!empty($args['alpha'])) {
                instructor_handler::alter_navbar();

                // This is from subjarea_handler, but I cannot
                // figure out how to generalize  and reuse.
                // Display the specific division's subjareas link.
                $navbarstr = get_string('instructorswith',
                    'block_ucla_browseby', strtoupper($args['alpha']));
            } else {
                // Came from all subjareas.
                $navbarstr = get_string('instructorsall',
                    'block_ucla_browseby');
            }

            $urlobj = clone($PAGE->url);
            $urlobj->remove_params('user');
            $urlobj->params(array('type' => 'instructor'));
            $PAGE->navbar->add($navbarstr, $urlobj);

            // Query for available terms (for the terms dropdown).
            $sql = "SELECT DISTINCT term
                    FROM {user} us
                    INNER JOIN {ucla_browseall_instrinfo} ubii
                            ON ubii.uid = us.idnumber
                    WHERE us.id = :user";

        } else {
            // There is no way to know what we are looking at.
            return array(false, false);
        }

        $s .= block_ucla_browseby_renderer::render_terms_selector(
            $args['term'], $sql, $param);

        if (empty($courseslist)) {
            $s .= $OUTPUT->box(get_string('coursesnotfound',
                    'block_ucla_browseby'), array('class' => 'errorbox'));
            return array($t, $s);
        }

        $uselocalcourses = $this->get_config('use_local_courses');

        // Takes a denormalized Array of course-instructors and returns a set of
        // courses into $fullcourseslist.
        $fullcourseslist = array();
        foreach ($courseslist as $course) {
            if (!empty($course->no_display)) {
                continue;
            }

            $k = make_idnumber($course);
            // Append instructors, since they could have duplicate rows.
            if (isset($fullcourseslist[$k])) {
                // CCLE-3989 - Supervising Instructor Shown On Course List.
                if (!empty($course->profcode) && $course->profcode != '03') {
                    $courseobj = $fullcourseslist[$k];
                    if ($instructorname = $this->fullname($course)) {
                        $courseobj->instructors[$course->userid] = $instructorname;
                    }
                }
            } else {
                $courseobj = new stdclass();
                $courseobj->dispname = ucla_make_course_title($course);

                if ($uselocalcourses && !empty($course->courseid)) {
                    $course->id = $course->courseid;
                    $courseobj->url = uclacoursecreator::build_course_url($course);
                } else if (!empty($course->url)) {
                    $courseobj->url = $course->url;
                } else if (!self::ignore_course($course)) {
                    $courseobj->url = $this->registrar_url(
                        $course
                    );

                    $courseobj->nonlinkdispname = $courseobj->dispname;
                    $courseobj->dispname = html_writer::tag(
                        'span', get_string('registrar_link',
                            'block_ucla_browseby'),
                        array('class' => 'registrar-link'));
                } else {
                    continue;
                }

                $cancelledmess = '';
                if (enrolstat_is_cancelled($course->enrolstat)) {
                    $cancelledmess = html_writer::tag('span',
                        get_string('cancelled'),
                        array('class' => 'ucla-cancelled-course')) . ' ';
                }

                $courseobj->fullname = $cancelledmess .
                    uclacoursecreator::make_course_title(
                        $course->course_title, $course->section_title
                    );

                // CCLE-3989 - Supervising Instructor Shown On Course List.
                if (!empty($course->profcode) && $course->profcode != '03') {
                    if ($instructorname = $this->fullname($course)) {
                        $courseobj->instructors[$course->userid] = $instructorname;
                    }
                }

                $courseobj->session_group = $course->session_group;
                $courseobj->coursenum = $course->coursenum;
            }

            $courseobj->has_public_world_syllabus = $course->has_public_world_syllabus;
            $courseobj->has_public_ucla_syllabus = $course->has_public_ucla_syllabus;
            $courseobj->has_private_syllabus = $course->has_private_syllabus;
            $courseobj->courseid = $course->courseid;

            $fullcourseslist[$k] = $courseobj;
        }

        // Flatten out instructors for display.
        foreach ($fullcourseslist as $k => $course) {
            $instrstr = '';
            if (!empty($course->instructors)) {
                $instrstr = implode(' / ', $course->instructors);
            }

            $course->instructors = $instrstr;
            $fullcourseslist[$k] = $course;
        }

        if ($issummer) {
            $sessionsplits = array();
            foreach ($fullcourseslist as $k => $fullcourse) {
                $session = $fullcourse->session_group;

                if (!isset($sessionsplits[$session])) {
                    $sessionsplits[$session] = array();
                }

                unset($fullcourse->session_group);

                $sessionsplits[$session][$k] = $fullcourse;
            }

            $table = new html_table();
            $table->id = 'browsebycourseslist';

            $table->head = block_ucla_browseby_renderer::ucla_browseby_course_list_headers();

            foreach ($sessionsplits as $session => $courses) {
                $sessioncell = new html_table_cell();
                $sessioncell->text = get_string(
                    'session_break', 'block_ucla_browseby', $session);

                $sessioncell->colspan = '4';
                $sessionrow = new html_table_row();
                $sessionrow->attributes['class'] = 'header summersession';
                $sessionrow->cells[] = $sessioncell;

                $subtable = block_ucla_browseby_renderer::ucla_browseby_courses_list($courses);

                $table->data[] = $sessionrow;
                $table->data = array_merge($table->data, $subtable->data);
            }

            $s .= html_writer::table($table);
        } else {
            foreach ($fullcourseslist as $k => $course) {
                unset($fullcourseslist[$k]->session_group);
            }

            $table = block_ucla_browseby_renderer::ucla_browseby_courses_list(
                $fullcourseslist);

            $s .= html_writer::table($table);
        }

        return array($t, $s);
    }

    /**
     * Returns user information, with a link if there is a provided URL in the
     * user table.
     *
     * @param stdClass $userinfo
     * @return string
     */
    public function fullname($userinfo) {
        if (empty($userinfo->firstname) || empty($userinfo->lastname)) {
            if (!empty($userinfo->firstname)) {
                $name = $userinfo->firstname;
            } else {
                $name = $userinfo->lastname;
            }
        } else {
            $name = ucla_format_name(fullname($userinfo));
        }

        if (!empty($userinfo->userlink)) {
            $userurl = $userinfo->userlink;

            if (strpos($userurl, 'http://') === false
                    && strpos($userurl, 'https://') === false) {
                $userurl = 'http://' . $userurl;
            }

            $name = html_writer::link(new moodle_url($userurl),
                $name, array('target' => '_blank'));
        }

        return $name;
    }

    /**
     * Returns link to schedule of classes for given course.
     *
     * @param stdClass $course
     * @return string
     */
    public static function registrar_url($course) {
        // CCLE-5854 - Use new registrar website.
        $page = get_config('local_ucla', 'registrarurl');
        $page .= '/ro/public/soc/Results?t=' . $course->term;
        $page .= '&sBy=classidnumber&id=' . $course->srs;
        $page .= '&btnIsInIndex=btn_inIndex';

        return $page;
    }
}
