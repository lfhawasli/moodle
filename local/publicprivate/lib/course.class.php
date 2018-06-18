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

include_once($CFG->dirroot.'/local/publicprivate/lib/course_exception.class.php');
include_once($CFG->dirroot.'/local/publicprivate/lib/module.class.php');
include_once($CFG->dirroot.'/local/publicprivate/lib/site.class.php');
include_once($CFG->dirroot.'/group/lib.php');
include_once($CFG->dirroot . '/lib/enrollib.php');

require_once($CFG->dirroot.'/lib/grouplib.php');
require_once($CFG->dirroot.'/group/lib.php');

/**
 * PublicPrivate_Course
 *
 * Object that represents a course in terms of public/private, providing related
 * accessors and mutators for enabling/disabling public/private, adding and
 * removing users from the public/private group, and checking if a user is a
 * member of the public/private group.
 *
 * @package    local_publicprivate
 * @copyright  2016 UC Regents
 */

class PublicPrivate_Course {
    /**
     * The represented record from the `course` table.
     *
     * @var object
     */
    private $_course = null;

    /**
     * Constructor for a PublicPrivate_Course object bound to $course.
     *
     * @param int|object|array $course
     * @throws PublicPrivate_Course_Exception
     */
    public function __construct($course) {
        global $DB;

        if (is_scalar($course)) {
            try {
                $this->_course =  $DB->get_record('course', array('id' => $course), '*', MUST_EXIST);
            } catch (DML_Exception $e) {
                throw new PublicPrivate_Course_Exception('Database query failed for __construct.', 100, $e);
            }
        } else if (is_array($course)) {
            $this->_course = (object)$course;
        } else {
            $this->_course = $course;
        }

        if (!self::is_publicprivate_capable($this->_course)) {
            // No need to throw an error if publicprivate is disabled.
            throw new PublicPrivate_Course_Exception('Required course properties not available for __construct.', 101);
        }
    }

    /**
     * Returns if the course is capable of public private functionality.
     *
     * @param object $course
     * @return boolean
     */
    public static function is_publicprivate_capable($course) {
        return isset($course->enablepublicprivate)
            && isset($course->grouppublicprivate)
            && isset($course->groupingpublicprivate);
    }

    /**
     * Returns a PublicPrivate_Course object for the provided $course.
     *
     * @param int|object|array $course
     * @throws PublicPrivate_Course_Exception
     * @return PublicPrivate_Course
     */
    public static function build($course) {
        return new PublicPrivate_Course($course);
    }

    /**
     * Get the course that this object is bound to.
     *
     * @return object
     */
    public function get_course() {
        return $this->_course;
    }

    /**
     * Get the id of the course public/private group or false if not active.
     *
     * @return int
     */
    public function get_group() {
        return $this->is_activated() ? $this->_course->grouppublicprivate : false;
    }

    /**
     * Passed a group id or either a group record object or array, returns true
     * if the group is the same as the course's public/private group or false
     * otherwise.
     *
     * @param int|object|array $group
     * @return boolean
     */
    public function is_group($group) {
        $groupid = is_scalar($group)
                    ? $group
                    : (is_object($group) && isset($group->id)
                        ? $group->id
                        : (is_array($group) && isset($group['id'])
                            ? $group['id']
                            : false));

        $cgroupid = $this->get_group();

        return $cgroupid !== false && $groupid !== false && $cgroupid == $groupid;
    }

    /**
     * Get the id of the course public/private group or false if not active.
     *
     * @return int
     */
    public function get_grouping() {
        return $this->is_activated() ? $this->_course->groupingpublicprivate : false;
    }

    /**
     * Passed a grouping id or either a grouping record object or array, returns
     * true if the grouping is the same as the course's public/private grouping
     * or false otherwise.
     *
     * @param int|object|array $group
     * @return boolean
     */
    public function is_grouping($grouping) {
        $groupingid = is_scalar($grouping)
                    ? $grouping
                    : (is_object($grouping) && isset($grouping->id)
                        ? $grouping->id
                        : (is_array($grouping) && isset($grouping['id'])
                            ? $grouping['id']
                            : false));

        $cgroupingid = $this->get_grouping();

        return $cgroupingid !== false && $groupingid !== false && $cgroupingid == $groupingid;
    }

    /**
     * Activates public/private for a course that does not already have public/
     * private enabled.
     *
     * @throws PublicPrivate_Course_Exception
     */
    public function activate() {
        global $DB;

        /*
         * Cannot activate if already activated.
         */

        if ($this->is_activated()) {
            throw new PublicPrivate_Course_Exception('Illegal action trying to activate public/private where already active.', 200);
        }

        /*
         * Change name of an existing group with name get_string('publicprivategroupname')
         */

        if ($groupid = groups_get_group_by_name($this->_course->id, get_string('publicprivategroupname', 'local_publicprivate'))) {
            $data = groups_get_group($groupid);
            if (!groups_get_group_by_name($this->_course->id, $data->name . ' ' . get_string('publicprivategroupdeprecated', 'local_publicprivate'))) {
                $data->name = $data->name . ' ' . get_string('publicprivategroupdeprecated', 'local_publicprivate');
            } else {
                for ($i = 1; groups_get_group_by_name($this->_course->id,  $data->name . ' ' . get_string('autoassigndeprecatedgroup') . ' [' . $i . ']'); $i++);
                $data->name = $data->name . ' ' . get_string('autoassigndeprecatedgroup') . ' [' . $i . ']';
            }

            try {
                if (!groups_update_group($data)) {
                    throw new PublicPrivate_Course_Exception('Failed to move existing group with required group name.', 201);
                }
            } catch (DML_Exception $e) {
                throw new PublicPrivate_Course_Exception('Failed to move existing group with required group name.', 201, $e);
            }
        }

        /*
         * Change name of an existing grouping with name get_string('publicprivategroupingname')
         */

        if ($groupingid = groups_get_grouping_by_name($this->_course->id, get_string('publicprivategroupingname', 'local_publicprivate'))) {
            $data = groups_get_group($groupingid);
            if ($data) {
                if (!groups_get_grouping_by_name($this->_course->id, $data->name . ' ' . get_string('publicprivategroupingdeprecated', 'local_publicprivate'))) {
                    $data->name = $data->name . ' ' . get_string('publicprivategroupingdeprecated', 'local_publicprivate');
                } else {
                    for($i = 1; groups_get_grouping_by_name($this->_course->id, $data->name . ' ' . get_string('publicprivategroupingdeprecated', 'local_publicprivate') . ' [' . $i . ']'); $i++);
                    $data->name = $data->name . ' ' . get_string('publicprivategroupingdeprecated', 'local_publicprivate') . ' [' . $i . ']';
                }

                try {
                    if (!groups_update_grouping($data)) {
                        throw new PublicPrivate_Course_Exception('Failed to move existing grouping with required group name.', 202);
                    }
                } catch (DML_Exception $e) {
                    throw new PublicPrivate_Course_Exception('Failed to move existing grouping with required group name.', 202, $e);
                }
            }
        }

        /*
         * Create new publicprivategroupname group and publicprivategroupingname grouping.
         */

        $data = new stdClass();
        $data->courseid = $this->_course->id;
        $data->name = get_string('publicprivategroupname', 'local_publicprivate');
        $data->description = get_string('publicprivategroupdescription', 'local_publicprivate');

        try {
            if (!$newgroupid = groups_create_group($data)) {
                throw new PublicPrivate_Course_Exception('Failed to create public/private group.', 203);
            }
        } catch (DML_Exception $e) {
            throw new PublicPrivate_Course_Exception('Failed to create public/private group.', 203, $e);
        }

        /*
         * Create new publicprivategroupingname grouping.
         */

        $data = new stdClass();
        $data->courseid = $this->_course->id;
        $data->name = get_string('publicprivategroupingname', 'local_publicprivate');
        $data->description = get_string('publicprivategroupingdescription', 'local_publicprivate');

        try {
            if (!$newgroupingid = groups_create_grouping($data)) {
                throw new PublicPrivate_Course_Exception('Failed to create public/private grouping.', 204);
            }
        } catch (DML_Exception $e) {
            throw new PublicPrivate_Course_Exception('Failed to create public/private grouping.', 204, $e);
        }

        /*
         * Bind public/private group to grouping.
         */

        try {
            if (!groups_assign_grouping($newgroupingid, $newgroupid)) {
                throw new PublicPrivate_Course_Exception('Failed to bind public/private group to grouping.', 205);
            }
        } catch (DML_Exception $e) {
            throw new PublicPrivate_Course_Exception('Failed to bind public/private group to grouping.', 205, $e);
        }

        /*
         * Update course settings for public/private.
         */

        $this->_course->enablepublicprivate = 1;
        $this->_course->grouppublicprivate = $newgroupid;
        $this->_course->groupingpublicprivate = $newgroupingid;
        $this->_course->guest = 1;
        $this->_course->defaultgroupingid = $newgroupingid;

        try {
            // We used to call update_course(), but may lead to recusion
            // problems when we start using the course_updated/course_added
            // events, so just update database directly.
            $DB->update_record('course', $this->_course);
            rebuild_course_cache($this->_course->id, true);
        } catch (DML_Exception $e) {
            throw new PublicPrivate_Course_Exception('Failed to update course settings for public/private.', 206, $e);
        }

        /*
         * Set all 'public' course modules private initially.
         */

        try {
            // Get all course modules.
            $modinfo = get_fast_modinfo($this->_course);
            $mods = $modinfo->get_cms();
            foreach ($mods as $mod) {
                $ppmod = PublicPrivate_Module::build($mod);
                $ppmod->enable();
            }
        } catch (DML_Exception $e) {
            throw new PublicPrivate_Course_Exception('Failed to set public modules private on activation.', 207, $e);
        }

        /*
         * Add enrolled users to public/private group.
         */

        try {
            $this->add_enrolled_users();
        } catch (PublicPrivate_Course_Exception $e) {
            throw new PublicPrivate_Course_Exception('Failed to add enrolled users to public/private group.', 208, $e);
        }

        /*
         * Make sure guest access enrolment plugin is installed and enabled
         */
        self::set_guest_plugin($this->_course, ENROL_INSTANCE_ENABLED);
    }

    /**
     * Deactivates public/private for a course that has public/private enabled.
     *
     * @throws PublicPrivate_Course_Exception
     */
    public function deactivate() {
        global $DB;

        /*
         * Cannot deactivate if not activated.
         */

        if (!$this->is_activated()) {
            throw new PublicPrivate_Course_Exception('Illegal action trying to deactivate public/private where not active.', 300);
        }

        /*
         * Unset public/private module conditions.
         */
        try {
            // Get all course modules.
            $modinfo = get_fast_modinfo($this->_course);
            $mods = $modinfo->get_cms();
            foreach ($mods as $mod) {
                $ppmod = PublicPrivate_Module::build($mod);
                $ppmod->disable();
            }
        } catch (DML_Exception $e) {
            throw new PublicPrivate_Course_Exception('Failed to unset public/private module visibilities.', 302, $e);
        }

        /*
         * Update course to no longer have an public/private group or grouping setting.
         */
        $oldgrouppublicprivate = $this->_course->grouppublicprivate;
        $oldgroupingpublicprivate = $this->_course->groupingpublicprivate;
        $this->_course->enablepublicprivate = 0;
        $this->_course->grouppublicprivate = 0;
        $this->_course->groupingpublicprivate = 0;
        $this->_course->defaultgroupingid = 0;

        try {
            // We used to call update_course(), but may lead to recusion
            // problems when we start using the course_updated/course_added
            // events, so just update database directly.
            $DB->update_record('course', $this->_course);
            rebuild_course_cache($this->_course->id, true);
        } catch (DML_Exception $e) {
            throw new PublicPrivate_Course_Exception('Failed to update course settings to disable public/private.', 301, $e);
        }

        /*
         * Delete public/private group and grouping.
         */
        
        try {
            groups_delete_group($oldgrouppublicprivate);
            groups_delete_grouping($oldgroupingpublicprivate);
        } catch (DML_Exception $e) {
            throw new PublicPrivate_Course_Exception('Failed to delete public/private group and grouping.', 303, $e);
        }

        /*
         * Deactivate guest enrollment plugin (if any)
         */
        self::set_guest_plugin($this->_course, ENROL_INSTANCE_DISABLED);
    }

    /**
     * Helper method to make sure guest enrollment plugin is enabled or disabled
     * for given course.
     *
     * @param object $course    Course record.
     * @param int $enrolstatus
     *
     * return boolean           Returns false on error, otherwise true.
     */
    public static function set_guest_plugin($course, $enrolstatus) {
        global $DB;

        if (!in_array($enrolstatus, array(ENROL_INSTANCE_ENABLED, ENROL_INSTANCE_DISABLED))) {
            return false;
        }

        $enrolguestplugin = enrol_get_plugin('guest');
        if (empty($enrolguestplugin)) {
            return false;
        }

        // For some reason, there might be multiple guest enrollment plugins.
        $guestplugins = $DB->get_records('enrol', array('enrol' => 'guest',
            'courseid' => $course->id));

        if (empty($guestplugins)) {
            if ($enrolstatus == ENROL_INSTANCE_ENABLED) {
                // No guest enrolment plugin found, so add one.
                $enrolguestplugin->add_instance($course);
            }
        } else {
            // make sure existing plugin is enabled or disabled
            foreach ($guestplugins as $guestplugin) {
                $enrolguestplugin->update_status($guestplugin, $enrolstatus);
            }
        }

        return true;
    }

    /**
     * Returns true for a course that has public/private enabled.
     *
     * @return boolean
     */
    public function is_activated() {
        return $this->_course->enablepublicprivate > 0 &&
                $this->_course->grouppublicprivate > 0 &&
                $this->_course->groupingpublicprivate > 0;
    }

    /**
     * Adds all users with an explicit role assignment in the course to the
     * public/private group if they're not already in the public/private
     * group.
     *
     * @throws PublicPrivate_Course_Exception
     */
    public function add_enrolled_users() {
        global $DB, $CFG;

        /*
         * Cannot add enrolled if public/private not activated.
         */

        if (!$this->is_activated()) {
            throw new PublicPrivate_Course_Exception('Illegal action trying to add enrolled where public/private is not active.', 400);
        }

        $context = context_course::instance($this->_course->id);

        /*
         * Add users with an explicit assignment to public/private group.
         *
         * Attempts to do this with INSERT...SELECT statement. If this is not
         * a supported query type, then takes O(N) to add all members 1-by-1.
         */
        try {
            $DB->execute('INSERT IGNORE INTO '.$CFG->prefix.'groups_members (groupid, userid, timeadded)
                            SELECT DISTINCT '.$this->_course->grouppublicprivate.' AS groupid, ra.userid AS userid, '.time().' AS timeadded
                            FROM '.$CFG->prefix.'role_assignments ra
                            WHERE ra.contextid = '.$context->id.'
                                AND ra.userid NOT IN (
                                    SELECT DISTINCT userid
                                    FROM '.$CFG->prefix.'groups_members
                                    WHERE groupid = '.$this->_course->grouppublicprivate.')');
        } catch (DML_Exception $e) {
            try {
                $rs = $DB->get_records('role_assignments', array('contextid' => $context->id));

                $seen = array();
                foreach ($rs as $row) {
                    if (isset($seen[$row->userid])) {
                        continue;
                    }

                    $seen[$row->userid] = true;

                    $member = new stdClass();
                    $member->groupid = $this->_course->grouppublicprivate;
                    $member->userid = $row->userid;
                    $member->timeadded = time();

                    $DB->insert_record('groups_members', $member);
                }
            } catch (DML_Exception $e) {
                throw new PublicPrivate_Course_Exception('Failed to add users with an explicit assignment to public/private group.', 401, $e);
            }
        }
    }

    /**
     * Add user to the public/private group if they're not already in the
     * public/private group.
     *
     * @param object $user
     *
     * @throws PublicPrivate_Course_Exception
     */
    public function add_user($user) {
        global $DB;

        /*
         * Cannot add enrolled if public/private not activated.
         */

        if (!$this->is_activated()) {
            throw new PublicPrivate_Course_Exception('Illegal action trying to add user to course where public/private is not active.', 500);
        }

        /*
         * Parse $user parameter as scalar, object or array, or else throw exception.
         */

        $userid = is_scalar($user)
                    ? $user
                    : (is_object($user) && isset($user->id)
                        ? $user->id
                        : (is_array($user) && isset($user['id'])
                            ? $user['id']
                            : false));

        if ($userid === false) {
            throw new PublicPrivate_Course_Exception('Required user properties not available for add user to public/private group.', 501);
        }

        /*
         * Return before adding if user is already a member of the group.
         */

        if ($this->is_member($userid)) {
            return;
        }

        /*
         * Do not add a user if they have no roles in the course.
         */

        $coursecontext = context_course::instance($this->_course->id);
        $roles = get_user_roles($coursecontext, $userid, false);
        if (empty($roles)) {
            return;
        }

        /*
         * If the user has no active enrolments, we don't want to add them.
         */

        if (!is_enrolled($coursecontext, $userid, '', true)) {
            throw new PublicPrivate_Course_Exception('Trying to add unenroled user to public/private group.', 502);
        }

        /*
         * Add row to groups_members for userid in public/private group.
         */

        try {
            $member = new stdClass();
            $member->groupid = $this->_course->grouppublicprivate;
            $member->userid = $userid;
            $member->timeadded = time();
            $DB->insert_record('groups_members', $member);
        } catch (DML_Exception $e) {
            throw new PublicPrivate_Course_Exception('Failed to add user to public/private group.', 503, $e);
        }
    }

    /**
     * Remove user from the public/private group.
     *
     * @param int|object $user
     * @throws PublicPrivate_Course_Exception
     */
    public function remove_user($user) {
        global $DB;

        /*
         * Cannot add enrolled if public/private not activated.
         */

        if (!$this->is_activated()) {
            throw new PublicPrivate_Course_Exception('Illegal action trying to remove user where public/private is not active.', 600);
        }

        /*
         * Parse $user parameter as scalar, object or array, or else throw exception.
         */

        $userid = is_scalar($user)
                    ? $user
                    : (is_object($user) && isset($user->id)
                        ? $user->id
                        : (is_array($user) && isset($user['id'])
                            ? $user['id']
                            : false));

        if ($userid === false) {
            throw new PublicPrivate_Course_Exception('Required user properties not available to remove user to public/private group.', 601);
        }

        /*
         * Delete rows from groups_members for userid in public/private group.
         */

        try {
            $DB->delete_records('groups_members', array('groupid' => $this->_course->grouppublicprivate, 'userid' => $userid));
        } catch (DML_Exception $e) {
            throw new PublicPrivate_Course_Exception('Failed to add user to public/private group.', 602, $e);
        }
    }

    /**
     * Check if the user is currently a member of public/private group.
     *
     * @param int|object $user
     * @return boolean
     * @throws PublicPrivate_Course_Exception
     */
    public function is_member($user) {
        global $DB;

        /*
         * Cannot add enrolled if public/private not activated.
         */

        if (!$this->is_activated()) {
            throw new PublicPrivate_Course_Exception('Illegal action trying to check if user is in public/private group where public/private is not active.', 700);
        }

        /*
         * Parse $user parameter as scalar, object or array, or else throw exception.
         */

        $userid = is_scalar($user)
                    ? $user
                    : (is_object($user) && isset($user->id)
                        ? $user->id
                        : (is_array($user) && isset($user['id'])
                            ? $user['id']
                            : false));

        if ($userid === false) {
            throw new PublicPrivate_Course_Exception('Required user properties not available to remove user to public/private group.', 701);
        }

        /*
         * Return boolean on if record exists.
         */

        try {
            return $DB->record_exists('groups_members', array('groupid' => $this->_course->grouppublicprivate, 'userid' => $userid));
        } catch (DML_Exception $e) {
            throw new PublicPrivate_Course_Exception('Failed to check if user is in public/private group.', 702, $e);
        }
    }

    /**
     * Check the user's enrolment. If not active, then remove from all groups
     * in course.
     *
     * If they are enrolled, make sure they are in the group.
     *
     * @param int $userid
     */
    public function check_enrolment($userid) {
        $coursecontext = context_course::instance($this->_course->id);
        if (is_enrolled($coursecontext, $userid, '', true)) {
            // If the user is enrolled, but not a member of 'Course members',
            // add them.
            if (!$this->is_member($userid)) {
                $this->add_user($userid);
            }
        } else {
            $removefromgroups = false;
            if ($this->is_member($userid)) {
                $removefromgroups = true;
            } else {
                $roles = get_user_roles($coursecontext, $userid, false);
                if (empty($roles)) {
                    $removefromgroups = true;
                }
            }

            // If the user is not enrolled, but is a member or has no roles,
            // remove them from all groups.
            if ($removefromgroups) {
                $groups = groups_get_all_groups($this->_course->id, $userid);
                if (!empty($groups)) {
                    foreach ($groups as $group) {
                        groups_remove_member($group->id, $userid);
                    }
                }
            }
        }
    }
}
