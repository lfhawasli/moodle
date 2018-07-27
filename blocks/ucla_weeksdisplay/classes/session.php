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
 * UCLA session abstract class.
 * 
 * A UCLA session is basically an interval from a set of attributes retrieved 
 * from a Registrar query given a UCLA term (14F, 15W, etc). The query result 
 * is encoded into a $session object with attributes:
 * 
 *      term_start, term_end
 *      session_start, session_end
 *      instruction_start
 * 
 * The attributes hold some date in Y-m-d H:i:s format which are used to create 
 * PHP DateTime objects that we can operate on.
 * 
 * The session_start and session_end  are the primary attributes to determine
 * what week we are in, or wether we are between sessions or if we need to
 * roll over a term.  This class basically operates on an interval descibed by
 * the $session atributes:
 * 
 *  {term_start}
 *  |
 *  |    {sesion_start}               {session_end}
 *  |    |                            |
 *  |----#---*------------------------#-----|
 *           |                              |
 *           {instruction_start}            {term_end}
 * 
 * 
 * Weeks representation
 * 
 * The week begins when instruction begins.  In Fall, we account for week 0.
 * The week can also be represented in some transient state. 
 * 
 *                      {winter}                                           {spring}
 *    | between_session | 1 | ... | 9 | 10 | finals_week | between_session | 1 | 2 | ...
 * 
 * 
 * 
 *
 * @package    block_ucla_weeksdisplay
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class block_ucla_weeksdisplay_session {

    const SPRING        = 'S';
    const SUMMER        = '1';
    const FALL          = 'F';
    const WINTER        = 'W';

    const WEEK_ERR              = 400;
    const WEEK_BETWEEN_SESSION  = 300;
    const WEEK_FINALS           = 200;

    protected $session;
    protected $term;
    protected $quarter;
    private $today;
    protected $renderedweek;

    public function __construct($session) {

        $this->session = $session;
        $this->term = $session->term;
        $this->quarter = substr($this->term, -1);

        // Format today's date to something like 2014-09-22.
        $this->set_today(new DateTime());
        $this->clean_session_properties();
    }

    public abstract function active_weeks();

    /**
     * Updates the weeks display and saves corresponding config values.
     * If it detects the end of the term, it set the next term.
     * 
     */
    public abstract function update_week_display();

    /**
     * Removes up trailing data from session date properties.
     */
    protected function clean_session_properties() {
        $len = strlen('0000-00-00');

        foreach ($this->session as $key => $val) {
            if (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}.*/', $val)) {
                $this->session->$key = substr($val, 0, $len);
            }
        }
    }

    /**
     * Gives us the number of weeks in a given session.  This is generally
     * active weeks + finals week.
     * 
     * @return int number of weeks.
     */
    public function weeks_in_session() {
        $start = new DateTime($this->session->instruction_start);
        $end = new DateTime($this->session->session_end);

        // Count number of Mondays from instruction start to session end.
        // Round start to next Monday, end to previous Monday.
        $start->modify('Monday');
        $end->modify('previous Monday');

        // Difference of days between session start and session end 
        // will tell us how many weeks for session.
        $diff = $end->diff($start);

        $weeks = intval(round($diff->days / 7)) + 1;

        return $weeks;
    }

    /**
     * Returns max number of weeks we can have in in a term.  This is 
     * generally the active weeks + finals week + some padding.
     * 
     * @return type
     */
    protected function max_weeks_in_session() {
        $start = new DateTime($this->session->instruction_start);
        $end = new DateTime($this->session->term_end);

        // Count number of Mondays from instruction start to term end.
        $start->modify('Monday');
        $end->modify('previous Monday');

        $diff = $end->diff($start);

        $weeks = intval(round($diff->days / 7)) + 1;

        return $weeks + 1;
    }

    /**
     * Returns the current week or transient condition.  These can be:
     * 
     *      self::WEEK_ERR              error condition
     *      self::WEEK_FINALS           finals week
     *      self::WEEK_BETWEEN_SESSION  between session
     * 
     * @return int | string
     */
    public function current_week() {

        $termstart = new DateTime($this->session->term_start);
        $sessionstart = new DateTime($this->session->session_start);
        $instructionstart = new DateTime($this->session->instruction_start);

        // Error condition.
        if ($this->today < $termstart) {
            return self::WEEK_ERR;
        }
        // If session hasn't started, we're between session.
        if ($this->today < $sessionstart) {
            return self::WEEK_BETWEEN_SESSION;
        }
        
        // The current week is a delta of today's week number in the year {1,..,52}
        // and the instruction start week number.  
        $currentweek = intval($this->today->format('W')) - intval($instructionstart->format('W'));

        // A week < 0 is an error condition.
        if ($currentweek < 0) {
            return self::WEEK_ERR;
        }

        // If the number of weeks is greater than the max weeks we can 
        if ($currentweek > $this->max_weeks_in_session()) {
            return self::WEEK_ERR;
        }

        // Only Fall quarter has week 0.
        if ($this->quarter !== self::FALL) {
            $currentweek++;
        }

        // 
        if ($currentweek == $this->weeks_in_session()) {
            return self::WEEK_FINALS;
        } else if ($currentweek > $this->weeks_in_session()) {
            return self::WEEK_BETWEEN_SESSION;
        }

        return $currentweek;
    }

    /**
     * Force a new 'today' value.
     * 
     * @param type $today
     */
    public function set_today(DateTime $today) {
        $today->setTime(0, 0, 0);
        $this->today = $today;
    }

    /**
     * Returns true if the current week is finals week.
     * 
     * @return type
     */
    public function is_finals_week() {
        return $this->current_week() === self::WEEK_FINALS;
    }

    /**
     * Returns true if instruction has started as of today.
     * 
     * @return bool
     */
    public function instruction_started() {
        $instructionstart = new DateTime($this->session->instruction_start);
        return ($this->today >= $instructionstart);
    }

    /**
     * Returns true if session has already started as of today.
     * 
     * @return type
     */
    public function session_started() {
        $sessionstart = new DateTime($this->session->session_start);
        return ($this->today >= $sessionstart);
    }

    /**
     * Returns true if session has ended as of today.
     * 
     * @return bool
     */
    public function session_ended() {
        $sessionend = new DateTime($this->session->session_end);
        return ($this->today > $sessionend);
    }

    /**
     * Returns true if term has ended as of today.
     * 
     * @return bool
     */
    public function term_ended() {
        $termend = new DateTime($this->session->term_end);
        return ($this->today > $termend);
    }

    /**
     * Returns true if we are still in session as of today.
     * 
     * @return bool
     */
    public function in_session() {
        $sessionstart = new DateTime($this->session->session_start);
        $sessionend = new DateTime($this->session->session_end);
        return ($this->today >= $sessionstart && $this->today <= $sessionend);
    }

    /**
     * Returns the term that follows given $term.  If no $term is 
     * specified, then the current term is used.
     * 
     * @param string $term
     * @return string
     */
    public function next_term($term = '') {

        if (empty($term)) {
            $term = $this->term;
        }

        return self::term_get_next($term);
    }
    
    public static function term_get_next($term) {
        $year = substr($term, 0, 2);
        $quarter = substr($term, -1);

        switch ($quarter) {
            case 'F':
                $nextyear = ($year == 99) ? '00' : sprintf('%02d', $year + 1);
                return $nextyear . 'W';
            case 'W':
                return $year . 'S';
            case 'S':
                return $year . '1';
            case '1':
                return $year . 'F';
        }
    }

    /**
     * Returns the human readable name of the quarter in lowercase.
     * 
     * @return string
     */
    public function quarter_name() {
        $name = '';

        switch ($this->quarter) {
            case '1':
                $name = 'summer';
                break;
            case 'S':
                $name = 'spring';
                break;
            case 'W':
                $name = 'winter';
                break;
            case 'F':
                $name = 'fall';
                break;
        }

        return $name;
    }

    /**
     * Returns a formatted string for current quarter.
     * 
     * @return string
     */
    public function string_for_quarter() {

        $name = $this->quarter_name();
        $year = substr($this->term, 0, 2);

        return ucwords($name) . ' 20' . $year;
    }

    /**
     * Returns a formatted string for current week, ex: 'Week 1'.  If it is 
     * finals week will return 'Finals week'.  If there is an error condition,
     * it will return an empty string.
     * 
     * @return string
     */
    public function string_for_week() {

        $week = $this->current_week();

        if ($week === self::WEEK_ERR || $week === self::WEEK_BETWEEN_SESSION) {
            return '';
        } else if ($week === self::WEEK_FINALS) {
            return get_string('finals_week', 'block_ucla_weeksdisplay');
        }

        return 'Week ' . $week;
    }

    /**
     * Factory method to get a UCLA session class. Possible sessions are summer and regular.
     * 
     * @param array $session result from registrar query 'ucla_getterms'
     * @return \block_ucla_weeksdisplay_summer_session|\block_ucla_weeksdisplay_regular_session
     */
    public static function create($session) {

        // Return appropriate session.
        foreach ($session as $ses) {

            // Regular session.
            if ($ses['session'] === 'RG') {
                return new \block_ucla_weeksdisplay_regular_session((object) $ses);

                // Summer session.
            } else if (in_array($ses['session'], \block_ucla_weeksdisplay_summer_session::$sessioncodes)) {
                return new \block_ucla_weeksdisplay_summer_session($session);
            }
        }

        return null;
    }

    /**
     * Saves configs to database.  Will update the week number, or set error
     * flags.
     *
     * Will also roll over the term when current session is over 1 week from
     * when it ended.
     */
    public function save_configs() {

        // Save the week.
        $this->save_current_week();

        // Check if we need to update term.
        if ($this->session_ended() && $this->current_week() !== self::WEEK_ERR) {
            // We want to wait one week after a session has ended before
            // switching current term to allow users to access their courses.
            $sessionend = new DateTime($this->session->session_end);
            $sessionend->modify('+1 week');
            if ($this->today > $sessionend) {
                // Roll over the term.
                $this->save_next_term();

                // Update active terms.
                $this->save_active_terms();
            }
        }
    }

    /**
     * Saves the next term.
     */
    public function save_next_term() {
        $nextterm = $this->next_term($this->term);
        set_config('currentterm', $nextterm);
    }

    /**
     * Saves the current week.
     */
    public function save_current_week() {
        // If week changed, trigger event.
        $oldweek = get_config('local_ucla', 'current_week');
        $currentweek = $this->current_week();
        if ($oldweek != $currentweek) {
            set_config('current_week', $currentweek, 'local_ucla');
            $event = block_ucla_weeksdisplay\event\week_changed::create(
                    array (
                        'other' => array('week' => $currentweek)
                        ));
            $event->trigger();
        }
    }

    /**
     * Saves the renderable weeks display string.
     */
    public function save_current_week_display() {
        set_config('current_week_display', $this->renderedweek, 'local_ucla');
    }

    /**
     * Saves list of active terms.
     */
    protected function save_active_terms() {

        $ta = array($this->term);
        $term = $this->term;

        for ($i = 0; $i < 3; $i++) {
            $term = $this->next_term($term);
            $ta[] = $term;
        }

        $termstring = implode(',', $ta);

        set_config('active_terms', $termstring, 'local_ucla');
        set_config('terms', $termstring, 'tool_uclacourserequestor');
        set_config('terms', $termstring, 'tool_uclacoursecreator');
    }

    /**
    * Returns a formatted string for the corresponding week in a RG for the given date, ex: 'Week 1'.
    * If it is finals week will return 'Finals week'.  
    * If there is an error condition or is between sessions, it will return an empty string.
    * 
    * @param DateTime $date
    * @return int
    */
    public function get_week(DateTime $date) {
        if ($date == null) {
            return -1;
        }

        $totalweeks = $this->weeks_in_session() - 1;
        $week = 1;
        $start = new DateTime($this->session->instruction_start);
        $end = (new DateTime($this->session->instruction_start))->modify('+' . $totalweeks . ' week');

        // Fall quarter begins on Week 0.
        if ($this->quarter == self::FALL) {
            $start->modify('-3 day');
            $end->modify('+3 day');
            $week--;
        }
        
        if ($date >= $start && $date < $end) {
            while ($week <= $totalweeks) {
                if ($date >= $start && $date < $start->modify('+1 week')) {
                    return $week;
                }
                $week++;
            }
        } else if ($date >= $end && $date < $end->modify('+1 week')) {
            return 11;
        }
        return -1;
    }
}
