<?php

define('AJAX_SCRIPT', true);

require('../../../config.php');
require_once($CFG->dirroot . '/blocks/competencies/lib.php');

require_capability('moodle/site:config',get_context_instance(CONTEXT_SYSTEM));

$current_items = array_keys(block_competencies_db::get_items());
$current_sets = array_keys(block_competencies_db::get_sets());
$updated_items = array();
$updated_sets = array();

$data = get_magic_quotes_gpc() ? stripslashes($_POST['data']) : $_POST['data'];

if(($sets = json_decode($data)) !== null) {
    
    try{
    
        $transaction = $DB->start_delegated_transaction();

        foreach($sets as $set_id => $set) {

            $set_data = new stdClass;
            $set_data->name = $set->name;

            if($set_id !== '0'){
                if(!is_numeric($set_id)){
                    $set_id = block_competencies_db::insert_set($set_data);
                }if(in_array($set_id, $current_sets)){
                    $set_data->id = $set_id;
                    block_competencies_db::update_set($set_data);
                }
            }

            $updated_sets[] = $set_id;

            $item_order_idx = 0;

            foreach($set->items as $item_id => $item) {

                $item_data = new stdClass;
                $item_data->set_id = $set_id;
                $item_data->ord = $item_order_idx++;
                $item_data->ref = $item->ref;
                $item_data->name = $item->name;
                $item_data->description = $item->description;

                if(in_array($item_id, $current_items)){
                    $item_data->id = $item_id;
                    block_competencies_db::update_item($item_data);
                } else {
                    block_competencies_db::insert_item($item_data);
                }

                $updated_items[] = $item_id;
            }

        }

        foreach($current_items as $item_id) {

            if(!in_array($item_id, $updated_items))
                 block_competencies_db::delete_item($item_id);

        }

        foreach($current_sets as $set_id){

            if(!in_array($set_id, $updated_sets))
                block_competencies_db::delete_set ($set_id);

        }

        $transaction->allow_commit();
    
        echo 'true';
    
    }catch(Exception $e){
        
        $transaction->rollback($e);
        
        echo 'false';
        
    }
    
} else {
    
    echo 'false';
    
}
