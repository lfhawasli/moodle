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
/**
 * Overriding the core backup render (backup/util/ui/renderer.php).
 *
 * @copyright  UC Regents 2014
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class theme_uclashared_core_backup_renderer extends core_backup_renderer {
    /**
     * Displays the import course selector
     *
     * @param moodle_url $nextstageurl
     * @param import_course_search $courses
     * @return string
     */
    public function import_course_selector(moodle_url $nextstageurl, import_course_search $courses=null) {
        $html  = html_writer::start_tag('div', array('class'=>'import-course-selector backup-restore'));
        $html .= html_writer::start_tag('form', array('method'=>'post', 'action'=>$nextstageurl->out_omit_querystring()));
        foreach ($nextstageurl->params() as $key=>$value) {
            $html .= html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>$key, 'value'=>$value));
        }
        // We only allow import adding for now. Enforce it here.
        $html .= html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'target', 'value'=>backup::TARGET_CURRENT_ADDING));
        $html .= html_writer::start_tag('div', array('class'=>'ics-existing-course backup-section'));
        $html .= $this->output->heading(get_string('importdatafrom'), 2, array('class'=>'header'));
        $search = new local_ucla_import_course_search(array('url'=>$nextstageurl));
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
        $url = $component->get_url();
        $output = html_writer::start_tag('div', array('class' => 'import-course-search'));
        $output .= html_writer::start_tag('div', array('class'=>'ics-search'));
        $output .= html_writer::empty_tag('input', array('type'=>'text', 'name'=>restore_course_search::$VAR_SEARCH, 'value'=>$component->get_search()));
        $output .= html_writer::empty_tag('input', array('type'=>'submit', 'name'=>'searchcourses', 'value'=>get_string('search')));
        $output .= html_writer::end_tag('div');
        $output .= $this->backup_detail_pair('', html_writer::empty_tag('input', array('type'=>'submit', 'value'=>get_string('continue'))));
 
        if ($component->get_count() === 0) {
            $output .= $this->output->notification(get_string('nomatchingcourses', 'backup'));
            return $output;
        }
        $countstr = '';
        if ($component->has_more_results()) {
            $countstr = get_string('morecoursesearchresults', 'backup', $component->get_count());
        } else {
            $countstr = get_string('totalcoursesearchresults', 'backup', $component->get_count());
        }
        $output .= html_writer::tag('div', $countstr, array('class'=>'ics-totalresults'));
        $output .= html_writer::start_tag('div', array('class' => 'ics-results', 'style' => 'overflow-y:scroll; height:300px;'));
        $table = new html_table();
        $table->head = array('', get_string('shortnamecourse'), get_string('fullnamecourse'), get_string('instructorcourse', 'theme_uclashared'));
        $table->data = array();
        foreach ($component->get_results() as $course) {
            $row = new html_table_row();
            $row->attributes['class'] = 'ics-course';
            if (!$course->visible) {
                $row->attributes['class'] .= ' dimmed';
            }
            $row->cells = array(
                html_writer::empty_tag('input', array('type'=>'radio', 'name'=>'importid', 'value'=>$course->id)),
                format_string($course->shortname, true, array('context' => context_course::instance($course->id))),
                format_string($course->fullname, true, array('context' => context_course::instance($course->id))),
                format_string($course->lastname.', '.$course->firstname, true, array('context'=>context_course::instance($course->id)))
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