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
 * Override Moodle's core backup renderer.
 *
 * @package    theme_uclashared
 * @copyright  UC Regents 2014
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->dirroot . '/backup/util/ui/renderer.php');
require_once($CFG->dirroot . '/blocks/ucla_tasites/block_ucla_tasites.php');

/**
 * Overriding the core backup render (backup/util/ui/renderer.php).
 *
 * @copyright  UC Regents 2014
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class theme_uclashared_core_backup_renderer extends core_backup_renderer {

    /**
     * Displays the general information about a backup file with non-standard format
     *
     * @param moodle_url $nextstageurl URL to send user to
     * @param array $details basic info about the file (format, type)
     * @return string HTML code to display
     */
    public function backup_details_nonstandard($nextstageurl, array $details) {

        $html = html_writer::start_tag('div', array('class' => 'backup-restore nonstandardformat'));
        $html .= html_writer::start_tag('div', array('class' => 'backup-section'));
        $html .= $this->output->heading(get_string('backupdetails', 'backup'), 2, 'header');
        // START UCLA MOD: CCLE-3023 - restore in Moodle2.x site menu block is  not displayed and not default to UCLA format
        // Friendlier notice to users
        //$html .= $this->output->box(get_string('backupdetailsnonstandardinfo', 'backup'), 'noticebox');
        $html .= $this->output->notification(get_string('backupdetailsnonstandardinfo', 'backup',
                get_string('backupformat' . $details['format'], 'backup')), 'notifymessage');
        // END UCLA MOD: CCLE-3023
        $html .= $this->backup_detail_pair(
                get_string('backupformat', 'backup'), get_string('backupformat' . $details['format'], 'backup'));
        $html .= $this->backup_detail_pair(
                get_string('backuptype', 'backup'), get_string('backuptype' . $details['type'], 'backup'));
        $html .= html_writer::end_tag('div');
        $html .= $this->output->single_button($nextstageurl, get_string('continue'), 'post');
        $html .= html_writer::end_tag('div');

        return $html;
    }

    /**
     * Displays a course selector for restore
     *
     * @param moodle_url $nextstageurl
     * @param bool $wholecourse true if we are restoring whole course (as with backup::TYPE_1COURSE), false otherwise
     * @param restore_category_search $categories
     * @param restore_course_search $courses
     * @param int $currentcourse
     * @return string
     */
    public function course_selector(moodle_url $nextstageurl, $wholecourse = true,
            restore_category_search $categories = null, restore_course_search $courses = null, $currentcourse = null) {
        global $CFG, $PAGE;
        require_once($CFG->dirroot . '/course/lib.php');

        $nextstageurl->param('sesskey', sesskey());

        $form = html_writer::start_tag('form', array('method' => 'post', 'action' => $nextstageurl->out_omit_querystring()));
        foreach ($nextstageurl->params() as $key => $value) {
            $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => $key, 'value' => $value));
        }

        $hasrestoreoption = false;

        $html = html_writer::start_tag('div', array('class' => 'backup-course-selector backup-restore'));

        if ($wholecourse && !empty($currentcourse)) {
            // Current course.
            $hasrestoreoption = true;
            $html .= $form;
            $html .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'targetid', 'value' => $currentcourse));
            $html .= html_writer::start_tag('div', array('class' => 'bcs-current-course backup-section'));
            $html .= $this->output->heading(get_string('restoretocurrentcourse', 'backup'), 2, array('class' => 'header'));
            $html .= $this->backup_detail_input(get_string('restoretocurrentcourseadding', 'backup'),
                    'radio', 'target', backup::TARGET_CURRENT_ADDING, array('checked' => 'checked'));
            //$html .= $this->backup_detail_input(get_string('restoretocurrentcoursedeleting', 'backup'), 'radio', 'target', backup::TARGET_CURRENT_DELETING);
            // BEGIN UCLA MOD: CCLE-3446-Disable-course-delete-option-from-course-restore
            if (has_capability('local/ucla:deletecoursecontentsandrestore', context_system::instance())) {
                $html .= $this->backup_detail_input(get_string('restoretocurrentcoursedeleting', 'backup'),
                        'radio', 'target', backup::TARGET_CURRENT_DELETING);

                // BEGIN UCLA MOD: CCLE-4416-Prompt-overwriting-warning
                // Prompt user to back-up course content that will be overriden.
                global $COURSE;
                $PAGE->requires->yui_module('moodle-local_ucla-restoreoverwritewarning', 'M.core_backup.course_deletion_check', array(array('courseid' => $COURSE->id)));
                // END UCLA MOD CCLE-4416
            }
            // END UCLA MOD: CCLE-3446

            $html .= $this->backup_detail_pair('', html_writer::empty_tag('input',
                    array('type' => 'submit', 'value' => get_string('continue'))));
            $html .= html_writer::end_tag('div');
            $html .= html_writer::end_tag('form');
        }

        if ($wholecourse && !empty($categories) && ($categories->get_count() > 0 || $categories->get_search())) {
            // New course.
            $hasrestoreoption = true;
            $html .= $form;
            $html .= html_writer::start_tag('div', array('class' => 'bcs-new-course backup-section'));
            $html .= $this->output->heading(get_string('restoretonewcourse', 'backup'), 2, array('class' => 'header'));
            $html .= $this->backup_detail_input(get_string('restoretonewcourse', 'backup'),
                    'radio', 'target', backup::TARGET_NEW_COURSE, array('checked' => 'checked'));
            $html .= $this->backup_detail_pair(get_string('selectacategory', 'backup'), $this->render($categories));
            $html .= $this->backup_detail_pair('', html_writer::empty_tag('input',
                    array('type' => 'submit', 'value' => get_string('continue'))));
            $html .= html_writer::end_tag('div');
            $html .= html_writer::end_tag('form');
        }

        if (!empty($courses) && ($courses->get_count() > 0 || $courses->get_search())) {
            // Existing course.
            $hasrestoreoption = true;
            $html .= $form;
            $html .= html_writer::start_tag('div', array('class' => 'bcs-existing-course backup-section'));
            $html .= $this->output->heading(get_string('restoretoexistingcourse', 'backup'), 2, array('class' => 'header'));
            if ($wholecourse) {
                $html .= $this->backup_detail_input(get_string('restoretoexistingcourseadding', 'backup'),
                        'radio', 'target', backup::TARGET_EXISTING_ADDING, array('checked' => 'checked'));
                $html .= $this->backup_detail_input(get_string('restoretoexistingcoursedeleting', 'backup'),
                        'radio', 'target', backup::TARGET_EXISTING_DELETING);
                $html .= $this->backup_detail_pair(get_string('selectacourse', 'backup'), $this->render($courses));
            } else {
                // We only allow restore adding to existing for now. Enforce it here.
                $html .= html_writer::empty_tag('input', array('type' => 'hidden',
                    'name' => 'target', 'value' => backup::TARGET_EXISTING_ADDING));
                $courses->invalidate_results(); // Clean list of courses.
                $courses->set_include_currentcourse(); // Show current course in the list.
                $html .= $this->backup_detail_pair(get_string('selectacourse', 'backup'), $this->render($courses));
            }
            $html .= $this->backup_detail_pair('', html_writer::empty_tag('input',
                    array('type' => 'submit', 'value' => get_string('continue'))));
            $html .= html_writer::end_tag('div');
            $html .= html_writer::end_tag('form');
        }

        if (!$hasrestoreoption) {
            echo $this->output->notification(get_string('norestoreoptions', 'backup'));
        }

        $html .= html_writer::end_tag('div');
        return $html;
    }

    /**
     * Displays the import course selector
     *
     * @param moodle_url $nextstageurl
     * @param import_course_search $courses
     * @return string
     */
    public function import_course_selector(moodle_url $nextstageurl, import_course_search $courses = null) {
        $html = html_writer::start_tag('div', array('class' => 'import-course-selector backup-restore'));
        $html .= html_writer::start_tag('form', array('method' => 'post', 'action' => $nextstageurl->out_omit_querystring()));
        foreach ($nextstageurl->params() as $key => $value) {
            $html .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => $key, 'value' => $value));
        }
        // We only allow import adding for now. Enforce it here.
        $html .= html_writer::empty_tag('input', array('type' => 'hidden',
            'name' => 'target', 'value' => backup::TARGET_CURRENT_ADDING));
        $html .= html_writer::start_tag('div', array('class' => 'ics-existing-course backup-section'));
        $html .= $this->output->heading(get_string('importdatafrom'), 2, array('class' => 'header'));

        $search = new local_ucla_import_course_search(array('url' => $nextstageurl));
        $html .= $this->backup_detail_pair(get_string('selectacourse', 'backup'), $this->render($search));

        $html .= html_writer::end_tag('div');
        $html .= html_writer::end_tag('form');
        $html .= html_writer::end_tag('div');
        return $html;
    }

    /**
     * Renders an import course search object
     *
     * @param import_course_search $component
     * @return string
     */
    public function render_local_ucla_import_course_search(import_course_search $component) {
        global $CFG;
        $url = $component->get_url();
        $output = html_writer::start_tag('div', array('class' => 'import-course-search'));
        $output .= html_writer::start_tag('div', array('class' => 'ics-search'));
        $output .= html_writer::empty_tag('input', array('type' => 'text',
            'name' => restore_course_search::$VAR_SEARCH, 'value' => $component->get_search()));
        $output .= html_writer::empty_tag('input', array('type' => 'submit',
            'name' => 'searchcourses', 'value' => get_string('search')));
        $output .= html_writer::end_tag('div');
        $output .= $this->backup_detail_pair('', html_writer::empty_tag('input',
                array('type' => 'submit', 'value' => get_string('continue'))));

        if ($component->get_count() === 0) {
            $output .= $this->output->notification(get_string('nomatchingcourses', 'backup'));
            $output .= html_writer::end_tag('div');
            return $output;
        }

        $countstr = '';

        if ($component->has_more_results()) {
            $countstr = get_string('morecoursesearchresults', 'backup', $component->get_count());
        } else {
            $countstr = get_string('totalcoursesearchresults', 'backup', $component->get_count());
        }

        $output .= html_writer::tag('div', $countstr, array('class' => 'ics-totalresults'));
        $output .= html_writer::start_tag('div', array('class' => 'ics-results',
            'style' => 'overflow-y:scroll; height:300px;'));

        $table = new html_table();
        $table->head = array('', get_string('shortnamecourse'),
            get_string('fullnamecourse'), get_string('instructorcourse', 'theme_uclashared'));
        $table->data = array();

        foreach ($component->get_results() as $course) {
            $row = new html_table_row();
            $instructors = array();
            $row->attributes['class'] = 'ics-course';
            if (!$course->visible) {
                $row->attributes['class'] .= ' dimmed';
            }

            // Get instructors for each course, if any.
            $courseinstrs = course_get_format($course->id)->display_instructors();

            // Fetch TA-Admin/Owner for the TA site.
            if (block_ucla_tasites::is_tasite($course->id)) {
                $tasiteenrol = block_ucla_tasites::get_tasite_enrol_meta_instance($course->id);
                if ($tasiteenrol) {
                    foreach ($courseinstrs as $instr) {
                        if ($instr->id == $tasiteenrol->customint4) {
                            $course->instructors[] = fullname($instr);
                            break;
                        }
                    }
                }
            } else if (!empty($courseinstrs)) {
                // Fetch all the instructors for the regular sites.
                foreach ($courseinstrs as $instr) {
                    if (in_array($instr->shortname, $CFG->instructor_levels_roles['Instructor']) ||
                            in_array($instr->shortname, $CFG->instructor_levels_roles['Student Facilitator'])) {
                        $course->instructors[] = fullname($instr);
                    }
                }
            }

            // Sort the course instructors list to display using separator '/'.
            if (empty($course->instructors)) {
                $instructors = 'N/A';
            } else {
                $instructors = implode('/', $course->instructors);
            }
            $row->cells = array(
                html_writer::empty_tag('input', array('type' => 'radio', 'name' => 'importid', 'value' => $course->id)),
                format_string($course->shortname, true, array('context' => context_course::instance($course->id))),
                format_string($course->fullname, true, array('context' => context_course::instance($course->id))),
                format_string($instructors, true, array('context' => context_course::instance($course->id)))
            );
            $table->data[] = $row;
        }

        if ($component->has_more_results()) {
            $cell = new html_table_cell(get_string('moreresults', 'backup'));
            $cell->colspan = 3;
            $cell->attributes['class'] = 'notifyproblem';
            $row = new html_table_row(array($cell));
            $row->attributes['class'] = 'rcs-course';
            $table->data[] = $row;
        }

        $output .= html_writer::table($table);
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div');
        return $output;
    }

}
