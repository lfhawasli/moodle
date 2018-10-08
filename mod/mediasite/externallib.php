<?php

require_once($CFG->libdir . "/externallib.php");

class local_mediasite_external extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function getGroupMembership_parameters() {
        return new external_function_parameters(
                array('groupId' => new external_value(PARAM_INT, 'The assignment group id"'))
        );
    }

    /**
     * Returns welcome message
     * @return string welcome message
     */
    public static function getGroupMembership($groupId) {
        global $CFG, $DB, $USER;

        //Parameter validation
        //REQUIRED
        $params = self::validate_parameters(self::getGroupMembership_parameters(),
                array('groupId' => $groupId));

        //Context validation
        //OPTIONAL but in most web service it should present
        $context = get_context_instance(CONTEXT_USER, $USER->id);
        self::validate_context($context);

        //Capability checking
        //OPTIONAL but in most web service it should present
        if (!has_capability('moodle/user:viewdetails', $context)) {
            throw new moodle_exception('cannotviewprofile');
        }

        //get all the group membership
        //pay attention here: the first field of select statement should NOT be "groupid" or "groupname" which is unique, otherwise php will return only one record instead of many records we want
        $selectstatement = '
        SELECT gm.userid, u.username, u.email as useremail, gm.groupid, g.name as groupname, g.idnumber as groupidnumber, u.firstname as userfirstname, u.lastname as userlastname
        FROM {groups_members} gm
            INNER JOIN {groups} g ON g.id = gm.groupid
            INNER JOIN {user} u ON u.id = gm.userid
        WHERE gm.groupid =?';

        $groupMembers = $DB->get_records_sql($selectstatement, array($groupId));
        $members = array();
        foreach ($groupMembers as $member) {
            array_push($members, 
                array(                    
                    'groupId' => $member->groupid,
                    'groupIdNumber'=> $member->groupidnumber,
                    'groupName'=> $member->groupname,
                    'userId' => $member->userid,
                    'username' => $member->username,
                    'userEmail'=> $member->useremail,
                    'userFirstName' => $member->userfirstname,
                    'userLastName' => $member->userlastname,
                ));
        }

        return $members;
    }
 
    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function getGroupMembership_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'groupId' => new external_value(PARAM_INT, 'group id'),
                    'groupIdNumber' => new external_value(PARAM_TEXT, 'group id number'),
                    'groupName' => new external_value(PARAM_TEXT, 'group name'),
                    'userId' => new external_value(PARAM_INT, 'user id of the member'),
                    'username' => new external_value(PARAM_TEXT, 'username of the member'),
                    'userEmail'=> new external_value(PARAM_TEXT, 'email of the member'),
                    'userFirstName' => new external_value(PARAM_TEXT, 'user first name'),
                    'userLastName' => new external_value(PARAM_TEXT, 'user last name'),
                )
            )
        );
    }

}
