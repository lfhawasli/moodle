<?php
// This file is part of the UCLA local help plugin for Moodle - http://moodle.org/
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

class sidebar_feedback extends sidebar_html implements sidebar_widget {
    
    public function render() {
        global $OUTPUT;
        
//        require_once($CFG->dirroot . '/local/ucla/jira.php');
//        require_once($CFG->dirroot . '/blocks/ucla_help/ucla_help_lib.php');
//        require_once($CFG->dirroot . '/blocks/ucla_help/help_form.php' );
        
        // Link to help & feedback
        // @todo: eventually make this RESTful
        $link = $help_locale = $OUTPUT->call_separate_block_function(
                'ucla_help', 'get_action_link'
            );
        
        $template = $this->title('Help & feedback');
        
        $template .= '
            <div class="ui segment raised" >
                <div class="ui list">
                    <div class="header">
                       Don\'t see your question?
                    </div>
                    <div class="item">
                        Find FAQs, tutorials and a large database of help documentation at <a href="https://docs.ccle.ucla.edu">CCLE Help</a>
                        <div class="ui horizontal divider">
                            Or
                        </div>
                        <a class="btn btn-primary btn-sm btn-block btn-support" href="' . $link .'">Ask support</a>
                    </div>
                </div>
            </div>';
        
        return $template;
        
    }    
}