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
 * UCLA summer session class.
 *
 * @package    block_ucla_weeksdisplay
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_ucla_weeksdisplay_summer_session extends block_ucla_weeksdisplay_session {

    private $summersessions = array();

    public function __construct($session) {

        $this->summersessions = array();

        // Split sessions.  
        foreach ($session as $ses) {

            if ($ses['session'] === '6A') {
                $this->summersessions['6A'] = (object) $ses;
            } else if ($ses['session'] === '6C') {
                $this->summersessions['6C'] = (object) $ses;
            } else if ($ses['session'] === '8A') {
                $this->summersessions['8A'] = (object) $ses;
            }
        }

        // Construct with 6A, we'll check each session later
        parent::__construct($this->summersessions['6A']);
    }

    public function active_weeks() {

        if ($this->session === $this->summersessions['6A']) {
            return 6;
        } else if ($this->session === $this->summersessions['6C']) {
            return 8;
        } else {
            return 10;
        }
    }

    public function update_week_display() {
        global $PAGE;

        // Need the weeks display renderer
        $PAGE->set_context(context_system::instance());

        /* @var block_ucla_weeksdisplay_renderer */
        $renderer = $PAGE->get_renderer('block_ucla_weeksdisplay');

        $out = array();

        foreach ($this->summersessions as $session) {
            // Switch the inner session.
            $this->session = $session;

            if ($this->in_session()) {

                $termstring = $this->string_for_quarter();
                $weekstring = $this->string_for_week();

                $renderable = new ucla_week($termstring, $weekstring);
                $out[] = $renderer->render($renderable);
            }
        }

        $content = implode(' | ', $out);
        $this->renderedweek = $renderer->display_wrapper($content, $this->quarter_name());

        // Save to config.
        $this->save_configs();
        $this->save_current_week_display();
    }

    /**
     * Appends the session to the quarter string.
     * 
     * @return type
     */
    public function string_for_quarter() {
        $quarter = parent::string_for_quarter();

        $session = '';
        if ($this->session === $this->summersessions['6A']) {
            $session = 'Session A';
        } else if ($this->session === $this->summersessions['6C']) {
            $session = 'Session C';
        } else {
            $session = 'Session 8A'; // ???
        }

        return $quarter . ' - ' . $session;
    }

    /**
     * Handles summer sessions.
     * 
     *  Either "a" or "c". 
     */
    public function save_current_week() {
        
        parent::save_current_week();
        
        $session = 'c';
        if ($this->session === $this->summersessions['6A']) {
            $session = 'a';
        } else if ($this->session === $this->summersessions['6C']) {
            $session = 'c';
        }
        
        set_config('current_week_summer' . $session, $this->current_week(), 'local_ucla');
    }
}
