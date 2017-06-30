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
 * Control Panel Module class.
 * @copyright  UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
class ucla_cp_module {
    /**
     *  The block that is associated with this class.
     **/
    const PARENT_BLOCK = 'block_ucla_control_panel';
    /**
     * @var string $itemname This is the item identifier. It should relate to the lang file.
     */
    public $itemname;
    /**
     * @var array $tags This determines which group the object is in.
     */
    public $tags;
    /**
     * @var string $requiredcap A required capability
     */
    public $requiredcap;
    /**
     * @var moodle_url $requiredcap This is the moodle_url.
     */
    public $action;
    /**
     *
     * @var array $options An array of display options
     */
    public $options;


     /**
      * @var string $associatedblock
      * This should not be used when declaring or creating a module.
      * This is purely a prototyping tool, and will automatically
      * be populated.
      */
    public $associatedblock = null;
     // Currently cannot do nested categories.

    /**
     * This will construct your object.
     *
     * If your action is simple (such as a link), you do not need to
     * do additional programming and just instantiate a ucla_cp_module.
     *
     * @param string $itemname
     * @param moodle_url $action
     * @param array $tags
     * @param string $capability
     * @param array $options
     *
     * @return void
     */
    public function __construct($itemname=null, $action=null, $tags=null,
            $capability=null, $options=null) {

        if ($itemname != null) {
            $this->itemname = $itemname;
        } else {
            // Available PHP 5.1.0+.
            if (!class_parents($this)) {
                throw new moodle_exception('You must specify an item '
                    . 'name if you are using the base ucla_cp_module '
                    . 'class!');
            }

            $this->itemname = $this->figure_name();
        }

        if ($tags == null) {
            $this->tags = $this->autotag();
        } else {
            $this->tags = $tags;
        }

        if ($action != null) {
            $this->action = $action;
        }

        if ($capability == null) {
            $this->requiredcap = $this->autocap();
        } else {
            $this->requiredcap = $capability;
        }

        if ($options === null) {
            $this->options = $this->autoopts();
        } else {
            $this->options = $options;
        }
    }

    /**
     *  This function is automatically called to generate some kind of name
     *  if you want this class to be automatically named to the class name.
     **/
    public function figure_name() {
        $origname = get_class($this);

        $parents = class_parents($this);

        foreach ($parents as $parent) {
            if (method_exists($parent, 'figure_name')) {
                return substr($origname, strlen($parent) + 1);
            }
        }

        return $origname;
    }

    /**
     * This is the default function that is used to check if the module
     * should be displayed or not.
     * Currently only supports one capability per module.
     * @param stdClass $course
     * @param course_context $context
     **/
    public function validate($course, $context) {
        $hc = true;

        if ($this->requiredcap != null) {
            $hc = has_capability($this->requiredcap, $context);
        }

        return $hc;
    }

    /**
     * Simple wrapper function.
     **/
    public function is_tag() {
        return empty($this->action);
    }


    /**
     * This function can be overwritten to allow a child class to
     * define their tags in code instead when instantiated.
     **/
    public function autotag() {
        return null;
    }

    /**
     * @see ucla_cp_module::autotag()
     *
     * This is similar to autotag, except for the capability
     * that is used to check for validity.
     **/
    public function autocap() {
        return null;
    }

    /**
     * @see ucla_cp_module::autotag()
     *
     * This is similar to autotag, except for the options
     * that are set.
     **/
    public function autoopts() {
        return array();
    }

    /**
     * Simple wrapper function.
     **/
    public function get_action() {
        return $this->action;
    }

    /**
     * Simple function for differentiating different instances
     * of the same type of control panel module.
     **/
    public function get_key() {
        return $this->itemname;
    }

    /**
     * This is a wrapper to get a set option from the current class.
     * Options, unfortunately, are known by the viewer.
     *
     * @param string $option
     **/
    public function get_opt($option) {
        if (!isset($this->options[$option])) {
            return null;
        }

        return $this->options[$option];
    }

    /**
     * This is to set options.
     *
     * @param string $option
     * @param boolean $value
     **/
    public function set_opt($option, $value) {
        $this->options[$option] = $value;
    }

    /**
     *  Returns the relevant block of th emodule.
     *  This function should not overwritten, nor should be changed.
     **/
    public function associated_block() {
        if ($this->associatedblock == null) {
            return self::PARENT_BLOCK;
        }
        return $this->associatedblock;
    }

    /**
     *  Magic loader function.
     *
     * @param string $name
     **/
    public static function load($name) {
        $modulepath = dirname(__FILE__) . '/modules/';
        if (!file_exists($modulepath)) {
            debugging(get_string('badsetup', self::PARENT_BLOCK));
            return false;
        }

        $filepath = $modulepath . $name . '.php';

        if (!file_exists($filepath)) {
            debugging(get_string('badmodule', self::PARENT_BLOCK,
                $name));
            return false;
        }

        require_once($filepath);
        return true;
    }

    /**
     * Convenience wrapper to build a particular cp_module.
     *
     * @param array $args
     *
     * @return ucla_cp_module
     */
    public static function build($args) {

        $params = get_class_vars(get_class());

        if (is_object($args)) {
            $args = get_object_vars($args);
        }

        $callparams = array();
        foreach ($params as $param => $noval) {
            if (isset($args[$param])) {
                $callparams[$param] = $args[$param];
            } else {
                $callparams[$param] = null;
            }
        }

        // Manually check for class var whose name does not match args'
        // snake-case key.

        if (isset($args['item_name'])) {
            $callparams['item_name'] = $args['item_name'];
        } else {
            $callparams['item_name'] = null;
        }

        return new ucla_cp_module($callparams['item_name'], $callparams['action'],
            $callparams['tags'], $callparams['requiredcap'], $callparams['options']);
    }
}
