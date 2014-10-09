<?php

function videoannotation_restore_mods($mod, $restore) {
    global $CFG;

    $status = true;

    //Get record from mdl_backup_ids
    
    $data = backup_getid($restore->backup_unique_code, $mod->modtype, $mod->id);
    if (!$data) {
        error("In videoannotation_restore_mods(): {$mod->modtype} #{$mod->id} does not have a record in backup_ids table."); 
    }
    
    // Build the videoannotation record structure
    
    $datainfo = $data->info;
    
    $videoannotation->course = $restore->course_id;
    $videoannotation->name = backup_todb($datainfo['MOD']['#']['NAME']['0']['#']);
    $videoannotation->intro = backup_todb($datainfo['MOD']['#']['INTRO']['0']['#']);
    $videoannotation->introformat = backup_todb($datainfo['MOD']['#']['INTROFORMAT']['0']['#']);
    $videoannotation->clipselect = backup_todb($datainfo['MOD']['#']['CLIPSELECT']['0']['#']);
    $videoannotation->groupmode = backup_todb($datainfo['MOD']['#']['GROUPMODE']['0']['#']);
    $videoannotation->timecreated = backup_todb($datainfo['MOD']['#']['TIMECREATED']['0']['#']);
    $videoannotation->timemodified = backup_todb($datainfo['MOD']['#']['TIMEMODIFIED']['0']['#']);
    
    $newid = insert_record('videoannotation', $videoannotation);
    if (!$newid)
        return false;
    
    backup_putid($restore->backup_unique_code, $mod->modtype, $mod->id, $newid);
    
    $status &= videoannotation_restore_clips($mod, $restore, $datainfo);
    
    if (restore_userdata_selected($restore,'videoannotation',$mod->id)) {
        $status &= videoannotation_restore_tags($mod, $restore, $datainfo);
        $status &= videoannotation_restore_events($mod, $restore, $datainfo);
        $status &= videoannotation_restore_submissions($mod, $restore, $datainfo);
    }
    
    return $status;
}

function videoannotation_restore_clips($mod, $restore, $datainfo) {
    global $CFG;

    $status = true;

    if (!isset($datainfo['MOD']['#']['CLIPS']['0']['#']['CLIP']))
        return $status;
    
    foreach ($datainfo['MOD']['#']['CLIPS']['0']['#']['CLIP'] as $clip_info) {        
        $clip = new stdClass();
        $clip->videoannotationid = backup_todb($clip_info['#']['VIDEOANNOTATIONID']['0']['#']);
        $clip->userid = backup_todb($clip_info['#']['USERID']['0']['#']);
        $clip->groupid = backup_todb($clip_info['#']['GROUPID']['0']['#']);
        $clip->url = backup_todb($clip_info['#']['URL']['0']['#']);
        $clip->playabletimestart = backup_todb($clip_info['#']['PLAYABLETIMESTART']['0']['#']);
        $clip->playabletimeend = backup_todb($clip_info['#']['PLAYABLETIMEEND']['0']['#']);
        $clip->videowidth = backup_todb($clip_info['#']['VIDEOWIDTH']['0']['#']);
        $clip->videoheight = backup_todb($clip_info['#']['VIDEOHEIGHT']['0']['#']);
        $clip->timecreated = backup_todb($clip_info['#']['TIMECREATED']['0']['#']);
        $clip->timemodified = backup_todb($clip_info['#']['TIMEMODIFIED']['0']['#']);
        
        // Get the new video annotation instance ID from mdl_backup_ids table
        
        $videoannotation = backup_getid($restore->backup_unique_code, 'videoannotation', $clip->videoannotationid);
        if ($videoannotation) {
            $clip->videoannotationid = $videoannotation->new_id;
        } else {
            error("In videoannotation_restore_clips(): Videoannotation #{$clip->videoannotation} does not have a record in backup_ids table."); 
        }
        
        // Get the new user ID and group ID from mdl_backup_ids table
        
        if ($clip->userid) {
            $user = backup_getid($restore->backup_unique_code, 'user', $clip->userid);
            if ($user) {
                $clip->userid = $user->new_id;
            } else {
                error("In videoannotation_restore_clips(): User #{$clip->userid} does not have a record in backup_ids table."); 
            }
        }
        if ($clip->groupid) {
            $group = backup_getid($restore->backup_unique_code, 'groups', $clip->groupid);
            if ($group) {
                $clip->groupid = $group->new_id;
            } else {
                error("In videoannotation_restore_clips(): Group #{$clip->groupid} does not have a record in backup_ids table."); 
            }
        }
        
        $newid = insert_record('videoannotation_clips', $clip);
        if (!$newid)
            return false;
            
        $oldid = backup_todb($clip_info['#']['ID']['0']['#']);
        backup_putid($restore->backup_unique_code, 'videoannotation_clips', $oldid, $newid);
    }
    
    return $status;
}

function videoannotation_restore_events($mod, $restore, $datainfo) {
    global $CFG;

    $status = true;

    if (!isset($datainfo['MOD']['#']['EVENTS']['0']['#']['EVENT']))
        return $status;
        
    foreach ($datainfo['MOD']['#']['EVENTS']['0']['#']['EVENT'] as $event_info) {
        $event = new stdClass();
        $event->tagid = backup_todb($event_info['#']['TAGID']['0']['#']);
        $event->userid = backup_todb($event_info['#']['USERID']['0']['#']);
        $event->groupid = backup_todb($event_info['#']['GROUPID']['0']['#']);
        $event->starttime = backup_todb($event_info['#']['STARTTIME']['0']['#']);
        $event->endtime = backup_todb($event_info['#']['ENDTIME']['0']['#']);
        $event->content = backup_todb($event_info['#']['CONTENT']['0']['#']);
        $event->timecreated = backup_todb($event_info['#']['TIMECREATED']['0']['#']);
        $event->timemodified = backup_todb($event_info['#']['TIMEMODIFIED']['0']['#']);
        $event->scope = backup_todb($event_info['#']['SCOPE']['0']['#']);
        $event->latitude = backup_todb($event_info['#']['LATITUDE']['0']['#']);
        $event->longitude = backup_todb($event_info['#']['LONGITUDE']['0']['#']);
        // Get the new tag ID from mdl_backup_ids table
        
        $tag = backup_getid($restore->backup_unique_code, 'videoannotation_tags', $event->tagid);
        if ($tag) {
            $event->tagid = $tag->new_id;
        } else {
            error("In videoannotation_restore_events(): Tag #{$event->tagid} does not have a record in backup_ids table."); 
        }
        
        // Get the new user ID and group ID from mdl_backup_ids table
        
        if ($event->userid) {
            $user = backup_getid($restore->backup_unique_code, 'user', $event->userid);
            if ($user) {
                $event->userid = $user->new_id;
            } else {
                error("In videoannotation_restore_events(): User #{$event->userid} does not have a record in backup_ids table."); 
            }
        }
        if ($event->groupid) {
            $group = backup_getid($restore->backup_unique_code, 'groups', $event->groupid);
            if ($group) {
                $event->groupid = $group->new_id;
            } else {
                error("In videoannotation_restore_events(): Group #{$event->groupid} does not have a record in backup_ids table."); 
            }
        }
        
        $newid = insert_record('videoannotation_events', $event);
        if (!$newid)
            return false;

        $oldid = backup_todb($event_info['#']['ID']['0']['#']);
        backup_putid($restore->backup_unique_code, 'videoannotation_events', $oldid, $newid);
    }
    return $status;
}

function videoannotation_restore_submissions($mod, $restore, $datainfo) {
    global $CFG;
    
    $status = true;
    
    if (!isset($datainfo['MOD']['#']['SUBMISSIONS']['0']['#']['SUBMISSION']))
        return $status;
    
    foreach ($datainfo['MOD']['#']['SUBMISSIONS']['0']['#']['SUBMISSION'] as $submission_info) {
        $submission = new stdClass();
        $submission->videoannotationid = backup_todb($submission_info['#']['VIDEOANNOTATIONID']['0']['#']);
        $submission->userid = backup_todb($submission_info['#']['USERID']['0']['#']);
        $submission->groupid = backup_todb($submission_info['#']['GROUPID']['0']['#']);
        $submission->clipid = backup_todb($submission_info['#']['CLIPID']['0']['#']);
        $submission->grade = backup_todb($submission_info['#']['GRADE']['0']['#']);
        $submission->gradecomment = backup_todb($submission_info['#']['GRADECOMMENT']['0']['#']);
        $submission->timesubmitted = backup_todb($submission_info['#']['TIMESUBMITTED']['0']['#']);
        $submission->timegraded = backup_todb($submission_info['#']['TIMEGRADED']['0']['#']);
        $submission->timecreated = backup_todb($submission_info['#']['TIMECREATED']['0']['#']);
        $submission->timemodified = backup_todb($submission_info['#']['TIMEMODIFIED']['0']['#']);
        
        // Get the new video annotation instance ID from mdl_backup_ids table
        
        $videoannotation = backup_getid($restore->backup_unique_code, 'videoannotation', $submission->videoannotationid);
        if ($videoannotation) {
            $submission->videoannotationid = $videoannotation->new_id;
        } else {
            error("In videoannotation_restore_submissions(): Video annotation #{$submission->videoannotationid} does not have a record in backup_ids table."); 
        }
        
        // Get the new user ID and group ID from mdl_backup_ids table
        
        if ($submission->userid) {
            $user = backup_getid($restore->backup_unique_code, 'user', $submission->userid);
            if ($user) {
                $submission->userid = $user->new_id;
            } else {
                error("In videoannotation_restore_submissions(): User #{$submission->userid} does not have a record in backup_ids table."); 
            }
        }
        if ($submission->groupid) {
            $group = backup_getid($restore->backup_unique_code, 'groups', $submission->groupid);
            if ($group) {
                $submission->groupid = $group->new_id;
            } else {
                error("In videoannotation_restore_submissions(): Group #{$submission->groupid} does not have a record in backup_ids table."); 
            }
        }
        
        // Get the new clip ID from mdl_backup_ids table
        
        $clip = backup_getid($restore->backup_unique_code, 'videoannotation_clips', $submission->clipid);
        if ($clip) {
            $submission->clipid = $clip->new_id;
        } else {
            error("In videoannotation_restore_submissions(): Clip #{$submission->clipid} does not have a record in backup_ids table."); 
        }
        
        $newid = insert_record('videoannotation_submissions', $submission);
        if (!$newid)
            return false;

        $oldid = backup_todb($submission_info['#']['ID']['0']['#']);
        backup_putid($restore->backup_unique_code, 'videoannotation_submissions', $oldid, $newid);
    }
    
    return $status;
}

function videoannotation_restore_tags($mod, $restore, $datainfo) {
    global $CFG;
    
    $status = true;
    
    if (!isset($datainfo['MOD']['#']['TAGS']['0']['#']['TAG']))
        return $status;
    
    foreach ($datainfo['MOD']['#']['TAGS']['0']['#']['TAG'] as $tag_info) {
        $tag = new stdClass();
        $tag->clipid = backup_todb($tag_info['#']['CLIPID']['0']['#']);
        $tag->userid = backup_todb($tag_info['#']['USERID']['0']['#']);
        $tag->groupid = backup_todb($tag_info['#']['GROUPID']['0']['#']);
        $tag->name = backup_todb($tag_info['#']['NAME']['0']['#']);
        $tag->color = backup_todb($tag_info['#']['COLOR']['0']['#']);
        $tag->sortorder = backup_todb($tag_info['#']['SORTORDER']['0']['#']);
        $tag->timecreated = backup_todb($tag_info['#']['TIMECREATED']['0']['#']);
        $tag->timemodified = backup_todb($tag_info['#']['TIMEMODIFIED']['0']['#']);

        // Get the new clip ID from mdl_backup_ids table
        
        $clip = backup_getid($restore->backup_unique_code, 'videoannotation_clips', $tag->clipid);
        if ($clip) {
            $tag->clipid = $clip->new_id;
        } else {
            error("In videoannotation_restore_tags(): Clip #{$tag->clipid} does not have a record in backup_ids table."); 
        }
        
        // Get the new user ID and group ID from mdl_backup_ids table
        
        if ($tag->userid) {
            $user = backup_getid($restore->backup_unique_code, 'user', $tag->userid);
            if ($user) {
                $tag->userid = $user->new_id;
            } else {
                error("In videoannotation_restore_tags(): User #{$tag->userid} does not have a record in backup_ids table."); 
            }
        }
        if ($tag->groupid) {
            $group = backup_getid($restore->backup_unique_code, 'groups', $tag->groupid);
            if ($group) {
                $tag->groupid = $group->new_id;
            } else {
                error("In videoannotation_restore_tags(): Group #{$tag->groupid} does not have a record in backup_ids table."); 
            }
        }
        
        $newid = insert_record('videoannotation_tags', $tag);
        if (!$newid)
            return false;

        $oldid = backup_todb($tag_info['#']['ID']['0']['#']);
        backup_putid($restore->backup_unique_code, 'videoannotation_tags', $oldid, $newid);
    }
    
    return $status;
}

?>
