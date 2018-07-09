<?php
// This file is part of the UCLA course download plugin for Moodle - http://moodle.org/
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
 * Behat block_ucla_course_download step definitions.
 *
 * @package    block_ucla_course_download
 * @copyright  2018 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');
use Behat\Behat\Context\Step\Given as Given,
    Behat\Gherkin\Node\TableNode as TableNode;
    
/**
 * Steps definitions.
 *
 * @package    block_ucla_course_download
 * @copyright  2018 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
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
        
        $this->execute('behat_course::i_add_to_section', 'File', $section);
        $this->execute('behat_forms::i_set_the_following_fields_to_these_values', new TableNode($table));
        $this->execute('behat_repository_upload::i_upload_file_to_filemanager', $filepath, 'Select files');
        $this->execute('behat_forms::press_button', 'Save and return to course');
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