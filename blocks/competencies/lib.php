<?php

/**
 * This file contains classes related to competency management and display.
 *
 * @package   block_competencies
 * @copyright 2012 UC Regents
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Eric Bollens <ebollens@ucla.edu>
 */

require_once($CFG->dirroot . '/lib/adminlib.php');

/**
 * The competencies settings block class where administrators can manage the
 * competency items and their categories site-wide.
 *
 * @package   block_competencies
 * @author    Eric Bollens <ebollens@ucla.edu>
 */

class block_competencies_admin_setting_manager extends admin_setting {
    
    public function __construct($name) {
        parent::__construct($name, false, false, array());
    }
    
    public function get_setting() {
        return block_competencies_db::get_item_sets();;
    }

    public function write_setting($data) {
        $write_error = false;
        return (!$write_error ? '' : get_string('errorsetting', 'admin'));
    }
    
    public function output_html($data, $query='') {
        echo block_competencies_renderer_admin_sets::render_html($this->get_setting());
        echo block_competencies_renderer_admin_sets::render_js();
    }
    
}

class block_competencies_renderer_admin_sets {
    
    public static function render_html($sets){
        
        $set_list = array();
        
        foreach($sets as $set){
            
            if($set->id != 0)
                $title = html_writer::tag('h2', 'Category'.html_writer::tag('span', '&nbsp;&times;', array('class'=>'delete-category-control')));
            else
                $title = html_writer::tag('h2', 'Category');
            
            $control_id = 'block_competency_set-'.$set->id;
            $input_id_hidden = html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'category_id', 'value'=>$set->id, 'disabled'=>'disabled'));
            
            $create = html_writer::tag('a', '[Add Item]', array('class'=>'create-control', 'id'=>$control_id.'-create', 'href'=>'#'));
            
            $label = html_writer::label('Name', $control_id.'-name');
            $input = html_writer::empty_tag('input', array('type'=>'text', 'name'=>'category_name', 'id'=>$control_id.'-name', 'value'=>$set->name));
            $name = html_writer::tag('div', $label.$input);
            
            $list_title = html_writer::tag('div', 'Items', array('class'=>'label'));
            $list_items = block_competencies_renderer_admin_set_items::render_html($set->items);
            
            $set_list[] = $title.$input_id_hidden.$name.$list_title.$list_items.$create;
        }
        
        $add_category = html_writer::tag('a', '[Add Category]', array('class'=>'create-category-control', 'href'=>'#'));
        
        return html_writer::alist($set_list, array('class'=>'block_competencies_sets')).$add_category;
    }
    
    public static function render_js(){
        
        ob_start();
        $ajax_url = new moodle_url('/blocks/competencies/ajax/admin.php');
        include(dirname(__FILE__).'/js/admin.js');
        $contents = ob_get_contents();
        ob_end_clean();
        
        return html_writer::script($contents);
    }
}

class block_competencies_renderer_admin_set_items {
    
    public static function render_html($items){
        
        $item_list = array();
        
        foreach($items as $item){
            
            $control_id = 'block_competency_item-'.$item->id;
            $input_id_hidden = html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'id', 'value'=>$item->id, 'disabled'=>'disabled'));
            
            $up = html_writer::tag('div', '&uarr;', array('class'=>'move_up', 'id'=>$control_id.'-up'));
            $down = html_writer::tag('div', '&darr;', array('class'=>'move_down', 'id'=>$control_id.'-down'));
            $delete = html_writer::tag('div', '&times;', array('class'=>'delete', 'id'=>$control_id.'-delete'));
            $controls = html_writer::tag('div', $up.$down.$delete, array('class'=>'controls'));
            
            $label = html_writer::label('Key', $control_id.'-ref');
            $input = html_writer::empty_tag('input', array('type'=>'text', 'name'=>'ref', 'id'=>$control_id.'-ref', 'value'=>$item->ref));
            $key = html_writer::tag('div', $label.$input);
            
            $label = html_writer::label('Name', $control_id.'-name');
            $input = html_writer::empty_tag('input', array('type'=>'text', 'name'=>'name', 'id'=>$control_id.'-name', 'value'=>$item->name));
            $name = html_writer::tag('div', $label.$input);
            
            $label = html_writer::label('Description', $control_id.'-description');
            $input = html_writer::tag('textarea', $item->description, array('id'=>$control_id.'-description', 'name'=>'description'));
            $description = html_writer::tag('div', $label.$input);
            
            $container = html_writer::tag('div', $controls.$input_id_hidden.$key.$name.$description, array('id'=>$control_id));
            
            $item_list[] = $container;
            
        }
        
        return html_writer::alist($item_list, array('class'=>'block_competencies_items'));
    }
    
}

class block_competencies_course_editor {
    
    public static function render_html($competency_sets, $course_competencies){
        
        $course_competencies_list = array();
        foreach($course_competencies as $course_competency){
            $str = html_writer::tag('span', $course_competency->name, array('id'=>'competency-'.$course_competency->set_id.'-'.$course_competency->id, 'class'=>'competency'));
            $str .= ' '.html_writer::tag('a', '[Add]', array('class'=>'add')).' '.html_writer::tag('a', '[Remove]', array('class'=>'remove'));
            $course_competencies_list[] = $str;
        }
        
        $available_sets_list = array();
        
        foreach($competency_sets as $set_id=>$set){
            $available_items_list = array();
            foreach($set->items as $item_id=>$item){
                if(isset($course_competencies[$item_id]))
                    continue;
                $str = html_writer::tag('span', $item->name, array('id'=>'competency-'.$item->set_id.'-'.$item->id, 'class'=>'competency'));
                $str .= ' '.html_writer::tag('a', '[Add]', array('class'=>'add')).' '.html_writer::tag('a', '[Remove]', array('class'=>'remove'));
                $available_items_list[] = $str;
            }
            $set_title = html_writer::tag('h5', $set->name);
            $set_list = html_writer::alist($available_items_list, array('id'=>'competency-set-'.$set_id));
            
            $available_sets_list[] = $set_title.$set_list;
        }
        
        $existing_title = html_writer::tag('h3', 'Course Competencies');
        $existing_list = html_writer::alist($course_competencies_list, array('class'=>'competency-edit-list existing'));
        $available_title = html_writer::tag('h3', 'Available Competencies');
        $available = html_writer::alist($available_sets_list, array('class'=>'competency-edit-list available'));
        
        return $existing_title.$existing_list.$available_title.$available;
    }
    
    public static function render_js($course_id){
        
        ob_start();
        $ajax_url = new moodle_url('/blocks/competencies/ajax/course.php', array('id'=>$course_id));
        include(dirname(__FILE__).'/js/course.js');
        $contents = ob_get_contents();
        ob_end_clean();
        
        return html_writer::script($contents);
    }
}

class block_competencies_course_view {
    
    public static function render($items) {
        
        $sets = block_competencies_db::get_item_sets();
        
        $li = array();
        foreach($items as $item)
        {
            $set_name = isset($sets[$item->set_id]) ? $sets[$item->set_id]->name : 'Other';
            $li[] = html_writer::tag('h4', $set_name.': '.$item->name).html_writer::tag('p', $item->description);
        }
        
        return html_writer::alist($li, array('class'=>'competency-view-list'));
        
    }
    
}

/**
 * The competencies database accessor library.
 *
 * @package   block_competencies
 * @author    Eric Bollens <ebollens@ucla.edu>
 */

class block_competencies_db {
    
    const TABLE_SETS = 'block_competencies_sets';
    const TABLE_ITEMS = 'block_competencies_items';
    const TABLE_COURSE = 'block_competencies_courses';
    
    /**
     * Get an array of competency sets keyed by set id with an additional field
     * 'items' contaning an array of items keyed by item id.
     * 
     * @global type $DB 
     */
    public static function get_item_sets()
    {
        global $DB;

        $sets = $DB->get_records(self::TABLE_SETS);
        
        // special "unassigned" set
        $sets[0] = new stdClass;
        $sets[0]->name = 'Other';
        $sets[0]->id = 0;

        // add an items array to all set records
        array_walk($sets, function ($set) {
            $set->items = array();
            return $set;
        });

        // add items to set (or unassigned set if no match)
        $items = self::get_items();
        foreach($items as $item){
            if(isset($sets[$item->set_id])){
                $sets[$item->set_id]->items[$item->id] = $item;
            }else{
                $sets[$item->set_id]->items[$item->id] = $item;
            }
        }
        
        return $sets;
    }
    public static function get_sets()
    {
        global $DB;
        
        $sets = $DB->get_records(self::TABLE_SETS);
        
        // special "unassigned" set
        $sets[0] = new stdClass;
        $sets[0]->name = 'Other';
        $sets[0]->id = 0;
        
        return $sets;
    }
    
    public static function get_course_items($course_id, $with_category_name = false)
    {
        global $DB;
        
        $items = $DB->get_records_sql('SELECT i.* 
                                       FROM {'.self::TABLE_COURSE.'} c 
                                       INNER JOIN {'.self::TABLE_ITEMS.'} i
                                           ON i.id = c.item
                                       WHERE c.course = ?', array($course_id));
        
        return $items;
    }
    
    public static function insert_course_item($course_id, $item_id)
    {
        global $DB;
        
        $insert = new stdClass;
        $insert->course = $course_id;
        $insert->item = $item_id;
        
        return $DB->insert_record(self::TABLE_COURSE, $insert);
    }
    
    public static function delete_course_item($course_id, $item_id)
    {
        global $DB;
        return $DB->delete_records(self::TABLE_COURSE, array('course'=>$course_id, 'item'=>$item_id));
    }
    
    public static function get_items()
    {
        global $DB;
        return $DB->get_records(self::TABLE_ITEMS, null, 'ord ASC');
    }
    
    public static function insert_item($item_data)
    {
        global $DB;
        return $DB->insert_record(self::TABLE_ITEMS, $item_data);
    }
    
    public static function delete_item($item_id)
    {
        global $DB;
        
        $item_data = array();
        $item_data['id'] = $item_id;
        
        return $DB->delete_records(self::TABLE_ITEMS, $item_data);
    }
    
    public static function update_item($item_data)
    {
        global $DB;
        return $DB->update_record(self::TABLE_ITEMS, $item_data);
    }
    
    public static function insert_set($set_data)
    {
        global $DB;
        return $DB->insert_record(self::TABLE_SETS, $set_data);
    }
    
    public static function delete_set($set_id)
    {
        global $DB;
        
        $set_data = array();
        $set_data['id'] = $set_id;
        
        return $DB->delete_records(self::TABLE_SETS, $set_data);
    }
    
    public static function update_set($set_data)
    {
        global $DB;
        return $DB->update_record(self::TABLE_SETS, $set_data);
    }
    
}