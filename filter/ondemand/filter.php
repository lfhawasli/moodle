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
 * OnDemand Video filter plugin.
 * @Author    Pramod Ubbala (AGS -> Infobase)
 * @package    filter_ondemand
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

//require_once(dirname(dirname(dirname(__FILE__))) . '/repository/lib.php');

class filter_ondemand extends moodle_text_filter {
  
  private $embedmarkers;

  public function filter($text, array $options = array()) {
    global $CFG, $PAGE;

    if (!is_string($text) or empty($text)) {
       // non string data can not be filtered anyway
       return $text;
    }

    if (stripos($text, '</a>') === false) {
    // Performance shortcut - if not </a> tag, nothing can match.
    return $text;
    }
    
    $this->embedmarkers = 'lti\.films\.com|localhost|\.mp4';
    
    $this->trusted = !empty($options['noclean']) or !empty($CFG->allowobjectembed);
     
    /*$newtext = preg_replace_callback($re = '~<a\s[^>]*href="([^"]*(?:' .
                $this->embedmarkers . ')[^"]*)"[^>]*">([^>]*)</a>~is',
                array($this, 'callback'), $text);*/

	preg_match("/<a\s+(?:[^>]*?\s+)?href=\"([^\"]*)\"/", $text, $newtext);

	 $width =  435;
     $height = 382;
      
	if(strpos($newtext[1], '.infobase.com') !== false) {

     $text = '<iframe src="' . $newtext[1] . '" frameborder="0" style="width: ' . $width . 'px;height:' . $height . 'px;" allowfullscreen></iframe>';

	}
    
    if (empty($newtext) or $newtext === $text) {
            // error or not filtered
            return $text;
        }

     return $text;

  }
  
  /**
     * Replace link with embedded content, if supported.
     *
     * @param array $matches
     * @return string
     */
    private function callback(array $matches) {
        global $CFG, $PAGE;
        
        // Get name.
        $name = trim($matches[2]);
        if (empty($name) or strpos($name, 'http') === 0) {
            $name = ''; // Use default name.
        }

        $width =  364;
        $height = 300;
       
        /*$result = '<iframe src="' . $matches[1] . '" frameborder="0" style="width: ' . $width . 'px;height:' . $height . 'px;" allowfullscreen></iframe>';*/

		$result = $matches[1];
        // If something was embedded, return it, otherwise return original.
        if ($result !== '') {
            return $result;
        } else {
            return $matches[0];
        }
    }

}
