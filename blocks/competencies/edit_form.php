<?php
/**
 * Form for editing course competencies.
 *
 * @package   block_competencies
 * @copyright 2012 UC Regents
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Eric Bollens <ebollens@ucla.edu>
 */

require_once(dirname(__FILE__).'/lib.php');

/**
 * Form for editing course competencies block instance. Note that this is 
 * context-specific to the course in question.
 *
 * @package   block_competencies
 * @author    Eric Bollens <ebollens@ucla.edu>
 */
class block_competencies_edit_form extends block_edit_form {
    
    protected function specific_definition($mform) {
        
        global $DB;
        global $COURSE;
        
        if($COURSE->format == 'site'){
            return;
        }
        
        $competency_sets = block_competencies_db::get_item_sets();
        $course_competencies = block_competencies_db::get_course_items($COURSE->id);

        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block_competencies'));
        $mform->addElement('html', block_competencies_course_editor::render_html($competency_sets, $course_competencies));
        $mform->addElement('html', block_competencies_course_editor::render_js($COURSE->id));

    }
}
