<?php
// This file is part of the UCLA public/private plugin for Moodle - http://moodle.org/
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
 * Public/Private module class file.
 *
 * @package    local_publicprivate
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

include_once($CFG->dirroot.'/local/publicprivate/lib/module_exception.class.php');
include_once($CFG->dirroot.'/local/publicprivate/lib/course.class.php');
include_once($CFG->dirroot.'/local/publicprivate/lib/site.class.php');
include_once($CFG->libdir.'/datalib.php');

/**
 * PublicPrivate_Module
 *
 * Object that represents a course module (course_modules tuple) in terms of
 * public/private, providing related accessors and mutators for checking
 * protections and enabling/disabling
 *
 * @package    local_publicprivate
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class PublicPrivate_Module {
    /**
     * The key value for the record from the `course_modules` table.
     *
     * @var int
     */
    private $_course_module_id;

    /**
     * The represented record from the `course_modules` table.
     *
     * @var object
     */
    private $_course_module_obj = null;

    /**
     * The record for the course from `course` table as bounded to the module.
     *
     * @var PublicPrivate_Course
     */
    private $_publicprivate_course_obj = null;

    /**
     * Constructor for a PublicPrivate_Module object bound to $course_module.
     *
     * @global Moodle_Database $DB
     * @param int|object|array $course_module
     */
    public function __construct($course_module) {
        /**
         * If passed a scalar, only store the id. Record will be lazy
         * instantiated on first access throgh _course_module(). Otherwise,
         * if passed a record, store it as the represented record.
         */
        if(is_scalar($course_module)) {
            $this->_course_module_id = (int)$course_module;
        } else {
            $this->_course_module_obj = is_object($course_module) ? $course_module : (object)$course_module;
            $this->_course_module_id = $this->_course_module_obj->id;
        }
    }

    /**
     * Adds grouping conditional activity using public/private grouping.
     *
     * Uses code from upgrade_group_members_only() in lib/db/upgradelib.php.
     *
     * @return boolean
     */
    private function add_grouping_condition() {
        global $CFG, $DB;
        require_once("$CFG->libdir/db/upgradelib.php");

        $ppgrouping = $this->_publicprivate_course()->get_grouping();
        $availability = $this->_course_module()->availability;
        $newavailability = upgrade_group_members_only($ppgrouping, $availability);
        return $DB->set_field('course_modules', 'availability', $newavailability,
                array('id' => $this->_course_module()->id));
    }

    /**
     * Finds and removes all group and grouping conditions.
     *
     * @return boolean
     */
    private function remove_group_grouping_conditions() {
        global $DB;
        $availability = json_decode($this->_course_module()->availability);

        if (!empty($availability) && isset($availability->c)) {
            // This function recurses into subtrees for us.
            $this->search_group_grouping_conditions($availability, true);

            if (count($availability->c) == 0) {
                $newavailability = null;
            } else {
                $newavailability = json_encode($availability);
            }

            return $DB->set_field('course_modules', 'availability', $newavailability,
                    array('id' => $this->_course_module()->id));
        }

        return false;   // No group and grouping conditions set.
    }

    /**
     * Returns a PublicPrivate_Module object for the provided $course_module.
     *
     * @param int|object|array $coursemodule
     * @return PublicPrivate_Module
     */
    public static function build($coursemodule) {
        return new PublicPrivate_Module($coursemodule);
    }

    /**
     * Returns true if the course module does not have group and grouping
     * restrictions.
     *
     * @return boolean
     */
    public function is_public() {
        return !$this->is_private();
    }

    /**
     * Returns true if course module is private, meaning that it has group and
     * grouping restrictions.
     *
     * @return boolean
     */
    public function is_private() {
        $availability = json_decode($this->_course_module()->availability);
        $private = false;

        if (!empty($availability) && isset($availability->c)) {
            $private = $this->search_group_grouping_conditions($availability);
        }

        return $private;
    }

    /**
     * Enables public/private for course_module:
     *  - If grouping restriction is not set, then set the groupingid to the
     *    course's public/private grouping. Otherwise keep the current grouping.
     *
     * @throws PublicPrivate_Module_Exception
     */
    public function enable() {
        try {
            $grouping = $this->get_grouping();
            if (empty($grouping)) {
                // Add new grouping conditional availablity.
                $this->add_grouping_condition();
                rebuild_course_cache($this->get_course(), true);

                $context = context_module::instance($this->_course_module_obj->id);
                $event = \local_publicprivate\event\private_used::create(array('context' => $context));
                $event->trigger();
            }
        } catch (DML_Exception $e) {
            throw new PublicPrivate_Module_Exception('Failed to set public/private visibility settings for module.', 300, $e);
        }
    }

    /**
     * Disables public/private for course_module:
     *  - Removes the current group and grouping conditions.
     *
     * @throws PublicPrivate_Module_Exception
     */
    public function disable() {
        try {
            if ($this->is_private()) {
                $this->remove_group_grouping_conditions();
                rebuild_course_cache($this->get_course(), true);

                $context = context_module::instance($this->_course_module_obj->id);
                $event = \local_publicprivate\event\public_used::create(array('context' => $context));
                $event->trigger();
            }
        } catch (DML_Exception $e) {
            throw new PublicPrivate_Module_Exception('Failed to set public/private visibility settings for module.', 400, $e);
        }
    }

    /**
     * Returns `course_module`.`id` (the key).
     *
     * @return int
     */
    public function get_id() {
        return $this->_course_module_id;
    }

    /**
     * Returns `course_module`.`course` which corresponds to `course`.`id`.
     *
     * @throws PublicPrivate_Module_Exception
     * @return int
     */
    public function get_course() {
        return $this->_course_module()->course;
    }

    /**
     * Returns `course_module`.`groupingid`, preferring the public-private grouping if found.
     *
     * @throws PublicPrivate_Module_Exception
     * @return int  Returns 0 if no grouping is found.
     */
    public function get_grouping() {
        $availability = json_decode($this->_course_module()->availability);
        $ppgrouping = $this->_publicprivate_course()->get_grouping();
        if (!empty($availability) && isset($availability->c)) {
            return $this->search_grouping_conditions($availability, $ppgrouping);
        }
        return 0;
    }

    /**
     * Check for grouping conditions, recursing into subtrees.
     *
     * If the public-private grouping condition is found, return its grouping id.
     * Otherwise, return the grouping id of the last grouping condition, or 0 if there are none.
     *
     * @param object &$availability Non-empty availability tree.
     * @param int $ppgrouping Public-private grouping id.
     * @return int
     */
    private function search_grouping_conditions($availability, $ppgrouping) {
        $groupingfound = 0;

        foreach ($availability->c as $index => $child) {
            if (empty($child)) {
                continue;
            }

            $temp = $groupingfound;
            if (isset($child->c)) {
                // $child is a tree.
                $temp = $this->search_grouping_conditions($child, $ppgrouping);
            } else {
                // $child is a condition.
                if ($child->type == 'grouping') {
                    $temp = $child->id;
                }
            }

            // With the grouping availability plugin there might be multiple
            // groupings, so try to find the grouping with public/private, else
            // return the last grouping found.
            if ($groupingfound != $ppgrouping) {
                $groupingfound = $temp;
            }
        }

        return $groupingfound;
    }

    /**
     * Check for group and grouping conditions, recursing into subtrees.
     *
     * If $delete is true, delete all group and grouping conditions.
     *
     * @param object &$availability Non-empty availability tree.
     * @param boolean $delete Optional.
     * @return boolean
     */
    private function search_group_grouping_conditions(&$availability, $delete = false) {
        $private = false;
        $useshowc = !empty($availability->showc);

        foreach ($availability->c as $index => &$child) {
            if (empty($child)) {
                continue;
            }

            $deletechild = false;
            if (isset($child->c)) {
                // $child is a tree.
                $private = $this->search_group_grouping_conditions($child, $delete);
                // Delete the child if it is now empty.
                $deletechild = count($child->c) == 0;
            } else if ($child->type == 'group' || $child->type == 'grouping') {
                // $child is a condition.
                $private = true;
                $deletechild = $delete;
            }

            if (!$delete && $private) {
                break;
            }

            if ($deletechild) {
                unset($availability->c[$index]);
                if ($useshowc) {
                    unset($availability->showc[$index]);
                }
            }
        }

        // Re-index object array.
        $availability->c = array_values($availability->c);
        if ($useshowc) {
            $availability->showc = array_values($availability->showc);
        }

        return $private;
    }

    /**
     * Returns a PublicPrivate_Course object that is bounded to the record for
     * the course from `course` table as bounded to the module.
     *
     * @throws PublicPrivate_Course_Exception
     * @return PublicPrivate_Course
     */
    private function &_publicprivate_course() {
        /**
         * If object does not already have a cached version, build it.
         */
        if(!$this->_publicprivate_course_obj) {
            $this->_publicprivate_course_obj = new PublicPrivate_Course($this->get_course());
        }

        return $this->_publicprivate_course_obj;
    }

    /**
     * Returns the represented record from the `course_modules` table.
     *
     * @throws PublicPrivate_Module_Exception
     * @return object
     */
    private function &_course_module() {
        global $DB;

        /**
         * If object does not already have a cached version, retrieve it.
         */
        if(!$this->_course_module_obj) {
            try {
                $this->_course_module_obj = $DB->get_record('course_modules', array('id'=>$this->_course_module_id), '*', MUST_EXIST);
            } catch(DML_Exception $e) {
                throw new PublicPrivate_Module_Exception('Failed to retrieve course module object.', 600, $e);
            }
        }

        return $this->_course_module_obj;
    }
}
