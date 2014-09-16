<?php
global $CFG;
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
 * @author ebollens
 * @version 20110719
 *
 * @uses PublicPrivate_Module_Exception
 * @uses $DB
 * @uses $CFG
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
        global $DB;

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
     * Returns a PublicPrivate_Module object for the provided $course_module.
     *
     * @param int|object|array $course
     * @return PublicPrivate_Module
     */
    public static function build($course_module) {
        return new PublicPrivate_Module($course_module);
    }

    /**
     * Returns true if the course module is visible to the public (guest).
     *
     * @throws PublicPrivate_Course_Exception
     * @throws PublicPrivate_Module_Exception
     * @return bool
     */
    public function is_public() {
        global $CFG;
        return \core_availability\info_module::is_user_visible($this->_course_module(), $CFG->siteguest);
    }

    /**
     * Returns true if course module is private, meaning that it belongs to a
     * grouping and that is visible only to those members.
     * 
     * @return bool
     */
    public function is_private() {
        return $this->get_groupmembersonly() != 0;
    }

    /**
     * Returns true if there is some sort of visibility setting that forbids
     * guests from accessing the course_module.
     *
     * @throws PublicPrivate_Module_Exception
     * @return bool
     */
    public function is_protected() {
        return !$this->public();
    }

    /**
     * Enables public/private for course_module:
     *  - If groupingid is not set, then set the groupingid to the course's
     *    public/private grouping. Otherwise keep the current grouping
     *  - Make sure that it can only be viewed by group members.
     *
     * @global Moodle_Database $DB
     * @link $CFG->enablegroupmembersonly
     * @throws PublicPrivate_Module_Exception
     */
    public function enable() {
        global $DB;
        
        try {
            $conditions = array('id'=>$this->get_id());
            $grouping = $this->get_grouping();
            if (empty($grouping)) {
                $DB->set_field('course_modules', 'groupingid',
                        $this->_publicprivate_course()->get_grouping(), $conditions);
            }
            $groupmembersonly = $this->get_groupmembersonly();
            if (empty($groupmembersonly)) {
                $DB->set_field('course_modules', 'groupmembersonly', 1, $conditions);
            }
            rebuild_course_cache($this->get_course(), true);
        } catch(DML_Exception $e) {
            throw new PublicPrivate_Module_Exception('Failed to set public/private visibility settings for module.', 300, $e);
        }
    }

    /**
     * Disables public/private for course_module:
     *  - If groupingid matches course's public/private grouping, then set
     *    groupingid to 0. Otherwise keep the current grouping.
     *  - Disable restriction so that it can only be viewed by anyone rather
     *    than just group members.
     *
     * @global Moodle_Database $DB
     * @throws PublicPrivate_Module_Exception
     */
    public function disable() {
        global $DB;
        
        try {
            $conditions = array('id'=>$this->get_id());
            if ($this->_publicprivate_course()->get_grouping() == $this->get_grouping()) {
                $DB->set_field('course_modules', 'groupingid', 0, $conditions);
            }
            $groupmembersonly = $this->get_groupmembersonly();
            if (!empty($groupmembersonly)) {
                $DB->set_field('course_modules', 'groupmembersonly', 0, $conditions);
            }
            rebuild_course_cache($this->get_course(), true);
        } catch(DML_Exception $e) {
            throw new PublicPrivate_Module_Exception('Failed to set public/private visibility settings for module.', 400, $e);
        }
    }

    /**
     * Returs `course_module`.`id` (the key).
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
     * Returns `course_module`.`groupingid`.
     *
     * @throws PublicPrivate_Module_Exception
     * @return int
     */
    public function get_grouping() {
        return $this->_course_module()->groupingid;
    }

    /**
     * Returns `course_module`.`groupmembersonly`.
     *
     * @throws PublicPrivate_Module_Exception
     * @return int
     */
    public function get_groupmembersonly() {
        return $this->_course_module()->groupmembersonly;
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
     * @global Moodle_Database $DB
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
