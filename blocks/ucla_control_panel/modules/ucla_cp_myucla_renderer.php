<?php
// This file is part of Moodle - http://moodle.org/
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
 * This file defines the control panel module class.
 * @package block_ucla_control_panel
 * @copyright  UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Used to render the myucla links section in the control panel.
 * @copyright  UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
class ucla_cp_myucla_row_renderer extends ucla_cp_renderer {

    /**
     * Renders an array of myucla_row modules.
     *
     * @param array $contents - The contents to diplay using the renderer.
     * @param boolean $format
     * @param string $orient
     * @param string $handler
     **/
    public static function control_panel_contents($contents, $format=false,
            $orient='', $handler='') {
        $table = new html_table();
        $table->id = 'my_ucla_functions';

        // For each row module...
        $nonrowcontent = "";
        foreach ($contents as $contentrows) {
            if (isset($contentrows->elements)) {
                $contentrowselements = $contentrows->elements;
                $tablerow = new html_table_row();
                // For each element in the row module...
                foreach ($contentrowselements as $contentitem) {
                    $tablerow->cells[] = ucla_cp_renderer::general_descriptive_link($contentitem,
                            array("target" => "_blank"));
                }
                $table->data[] = $tablerow;
            } else {
                $nonrowcontent .= ucla_cp_renderer::control_panel_contents(Array($contentrows), true);
            }
        }
        // Make sure content that is not part of the row is rendered before the
        // row content to avoid layout problem.
        return $nonrowcontent.html_writer::table($table);
    }
}
