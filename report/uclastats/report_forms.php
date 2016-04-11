<?php
/**
 * Collection of forms used by UCLA stats console.
 *
 * @package    report
 * @subpackage uclastats
 * @copyright  UC Regents
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');

/**
 * Form to generate form to run a given report.
 */
class runreport_form extends moodleform {
    /**
     * Using custom data, determine what form fields to show
     *
     * Custom data can be in following format:
     * array (
     *  [index] => [form_name]
     *  [index] => [form_name] => array(values for dropdowns)
     * )
     *
     */
    public function definition(){
        global $CFG, $DB;
        $mform =& $this->_form;
        $fields = $this->_customdata['fields'];
        $is_high_load = $this->_customdata['is_high_load'];

        $mform->addElement('header', 'run-report-header',
                get_string('run_report', 'report_uclastats'));

        // does the report run a long time? if so, we need to note that
        if ($is_high_load) {
            $mform->addElement('html', html_writer::tag('div',
                    get_string('warning_high_load', 'report_uclastats'),
                    array('class' => 'alert alert-warning')));
        }

        if (!empty($fields)) {
            foreach ($fields as $field) {
                if (is_array($field)) {
                    $fieldname = $field;
                } else {
                    $fieldname = $field;
                }
                switch ($fieldname) {
                    case 'term':
                        // get terms
                        $terms = $DB->get_records_select_menu(
                                'ucla_request_classes', '1=1', null, null,
                                'DISTINCT term, term');
                        // format terms
                        foreach ($terms as $term => $value) {
                            $terms[$term] = $term;
                        }
                        $terms = terms_arr_sort($terms, true);
                        // need to give user friendly names
                        $mform->addElement('select', 'term', get_string('term',
                                'report_uclastats'), $terms);
                        $mform->setDefault('term', $CFG->currentterm);
                        break;
                    case    'threshold':
                        $mform->addElement('text', 'threshold',
                                get_string('threshold', 'report_uclastats'));
                        $mform->setDefault('threshold', 5);
                        break;
                    case 'subjarea':
                        $query = "
                            SELECT DISTINCT urs.subjarea, urs.subj_area_full
                                FROM {ucla_reg_subjectarea} AS urs
                                JOIN {ucla_request_classes} AS urc ON
                                    urc.department = urs.subjarea
                            WHERE urc.action = 'built'
                            ORDER BY urs.subjarea
                            ";
                        $subjareas = $DB->get_records_sql($query);

                        $s = array();
                        foreach ($subjareas as $subjarea) {
                            $s[$subjarea->subjarea] =
                                    ucla_format_name($subjarea->subj_area_full);
                        }
                        
                        $mform->addElement('select', 'subjarea', 
                                get_string('subjarea', 'report_uclastats'), $s);
                        break;
                    case 'category':
                        $displaylist = coursecat::make_categories_list('moodle/course:create');
                        $mform->addElement('select', 'category', get_string('category'), $displaylist);
                        break;
                    case 'startendmonth':
                    case 'optionaldatepicker':
                        $optional = array('optional' => false);
                        if ($fieldname == 'optionaldatepicker') {
                            $optional = array('optional' => true);
                        }
                        // Data picker for start and end months to run report.
                        $mform->addElement('date_selector', 'startdate', get_string('startdate', 'report_uclastats'), $optional);
                        $mform->addElement('date_selector', 'enddate', get_string('enddate', 'report_uclastats'), $optional);

                        if ($fieldname != 'optionaldatepicker') {
                            // Default to 6 months ago, rounded to start of month.
                            $mform->setDefault('startdate', strtotime(date("F 1, Y", strtotime("-6 months"))));
                        }
                        break;
                    case 'academicyear':
                        // Get terms.
                        $terms = $DB->get_records_select_menu(
                                'ucla_request_classes', '1=1', null, null,
                                'DISTINCT term, term');
                        // Map terms to academic years.
                        $academicyears = array();
                        foreach ($terms as $term => $value) {
                            $academicyears[$this->get_academic_year($term)] = null;
                        }
                        // Format years.
                        foreach ($academicyears as $year => $value) {
                            $academicyears[$year] = $year;
                        }
                        arsort($academicyears);
                        // Add element to form.
                        $mform->addElement('select', 'academicyear', get_string('academicyear',
                                'report_uclastats'), $academicyears);
                        $currentacademicyear = $this->get_academic_year($CFG->currentterm);
                        $mform->setDefault('academicyear', $currentacademicyear);
                }
            }
        } else {
            $mform->addElement('html', get_string('noparams', 'report_uclastats'));
        }
        $this->add_action_buttons(false,
                get_string('run_report', 'report_uclastats'));
    }

    /**
     * Helper function to map a term to its associated academic year.
     *
     * @param string $term
     * @return string
     */
    public function get_academic_year($term) {
        $termstr = strval($term);
        if (!ucla_validator('term', $termstr)) {
            throw new moodle_exception('invalidterm', 'report_uclastats');
        }
        $termyear = substr($termstr, 0, 2);
        switch (substr($termstr, 2, 1)) {
            case '1':
            case 'F':
                $academicyear = '20' . $termyear . '-20' . ($termyear + 1);
                break;
            case 'W':
            case 'S':
                $academicyear = '20' . ($termyear - 1) . '-20' . $termyear;
        }
        return $academicyear;
    }
}