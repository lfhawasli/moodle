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
 * This is the eXplorance BPI Moodle block.
 * 
 * @package    moodlecore
 * @subpackage block
 * @copyright  2014 eXplorance inc.
 * @version    1.2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_explorancebpi extends block_base {

    /** @var string This is the default iframe height in pixels. */
    private $DefaultHeight = '290';

    /** @var string This is the default iframe height in pixels when it's a launcher block. */
    private $DefaultHeightFB = '200';

    /** @var bool This variable determines whether the URL is valid. */
    private $IsSupported = false;

    /** @var bool This variable determines whether the block is a launcher block. */
    private $IsLauncherOnly = false;
	
    /**
     * Note that $this->config is available in all block methods except init().
     * This is because init() is called immediately as the block is being created,
     * with the purpose of setting things up, so $this->config has not yet been instantiated.
     */
    function init() {
        $this->title = get_string('pluginname','block_explorancebpi');
    }

    /**
     * Automatically called by Moodle as soon as our instance configuration is loaded and available (that is, immediately after init() is called).
     *
     */
    public function specialization() {}

    /**
     * Automatically called by Moodle to know if multiple instances are allowed.
     *
     * @return bool
     */
    function instance_allow_multiple() { 
		return true; 
	}

    /**
     * Method overwritten, being called by Moodle to get the content of the block.
     *
     * @return stdClass The class that contains the code to be outputted
     */
    function get_content() {
        global $USER, $CFG;

        require_once($CFG->dirroot.'/message/lib.php');

        // If we already have content, we must return existing content.
        if ($this->content !== NULL) {
			return $this->content;
		}

        // We instantiate a new stdClass to fill content.
        $this->content = new stdClass;
        $this->content->text = '';

        // If the user is not logged in or if the user id is not empty, we show empty block.
        if (! isloggedin()) {
			return $this->content;
		}

        // If the configuration is not done yet, we display a message stating that it needs to be done to be used.
        if ( empty($this->config->url)) {
            $this->content->text .= "<div style=\"color: #FF3333;\">".get_string('notconfigured','block_explorancebpi')."</div>";
            return $this->content;
        }

        // We will set the page title and figure out if the url is valid.
        $this->set_title();

        // If the url is invalid, we show an error message.
        if ($this->IsSupported == false) {
            $this->content->text .= "<div style=\"color: #FF3333;\">".get_string('unsupported','block_explorancebpi')."</div>";
            return $this->content;
        }

        // Starting from here, everything should be fine, we start making the IFRAME.

        // We will set the URL with the right parameters.
        $newUrl = $this->get_ModifiedUrl();

        // To detect the blockid, we fetch it from the URL.
        $pos = strpos($newUrl,"blockid");
        $ArrBlockID = array();
        $ArrBlockID = preg_split('/&/',substr($newUrl, $pos+8, 1000),5);
        $blockId = $ArrBlockID[0];

        if (is_numeric($this->config->height)) {

            $height = $this->config->height;            // In case we have a height defined in the config, we overwrite any other height.

        } elseif ($this->IsLauncherOnly === true) {

            $height = $this->DefaultHeightFB;           // In launcher mode we have a specific height (if not overwritten).

        } else {

            $height = $this->DefaultHeight;             // In any other cases, we have a default height.

        }

        // START UCLA MOD: CCLE-7037 - Evaluate Blue Course Evaluation software
//        // In the end, we append the HTML of the IFRAME in the text parameter.
//		$this->content->text .= '<div class="userinfoblock"><iframe frameBorder="0" name="name_bpiframe_'.$blockId.'" id="id_bpiframe_'.$blockId.'" src="'.$newUrl.'" style="border: 0px solid #333;background-color:#FFF;" width="100%" height="'.$height.'"></iframe></div>';
        $ctx = stream_context_create(array('http' => array('timeout' => 3)));
        $response = file_get_contents($newUrl, false, $ctx);
        if (strpos($response, '  <div>Return Value (when no tasks are returned)</div>') === false &&
                strpos($response, 'You do not have any tasks to complete at this time') === false) {
            // In the end, we append the HTML of the IFRAME in the text parameter.
            $this->content->text .= '<div class="userinfoblock"><iframe frameBorder="0" name="name_bpiframe_'.$blockId.'" id="id_bpiframe_'.$blockId.'" src="'.$newUrl.'" style="border: 0px solid #333;background-color:#FFF;" width="100%" height="'.$height.'"></iframe></div>';            
        }
        // END UCLA MOD: CCLE-7037

        return $this->content;
    }

    /**
     * This method will set the title and will automatically figure out if we support this block type.
     *
     */
    function set_title(){
        // Get the URL and transform it to lower for later use.
        $tmp= strtolower($this->config->url);

        // Depending on the link type, we will change the header title value for the user.
        if (strpos($tmp,"reportview.aspx") !== false) {
            $this->IsSupported = true;
            $this->title = get_string('reportviewtitle','block_explorancebpi');
        }

        if (!$this->IsSupported && strpos($tmp,"taskview.aspx") !== false) {
            $this->IsSupported = true;
            $this->title = get_string('taskviewtitle','block_explorancebpi');
        }

        if (!$this->IsSupported && strpos($tmp,"fbview.aspx") !== false) {
            $this->IsSupported = true;
            $this->title = get_string('feebackviewtitle','block_explorancebpi');
        }

        if (!$this->IsSupported && strpos($tmp,"subjectview.aspx") !== false) {
            $this->IsSupported = true;
            $this->title = get_string('subjectmanagementtitle','block_explorancebpi');
        }		
        
        if (!$this->IsSupported && strpos($tmp,"launcher.aspx") !== false) {
            $this->IsSupported = true;
            $this->IsLauncherOnly = true;
            $this->title = get_string('launcherTitle','block_explorancebpi');
        }

        if ($this->IsSupported == false) {
            $this->title = get_string('unsupported','block_explorancebpi');
        }
    }

    /**
     * Method that does string replaces in order to put right parameters in url that is being returned.
     *
     * @return string modified url
     */
    function get_ModifiedUrl(){
        global $USER;

        $newUrl = str_ireplace("&userid=", "&userid=".$this->randomizeQueryString($USER->username), $this->config->url);

        // Set the language from the URL (Feedback).
        $newUrl = str_ireplace("&lng=", "&lng=".$USER->lang, $newUrl);

        // Replace the &culture= with &lng=ACTUALCULTURE (FOR REPORT AND TASKS).
        $newUrl =  str_ireplace("&culture=", "&culture=".$USER->lang, $newUrl);

		return $newUrl;
    }

    /**
     * Encrypts a string by adding 3 or 4 random characters before every character of the string.
     * Maps every single other character with a character mapping function.
     *
     * @param $str the string that needs to be encrypted
     * @return string
     */
    function randomizeQueryString($str){
        $numstr = array();
        $nbr = strlen($str);
        $outstr = "";

        for ($i = 0; $i < $nbr; $i++) {
            $numstr[$i] = substr($str, $i, 1);
        }

        for ($j = 0; $j < $nbr; $j++) {

            // Even number - we add 3 random chars before
            if ($j % 2 == 0) {
                $outstr .= $this->getRandomChars(3).$this->encryptSingleChar($numstr[$j]);

            // Odd number - we add 4 random chars before
            } else {
                $outstr .= $this->getRandomChars(4).$this->encryptSingleChar($numstr[$j]);
            }
        }

        return $outstr;
    }

    /**
     * Will return desired x random characters to fill.
     *
     * @param $size int The quantity of desired characters
     * @return string The random characters
     */
    function getRandomChars($size)
    {
        $randompool = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_-0123456789";
        $str = "";

        for ($i = 0; $i < $size; $i++) {
            $str .= $randompool[rand(0, (strlen($randompool) - 1))];
        }
		
        return $str;
    }

    /**
     * Character mapping function.
     * Will map any char to another char.
     *
     * @param $c string A character
     * @return string The mapped character
     */
    function encryptSingleChar($c) {
        $characterspool = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_-0123456789";
        $mapping = "iGFvTSI32oefCBrQP9-_lbcyWXLK6YVjADsROEz5mndxwqUN810hkauHZMJ74tgp";

        $index = strpos($characterspool, $c);

        // If the char is found in the pool, we return corresponding char.
        if ($index !== false) {
            return $mapping[$index];
        } else {
			// if the char is not found in the pool, we return the original char.
            return $c;
        }
    }
}


