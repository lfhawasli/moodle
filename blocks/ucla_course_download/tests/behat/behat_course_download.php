<?php

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');
require_once(__DIR__ . '/../../../../local/ucla/tests/behat/behat_ucla.php');

use Behat\Behat\Context\Step\Given as Given,
    Behat\Gherkin\Node\TableNode as TableNode;
    

class behat_course_download extends behat_base {
          
    /**
     * Convenience step wrapper to upload a file into a section.  Assumes that you
     * are already in given $section, then creates a file resource and uploads
     * $file into it and names it $name.
     * 
     * @Given /^I upload the "([^"]*)" file as "([^"]*)" to section "([^"]*)"$/
     */
    public function i_upload_file_to_section($filepath, $name, $section) {

        $table = array();
        $table[0] = array('Name', $name);
        $table[1] = array('Description', "$name description");
        return array(
            new Given('I add a "File" to section "' . $section . '"'),
            new Given('I set the following fields to these values:', new TableNode($table)),
            new Given('I upload "'.$filepath.'" file to "Select files" filemanager'),
            new Given('I press "Save and return to course"')
        );
    }

    /**
     * Step to change the max download filesize limit.  
     * @todo: Create general config switch step.
     * 
     * @Given /^I set the course download max limit to "([^"]*)" MB$/
     */
    public function i_set_max_course_download_to($size) {
        set_config('maxfilesize', $size, 'block_ucla_course_download');
        
    }


}