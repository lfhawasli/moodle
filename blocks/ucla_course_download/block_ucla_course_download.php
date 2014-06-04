<?php
class block_ucla_course_download extends block_base {
    public function init() {
        $this->title = get_string('pluginname', 'block_ucla_course_download');
    }
    
    /**
     * Returns true because block has a settings.php file.
     *
     * @return boolean
     */
    public function has_config() {
        return true;
    }
}
    