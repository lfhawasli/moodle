<?php

/**
 * UCLA Site Indicator 
 * 
 * @package     ucla
 * @subpackage  uclasiteindicator
 * @author      Alfonso Roman
 */

require_once(dirname(__FILE__) . '/../../../config.php');

require_once($CFG->libdir.'/formslib.php');

// From the UCLA help block -- to get support contacts and send jira ticket
require_once($CFG->dirroot . '/blocks/ucla_help/ucla_help_lib.php');
require_once($CFG->dirroot . '/local/ucla/lib.php');


/**
 * A site indicator entry - represents a site indicator for a given course 
 */
class siteindicator_site {
    
    public $property;
    
    private $_id;
    
    function __construct($courseid) {
        global $DB;
        
        $indicator = $DB->get_record('ucla_siteindicator', 
                array('courseid' => $courseid), '*', MUST_EXIST);
        
        $this->property = new stdClass();
        
        $this->property->courseid = $courseid;
        $this->property->type = $indicator->type;
        $this->_id = $indicator->id;
   }
    
    /**
     * Delete a site indicator entry
     */
    public function delete() {
        global $DB;
        $DB->delete_records('ucla_siteindicator', array('id' => $this->_id));
    }
    
    private function update() {
        global $DB;
        $DB->update_record('ucla_siteindicator',
                array('id' => $this->_id, 'type' => $this->property->type));
    }
   
    
    /**
     * Change a site type.  Re-maps the role assignments if the site type
     * is of a different role group
     * 
     * @param string $newtype of the site.
     */
    public function set_type($newtype) {  
        global $CFG, $DB;
        
        // Do we need to change role assignments?
        if($newtype != $this->property->type) {
            $uclaindicator = new siteindicator_manager();
            
            $mygroup = $uclaindicator->get_rolegroup_for_type($this->property->type);
            $newgroup = $uclaindicator->get_rolegroup_for_type($newtype);            
            
            // Get course context
            $context = context_course::instance($this->property->courseid);
            
            // Get enrolled users
            $users = get_enrolled_users($context);
            
            // for each user, reassign roles
            foreach($users as $u) {
                $roles = get_user_roles($context, $u->id);
                
                foreach($roles as $r) {
                    $oldrole = $r->shortname;
                    
                    // Only map roles that are remap-able
                    if($newrole = $uclaindicator->get_remapped_role($newgroup, $oldrole)) {
                        role_unassign($r->roleid, $u->id, $context->id);
                        role_assign($newrole->id, $u->id, $context->id);
                    }
                }
            }
            
            // CCLE-3599 - Private collab site
            // Deactivate public/private, Disable guest access
            if ($newtype == siteindicator_manager::SITE_TYPE_PRIVATE) {
                siteindicator_manager::make_private($this->property->courseid);
            }
            
            // Update new site type
            $this->property->type = $newtype;
            $this->update();            
        }        
    }
    
    /**
     * Get assignable roles for this indicator.
     * 
     * @return array of assignable roles 
     */
    public function get_assignable_roles() {
        global $DB;        
        $uclaindicator = new siteindicator_manager();        
        return $uclaindicator->get_assignable_roles($this->property->type);
    }    

    
    /**
     * Load an indicator.
     * 
     * @param int $courseid
     * @return null|siteindicator_site if indicator exists
     */
    static function load($courseid) {
        try {
            return new siteindicator_site($courseid);
            
        } catch(Exception $e) {
            
            return null;
        }
    }
    
    static function create($site) {
        global $DB;
        if(is_scalar($site)) {
            $new = new stdClass();
            $new->courseid = $site;
            $new->type = 'test';
        
            $site = $new;
        }

        $DB->insert_record('ucla_siteindicator', $site);

        if ($site->type == siteindicator_manager::SITE_TYPE_PRIVATE) {
            siteindicator_manager::make_private($site->courseid);
        }
        
        return new siteindicator_site($site->courseid);
    }
}

/**
 * A site indicator request - retrieves a course request 
 */
class siteindicator_request {

    public $request;
    
    public $entry;
    
    private $_id;
    
    function __construct($requestid) {
        global $DB;

        $this->request = new stdClass();
        $this->entry = new stdClass();

        //
        if(!$request = $DB->get_record('ucla_siteindicator_request', array('requestid' => $requestid))) {
            
            $request = $DB->get_record('ucla_siteindicator_request', 
                    array('courseid' => $requestid, 'requestid' => null), '*', MUST_EXIST);
            
            $requestid = null;
        }
        

        $this->_id = $request->id;                           // Indicator request ID
        $this->entry->type = $request->type;                // Indicator type
        $this->request->categoryid = $request->categoryid;  // Requested category
        $this->request->requestid = $requestid;             // Request ID of course_request
        $this->request->requester = $request->requester;    // User who requested the course
        $this->request->type = $request->type;
        $this->request->courseid = $request->courseid;
    }
 
    function approve($courseid) {
        $this->entry->courseid = $courseid;
                
        $this->_update_history();
        $this->_set_default_role();
        
        return $this->entry;
    }
    
    function update_category($newcat) {
        global $DB;
        
        $update = new stdClass();
        $update->id = $this->_id;
        $update->categoryid = $newcat;
        
        $DB->update_record('ucla_siteindicator_request', $update);
    }
    
    private function _update_history() {
        global $DB, $USER;
        
        $update = $this->request;
        
        $update->id = $this->_id;
        $update->courseid = $this->entry->courseid;
        $update->requestid = null;
        $update->type = $this->entry->type;
        // CCLE-4828 
        $update->timeprocessed = time();
        $update->processedby = $USER->id;
        
        $DB->update_record('ucla_siteindicator_request', $update);
    }
    
    /**
     * Set default role for user who requested the course.
     * 
     * @return type 
     */
    private function _set_default_role() {
        global $DB;
                
        // Course and user info
        $userid = $this->request->requester;
        $courseid = $this->entry->courseid;

        // Get toprole
        $uclaindicator = new siteindicator_manager();
        $toprole = $uclaindicator->get_requester_role_for_type($this->entry->type);
        
        $role = $DB->get_record('role', array('shortname' => $toprole));
        
        // Manually enroll course requester
        enrol_try_internal_enrol($courseid, $userid, $role->id);        
        
        // Get context
        $context = context_course::instance($courseid);
        
        // Assign default role
        role_assign($role->id, $userid, $context->id, '', NULL);
    }
        
    /**
     * Reject the site indicator request 
     */
    public function reject() {
        global $DB, $USER;
        
        $update = $this->request;
        
        $update->id = $this->_id;
        $update->courseid = null;
        $update->requestid = null;
        $update->type = $this->entry->type;
        // CCLE-4828
        $update->timeprocessed = time();
        $update->processedby = $USER->id;
        
        $DB->update_record('ucla_siteindicator_request', $update);
    }
        
    /**
     * Create a site indicator request.  
     * 
     * @param obj $newindicator is an object 
     */
    static function create($newindicator) {
        global $DB;
        $DB->insert_record('ucla_siteindicator_request', $newindicator);
    }
    

    /**
     * Load an indicator request.
     * 
     * @param type $requestid
     * @return null|\ssiteindicator_requestif request exists
     */
    static function load($requestid) {
        try {
            return new siteindicator_request($requestid);
        } catch(Exception $e) {
            return null;
        }
    }
}


/**
 * Collection of site indicator functions.
 * 
 */
class siteindicator_manager {
    /* CONSTANTS */
    const SITE_TYPE_INSTRUCTION = 'instruction';            // IEI instruction 
    const SITE_TYPE_INSTRUCTION_NONIEI = 'instruction_noniei';  // Non-IEI instruction
    const SITE_TYPE_NON_INSTRUCTION = 'non_instruction';
    const SITE_TYPE_RESEARCH = 'research';
    const SITE_TYPE_TEST = 'test';
    const SITE_TYPE_PRIVATE = 'private';
    const SITE_TYPE_TASITE = 'tasite';
    
    // special site type that is not displayed or assignable
    const SITE_TYPE_SRS_INSTRUCTION = 'srs_instruction';
    
    const SITE_GROUP_TYPE_INSTRUCTION = 'instruction';
    const SITE_GROUP_TYPE_PROJECT = 'project';
    const SITE_GROUP_TYPE_TEST = 'test';
    
    // A group of roles.  A group contains a set 
    // of roles that are mutually excluseive from other groups.
    private $_indicator_rolegroups;
    
    // Sets of role assignments for a particular group.
    private $_roleassignments;
    
    // A mapping specifiying which role group belongs to a site type
    private $_type_to_rolegroup_mapping;
    
    // A role re-map scheme used when a site changes type
    private $_role_remap;
    
    static $types = array();
    
    function __construct() {

        // setup the types list
        $this->get_types_list();
        
        $this->_indicator_rolegroups = array(
            self::SITE_GROUP_TYPE_INSTRUCTION => get_string('r_instruction', 'tool_uclasiteindicator'),
            self::SITE_GROUP_TYPE_PROJECT => get_string('r_project', 'tool_uclasiteindicator'),
            self::SITE_GROUP_TYPE_TEST => get_string('r_test', 'tool_uclasiteindicator'),
            );
        
        // Supported site types:
        //   Instruction (IEI)
        //   Instruction (Non-IEI)
        //   Non-Instruction
        //   Research
        //   Test
        $this->_type_to_rolegroup_mapping = array(
            self::SITE_TYPE_INSTRUCTION => self::SITE_GROUP_TYPE_INSTRUCTION,
            self::SITE_TYPE_INSTRUCTION_NONIEI => self::SITE_GROUP_TYPE_INSTRUCTION,
            self::SITE_TYPE_NON_INSTRUCTION => self::SITE_GROUP_TYPE_PROJECT,
            self::SITE_TYPE_RESEARCH => self::SITE_GROUP_TYPE_PROJECT,
            self::SITE_TYPE_TEST => self::SITE_GROUP_TYPE_TEST,
            self::SITE_TYPE_PRIVATE => self::SITE_GROUP_TYPE_PROJECT,
            self::SITE_TYPE_TASITE => self::SITE_GROUP_TYPE_INSTRUCTION,
            );
        
        // Define the roles allowed for a particular role group
        // See CCLE-2948/CCLE-2949/CCLE-2913/site invite
        $instruction = array(
            //'editinginstructor',
            'instructional_assistant',
            'editor',
            'grader',            
            'student',
            'participant',
            'visitor'
            );

        $project = array(
            'projectlead',
            'projectcontributor',
            'projectparticipant',
            'projectviewer',
            );
        
        $this->_roleassignments = array(
            'instruction' => $instruction,
            'project' => $project,
            'test' => array_merge($instruction, $project),
            );
        
        // CCLE-3981: Give requester a role assignment.
        $this->_requesterroleassignement = array(
            'instruction' => 'editinginstructor',
            'project' => 'projectlead',
            'test' => 'projectlead'
        );

        // remap roles
        // see CCLE-2913
        $this->_role_remap = array(
            'project' => array(
                'editinginstructor' => 'projectlead',
                'nonediting_instructor' => 'projectcontributor',
                'manager'      => 'projectlead',
                'instructional_assistant'      => 'projectcontributor',
                'grader'      => 'projectparticipant',
                'editor'      => 'projectcontributor',
                'student'   => 'projectparticipant',                
                'participant'      => 'projectparticipant',             
                'visitor'      => 'projectviewer',                
                'supervising_instructor' => 'projectlead',
                'ta_instructor' => 'projectlead',
                'ta_admin' => 'projectlead',
                'ta' => 'projectparticipant',
                ),
            'instruction' => array(
                'projectlead'           => 'editinginstructor',
                'projectcontributor'    => 'editor',
                'projectparticipant'    => 'student',
                'projectviewer'         => 'visitor',                
                'nonediting_instructor' => 'grader',
                'supervising_instructor' => 'instructional_assistant',
                'ta_instructor' => 'instructional_assistant',
                'ta_admin' => 'instructional_assistant',
                'ta' => 'participant',
                )
            );
    }
    
    /**
     * For a given role group, returns the set of roles in that group.
     * 
     * @param string $group shortname of the role group
     * @return array of roles (shortnames)
     */
    function get_roles_for_group($group) {
        return $this->_roleassignments[$group];
    }
    
    /**
     * For a given type, returns the set of roles for that type.
     * 
     * @param mixed $type of site
     * @return array of role (shortnames) 
     */
    function get_roles_for_type($type) {
//        $ntype = $this->disambiguate_type($type);
        return $this->_roleassignments[$this->_type_to_rolegroup_mapping[$type]];
    }

    /**
     * For a given type, returns the role that should be assigned to the course requester
     * part of CCLE-3981.
     * 
     * @param string $type of site
     * @return string designating role to assign
     */
    function get_requester_role_for_type($type) {
        return $this->_requesterroleassignement[$this->_type_to_rolegroup_mapping[$type]];
    }

    /**
     * For a given type, returns the rolegroup assigned to the type.
     * 
     * @param mixed $type of site
     * @return string role group 
     */
    function get_rolegroup_for_type($type) {
//        $ntype = $this->disambiguate_type($type);
        return $this->_type_to_rolegroup_mapping[$type];
    }
    
    /**
     * For a given rolegroup and (non-rolegroup role, returns the equivalent role.
     * 
     * @param string $rolegroup of site
     * @param string $role shortname
     * @return null|$newrole if the mapping exists
     */
    function get_remapped_role($rolegroup, $role) {
        global $DB;
        
        $newrole = new stdClass();

        if (empty($this->_role_remap[$rolegroup][$role])) {
            $newrole = null;
        } else {
            $newrole->shortname = $this->_role_remap[$rolegroup][$role];
            $record = $DB->get_record('role', 
                    array('shortname' => $newrole->shortname));
            $newrole->id = $record->id;
        }
        
        return $newrole;
    }

    static function get_types_list($type = null) {
        
        if(empty(self::$types)) {
            self::$types = array(
                self::SITE_TYPE_INSTRUCTION => array(
                    'shortname' => self::SITE_TYPE_INSTRUCTION,
                    'fullname' => get_string('site_instruction', 'tool_uclasiteindicator'),
                    'description' => get_string('site_instruction_desc', 'tool_uclasiteindicator'),
                    ),
                self::SITE_TYPE_INSTRUCTION_NONIEI => array(
                    'shortname' => self::SITE_TYPE_INSTRUCTION_NONIEI,
                    'fullname' => get_string('site_instruction_noniei', 'tool_uclasiteindicator'),
                    'description' => get_string('site_instruction_noniei_desc', 'tool_uclasiteindicator'),
                    ),
                self::SITE_TYPE_NON_INSTRUCTION => array(
                    'shortname' => self::SITE_TYPE_NON_INSTRUCTION,
                    'fullname' => get_string('site_non_instruction', 'tool_uclasiteindicator'),
                    'description' => get_string('site_non_instruction_desc', 'tool_uclasiteindicator'),
                    ),
                self::SITE_TYPE_RESEARCH => array(
                    'shortname' => self::SITE_TYPE_RESEARCH,
                    'fullname' => get_string('site_research', 'tool_uclasiteindicator'),
                    'description' => get_string('site_research_desc', 'tool_uclasiteindicator'),
                    ),
                self::SITE_TYPE_TEST => array(
                    'shortname' => self::SITE_TYPE_TEST,
                    'fullname' => get_string('site_test', 'tool_uclasiteindicator'),
                    'description' => get_string('site_test_desc', 'tool_uclasiteindicator'),
                    ),
                self::SITE_TYPE_PRIVATE => array(
                    'shortname' => self::SITE_TYPE_PRIVATE,
                    'fullname' => get_string('site_private', 'tool_uclasiteindicator'),
                    'description' => get_string('site_private_desc', 'tool_uclasiteindicator'),
                    ),
                self::SITE_TYPE_TASITE => array(
                    'shortname' => self::SITE_TYPE_TASITE,
                    'fullname' => get_string('site_tasite', 'tool_uclasiteindicator'),
                    'description' => get_string('site_tasite_desc', 'tool_uclasiteindicator'),
                    ),
            );
        }
        
        if($type) {
            return self::$types[$type]['fullname'];
        } 
        
        return self::$types;
    }

    /**
     * Returns a filtered categories list
     * 
     * @todo: hide categorie we don't want to make visible -- add option 
     * in admin area
     * 
     * @return type 
     */
    static function get_categories_list($categoryid = null) {
        global $DB;
        
        if(isset($categoryid)) {
            $category_string = "";

            if(empty($categoryid)) {
                $category_string = "Other -- specified in 'reason message'";
            } else {

                $category = $DB->get_record('course_categories', 
                        array('id' => $categoryid));

                if($category->parent) {
                    $parent = $DB->get_record('course_categories', 
                            array('id' => $category->parent));
                    
                    $category_string = $parent->name . ' > ';
                }
                
                $category_string .= $category->name;
            }

            return $category_string;
        }
        
        // Else we get all categories...
        $displaylist = array();

        //make_categories_list($displaylist, $parentlist, 'moodle/course:create');
        // @todo: pick up from DB eventually
        $exclusion_list = array('Miscellaneous');        
        
        // Division level categories
        $categories = $DB->get_records('course_categories', array('parent' => 0));
        
        // Subject area categories
        foreach($categories as $cat) {
            
            if(in_array($cat->name, $exclusion_list)) {
                continue;
            }

            $displaylist[$cat->id] = $cat->name;
            
            // Subject area level categories
            if($children = $DB->get_records('course_categories', 
                    array('parent' => $cat->id))) {
                foreach($children as $child) {
                    $displaylist[$child->id] = $cat->name . ' > ' . $child->name;
                }
            }
        }
        
        $displaylist[0] = get_string('req_selopt_other', 'tool_uclasiteindicator');
        
        // add "Choose a category..." option
        $displaylist = array('' => get_string('req_selopt_choose', 
                'tool_uclasiteindicator')) + $displaylist;
        
        return $displaylist;
    }
    
    /**
     * Given a category ID, retrieve the user assigned a category manager. 
     * This will eventually be used to filter the 'pending request' list 
     * so that only the category manager is able to see coruses requested 
     * in their category.
     * 
     * @todo: finish implementing
     * 
     * @param type $categoryid
     * @return type 
     */
    static function get_category_manager($categoryid) {
        global $DB;
        
        $query = "SELECT ra.userid, r.name
                FROM {role_assignments} ra
                JOIN {context} c ON ra.contextid = c.id
                JOIN {role} r ON ra.roleid = r.id
                LEFT JOIN {role_names} rn ON rn.roleid = ra.roleid
                AND rn.contextid = ra.contextid
                WHERE c.instanceid = ?
                AND r.shortname = ?";
        
        $record = $DB->get_records_sql($query, array($categoryid, 'manager'));

        return -1;
    }
    
    /**
     * Create an indicator request and notify support contact(s).
     * 
     * @param object $data
     */
    static function create_request($data) {
        global $DB;
        
        // Find the ID of the course_request
        $course_request = $DB->get_record('course_request', array('fullname' => $data->fullname, 
                'shortname' => $data->shortname), '*', MUST_EXIST);
        
        // Make the request obj
        $request = new stdClass();
        $request->requestid = $course_request->id;
        $request->type = $data->indicator_type;
        $request->categoryid = $data->indicator_category;
        $request->requester = $course_request->requester;
        // CCLE-4828
        $request->timerequested = time();
        
        // Create actual request
        siteindicator_request::create($request);

        // Create JIRA ticket
        $request->meta = $course_request;

        // Format the title/message to send to support contact(s).
        $ticketinfo = self::format_course_request_message($request);
        $title = get_string('jira_title', 'tool_uclasiteindicator', $ticketinfo);
        $message = get_string('jira_msg', 'tool_uclasiteindicator', $ticketinfo);

        // Determine support contact(s).
        $context = context_coursecat::instance($request->categoryid, IGNORE_MISSING);
        if (empty($context)) {
            $context = context_system::instance();
        }
        $supportcontacts = get_support_contact($context);

        // Either send email or create a JIRA ticket.
        foreach ($supportcontacts as $supportcontact) {
            message_support_contact($supportcontact, null, null, $title, $message, null, null);
        }
    }

    /**
     * Returns ticketinfo object that can be used to format title and message
     * for support contact.
     *
     * @param object $request
     * @return object
     */
    static function format_course_request_message($request) {
        global $CFG;

        // Set ticket info.
        $ticketinfo = $request->meta;

        $ticketinfo->type = self::get_types_list($request->type);
        $ticketinfo->user = self::get_username($request->requester);
        $ticketinfo->category = self::get_categories_list($request->categoryid);
        $ticketinfo->summary = self::format_message($request->meta->summary);

        // Attach the pending course links.
        $ticketinfo->action = $CFG->wwwroot . '/course/pending.php?request=' . $request->requestid;

        return $ticketinfo;
    }
    
    /**
     * Create a site indicator entry from an existing request.  
     * 
     * @param int $courseid for indicator entry
     * @param int $requestid of existing indicator request
     * @return int category ID specified in the indicator request
     */
    static function approve($courseid, $requestid = 0) {

        if($request = siteindicator_request::load($requestid)) {
            $site = $request->approve($courseid);
            siteindicator_site::create($site);
        }
    }
    
    /**
     * Reject a indicator request.
     * 
     * @param type $requestid 
     */
    static function reject($requestid) {
        if($request = siteindicator_request::load($requestid)) {
            $request->reject();
        }
    }

    static function update_site($data) {
        $indicator = siteindicator_site::load($data->id);
        if (empty($indicator)) {
            // no indicator yet, so create it
            $indicator = siteindicator_site::create($data->id);          
        }

        // Handle a type change (but do not allow change to/from tasites)
        if(!empty($data->indicator_change) &&
                $data->indicator_change != self::SITE_TYPE_TASITE &&
                $indicator->property->type != self::SITE_TYPE_TASITE) {
            // make change here instead of at siteindicator_site->set_type,
            // because we still want to be able to programatically set the
            // site type to tasite in the TA site creator block
            $indicator->set_type($data->indicator_change);
        }
        
        // Update category
        if($request = siteindicator_request::load($data->id)) {
            if ($request->request->categoryid != $data->category) {
                $request->update_category($data->category);
            }
        }
    }
    
    static function get_username($userid) {
        global $DB;
        
        $str = 'Nobody';
        
        if($user = $DB->get_record('user', array('id' => $userid))) {
            $str = $user->firstname . ' ' . $user->lastname . ' ('. $user->email . ')';
        }
        
        return $str;        
    }
    
    static function format_message($msg) {
        $fix = str_replace('</p>', "\n\n", $msg);
        $fix = preg_replace('#<br\s*/?>#i', "\n", $fix);
        $fix = strip_tags($fix);
        
        return $fix;
    }

    /**
     * Cleans the shortname to work with friendly URLs.  Replaces 
     * bad characters with '-'
     * 
     * @param type $data 
     */
    static function clean_shortname(&$data) {
        if(isset($data->shortname)) {
            // Force lowercase
            $clean = strtolower($data->shortname);
            // Avoid spaces in shortnames
            $clean = str_replace(' ', '-', $clean);
            // Avoid slashes in shortnames
            $clean = str_replace('/', '-', $clean);
            $clean = str_replace('\\', '-', $clean);
            $data->shortname = $clean;
        }
    }
    
    static function get_request_history() {
        global $DB;
        
        $requests = $DB->get_records('ucla_siteindicator_request');
        
        return $requests;
    }
    
    static function get_orphans() {
        global $DB;
        
        $sql = "SELECT  c.id,
                        c.shortname,
                        c.fullname,
                        c.category
                FROM    {course} AS c 
                LEFT JOIN {ucla_request_classes} AS r ON r.courseid = c.id 
                LEFT JOIN {ucla_siteindicator} AS s ON s.courseid = c.id 
                WHERE   c.id <> 1 AND 
                        r.id IS NULL AND 
                        s.id IS NULL 
                ORDER BY c.shortname";
        $recs = $DB->get_records_sql($sql);
        
        return $recs;
    }
    
    /**
     * Returns list of collaboration sites and their site type.
     */
    static function get_sites() {
        global $DB;
        
        $sql = "SELECT  c.id,
                        c.shortname,
                        c.fullname,
                        c.category,
                        s.type
                FROM    {course} AS c,
                        {ucla_siteindicator} AS s
                WHERE   s.courseid = c.id
                ORDER BY c.shortname";
        $recs = $DB->get_records_sql($sql);
        
        return $recs;
    }    
    
    static function find_and_set_collab_sites() {
        global $DB;
        
        // We want to get all the courses (A) that do not belong to registrar (B)
        // and are no in the site indicator table (C)
        // This is a set subtraction: A - B - C
        $query = "SELECT c.id 
                FROM {course} AS c 
                LEFT JOIN {ucla_request_classes} AS r ON r.courseid = c.id 
                LEFT JOIN {ucla_siteindicator} AS s ON s.courseid = c.id 
                WHERE c.id <> 1 
                AND r.id IS NULL 
                AND s.id IS NULL 
                GROUP BY c.id";
        $recs = $DB->get_recordset_sql($query);

        // Create new sites, and turn them into project sites
        if($recs->valid()) {
            foreach($recs as $r) {
                $site = siteindicator_site::create($r->id);
                $site->set_type('non_instruction');
            }
        }
        
        $recs->close();
    }
    
    /**
     * Get assignable roles for given site type.
     * 
     * @return array of assignable roles 
     */
    public function get_assignable_roles($type) {
        global $DB;
        
        $roleids = $this->get_roles_for_type($type);
        $roles = $DB->get_records_list('role', 'shortname', $roleids, 'sortorder');   
        
        return $roles;
    }        
    
    static function filter_category_tree(&$tree) {
        global $DB;
       
        $recs = $DB->get_records_select('ucla_siteindicator', 
                'type NOT LIKE "test" AND type NOT LIKE "private" AND type NOT LIKE "tasite"', 
                null, '', 'courseid');
        
        $ids = array();
        
        foreach($recs as $r) {
            $ids[] = $r->courseid;
        }
        
        self::traverse_tree($tree, $ids);
    }
    
    static function traverse_tree(&$tree, &$ids) {
        $hascollab = false;
        
        // Dig into category tree
        if(!empty($tree->categories)) {
            foreach($tree->categories as &$cat) {
                if(!self::traverse_tree($cat, $ids)) {
                    $cat = null;
                } else {
                    $hascollab = true;
                }
            }
        }
        
        // Check courses
        if(!empty($tree->courses)) {            
            foreach($tree->courses as &$c) {                
                if(in_array($c->id, $ids)) {
                    $hascollab = true;
                } else {
                    $c = null;
                }                
            }
        }
        
        return $hascollab;
    }
    
    static function searchbox_js_require() {
        global $PAGE, $CFG;
        
        $rest_url = $CFG->wwwroot . '/admin/tool/uclasiteindicator/rest.php';
        $course_url = $CFG->wwwroot . '/course/view.php?id=';
        
        $thisdir = '/' . $CFG->admin . '/tool/uclasiteindicator/';
        $PAGE->requires->js(new moodle_url($thisdir . '/autocomplete.js'));
        $PAGE->requires->js_init_call('M.collab_autocomplete.init', 
                array($rest_url, $course_url));

    }
    
    static function print_collab_searchbox() {
        $input = html_writer::tag('input', '', array('id' => 'ac_input', 
            'placeholder' => get_string('search_placeholder', 'tool_uclasiteindicator')));
        $wrapper = html_writer::tag('div', $input, array('class' => 'ac-search-wrapper'));
        $out = html_writer::tag('div', $wrapper, array('class' => 'ac-search-div'));
        
        return $out;
    }
    
    static function get_query_result_json($q) {
        global $DB;
        
        // Get collab sites (exclude test sites)
        $query = "
            SELECT c.id, c.fullname, c.shortname
            FROM {course} c
            JOIN {ucla_siteindicator} si ON c.id = si.courseid
            WHERE c.fullname LIKE :query 
            AND si.type NOT LIKE 'test'";
        
        $recs = $DB->get_records_sql($query, array('query' => '%'.$q.'%'));
        
        // Format results
        $results = array();
        
        foreach($recs as $r) {
            $obj = new stdClass();
            $obj->text = $r->shortname . ': ' . $r->fullname;
            $obj->id = $r->id;
            $results[] = $obj;
        }
        
        // Format output
        $out = new stdClass();
        
        $out->query = $q;
        $out->results = $results;
        $out->numresults = count($results);
        
        // Return as JSON text
        return json_encode($out);
    }

    /**
     * Makes a given course private by:
     *  - turning off public/private
     *  - turning off guest access
     *
     * @param int $courseid
     */
    static function make_private($courseid) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/local/publicprivate/lib/course.class.php');

        $pubpriv_course = new PublicPrivate_Course($courseid);
        if($pubpriv_course->is_activated()) {
            $pubpriv_course->deactivate();
        }
    }
}
