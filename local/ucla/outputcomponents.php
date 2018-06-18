<?php
// This file is part of the UCLA local plugin for Moodle - http://moodle.org/
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
 * Adds external link icons to html_writer.
 *
 * @package     local_ucla
 * @copyright   2012 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Extends html_writer to override external_link().
 * 
 * @copyright   2012 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ucla_html_writer extends html_writer {
    /**
     * Hack to add external link icon.
     *
     * @param moodle_url $url
     * @param string $text
     * @param array $attr
     * @return string
     */
    public static function external_link($url, $text, $attr=null) {
        global $CFG;
        if (strpos($url->out(), $CFG->wwwroot) === false) {
            if (empty($attr)) {
                $attr['class'] = '';
            } else {
                $attr['class'] .= ' ';
            }

            // Add external link icon after text.
            $text .= ' <i class="fa fa-external-link" aria-hidden="true"></i>';
            
            $attr['title'] = get_string('external-link', 'local_ucla');
            $attr['target'] = '_blank';
        }

        return parent::link($url, $text, $attr);
    }
}

