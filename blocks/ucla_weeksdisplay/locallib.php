<?php

/**
 * Class that represents a UCLA session 
 */
class ucla_session {
    
    private $_session;          // An array of session objects from registrar
    protected $_quarter;        // Current quarter in single digit code
    protected $_year;           // Quarter year
    protected $_today;          // Today's timestamp
    private $_summer;           // boolean flag for summer
    protected $_current_week;   // Current week in session (max of summer sessions)
    protected $_current_week_summera = null; // Current week in summer session A.
    protected $_current_week_summerc = null; // Current week in summer session C.
    protected $_lookahead;      // Number of terms to look ahead
    private $_term;             // Current term.
    
    function __construct($session) {
        $this->_session = $this->key_session($session);

        // Summer contains more than 1 session
        $this->_summer = count($this->_session) > 1 ? true : false;        

        // Other info
        $this->_quarter = substr($session[0]['term'], 2);
        $this->_year = substr($session[0]['term'], 0, -1);
        $this->_term = $session[0]['term'];
        
        // All session dates are tested against today
        $this->_today = substr(date('c'), 0, 10);
        
        // Use current week
        $this->_current_week = (int)get_config('local_ucla', 'current_week');
        
        if(empty($this->_current_week)) {
            // Start with undetermined week
            $this->_current_week = -1;
        }
        
        // Number of active terms to retrieve (not including current term)
        $this->_lookahead = 3;
    }
    
    
    /**
     * Updates term information such as the current week/session, or sets 
     * the next term if that's needed
     */
    function update() {

        if(!$this->_summer) {   // Regular term in session
            
            // Check if quarter is in session
            if($this->in_session($this->_session['RG']->session_end)) {
            
                // Check if session started and instruction has NOT started
                if($this->session_started($this->_session['RG']->session_start)
                        && $this->instruction_started($this->_session['RG']->instruction_start)) {
                    $weeks_str = $this->get_week_str($this->_session['RG']);
                } else {
                    // Get current quarter and year 
                    if($this->_quarter == 'W' && strcmp(date('y', strtotime($this->_today)), $this->_year) < 0) {
                        $weeks_str = get_string('winter_break', 'block_ucla_weeksdisplay');
                    } else {
                        $weeks_str = $this->get_quarter_and_year();
                    }
                }

                $weeks_str = html_writer::div($weeks_str, 'weeks-display label-' . strtolower($this->quarter_name($this->_quarter)));
                // Update weeks display
                $this->update_week_display($weeks_str);
                
            } else { // Update term when quarter is not in session
                $next_term = self::next_term($this->_term);
                $this->update_term($next_term);
                $this->_current_week = -1;
                $this->update_week();
            }
            
        } else { // Summer
            // Hold concat of summer session strings
            $quarter_week = '';
            $separator = '';
            
            // Check if summer session A has started
            if($this->in_session_summer($this->_session['1A']->session_start,
                    $this->_session['1A']->session_end)) {
                $quarter_week .= $this->get_week_str($this->_session['1A']);
                $separator = ' | ';
            }
            
            // Check if summer session C has started
            if($this->in_session_summer($this->_session['6C']->session_start, 
                    $this->_session['6C']->session_end)) {
                $quarter_week .= $separator;
                $quarter_week .= $this->get_week_str($this->_session['6C']);
            }
            
            // Or we're in week prior to session start, so display quarter and year
            if(empty($quarter_week)) {
                $quarter_week = $this->get_quarter_and_year();
            }
            
            // Summer session ended, update term
            if(!$this->in_session($this->_session['6C']->session_end)) {
                $next_term = self::next_term($this->_term);
                $this->update_term($next_term);
                $this->_current_week = -1;
                $this->update_week();
            }
            $quarter_week = html_writer::div($quarter_week, 'weeks-display label-summer');
            // Update quarter string
            $this->update_week_display($quarter_week);
        }
        
        // Update the current week
        $this->update_week();
        
        // Update active terms
        $this->update_active_terms();
    }
    
    /**
     * Convert the session array into session objects for desired sessions
     * 
     * @param type $session
     * @return array 
     */
    private function key_session($session) {
        $k = array();
        
        foreach($session as $s) {
            if($s['session'] == 'RG' || $s['session'] == '1A' || $s['session'] == '6C') {
                $k[$s['session']] = (object)$s;
           }
        }
        
        return $k;
    }

    /**
     * Get the week string
     * 
     * @param obj $session
     * @return string 
     */
    private function get_week_str($session) {
        $quarter_year = $this->get_quarter_and_year();
        $summersession = '';
        
        // Append session for summer
        if($this->_summer) {
            $summersession = get_string('session', 'block_ucla_weeksdisplay', substr($session->session, -1)) . ', ';
        }

        $weekstr = html_writer::span($quarter_year . ' - ' . $summersession, 'session ') .
                html_writer::span($this->get_week_for_session($session), 'week');
        
        return $weekstr;
    }
    
    /**
     * Get a string of <quarter> <year>
     * 
     * @return string 
     */
    private function get_quarter_and_year() {
        $yearstart = date('Y');
        $yearstart = substr($yearstart, 0, 2);
        return $this->quarter_name($this->_quarter) . ' ' . $yearstart . $this->_year;
    }
    
    /**
     * Gets the current week for a given session object
     * 
     * @param obj $session
     * @return string containing week display
     */
    private function get_week_for_session($session) {
        
        // Get a weeks count offset for instruction_star and today
        $weeks_start = date('W', strtotime($session->instruction_start));
        $weeks_today = date('W', strtotime($this->_today));
        
        // Offset week by +1 since week 1 will be week 0 if both values are equal
        $weeks = $weeks_today - $weeks_start + 1;
        
        // Fall has week 0
        if($this->_quarter == 'F') {
            $weeks--;
        }
         
        // Check if we need to display 'finals week'. There are only finals
        // weeks for "RG" (regular session) terms.
        if ($session->session == "RG" && $weeks > 10) {
            $week_str = get_string('finals_week', 'block_ucla_weeksdisplay');
        } else {
            $week_str = get_string('week', 'block_ucla_weeksdisplay', $weeks);
        }

        // Update summer sessions, if needed.
        // See if we are in Session A.
        if (in_array($session->session, array('6A', '8A', '9A', '1A'))) {
            $this->_current_week_summera = $weeks;
        } else if ($session->session == '6C') {
            $this->_current_week_summerc = $weeks;
        }

        // Update current week
        if($weeks > $this->_current_week) {
            $this->_current_week = $weeks;
            events_trigger('ucla_weeksdisplay_changed', $weeks);
        }
        
        return $week_str;
    }
    
    /**
     * Checks if term is in session given the session end date
     * 
     * @param type $end
     * @return bool true if term is in session
     */
    private function in_session($end) {
        $val = strcmp($this->_today, $end);
        return ($val < 0 ? true : false);
    }

    /** 
     * Checks if a summer session is active given session start and end dates
     * 
     * @param string $start of session
     * @param string $end of session
     * @return boolean true if session is active
     */
    private function in_session_summer($start, $end) {
        $start = substr($start, 0, 10);
        $end = substr($end, 0, 10);
        
        $sum_start = strcmp($start, $this->_today);
        $sum_end = strcmp($this->_today, $end);

        if($sum_start <= 0 && $sum_end <= 0) {
            return true;
        }
        
        return false;
    }
    
    private function session_started($start) {
        $start = substr($start, 0, 10);
        $val = strcmp($start, $this->_today);
        return ($val <= 0 ? true : false);
    }
    
    /**
     * Checks if instruction has started from a given start date
     * 
     * @param type $start
     * @return bool true if instruction has started
     */
    private function instruction_started($start) {
        $start = substr($start, 0, 10);
        $val = strcmp($start, $this->_today);
        return ($val <= 0 ? true : false);
    }
    
    /**
     * Gets next term from a term
     * 
     * @param string $term
     */
    public static function next_term($term) {
        
        $year = substr($term, 0, 2);
        $quarter = substr($term, -1);

        switch($quarter) {
            case 'F':
                $next_year = ($year == 99) ? '00' : sprintf('%02d', $year + 1);
                return $next_year.'W';
            case 'W':   
                return $year.'S';
            case 'S':
                return $year.'1';
            case '1':
                return $year.'F';
        }
    }
        
    /**
     * Returns quarter name string given a single digit code
     * 
     * @param char $quarter code
     * @return string quarter name
     */
    private function quarter_name($quarter) {
        switch ($quarter) {
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
        
        return get_string($name, 'block_ucla_weeksdisplay');
    }
    
    /**
     * Update term config
     * 
     * @param type $term 
     */
    protected function update_term($term) {
        block_ucla_weeksdisplay::set_term($term);
    }
    
    /**
     * Update weeks display config
     * 
     * @param type $str 
     */
    protected function update_week_display($str) {
        block_ucla_weeksdisplay::set_week_display($str);

    }
    
    /**
     * Update current week config
     *  
     */
    protected function update_week() {
        // Update summer sessions. If not in summer, then will set config to null.
        block_ucla_weeksdisplay::set_current_week($this->_current_week_summera, 'a');
        block_ucla_weeksdisplay::set_current_week($this->_current_week_summerc, 'c');

        block_ucla_weeksdisplay::set_current_week($this->_current_week);
    }
    
    /**
     * Update active terms
     *  
     */
    protected function update_active_terms() {
        $term = $this->_year . $this->_quarter;
        $ta = array($term);
        
        for($i = 0; $i < $this->_lookahead; $i++) {
            $term = self::next_term($term);
            $ta[] = $term;           
        }
        
        block_ucla_weeksdisplay::set_active_terms($ta);
    }
}