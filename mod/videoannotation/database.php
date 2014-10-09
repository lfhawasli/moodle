<?php

error_reporting(E_ALL);
ini_set('display_errors', true);

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');

$inputs = array_merge($_GET, $_POST);
    
if (is_array($inputs)) {
    $outputs = array();
    for ($commandNum = 1; isset($inputs["c{$commandNum}_command"]); $commandNum++) {
		$input = array();
    	foreach ($inputs as $key => $value) {
			if (stripos($key, "c{$commandNum}_") === 0)
				$input[substr($key, strlen("c{$commandNum}_"))] = $value;
		}
		
    	$outputs[] = handleCommand($input, $commandNum);
    }
    echo json_encode($outputs);   
} else {
    echo json_encode(array("success" => false, "message" => "Data not given"));
}

function getContextByCourseModule($courseModuleId) {
    global $CFG, $DB;
    $sql = "
    SELECT ctx.*
    FROM {context} ctx
    WHERE ctx.contextlevel = " . CONTEXT_MODULE . " AND ctx.instanceid = " . (int) $courseModuleId;
    return $DB->get_record_sql($sql);
}

function getContextByVideoAnnotation($videoannotationId) {
    global $CFG, $DB;
    $sql = "
    SELECT ctx.*
    FROM {context} ctx
    JOIN {course_modules} cm ON ctx.contextlevel = " . CONTEXT_MODULE . " AND ctx.instanceid = cm.id
    JOIN {modules} m ON cm.module = m.id
    JOIN {videoannotation} va ON cm.instance = va.id
    WHERE m.name = 'videoannotation'
    AND va.id = " . (int) $videoannotationId;
    return $DB->get_record_sql($sql);
}

function getCourseModuleByClip($clipId) {
    global $CFG, $DB;
    $sql = "
    SELECT cm.*
    FROM mdl_course_modules cm
    JOIN mdl_modules m ON cm.module = m.id and m.name = 'videoannotation'
    JOIN mdl_videoannotation v ON cm.instance = v.id
    JOIN mdl_videoannotation_clips vc ON v.id = vc.videoannotationid AND vc.id = " . (int) $clipId;
    return $DB->get_record_sql($sql);
}

function getVideoAnnotationByClip($clipId) {
    global $CFG, $DB;
    $sql = "
    SELECT v.*
    FROM {videoannotation} v
    JOIN {videoannotation_clips} vc ON v.id = vc.videoannotationid
    WHERE vc.id = " . (int) $clipId;
    return $DB->get_record_sql($sql);
}

function getVideoAnnotationByEvent($eventId) {
    global $CFG, $DB;
    $sql = "
    SELECT v.*
    FROM {videoannotation} v
    JOIN {videoannotation_clips} vc ON v.id = vc.videoannotationid
    JOIN {videoannotation_tags} vt ON vc.id = vt.clipid
    JOIN {videoannotation_events} ve ON vt.id = ve.tagid
    WHERE ve.id = " . (int) $eventId;
    return $DB->get_record_sql($sql);
}

function getVideoAnnotationByTag($tagId) {
    global $CFG, $DB;
    $sql = "
    SELECT v.*
    FROM {videoannotation} v
    JOIN {videoannotation_clips} vc ON v.id = vc.videoannotationid
    JOIN {videoannotation_tags} vt ON vc.id = vt.clipid
    WHERE vt.id = " . (int) $tagId;
    return $DB->get_record_sql($sql);
}

function handleCommand($input, $commandNum) {
    global $CFG, $USER, $db, $DB;
    switch ($input['command']) {
        case 'test':
            return array("success" => true, "test" => "testing");
        
        case 'gettagsevents':
            if (!isset($input['clipid']))
                return array("success" => false, "message" => "clipid must be given.");
             
            $videoAnnotation = getVideoAnnotationByClip($input['clipid']);
            $courseModule = getCourseModuleByClip($input['clipid']);
            $moduleContext = context_module::instance($courseModule->id);
            $canManage = has_capability('mod/videoannotation:manage', $moduleContext);
            $canView = has_capability('mod/videoannotation:view', $moduleContext);
            $isAdmin = is_siteadmin($USER->id);
            
            // Require mod/videoannotation:manage or mod/videoannotation:view capability
            
            if (!$canManage and !$canView)
                return array("success" => false, "message" => "mod/videoannotation:manage or mod/videoannotation:view capability required.");
            
            // Grant access only in one of these cases:
            // 1. Requestor has "manage" capability (instructor, etc.)
            // 2. Requestor has "view" capability, is in the given group, and the course has a group mode of SEPARATEGROUPS
            // 3. Requestor has "view" capability, is in any of the groups in the course, and the course has a group mode of VISIBLEGROUPS
            
            // Now that we have the course module and the context, let's determine if the requester can access the data he asks for
            
            $onlineusers = array();
            switch ($videoAnnotation->groupmode) {
                case NOGROUPS:
                    // "userid" parameter is required; we don't care about "groupid" parameter
                
                    if (!isset($input['userid']))
                        return array("success" => false, "message" => "userid is required.");
                
                    // If only "view" capability present, the requestor's user ID must be the same as the given user ID
                
                    if (!$isAdmin and !$canManage and $USER->id != $input['userid'])
                        return array("success" => false, "message" => "Access denied (cannot access other users' tags).");
                
                    // Get and return data: all tags and events "owned" by 
                    // the user (given userid and null groupid)
                
                    $tag_user_group_clause = '(t.userid = ' . (int) $input['userid'] . ' AND t.groupid IS NULL )';
                    $event_user_group_clause = '(e.userid = ' . (int) $input['userid'] . ' AND e.groupid IS NULL )';
                    
                    $groupid = null;
                    
                    $onlineusers = null;
                    break;
                
                case VIDEOANNOTATION_GROUPMODE_USER_USER:
                case VIDEOANNOTATION_GROUPMODE_GROUP_USER:
                case VIDEOANNOTATION_GROUPMODE_GROUP_GROUP:
                case VIDEOANNOTATION_GROUPMODE_ALL_USER:
                case VIDEOANNOTATION_GROUPMODE_ALL_GROUP:
                case VIDEOANNOTATION_GROUPMODE_ALL_ALL:
                
                    // "groupid" parameter is required; we don't care about "userid" parameter
                
                    if (!isset($input['groupid']))
                        return array("success" => false, "message" => "groupid is required.");
                
                    // If only "view" capability present, the requestor's must be in
                    // * the given group (for separate group), or
                    // * one of the groups in the course (for visible group)
                
                    if (!$isAdmin and !$canManage) {
                        if($videoAnnotation->groupmode == VIDEOANNOTATION_GROUPMODE_GROUP_USER) {
                            $groups = groups_get_all_groups($courseModule->course, null, $courseModule->groupingid);
                        } else {
                            $groups = groups_get_all_groups($courseModule->course, $USER->id, $courseModule->groupingid);
                        }
                        
                        if (in_array($videoAnnotation->groupmode, array(VIDEOANNOTATION_GROUPMODE_USER_USER, VIDEOANNOTATION_GROUPMODE_GROUP_USER, VIDEOANNOTATION_GROUPMODE_GROUP_GROUP)) and !isset($groups[$input['groupid']]))
                            return array("success" => false, "message" => "Access denied (not in specified group).");
                        
                        if (in_array($videoAnnotation->groupmode, array(VIDEOANNOTATION_GROUPMODE_ALL_USER, VIDEOANNOTATION_GROUPMODE_ALL_GROUP, VIDEOANNOTATION_GROUPMODE_ALL_ALL)) and !$groups)
                            return array("success" => false, "message" => "Access denied (not in any group).");
                        
                        if (isset($groups[$input['groupid']])) {
                            $lock = $DB->get_record('videoannotation_locks', array('videoannotationid'=>$videoAnnotation->id, 'userid'=>$USER->id, 'groupid'=> $input['groupid']));
                            if ($lock) {
                               $DB->update_record('videoannotation_locks', (object) array('id' => $lock->id, 'timemodified' => time()));
                            } else {
                                $DB->insert_record('videoannotation_locks', (object) array('videoannotationid' => $videoAnnotation->id, 'userid' => $USER->id, 'groupid' => $input['groupid'], 'timecreated' => time(), 'timemodified' => time()));
                            }
                        }
                    }
                    //SSC-1231: Add user info to videoannotation_locks for admin users too
                    else {
                        $lock = $DB->get_record('videoannotation_locks', array('videoannotationid'=>$videoAnnotation->id, 'userid'=>$USER->id, 'groupid'=>$input['groupid']));
                        if ($lock) {
                            $DB->update_record('videoannotation_locks', (object) array('id' => $lock->id, 'timemodified' => time()));
                        } else {
                            $DB->insert_record('videoannotation_locks', (object) array('videoannotationid' => $videoAnnotation->id, 'userid' => $USER->id, 'groupid' => $input['groupid'], 'timecreated' => time(), 'timemodified' => time()));
                        }
                    }

                    
                    $DB->delete_records('videoannotation_locks', array('timemodified' => (time()- (.25 * 60))));
                    
                    $sql = "SELECT u.id, u.lastname FROM {user} u 
                            JOIN {videoannotation_locks} vl ON u.id = vl.userid
                            WHERE vl.videoannotationid = " . $videoAnnotation->id . " 
                            AND vl.groupid = " . (int) $input['groupid'] . " 
                            AND vl.userid != " . $USER->id;
                    $onlineuserrecords = $DB->get_records_sql($sql);
                    $onlineusers = array();
                    if ($onlineuserrecords) {
                        foreach ($onlineuserrecords as $onlineuserrecord) {
                            $onlineusers[] = $onlineuserrecord->lastname;
                        }
                    }
                    
                    // Get and return data
                    // For "individual" group mode: all tags and events "owned" by the user and the group (the given userid and groupid)
                    // For other group modes: all tags and events "owned" by the group (null/non-null userid and the given groupid)
                    
                    if ($videoAnnotation->groupmode == VIDEOANNOTATION_GROUPMODE_USER_USER) {
                        $tag_user_group_clause = '(t.userid = ' . (int) $USER->id . ' AND t.groupid = ' . (int) $input['groupid'] . ')';
                        $event_user_group_clause = '(e.userid = ' . (int) $USER->id . ' AND e.groupid = ' . (int) $input['groupid'] . ')';
                    } else {
                        $tag_user_group_clause = '(t.groupid = ' . (int) $input['groupid'] . ')';
                        $event_user_group_clause = '(e.groupid = ' . (int) $input['groupid'] . ')';
                    }
                    
                    $groupid = (int) $input['groupid'];
                    
                    break;
                
                default:
                    return array("success" => false, "message" => "Access denied (invalid group mode).");
            }
            
            if (ini_get('max_execution_time') == 0 or $input['timeout'] + 5 >= ini_get('max_execution_time'))
                $timeout = 0;
            else
                $timeout = $input['timeout'];
            
            // Start the timer
            
            $start_time = time();
            
            // Keep fetching new tags and events (see break condition near the end of the loop)
            
            do {
                // Get tags
            
                $sql = 
                "SELECT t.id, t.name, t.color, t.timecreated, t.timemodified
                FROM {videoannotation_tags} AS t
                WHERE t.clipid = " . (int) $input['clipid'] . '
                AND ' . $tag_user_group_clause;
                if (isset($input['timestamp']))
                    $sql .= ' AND (t.timecreated > ' . (int) $input['timestamp'] . ' OR t.timemodified > ' . (int) $input['timestamp'] . ')';
                $sql .= ' ORDER BY t.sortorder';
                $rs = $DB->get_recordset_sql($sql);
           //Fill tags into array, even if there are none (new video annotation has no tags). Only error on database error, not empty rs
                try {
                    $data = array();
                    foreach($rs as $record) {
                        array_push($data, $record);
                    }
                    $rs->close();
                    $tags = $data;
                } catch (dml_exception $e) {
                    return array("success"=> false, "message" => "Database error "); //$db->ErrorNo() . " : " . $DB->ErrorMsg()); 
                }
            
                // Get events
            
                $sql = 
                "SELECT e.id, e.tagid, e.starttime, e.endtime, e.content, e.timecreated, e.timemodified, e.latitude, e.longitude, e.scope
                FROM {videoannotation_events} e
                JOIN {videoannotation_tags} t ON e.tagid = t.id
                WHERE t.clipid = " . (int) $input['clipid'] . '
                AND ' . $tag_user_group_clause . '
                AND ' . $event_user_group_clause;
                if (isset($input['timestamp']))
                    $sql .= ' AND (e.timecreated > ' . (int) $input['timestamp'] . ' OR e.timemodified > ' . (int) $input['timestamp'] . ')';
                $rs = $DB->get_recordset_sql($sql);
                try {
                    $rsarray = array();
                    foreach($rs as $record) { 
                        array_push($rsarray, $record);
                    }
                    $rs->close();
                    $events = $rsarray;
                } catch (dml_exception $e) {
                    return array("success"=> false, "message" => "Database error " );//$db->ErrorNo() . " : " . $DB->ErrorMsg()); 
                }
            
                // Get deleted tags
            
                if (isset($input['tags']) and preg_match('/^\d+(\,\d+)*$/', $input['tags'])) {
                    $old_existing_tags = explode(',', $input['tags']);
                    $new_existing_tags = $DB->get_records_sql("SELECT id FROM {videoannotation_tags} t WHERE id IN (" . $input['tags'] . ") AND " . $tag_user_group_clause);
                    if ($new_existing_tags)
                        $new_existing_tags = array_keys($new_existing_tags);
                    else
                        $new_existing_tags = array();
                    $deleted_tags = array_values(array_diff($old_existing_tags, $new_existing_tags));
                } else {
                    $deleted_tags = array();
                }
            
                // Get deleted events
            
                if (isset($input['events']) and preg_match('/^\d+(\,\d+)*$/', $input['events'])) {
                    $old_existing_events = explode(',', $input['events']);
                    $new_existing_events = $DB->get_records_sql("SELECT id FROM {videoannotation_events} e WHERE id IN (" . $input['events'] . ")");
                    if ($new_existing_events)
                        $new_existing_events = array_keys($new_existing_events);
                    else
                        $new_existing_events = array();
                    $deleted_events = array_values(array_diff($old_existing_events, $new_existing_events));
                } else {
                    $deleted_events = array();
                }
            
                // Determine new timestmp
            
                $newtimestamp = isset($input['timestamp']) ? $input['timestamp'] : 0;
                foreach ($tags as &$tag) {
                    $newtimestamp = max($newtimestamp, (int) $tag->timecreated, (int) $tag->timemodified);
                    unset($tag->timecreated);
                    unset($tag->timemodified);
                }
                foreach ($events as &$event) {
                    $newtimestamp = max($newtimestamp, (int) $event->timecreated, (int) $event->timemodified);
                    unset($event->timecreated);
                    unset($event->timemodified);
                }
                
                if ((time() - $start_time >= $timeout) or $tags or $events or $deleted_tags or $deleted_events)
                    break;
                
                sleep(1);
            } while (true);
            
            // Return tags and events
            
            $result = array(
                "success" => true, 
                "tags" => $tags, 
                'events' => $events, 
                'deletedtags' => $deleted_tags, 
                'deletedevents' => $deleted_events, 
                'timestamp' => $newtimestamp,
            );
            if ($onlineusers !== null)
                $result['onlineusers'] = $onlineusers;
            return $result;
            
        case 'getclipinfo':
            if (isset($input['clipurl'])) {
                $info = videoannotation_get_clip_info($input['clipurl']);
                $info['success'] = true;
                return $info;
            } else {
                return array("success" => false, "message" => "clipurl is not given.");
            }


        //SSC-1191: Detect changes to the clip during editing

        case 'getclipdata':
            if (!isset($input['clipid']))
                return array("success" => false, "message" => "clipid is not given.");
            $sql =
            "SELECT c.videoannotationid, c.groupid, c.url, c.playabletimestart,
             c.playabletimeend, c.videowidth, c.videoheight, c.timecreated, c.timemodified
             FROM {videoannotation_clips} c
             WHERE c.id =" . (int) $input['clipid'];
            
            $rs = $DB->get_recordset_sql($sql);
            if(!$rs->valid())
                return array("success" => false, "message" => "Database error. ");
            $data = array();
            foreach($rs as $record) {
                array_push($data, $record);
            }
            $rs->close();
            $result = array( 
                "success" => true,
                "data" => $data
            );
            return $result;

        //END SSC-1191
        	
        case 'addtag':
            if (!isset($input['clipid']) or !isset($input['name']))
                return array("success" => false, "message" => "clipid or name is not given.");
            
            $videoAnnotation = getVideoAnnotationByClip($input['clipid']);
            $courseModule = videoannotation_get_course_module_by_video_annotation($videoAnnotation->id);
            $moduleContext = context_module::instance($courseModule->id);
            $canManage = has_capability('mod/videoannotation:manage', $moduleContext);
            $canSubmit = has_capability('mod/videoannotation:submit', $moduleContext);
            $canView = has_capability('mod/videoannotation:view', $moduleContext);
            $isAdmin = is_siteadmin($USER->id);
            
            // Security check
            
            if (!$isAdmin) {
                // Case 1: "userid" not given, "groupid" not given (tag will be owned by the activity)

                if (!isset($input['userid']) and !isset($input['groupid'])) {
                    // The user needs to have "manage" capability.
                
                    if (!$canManage)
                        return array("success" => false, "message" => "mod/videoannotation:manage capability required.");
                }
                
                // Case 2: "userid" given, "groupid" given (tag will be owned by the group)
                
                else if (isset($input['userid']) and isset($input['groupid'])) {
                    // The user needs to have "manage" or "submit" capability
                
                    if (!$canManage and !$canSubmit)
                        return array("success" => false, "message" => "mod/videoannotation:manage or mod/videoannotation:submit capability required.");
                
                    // The group has not submitted this activity yet
                
                    if ($DB->record_exists('videoannotation_submissions', array('videoannotationid'=>$videoAnnotation->id, 'groupid'=>$input['groupid'])))
                        return array("success" => false, "Cannot add event in a timeline that has already been submitted.");
                
                    // The activity needs to have group mode on 
                
                    if ($videoAnnotation->groupmode == NOGROUPS)
                        return array("success" => false, "message" => "Group mode must be on.");
                
                    // The user ID given (required) needs to equal the requestor's user ID, 
                
                    if ($USER->id != $input['userid'])
                        return array("success" => false, "message" => "userid, if given, must be the requestor's user ID.");
                
                    // The user have to be in the given group
                
                    if (!groups_is_member($input['groupid'], $input['userid']))
                        return array("success" => false, "message" => "The given user must be in the given group.");
                }
                
                // Case 3: "userid" given, "groupid" not given (tag will be owned by the user)
                
                else if (isset($input['userid'])) {
                    // The user needs to have "manage" or "submit" capability
                
                    if (!$canManage and !$canSubmit)
                        return array("success" => false, "message" => "mod/videoannotation:manage or mod/videoannotation:submit capability required.");
                
                    // The user must not have submitted this activity yet
                
                    if ($DB->record_exists('videoannotation_submissions', array('videoannotationid'=>$videoAnnotation->id, 'userid'=>$input['userid'], 'groupid'=>null)))
                        return array("success" => false, "Cannot add event in a timeline that has already been submitted.");
                
                    // The activity must have group mode off, 
                
                    if ($videoAnnotation->groupmode != NOGROUPS)
                        return array("success" => false, "message" => "Group mode must be off.");
                
                    // The user ID given needs to equal the requestor's user ID
                
                    if ($USER->id != $input['userid'])
                        return array("success" => false, "message" => "userid, if given, must be the requestor's user ID.");
                }
            
                // Case 4: "userid" not given, "groupid" given
            
                else {
                    // Not acceptable; complain and abort
                
                    return array("success" => false, "message" => "userid must be given if groupid is given.");
                }
            }
            
            // Insert record
            
    		$data = (object) array('clipid' => $input['clipid'], 'name' => $input['name'], 'timecreated' => time());
        	if (isset($input['userid']))
        		$data->userid = $input['userid'];
        	if (isset($input['groupid']))
        		$data->groupid = $input['groupid'];
        	if (isset($input['color']))
        		$data->color = $input['color'];
            $lastId = $DB->insert_record('videoannotation_tags', $data);
        	if ($lastId !== false)
        		return array("success" => true, "id" => $lastId);
        	else
        		return array("success" => false, "message" => "Database error "); //. ErrorNo() . ": " . $DB->ErrorMsg());
            
        case 'edittag':
            // id must be given
            
            if (!isset($input['id']))
                return array("success" => false, "message" => "id is not given.");
            
            if (!isset($input['name']) and !isset($input['color'])) {
                return array("success" => false, "message" => "name or color must be given.");
            }
            
            $videoAnnotation = getVideoAnnotationByTag($input['id']);
            $courseModule = videoannotation_get_course_module_by_video_annotation($videoAnnotation->id);
            $moduleContext = context_module::instance($courseModule->id);
            $canManage = has_capability('mod/videoannotation:manage', $moduleContext);
            $canSubmit = has_capability('mod/videoannotation:submit', $moduleContext);
            $isAdmin = is_siteadmin($USER->id);
            
            if (!$isAdmin) {
                // Security check
            
                switch ($videoAnnotation->groupmode) {
                    // Case 1: group mode is off
                
                    case NOGROUPS:
                        // The tag must belong to the requestor and not a group
                    
                        if (!$DB->record_exists('videoannotation_tags', array('id'=>$input['id'], 'userid'=>$USER->id, 'groupid'=>null)))
                            return array("success" => false, "message" => "Access denied (not owner of the tag).");
                    
                        // The requestor must not have submitted the activity
                    
                        if ($DB->record_exists('videoannotation_submissions', array('videoannotationid'=> $videoAnnotation->id, 'userid'=> $USER->id, 'groupid'=>null)))
                            return array("success" => false, "Cannot edit tag in a timeline that has already been submitted.");
                    
                        break;
                
                    // Case 2: group mode is "separate" or "visible"
                        
                    case VIDEOANNOTATION_GROUPMODE_USER_USER:
                    case VIDEOANNOTATION_GROUPMODE_GROUP_USER:
                    case VIDEOANNOTATION_GROUPMODE_GROUP_GROUP:
                    case VIDEOANNOTATION_GROUPMODE_ALL_USER:
                    case VIDEOANNOTATION_GROUPMODE_ALL_GROUP:
                    case VIDEOANNOTATION_GROUPMODE_ALL_ALL:
                    
                        // The tag must belong to a group that the requestor belongs
                        // Also, if group mode is "individual", "read all", the tag must be belong to the requestor
                        
                        $sql = "SELECT g.*
                                FROM {videoannotation_tags} vt
                                JOIN {groups_members} gm ON vt.groupid = gm.groupid
                                JOIN {groups} g ON gm.groupid = g.id
                                WHERE vt.id = " . (int) $input['id'] . " AND gm.userid = " . $USER->id;
                        if (in_array($videoAnnotation->groupmode, array(VIDEOANNOTATION_GROUPMODE_USER_USER, VIDEOANNOTATION_GROUPMODE_ALL_USER))) {
                            $sql .= " AND vt.userid = " . $USER->id;
                        }
                        $group = $DB->get_record_sql($sql);
                        if (!$group) {
                            return array("success" => false, "message" => "Access denied (not member the of the owner group of the tag).");
                        }
                    
                        // The requestor must not have submitted the activity
                    
                        if ($DB->record_exists('videoannotation_submissions', array('videoannotationid'=>$videoAnnotation->id, 'groupid'=> $group->id)))
                            return array("success" => false, "Cannot edit tag in a timeline that has already been submitted.");
                    
                        break;
                
                    // Case 3: group mode is something else
                
                    default:
                        // Complain and abort
                    
                        return array("success" => false, "message" => "Access denied (invalid group mode).");
                }
            }
            
            // Update record 
            
            $dataobject = new stdClass();
            $dataobject->id = $input['id'];
            if (isset($input['name']))
		$dataobject->name = $input['name'];
            if (isset($input['color']))
		$dataobject->color = $input['color']; 
            try {
	        $rs = $DB->update_record('videoannotation_tags', $dataobject);
                if (!$rs)
                    return array("success" => false, "errortype" => "writeconflict", "message" => "Another user might have edited or deleted the tag you are editing.");
            } catch (dml_exeception $e) {
                return array("success" => false, "errortype" => "database", "message" => "Database error "); //. ErrorNo() . ": " . $DB->ErrorMsg());
            }
            return array("success" => true);

        case 'deletetag':
            // id must be given
            
            if (!isset($input['id']))
                return array("success" => false, "message" => "id is not given.");
            
            $videoAnnotation = getVideoAnnotationByTag($input['id']);
            $courseModule = videoannotation_get_course_module_by_video_annotation($videoAnnotation->id);
            $moduleContext = context_module::instance($courseModule->id);
            $canManage = has_capability('mod/videoannotation:manage', $moduleContext);
            $canSubmit = has_capability('mod/videoannotation:submit', $moduleContext);
            $isAdmin = is_siteadmin($USER->id);
            
            // Security check
            
            if (!$isAdmin) {
                switch ($videoAnnotation->groupmode) {
                    // Case 1: group mode is off
                
                    case NOGROUPS:
                        // The tag must belong to the requestor and not a group
                    
                        if (!$DB->record_exists('videoannotation_tags', array('id'=>$input['id'], 'userid'=>$USER->id, 'groupid'=>null)))
                            return array("success" => false, "message" => "Access denied (not owner of the tag).");
                    
                        // The requestor must not have submitted the activity
                    
                        if ($DB->record_exists('videoannotation_submissions', array('videoannotationid'=>$videoAnnotation->id, 'userid'=>$USER->id, 'groupid'=>null)))
                            return array("success" => false, "Cannot edit tag in a timeline that has already been submitted.");
                    
                        break;
                
                    // Case 2: group mode is "separate" or "visible"
                        
                    case VIDEOANNOTATION_GROUPMODE_USER_USER:
                    case VIDEOANNOTATION_GROUPMODE_GROUP_USER:
                    case VIDEOANNOTATION_GROUPMODE_GROUP_GROUP:
                    case VIDEOANNOTATION_GROUPMODE_ALL_USER:
                    case VIDEOANNOTATION_GROUPMODE_ALL_GROUP:
                    case VIDEOANNOTATION_GROUPMODE_ALL_ALL:
                        // The tag must belong to a group that the requestor belongs
                        // Also, if group mode is "individua" or "read all", the tag must be belong to the requestor
                    
                        $sql = "SELECT g.*
                                FROM {videoannotation_tags} vt
                                JOIN {groups_members} gm ON vt.groupid = gm.groupid
                                JOIN {groups} g ON gm.groupid = g.id
                                WHERE vt.id = " . (int) $input['id'] . " AND gm.userid = " . $USER->id;
                        if (in_array($videoAnnotation->groupmode, array(VIDEOANNOTATION_GROUPMODE_USER_USER, VIDEOANNOTATION_GROUPMODE_ALL_USER))) {
                            $sql .= " AND vt.userid = " . $USER->id;
                        }
                        $group = $DB->get_record_sql($sql);
                        if (!$group) {
                            return array("success" => false, "message" => "Access denied (not member of the owner of the tag).");
                        }
                        
                        // The requestor must not have submitted the activity
                    
                        if ($DB->record_exists('videoannotation_submissions', array('videoannotationid'=>$videoAnnotation->id, 'groupid'=>$group->id)))
                            return array("success" => false, "Cannot edit tag in a timeline that has already been submitted.");
                    
                        break;
                
                    // Case 3: group mode is something else
                
                    default:
                        // Complain and abort
                    
                        return array("success" => false, "message" => "Access denied (invalid group mode).");
                }
            }
                
            // Delete record
                        
            $rs = $DB->delete_records('videoannotation_tags', array('id'=>$input['id']));
            if (!$rs)
                return array("success" => false, "errortype" => "database", "message" => "Database error ");// . ErrorNo() . ": " . $DB->ErrorMsg());
            
            $rs = $DB->delete_records('videoannotation_events', array('tagid'=>$input['id']));
            if (!$rs)
                return array("success" => false, "errortype" => "database", "message" => "Database error ");// . ErrorNo() . ": " . $DB->ErrorMsg());
            
            return array("success" => true);
            
        case 'reordertags':
            // clipid and orders must be given
            
            if (!isset($input['clipid']) or !isset($input['orders']))
                return array("success" => false, "message" => "clipid or orders is not given.");
            
            // Require mod/videoannotation:manage or mod/videoannotation:submit capability
            // If only "submit" capability exists, there cannot have a submission for this activity and user
            $videoAnnotation = getVideoAnnotationByClip($input['clipid']);
            $courseModule = getCourseModuleByClip($input['clipid']);
            $moduleContext = context_module::instance($courseModule->id);
            $canManage = has_capability('mod/videoannotation:manage', $moduleContext);
            $canSubmit = has_capability('mod/videoannotation:submit', $moduleContext);
            $isAdmin = is_siteadmin($USER->id);
            $dataobject = new stdClass();
            if ($isAdmin or $canManage) {
                // OK
            } else if ($canSubmit) {
                if ($DB->record_exists('videoannotation_submissions', array('videoannotationid'=> $videoAnnotation->id, 'userid'=>$USER->id)))
                    return array("success" => false, "Cannot reorder tags in a timeline that has already been submitted.");
            } else {
                return array("success" => false, "message" => "mod/videoannotation:manage or mod/videoannotation:submit capability required.");
            }
            
            // Access is granted in two cases
            // 1. If "userid" is given and the group mode is off, 
            //    then the requestor can reorder her own tags
            // 2. If "groupid" is given and the group mode is "separate" or "visible", 
            //    and the user is in the given group,
            //    then the requestor can reorder her group's tags
            // Either "userid" or "groupid", but not both, should be given
            
            switch ($videoAnnotation->groupmode) {
                case NOGROUPS:
                    $dataobject->userid = (int) $USER->id;
                    //$condition = 'userid = ' . (int) $USER->id;
                    break;
                case VIDEOANNOTATION_GROUPMODE_USER_USER:
                case VIDEOANNOTATION_GROUPMODE_GROUP_USER:
                case VIDEOANNOTATION_GROUPMODE_GROUP_GROUP:
                case VIDEOANNOTATION_GROUPMODE_ALL_USER:
                case VIDEOANNOTATION_GROUPMODE_ALL_GROUP:
                case VIDEOANNOTATION_GROUPMODE_ALL_ALL:
                    if (!isset($input['groupid']))
                        return array("success" => false, "message" => "groupid must be given.");
                    
                    if (!$isAdmin and !$canManage and !groups_is_member($input['groupid'], $USER->id))
                        return array("success" => false, "message" => "Access denied (user not in group).");
                    
                    if (in_array($videoAnnotation->groupmode, array(VIDEOANNOTATION_GROUPMODE_USER_USER, VIDEOANNOTATION_GROUPMODE_GROUP_USER, VIDEOANNOTATION_GROUPMODE_ALL_USER))) {
                        $dataobject->userid = (int) $USER->id;
                        $dataobject->groupid = (int) $input['groupid'];
                    }
                    else
                        $dataobject->groupid = (int) $input['groupid'];
                    break;
                default:
                    return array("success" => false, "message" => "Access denied (invalid group mode).");
            }
            
            // Update records
            
            $i=0;
            try {
                $arr = explode(",", $input['orders']);
                foreach ($arr as $id) {
                    $dataobject->id = $id;
                    $dataobject->sortorder = $i;
                    $result = $DB->update_record('videoannotation_tags', $dataobject);
                    $i++;
                }
            } catch (dml_exeception $e) {
                return array("success" => false, "message" => "Database error"); //. ErrorNo() . ": " . $DB->ErrorMsg());
            }
            return array("success" => true); 
        
        case 'addevent':
            if (!isset($input['tagid']) or !isset($input['starttime']) or !isset($input['endtime']))
                return array("success" => false, "message" => "tagid or starttime or endtime is not given.");
            
            if (!$DB->record_exists('videoannotation_tags', array('id'=>$input['tagid'])))
                return array("success" => false, "message" => "Tag does not exist.");
            
            $videoAnnotation = getVideoAnnotationByTag($input['tagid']);
            $courseModule = videoannotation_get_course_module_by_video_annotation($videoAnnotation->id);
            $moduleContext = context_module::instance($courseModule->id);
            $canManage = has_capability('mod/videoannotation:manage', $moduleContext);
            $canSubmit = has_capability('mod/videoannotation:submit', $moduleContext);
            $isAdmin = is_siteadmin($USER->id);
            
            // Security check
            
            if (!$isAdmin) {
                // Case 1: "userid" not given, "groupid" not given (event will be owned by the activity)

                if (!isset($input['userid']) and !isset($input['groupid'])) {
                    // The user needs to have "manage" capability.
                
                    if (!$isAdmin and !$canManage)
                        return array("success" => false, "message" => "mod/videoannotation:manage capability required.");
                }
            
                // Case 2: "userid" given, "groupid" given (event will be owned by the group)
            
                else if (isset($input['userid']) and isset($input['groupid'])) {
                    // The user needs to have "manage" or "submit" capability
                
                    if (!$isAdmin and !$canManage and !$canSubmit)
                        return array("success" => false, "message" => "mod/videoannotation:manage or mod/videoannotation:submit capability required.");
                
                    // The group has not submitted this activity yet
                
                    if ($DB->record_exists('videoannotation_submissions', array('videoannotationid'=>$videoAnnotation->id, 'groupid'=>$input['groupid'])))
                        return array("success" => false, "Cannot add event in a timeline that has already been submitted.");
                
                    // The activity needs to have group mode on 
                
                    if ($videoAnnotation->groupmode == NOGROUPS)
                        return array("success" => false, "message" => "Group mode must be on.");
                
                    // The user ID given (required) needs to equal the requestor's user ID, 
                
                    if ($USER->id != $input['userid'])
                        return array("success" => false, "message" => "userid, if given, must be the requestor's user ID.");
                
                    // The user have to be in the given group
                
                    if (!$DB->record_exists('groups_members', array('groupid'=>$input['groupid'], 'userid'=>$input['userid'])))
                        return array("success" => false, "message" => "The given user must be in the given group.");
                }
            
                // Case 3: "userid" given, "groupid" not given (tag will be owned by the user)
            
                else if (isset($input['userid'])) {
                    // The user needs to have "manage" or "submit" capability
                
                    if (!$isAdmin and !$canManage and !$canSubmit)
                        return array("success" => false, "message" => "mod/videoannotation:manage or mod/videoannotation:submit capability required.");
                
                    // The user must not have submitted this activity yet
                
                    if ($DB->record_exists('videoannotation_submissions', array('videoannotationid'=>$videoAnnotation->id, 'userid'=>$input['userid'], 'groupid'=>null)))
                        return array("success" => false, "Cannot add event in a timeline that has already been submitted.");
                
                    // The activity must have group mode off
                
                    if ($videoAnnotation->groupmode != NOGROUPS)
                        return array("success" => false, "message" => "Group mode must be off.");
                
                    // The user ID given needs to equal the requestor's user ID
                
                    if ($USER->id != $input['userid'])
                        return array("success" => false, "message" => "userid, if given, must be the requestor's user ID.");
                }
            
                // Case 4: "userid" not given, "groupid" given
            
                else {
                    // Not acceptable; complain and abort
                
                    return array("success" => false, "message" => "userid must be given if groupid is given.");
                }
            }
            
            // Make sure that the tag record is still there
            
            if (!$DB->record_exists('videoannotation_tags', array('id'=>$input['tagid'])))
                return array("success" => false, "errortype" => "writeconflict", "message" => "Another user might have deleted the tag you are editing.");
            
            // Insert event record
            
            $data = (object) array('tagid' => $input['tagid'], 'starttime' => $input['starttime'], 'endtime' => $input['endtime'], 'timecreated' => time());
        	if (isset($input['content']))
        	    $data->content = $input['content'];
                else
                    $data->content = "";
        	if (isset($input['userid']))
        	    $data->userid = $input['userid'];
        	if (isset($input['groupid']))
        	    $data->groupid = $input['groupid'];
                if (isset($input['latitude']))
                    $data->latitude = $input['latitude'];
                if (isset($input['longitude']))
                    $data->longitude = $input['longitude'];
                if (isset($input['scope']))
                    $data->scope = $input['scope'];
 	
        	$lastId = $DB->insert_record('videoannotation_events', $data);
        	if ($lastId !== false)
        		return array("success" => true, "id" => $lastId);
        	else
        		return array("success" => false, "message" => "Database error w");// . ErrorNo() . ": " . $DB->ErrorMsg());
           
        case 'editevent':
            // id must be given
            
            if (!isset($input['id']))
                return array("success" => false, "message" => "id is not given.");
            
            if (!$DB->record_exists('videoannotation_events', array('id'=>$input['id'])))
                return array("success" => false, "message" => "Event does not exist.");
            
            $videoAnnotation = getVideoAnnotationByEvent($input['id']);
            $courseModule = videoannotation_get_course_module_by_video_annotation($videoAnnotation->id);
            $moduleContext = context_module::instance($courseModule->id);
            $canManage = has_capability('mod/videoannotation:manage', $moduleContext);
            $canSubmit = has_capability('mod/videoannotation:submit', $moduleContext);
            $isAdmin = is_siteadmin($USER->id);
            
            if (!$isAdmin) {
                // Security check
            
                switch ($videoAnnotation->groupmode) {
                    // Case 1: group mode is off
                
                    case NOGROUPS:
                        // The event must belong to the requestor and not a group
                    
                        if (!$DB->record_exists('videoannotation_events', array('id'=>$input['id'], 'userid'=>$USER->id, 'groupid'=>null)))
                            return array("success" => false, "message" => "Access denied (not owner of the event).");
                    
                        // The requestor must not have submitted the activity
                    
                        if ($DB->record_exists('videoannotation_submissions', array('videoannotationid'=>$videoAnnotation->id, 'userid'=>$USER->id, 'groupid'=>null)))
                            return array("success" => false, "Cannot edit event in a timeline that has already been submitted.");
                    
                        break;
                
                    // Case 2: group mode is "separate" or "visible"
                        
                    case VIDEOANNOTATION_GROUPMODE_USER_USER:
                    case VIDEOANNOTATION_GROUPMODE_GROUP_USER:
                    case VIDEOANNOTATION_GROUPMODE_GROUP_GROUP:
                    case VIDEOANNOTATION_GROUPMODE_ALL_USER:
                    case VIDEOANNOTATION_GROUPMODE_ALL_GROUP:
                    case VIDEOANNOTATION_GROUPMODE_ALL_ALL:
                        // The event must belong to a group that the requestor belongs
                        // Also, if group mode is "individual" or "read all", the tag must be belong to the requestor
                        
                        $sql = "SELECT g.*
                                FROM {videoannotation_events} ve
                                JOIN {groups_members} gm ON ve.groupid = gm.groupid
                                JOIN {groups} g ON gm.groupid = g.id
                                WHERE ve.id = " . (int) $input['id'] . " AND gm.userid = " . $USER->id;
                        $group = $DB->get_record_sql($sql);
                        if (!$group) {
                            return array("success" => false, "message" => "Access denied (not member of the owner of the event).");
                        }
                        if (in_array($videoAnnotation->groupmode, array(VIDEOANNOTATION_GROUPMODE_USER_USER, VIDEOANNOTATION_GROUPMODE_GROUP_USER, VIDEOANNOTATION_GROUPMODE_ALL_USER))) {
                            $sql .= " AND ve.userid = " . $USER->id;
                        }
                        
                        // The requestor must not have submitted the activity
                    
                        if ($DB->record_exists('videoannotation_submissions', array('videoannotationid'=>$videoAnnotation->id, 'groupid'=>$group->id)))
                            return array("success" => false, "Cannot edit event in a timeline that has already been submitted.");
                    
                        break;
                
                    // Case 3: group mode is something else
                
                    default:
                        // Complain and abort
                    
                        return array("success" => false, "message" => "Access denied (invalid group mode).");
                }
            }
            
            // Update record
            
            $dataobject = new stdClass();
            $dataobject->timemodified = time();
            if (isset($input['tagid']))
                $dataobject->tagid = $input['tagid'];
            if (isset($input['starttime']))
                $dataobject->starttime = $input['starttime'];
            if (isset($input['endtime']))
                $dataobject->endtime = $input['endtime'];
            if (isset($input['content']))
                $dataobject->content = $input['content'];
            if (isset($input['latitude']))
                $dataobject->latitude = $input['latitude'];
            if (isset($input['longitude']))
                $dataobject->longitude = $input['longitude'];
            if (isset($input['scope']))
                $dataobject->scope = $input['scope'];
            $dataobject->id = $input['id'];
            try {
                $rs = $DB->update_record('videoannotation_events', (object) $dataobject);
                if (!$rs)
                    return array("success" => false, "errortype" => "database", "message" => "Database error i");// . ErrorNo() . ": " . $DB->ErrorMsg());
            } catch (dml_exception $e) {
                return array("success" => false, "errortype" => "writeconflict", "message" => " Another user might have deleted the tag or edited the event you are editing.");
            }           
            return array("success" => true);
            
        case 'deleteevent':
            if (!isset($input['id']))
                return array("success" => false, "message" => "id is not given.");
                
            $videoAnnotation = getVideoAnnotationByEvent($input['id']);
            $context = getContextByVideoAnnotation($videoAnnotation->id);
            $courseModule = videoannotation_get_course_module_by_video_annotation($videoAnnotation->id);
            $canManage = has_capability('mod/videoannotation:manage', get_context_instance(CONTEXT_SYSTEM));
            $canSubmit = has_capability('mod/videoannotation:submit', get_context_instance(CONTEXT_SYSTEM));
            $isAdmin = is_siteadmin($USER->id);
            
            // Security check
            
            if (!$isAdmin) {
                switch ($videoAnnotation->groupmode) {
                    // Case 1: group mode is off
                
                    case NOGROUPS:
                        // The event must belong to the requestor and not a group
                    
                        if (!$DB->record_exists('videoannotation_events', array('id'=>$input['id'], 'userid'=>$USER->id, 'groupid'=>null)))
                            return array("success" => false, "message" => "Access denied (not owner of the event).");
                    
                        // The requestor must not have submitted the activity
                    
                        if ($DB->record_exists('videoannotation_submissions', array('videoannotationid'=>$videoAnnotation->id, 'userid'=>$USER->id, 'groupid'=> null)))
                            return array("success" => false, "Cannot edit event in a timeline that has already been submitted.");
                    
                        break;
                
                    // Case 2: group mode is "separate" or "visible"
                        
                    case VIDEOANNOTATION_GROUPMODE_USER_USER:
                    case VIDEOANNOTATION_GROUPMODE_GROUP_USER:
                    case VIDEOANNOTATION_GROUPMODE_GROUP_GROUP:
                    case VIDEOANNOTATION_GROUPMODE_ALL_USER:
                    case VIDEOANNOTATION_GROUPMODE_ALL_GROUP:
                    case VIDEOANNOTATION_GROUPMODE_ALL_ALL:
                        // The event must belong to a group that the requestor belongs
                        // Also, if group mode is "individual" or  "read all", the event must be belong to the requestor
                    
                        $sql = "SELECT g.*
                                FROM {videoannotation_events} ve
                                JOIN {groups_members} gm ON ve.groupid = gm.groupid
                                JOIN {groups} g ON gm.groupid = g.id
                                WHERE ve.id = " . (int) $input['id'] . " AND gm.userid = " . $USER->id;
                        if (in_array($videoAnnotation->groupmode, array(VIDEOANNOTATION_GROUPMODE_USER_USER, VIDEOANNOTATION_GROUPMODE_GROUP_USER, VIDEOANNOTATION_GROUPMODE_ALL_USER))) {
                            $sql .= " AND ve.userid = " . $USER->id;
                        }
                        $group = $DB->get_record_sql($sql);
                        if (!$group) {
                            return array("success" => false, "message" => "Access denied (not member of the owner of the event).");
                        }
                        
                        // The requestor must not have submitted the activity
                    
                        if ($DB->record_exists('videoannotation_submissions', array('videoannotationid'=>$videoAnnotation->id, 'groupid'=>$group->id)))
                            return array("success" => false, "Cannot edit event in a timeline that has already been submitted.");
                    
                        break;
                
                    // Case 3: group mode is something else
                
                    default:
                        // Complain and abort
                    
                        return array("success" => false, "message" => "Access denied (invalid group mode).");
                }
            }
            
            // Delete record
            
            $rs = $DB->delete_records('videoannotation_events', array('id'=>$input['id']));
            if (!$rs)
                return array("success" => false, "message" => "Database error q");// . ErrorNo() . ": " . $DB->ErrorMsg());
            
            return array("success" => true);
            
        default:
            return array("success" => false, "message" => "Unknown command \"${input['command']}\"");
    }
}    

?>
