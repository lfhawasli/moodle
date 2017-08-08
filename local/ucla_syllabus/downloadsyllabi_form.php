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
 * Bulk download syllabi form definition.
 *
 * @copyright 2017 UC Regents
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package local_ucla_syllabus
 */

defined('MOODLE_INTERNAL') || die;
require_once("$CFG->libdir/formslib.php");

/**
 * Form used to download syllabi for a selected category and term in a zip file.
 *
 * @copyright 2017 UC Regents
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package local_ucla_syllabus
 */
class downloadsyllabi_form extends moodleform {
    /**
     * List of possible terms for the selected category.
     * @var array
     */
    private $terms = array();
    /**
     * The selected category.
     * @var object
     */
    private $category = null;

    /**
     * Add elements to the moodleform and initializes $terms.
     */
    public function definition() {
        global $CFG, $DB;

        $this->category = $this->_customdata['category'];

        // Retrieve the list of terms valid for the selected category.
        $this->terms = self::get_category_terms($this->category);

        $mform = $this->_form;
        $mform->addElement('select', 'term', get_string('selectedterm', 'local_ucla_syllabus'), $this->terms);
        $mform->setType('term', PARAM_ALPHANUM);
        $mform->setDefault('term', $CFG->currentterm);
        $mform->addElement('submit', 'retrievebutton', get_string('showsyllabi', 'local_ucla_syllabus'));
        $mform->addElement('submit', 'downloadbutton', get_string('downloadsyllabi', 'local_ucla_syllabus'));

        $mform->addElement('hidden', 'id', $this->category->id);
        $mform->setType('id', PARAM_INT);
    }

    /**
     * Checks if the provided term is valid.
     *
     * @param object $data
     * @param array $files
     */
    public function validation($data, $files) {
        global $DB;

        $errors = array();
        if (!array_key_exists($data['term'], $this->terms)) {
            $errors['term'] = get_string('errornocourses', 'local_ucla_syllabus');
        }
        return $errors;
    }
    /**
     * Recursively retrieves the terms of the courses that belong to
     * a category and all of its subcategories.
     *
     * @param object $category
     * @return array of terms
     */
    public function get_category_terms($category) {
        global $DB;

        $sql = "SELECT DISTINCT term
                  FROM {ucla_request_classes} urc
                 WHERE urc.department = :catshortname";
        $terms = $DB->get_records_select_menu('ucla_request_classes', 'department=?',
                array($category->idnumber), '', 'DISTINCT term AS id, term');
        // Check terms of each subcategory as well.
        if ($subcategories = $category->get_children()) {
            foreach ($subcategories as $subcategory) {
                $terms = array_merge($terms, self::get_category_terms($subcategory));
            }
        }        
        return terms_arr_sort($terms, true);
    }
    /**
     * Recursively retrieves courses with syllabi that belong the provided
     * category (and its subcategories) and term.
     *
     * @param object $category
     * @param string $term
     * @return array    Array of course objects
     */
    public function get_category_courses($category, $term) {
        global $DB;

        $sql = "SELECT DISTINCT c.*
                  FROM mdl_ucla_request_classes urc
                  JOIN mdl_ucla_syllabus us
                       ON urc.courseid = us.courseid
                  JOIN mdl_ucla_reg_classinfo rc
                       ON urc.srs = rc.srs AND urc.term = rc.term
                  JOIN mdl_course c
                       ON urc.courseid = c.id
                 WHERE urc.department = :catshortname
                       AND urc.term = :term
                       AND urc.hostcourse = 1
                       AND rc.enrolstat != 'X'
              ORDER BY rc.crsidx ASC, rc.secidx ASC";
        $params = array('catshortname' => $category->idnumber, 'term' => $term);
        $syllabi = $DB->get_records_sql($sql, $params);

        // Check syllabi of each subcategory as well.
        if ($subcategories = $category->get_children()) {
            foreach ($subcategories as $subcategory) {
                $syllabi = array_merge($syllabi, $this->get_category_courses($subcategory, $term));
            }
        }
        return $syllabi;
    }
}