<?php
// This file is part of the UCLA course creator plugin for Moodle - http://moodle.org/
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
 * Contains the subject area form for the course requestor.
 *
 * @package    tool_uclacourserequestor
 * @copyright  2011 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');
require_once(dirname(__FILE__) . '/requestor_shared_form.php');

/**
 * Form for requesting courses based on subject area.
 *
 * @package    tool_uclacourserequestor
 * @copyright  2011 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class requestor_subjarea_form extends requestor_shared_form {
    /**
     * Determines which string to use as the submit button.
     * @var string
     */
    public $type = 'builddept';

    /**
     * Returns an array of mForm elements to attach into the group.
     * @return array
     */
    public function specification() {
        $mform =& $this->_form;

        $subjareas = $this->_customdata['subjareas'];

        $pulldownsubject = array();
        foreach ($subjareas as $subjarea) {
            $s = $subjarea['subjarea'];
            $pulldownsubject[$s] = $s . ' - ' . $subjarea['subj_area_full'];
        }

        $spec = array();
        $spec[] =& $mform->createElement('select', 'subjarea', null,
            $pulldownsubject);

        return $spec;
    }
    /**
     * Returns the set of courses that should respond to the request method
     * and parameters. Called after all the data has been verified.
     * @param object $data responses from the fetch form.
     * @return array Sets of course-term-srs sets
     */
    public function respond($data) {
        $ci = $data->{$this->groupname};

        $term = $ci['term'];
        $sa = $ci['subjarea'];

        // Fetch all possible courses.
        $sac = get_courses_for_subj_area($term, $sa);

        // This means no registrar connection.
        if ($sac === false) {
            return false;
        }

        // The stored procedures returns fields with different names for
        // the same semantic data.
        // Destination - Source.
        $translators = array(
            'subj_area' => 'subjarea',
            'coursenum' => 'course',
            'sectnum' => 'section',
            'enrolstat' => 'sect_enrl_stat_cd'
        );

        // Translate everything.
        $sacreq = array();
        foreach ($sac as $course) {
            if (is_object($course)) {
                $course = get_object_vars($course);
            }

            // Add the stupid term to the data.
            $course['term'] = $term;

            foreach ($translators as $to => $from) {
                $course[$to] = $course[$from];
                unset($course[$from]);
            }

            $k = make_idnumber($course);

            $sacreq[$k] = $course;
        }

        // Get the request in the DB...
        $exists = get_course_requests($sacreq);

        foreach ($sacreq as $key => $course) {
            // Ignore request that either need to be ignored for department
            // builds or have already been requested, in terms of
            // prepping from registrar to requests.
            if (requestor_ignore_entry($course) || !empty($exists[$key])) {
                unset($sacreq[$key]);
            }
        }

        $newones = registrar_to_requests($sacreq);

        $hcs = array_merge($exists, $newones);

        // And their figure out their links.
        $sets = array();
        foreach ($hcs as $hc) {
            if ($hc) {
                $set = get_crosslist_set_for_host($hc);

                $sets[] = $set;
            }
        }

        return $sets;
    }
}