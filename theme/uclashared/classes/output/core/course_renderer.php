<?php
// This file is part of the UCLA shared theme for Moodle - http://moodle.org/
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

namespace theme_uclashared\output\core;
defined('MOODLE_INTERNAL') || die();

use moodle_url;
use cm_info;

require_once($CFG->dirroot . '/course/renderer.php');

/**
 * UCLA specific renderers and overrides Boost renders.
 *
 * @package    theme_uclashared
 * @copyright  2018 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class course_renderer extends \theme_boost\output\core\course_renderer{
    
    /**
     * Returns the CSS classes for the activity name/content
     *
     * @param cm_info $mod
     * @return array array of two elements ($linkclasses, $textclasses)
     */
    protected function course_section_cm_classes(cm_info $mod) {
        $linkclasses = '';
        $textclasses = '';
        if ($mod->uservisible) {
            $conditionalhidden = $this->is_cm_conditionally_hidden($mod);
            $accessiblebutdim = (!$mod->visible || $conditionalhidden) &&
                has_capability('moodle/course:viewhiddenactivities', $mod->context);
            // Lines 50-59 restructured from original method to add dimmed class 
            // only to hidden section items.
            if (!$mod->visible) {
                $linkclasses .= ' dimmed';
                $textclasses .= ' dimmed_text';
            }
            if ($accessiblebutdim) {
                if ($conditionalhidden) {
                    $linkclasses .= ' conditionalhidden';
                    $textclasses .= ' conditionalhidden';
                }
            }
            if ($mod->is_stealth()) {
                // Stealth activity is the one that is not visible on course page.
                // It still may be displayed to the users who can manage it.
                $linkclasses .= ' stealth';
                $textclasses .= ' stealth';
            }
        } else {
            $linkclasses .= ' dimmed';
            $textclasses .= ' dimmed dimmed_text';
        }
        return array($linkclasses, $textclasses);
    }
}
