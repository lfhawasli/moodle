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

    public static $sessioncodes = array('6A', '8A', '1A', '6C');

    public function __construct($session) {
        $this->summersessions = array();

        // Split sessions.  
        foreach ($session as $ses) {
            if (in_array($ses['session'], static::$sessioncodes)) {
                $this->summersessions[$ses['session']] = (object) $ses;
            }
        }

        // Construct with 6A, we'll check each session later
        parent::__construct($this->summersessions['6A']);
    }

    public function active_weeks() {
        switch (substr($this->session->session, 0, 1)) {
            case '6': return 6;
            case '8': return 8;
            case '1': return 10;
            default: return 0;
        }
    }

    /**
     * No finals week in summer.
     *
     * @return string
     */
    public function current_week() {
        $week = parent::current_week();
        if ($week == \block_ucla_weeksdisplay_session::WEEK_FINALS) {
            $week = parent::weeks_in_session();
        }
        return $week;
    }

    public function update_week_display() {
        global $PAGE;

        // Need the weeks display renderer
        $PAGE->set_context(context_system::instance());

        /* @var block_ucla_weeksdisplay_renderer */
        $renderer = $PAGE->get_renderer('block_ucla_weeksdisplay');

        $out = array();

        // Loop through sessions in order.
        foreach (static::$sessioncodes as $sessioncode) {
            if (empty($this->summersessions[$sessioncode])) {
                continue;
            }

            // Switch the inner session.
            $this->session = $this->summersessions[$sessioncode];

            if ($this->in_session()) {

                $termstring = $this->string_for_quarter();
                $weekstring = $this->string_for_week();

                $renderable = new ucla_week($termstring, $weekstring);
                $string = $renderer->render($renderable);
                // Only display identical weekstrings once.
                $out[$string] = 1;
            }
        }

        $content = implode(' | ', array_keys($out));
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

        $session = substr($this->session->session, 1, 1);

        return $quarter . ' - Session ' . $session;
    }

    /**
     * Handles summer sessions.
     * 
     *  Either "a" or "c". 
     */
    public function save_current_week() {
        $currentweek = array(
            'A' => \block_ucla_weeksdisplay_session::WEEK_ERR,
            'C' => \block_ucla_weeksdisplay_session::WEEK_ERR
        );
        foreach ($this->summersessions as $session) {
            $this->session = $session;
            $week = &$currentweek[substr($session->session, 1, 1)];
            $sessionweek = $this->current_week();

            switch ($sessionweek) {
                case \block_ucla_weeksdisplay_session::WEEK_ERR:
                    break;

                case \block_ucla_weeksdisplay_session::WEEK_BETWEEN_SESSION:
                    if ($week == \block_ucla_weeksdisplay_session::WEEK_ERR) {
                        $week = $sessionweek;
                    }
                    break;

                default:
                    $week = $sessionweek;
            }
        }

        // CCLE-2307: "if weeks overlap, like in summer, choose highest number":
        // use session A until it is over.
        if ($currentweek['A'] != \block_ucla_weeksdisplay_session::WEEK_ERR
                && $currentweek['A'] != \block_ucla_weeksdisplay_session::WEEK_BETWEEN_SESSION) {
            set_config('current_week', $currentweek['A'], 'local_ucla');
        } else {
            set_config('current_week', $currentweek['C'], 'local_ucla');
        }
        set_config('current_week_summera', $currentweek['A'], 'local_ucla');
        set_config('current_week_summerc', $currentweek['C'], 'local_ucla');
    }

    /**
     * Override base class method to use term_end instead of session_end,
     * because summer courses have overlaping session (A & B) that session_end
     * would cause the summer term to end too early.
     */
    public function save_configs() {

        // Save the week.
        $this->save_current_week();

        // Check if we need to update term.
        if ($this->term_ended() && $this->current_week() !== self::WEEK_ERR) {
            // We want to wait one week after a session has ended before
            // switching current term to allow users to access their courses.
            $termnend = new DateTime($this->session->term_end);
            $termnend->modify('+1 week');

            if ($this->today > $termnend) {
                // Roll over the term.
                $this->save_next_term();

                // Update active terms.
                $this->save_active_terms();
            }
        }
    }
}
