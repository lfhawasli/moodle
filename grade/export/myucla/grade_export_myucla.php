<?php

require_once($CFG->dirroot.'/grade/export/lib.php');
require_once($CFG->dirroot.'/local/ucla/lib.php'); //Uses the ucla_validator function in order to determine whether or not a UID is valid
require_once($CFG->libdir . '/csvlib.class.php');

class grade_export_myucla extends grade_export {

    public $plugin = 'myucla';
    public $separator;
    public $fileextension;

    /**
     * Constructor should set up all the private variables ready to be pulled
     * @param object $course
     * @param int $groupid id of selected group, 0 means all
     * @param int $grouping id of selected grouping, 0 means none selected
     * @param stdClass $formdata The validated data from the grade export form.
     */
    public function __construct($course, $groupid, $formdata) {
        parent::__construct($course, $groupid, $formdata);
        $this->displaytype = GRADE_DISPLAY_TYPE_LETTER;
        $this->separator = 'tab';
        $this->fileextension = '.txt';
    }
    
    /**
     * Sends the course total (final grades) as a text (tab-delimited) or CSV file
	 *
	 * @return none
     */
    public function print_grades() {
        global $CFG;

        $export_tracking = $this->track_exports();

        $strgrades = get_string('grades');

        $shortname = format_string($this->course->shortname, true, array('context' => context_course::instance($this->course->id)));
        $downloadfilename = clean_filename("$shortname $strgrades");
        $csvexport = new csv_export_writer($this->separator);
        $csvexport->set_filename($downloadfilename, $this->fileextension);

        // Print names of all the fields
        $exporttitle = array();
        $exporttitle[] = get_string('fieldidnumber', 'gradeexport_myucla');
        $exporttitle[] = get_string('fieldname', 'gradeexport_myucla');
        $exporttitle[] = get_string('fieldgrade', 'gradeexport_myucla');
        $exporttitle[] = get_string('fieldremark', 'gradeexport_myucla');

        $csvexport->add_data($exporttitle);

        // Print all the lines of data.
        $geub = new grade_export_update_buffer();
        $gui = new graded_users_iterator($this->course, $this->columns, $this->groupid, $this->groupingid);
        $gui->require_active_enrolment($this->onlyactive);
        $gui->init();
        while ($userdata = $gui->next_user()) {

            $exportdata = array();
            $user = $userdata->user;

            $exportdata[] = $user->idnumber;
            $exportdata[] = strtoupper($user->lastname . ', ' . $user->firstname);

            foreach ($userdata->grades as $itemid => $grade) {
                if ($export_tracking) {
                    $status = $geub->track($grade);
                }

                $exportdata[] = $this->format_grade($grade, $this->displaytype);
                $exportdata[] = $this->format_feedback($userdata->feedbacks[$itemid]);
            }
            $csvexport->add_data($exportdata);
        }
        $gui->close();
        $geub->close();
        $csvexport->download_file();
        exit;
    }

    /**
     * Init object based using data from form
     * @param object $formdata
     */
    function process_form($formdata) {
        //Overrides parent function
        parent::process_form($formdata);
    }
}
