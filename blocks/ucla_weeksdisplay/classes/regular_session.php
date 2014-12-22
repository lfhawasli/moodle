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
 * Regular session class.
 *
 * @package    block_ucla_weeksdisplay
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * 
 */
class block_ucla_weeksdisplay_regular_session extends block_ucla_weeksdisplay_session {

    public function active_weeks() {
        return 10;
    }

    public function update_week_display() {
        global $PAGE;

        // Generate the week display strings.
        $termstring = $this->string_for_quarter();
        $weekstring = $this->string_for_week();

        // Show winter break message.
        if ($this->quarter === self::WINTER && !$this->instruction_started()) {
            $weekstring = get_string('winter_break', 'block_ucla_weeksdisplay');
        }

        // Need the weeks display renderer.
        $PAGE->set_context(context_system::instance());

        /* @var block_ucla_weeksdisplay_renderer */
        $renderer = $PAGE->get_renderer('block_ucla_weeksdisplay');

        $renderable = new ucla_week($termstring, $weekstring);
        $out = $renderer->render($renderable);
        $this->renderedweek = $renderer->display_wrapper($out, $this->quarter_name());

        // Save to config.
        $this->save_configs();
        $this->save_current_week_display();
    }

}
