<?php
class block_ucla_course_download extends block_base {
    public function init() {
        $this->title = get_string('pluginname', 'block_ucla_course_download');
    }
    
    public function get_content() {
        if ($this->content !== null) {
            return $this->content;
        }
 
        $this->content         =  new stdClass;
        $this->content->text   = 'The content of our SimpleHTML block!';
        $this->content->footer = 'Footer here...';
 
        return $this->content;
    }
}
    