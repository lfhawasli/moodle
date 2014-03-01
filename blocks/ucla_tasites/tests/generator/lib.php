<?php
// This file is part of the UCLA TA site creator plugin for Moodle - http://moodle.org/
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
     * Create new TA site for given course record and user.
     * 
     * @param stdClass $course  Parent course
     * @param array $user       Owner of TA site
     * 
     * @return stdClass         Newly create TA site course record
     */
    public function create_instance($course = null, array $user = null) {
        if (empty($course)) {
            $course = $this->datagenerator->create_course();
        }
        if (empty($user)) {
            $user = $this->datagenerator->create_user();
        }
        $tasite = $this->create_instance_with_role($course, (array) $user, 'ta');
        return $tasite;
    }

    /**
     * Create new TA site for given course record. Will also make sure that user
     * is a TA with given role for parent course.
     * 
     * @param stdClass $course  Parent course
     * @param array $user       Owner of TA site
     * @param string $role      Should be 'ta' or 'ta_admin', defaults to 'ta'.
     * 
     * @return stdClass         Newly create TA site course record
     */
    public function create_instance_with_role($course, array $user, $role = 'ta') {
        global $DB;

        // Make sure that user has given role in parent course.
        $context = context_course::instance($course->id);
        $roleid = $this->taid;
        if ($role == 'ta_admin') {
            $roleid = $this->taadminid;
        }
        $this->datagenerator->enrol_user($user['id'], $course->id, $roleid);

        $tainfo = new stdClass();
        $tainfo->parent_course = $course;
        $tainfo->id = $user['id'];
        $tainfo->firstname = $user['firstname'];
        $tainfo->lastname = $user['lastname'];
        $tainfo->fullname = fullname((object) $user);

        $tasite = block_ucla_tasites::create_tasite($tainfo);

        return $tasite;
    }

    /**
     * Does al needed setup to get TA sites working in phpunit.
     *  - Creates TA and TA admin roles necessary to use the TA site block for 
     *    unit tests.
     *  - Enables meta enrollment plugin.
     */
    public function setup() {
        global $DB;

        // Create UCLA TA and TA admin roles.
        $this->datagenerator
                ->get_plugin_generator('local_ucla')
                ->create_ucla_roles();
        $this->taadminid = $DB->get_field('role', 'id',
                array('shortname' => 'ta_admin'));
        $this->taid = $DB->get_field('role', 'id', array('shortname' => 'ta'));

        // To enable meta enrollment plugin we are just going to enable
        // everything.
        $all = enrol_get_plugins(false);
        set_config('enrol_plugins_enabled', implode(',', array_keys($all)));
    }

}