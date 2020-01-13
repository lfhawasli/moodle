<?php
// This file is part of the UCLA TA sites block for Moodle - http://moodle.org/
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
 * Generator class to help in the writing of unit tests for the TA sites plugin.
 *
 * @package    block_ucla_tasites
 * @category   test
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/blocks/ucla_tasites/block_ucla_tasites.php');
require_once($CFG->dirroot . '/enrol/meta/lib.php');

/**
 * Data generator class.
 *
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_ucla_tasites_generator extends testing_block_generator {

    /**
     * Role ID of the ta_admin role.
     * @var int
     */
    public $taadminid = null;

    /**
     * Role ID of the ta role.
     * @var int
     */
    public $taid = null;

    /**
     * UCLA data generator.
     * @var local_ucla_generator
     */
    public $ucladatagen = null;

    /**
     * Create new TA site for given course record and user.
     * 
     * @param stdClass $parentcourse   Parent class.
     * @param array $typeinfo   This is a subset of what we get from
     *                          get_tasection_mapping:
     *                  [bysection] => [secnum] => [secsrs] => [array of srs numbers]
     *                                          => [tas] => [array of uid => fullname]
     *                  [byta] => [fullname] => [ucla_id] => [uid]
     *                                       => [secsrs] => [secnum] => [srs numbers]
     * 
     * @return stdClass         Newly create TA site course record
     */
    public function create_instance($parentcourse = null, $typeinfo = array()) {
        if (empty($parentcourse)) {
            // Create parent course.
            $class = $this->ucladatagen->create_class();
            $class = array_pop($class);
            $parentcourse = get_course($class->courseid);
        }
        // If typeinfo is empty, assume creating TA site with no sections for
        // one TA.
        if (empty($typeinfo)) {
            $ta = $this->ucladatagen->create_user();

            // Make sure that TA is role of TA in parent course.
            $this->datagenerator->enrol_user($ta->id, $parentcourse->id, $this->taid);

            $typeinfo = array();
            $typeinfo['byta'][fullname($ta)] = array('ucla_id' => $ta->idnumber);
        }
        $tasite = block_ucla_tasites::create_tasite($parentcourse, $typeinfo);

        return $tasite;
    }

    /**
     * Does all needed setup to get TA sites working in phpunit.
     *  - Creates TA and TA admin roles necessary to use the TA site block for 
     *    unit tests.
     *  - Enables meta enrollment plugin.
     */
    public function setup() {
        global $DB;

        $this->ucladatagen = $this->datagenerator->get_plugin_generator('local_ucla');

        // Create UCLA TA and TA admin roles.
        $roles = $this->ucladatagen->create_ucla_roles(array('ta', 'ta_admin'));
        $this->taadminid = $roles['ta_admin'];
        $this->taid = $roles['ta'];

        // To enable meta enrollment plugin we are just going to enable
        // everything.
        $all = enrol_get_plugins(false);
        set_config('enrol_plugins_enabled', implode(',', array_keys($all)));
    }

}