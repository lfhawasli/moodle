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

/**
 * Sidebar for feedback page
 *
 * @package    block_ucla_help
 * @author     Rex Lorenzo <rex@seas.ucla.edu>
 * @copyright  2011 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Sidebar for feedback page
 *
 * @copyright  2011 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sidebar_feedback extends sidebar_html implements sidebar_widget {

    /**
     * Ouputs the HTML string for the sidebar
     *
     * @return string
     */
    public function render() {
        global $OUTPUT;

        $docsurl = get_config('block_ucla_help', 'docs_wiki_url');

        // Link to help & feedback
        // TODO: eventually make this RESTful.
        $link = $helplocale = $OUTPUT->call_separate_block_function(
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
                        Find FAQs, tutorials and a large database of help documentation at our <a href="' .
                        $docsurl .'">Help site</a>
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
