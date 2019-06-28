<?php
// This file is part of the UCLA weeks display block for Moodle - http://moodle.org/
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
 * Renderer for UCLA weeks display block.
 *
 * @package    block_ucla_weeksdisplay
 * @category   renderer
 * @copyright  2013 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Renderable UCLA week.
 */
class ucla_week implements renderable {
    public $term;
    public $week;
    
    public function __construct($term, $week) {
        $this->term = $term;
        $this->week = $week;
    }
}

class block_ucla_weeksdisplay_renderer extends plugin_renderer_base {
    
    public function display_wrapper($content, $class) {
        return html_writer::div($content, 'ml-auto weeks-display label-' . $class);
    }
    
    protected function render_ucla_week(ucla_week $term) {
        
        $content = html_writer::span($term->term, 'session');
        
        if ($term->week !== '') {
            $content .= html_writer::span($term->week, 'week');
        }
        
        return $content;
    }
}