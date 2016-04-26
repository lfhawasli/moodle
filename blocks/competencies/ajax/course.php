<?php

define('AJAX_SCRIPT', true);

require('../../../config.php');
require_once($CFG->dirroot . '/blocks/competencies/lib.php');
    
$course_id = required_param('id', PARAM_INT);
$course = $DB->get_record('course', array('id'=>$course_id), '*', MUST_EXIST);

$coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);
require_capability('moodle/course:update', $coursecontext);

$data = get_magic_quotes_gpc() ? stripslashes($_POST['data']) : $_POST['data'];

if(($updated_course_competencies = json_decode($data)) !== null) {
    
    try{
    
        $transaction = $DB->start_delegated_transaction();
    
        $course_competencies = array_keys(block_competencies_db::get_course_items($course_id));

        foreach($updated_course_competencies as $updated_course_competency_id)
            if(!in_array($updated_course_competency_id, $course_competencies))
                block_competencies_db::insert_course_item($course_id, $updated_course_competency_id);

        foreach($course_competencies as $course_competency_id)
            if(!in_array($course_competency_id, $updated_course_competencies))
                block_competencies_db::delete_course_item($course_id, $course_competency_id);

        $transaction->allow_commit();
    
        echo 'true';
    
    }catch(Exception $e){
        
        $transaction->rollback($e);
        
        echo 'false';
        
    }
    
} else {
    
    echo 'false';
    
}
