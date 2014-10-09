<?php

    function videoannotation_backup_clips($bf, $preferences, $videoannotation, $activity_clips_only) {
        global $CFG;
        
        $status = true;

        // If $activity_clips_only is set,
        // then include only clips associated with this activity (i.e. user is NULL and group is NULL)
        // otherwise include all clips in this activity
        
        if ($activity_clips_only) {
            $videoannotation_clips = get_records_sql("
            SELECT *
            FROM {$CFG->prefix}videoannotation_clips
            WHERE videoannotationid = " . (int) $videoannotation . "
            AND userid IS NULL AND groupid IS NULL
            ORDER BY id");
        } else {
            $videoannotation_clips = get_records('videoannotation_clips', 'videoannotationid', $videoannotation, "id");
        }
        
        //If there are clips
        if ($videoannotation_clips) {
            //Write start tag
            $status = fwrite($bf,start_tag("CLIPS",4,true));
            //Iterate over each clip
            foreach ($videoannotation_clips as $videoannotation_clip) {
                //Start clip
                $status = fwrite($bf,start_tag("CLIP",5,true));
                //Print clip contents
                fwrite ($bf,full_tag("ID",6,false,$videoannotation_clip->id));       
                fwrite ($bf,full_tag("VIDEOANNOTATIONID",6,false,$videoannotation_clip->videoannotationid));       
                fwrite ($bf,full_tag("USERID",6,false,$videoannotation_clip->userid));       
                fwrite ($bf,full_tag("GROUPID",6,false,$videoannotation_clip->groupid));       
                fwrite ($bf,full_tag("URL",6,false,$videoannotation_clip->url));       
                fwrite ($bf,full_tag("PLAYABLETIMESTART",6,false,$videoannotation_clip->playabletimestart));       
                fwrite ($bf,full_tag("PLAYABLETIMEEND",6,false,$videoannotation_clip->playabletimeend));       
                fwrite ($bf,full_tag("VIDEOWIDTH",6,false,$videoannotation_clip->videowidth));       
                fwrite ($bf,full_tag("VIDEOHEIGHT",6,false,$videoannotation_clip->videoheight));       
                fwrite ($bf,full_tag("TIMECREATED",6,false,$videoannotation_clip->timecreated));       
                fwrite ($bf,full_tag("TIMEMODIFIED",6,false,$videoannotation_clip->timemodified));       
                //End clip
                $status = fwrite($bf,end_tag("CLIP",5,true));
            }
            //Write end tag
            $status = fwrite($bf,end_tag("CLIPS",4,true));
        }
        
        return $status;
    }

    function videoannotation_backup_events($bf, $preferences, $videoannotation) {
        global $CFG;
        
        $status = true;
        
        $videoannotation_events = get_records_sql(
        "SELECT e.*
        FROM {$CFG->prefix}videoannotation_clips c
        JOIN {$CFG->prefix}videoannotation_tags t ON c.id = t.clipid
        JOIN {$CFG->prefix}videoannotation_events e ON t.id = e.tagid
        WHERE c.videoannotationid = " . (int) $videoannotation . "
        ORDER BY t.id");

        //If there are events
        if ($videoannotation_events) {
            //Write start tag
            $status = fwrite($bf,start_tag("EVENTS",4,true));
            //Iterate over each event
            foreach ($videoannotation_events as $videoannotation_event) {
                //Start event
                $status = fwrite($bf,start_tag("EVENT",5,true));
                //Print event contents
                fwrite ($bf,full_tag("ID",6,false,$videoannotation_event->id));       
                fwrite ($bf,full_tag("TAGID",6,false,$videoannotation_event->tagid));       
                fwrite ($bf,full_tag("USERID",6,false,$videoannotation_event->userid));       
                fwrite ($bf,full_tag("GROUPID",6,false,$videoannotation_event->groupid));       
                fwrite ($bf,full_tag("STARTTIME",6,false,$videoannotation_event->starttime));       
                fwrite ($bf,full_tag("ENDTIME",6,false,$videoannotation_event->endtime));       
                fwrite ($bf,full_tag("CONTENT",6,false,$videoannotation_event->content));       
                fwrite ($bf,full_tag("TIMECREATED",6,false,$videoannotation_event->timecreated));       
                fwrite ($bf,full_tag("TIMEMODIFIED",6,false,$videoannotation_event->timemodified));       
                fwrite ($bf,full_tag("SCOPE",6,false,$videoannotation_event->scope));       
                fwrite ($bf,full_tag("LATITUDE",6,false,$videoannotation_event->latitude));       
                fwrite ($bf,full_tag("LONGITUDE",6,false,$videoannotation_event->longitude));       
                //End event
                $status = fwrite($bf,end_tag("EVENT",5,true));
            }
            //Write end tag
            $status = fwrite($bf,end_tag("EVENTS",4,true));
        }
        
        return $status;
    }

	function videoannotation_backup_mods($bf, $preferences) {
        global $CFG;

        $status = true;

        //Iterate over videoannotation table
        if ($videoannotations = get_records("videoannotation", "course", $preferences->backup_course, "id")) {
            foreach ($videoannotations as $videoannotation) {
                if (backup_mod_selected($preferences, 'videoannotation', $videoannotation->id)) {
                    $status = videoannotation_backup_one_mod($bf, $preferences, $videoannotation);
                    // backup files happens in backup_one_mod now too.
                }
            }
        }
        
        return $status;
	}
	
	function videoannotation_backup_one_mod($bf, $preferences, $videoannotation) {
        global $CFG;
    
        if (is_numeric($videoannotation)) {
            $videoannotation = get_record('videoannotation', 'id', $videoannotation);
        }
    
        $status = true;

        //Start mod
        
        fwrite ($bf,start_tag("MOD",3,true));
        
        //Print videoannotation data
        
        fwrite ($bf,full_tag("ID",4,false,$videoannotation->id));
        fwrite ($bf,full_tag("MODTYPE",4,false,"videoannotation"));
        fwrite ($bf,full_tag("NAME",4,false,$videoannotation->name));
        fwrite ($bf,full_tag("INTRO",4,false,$videoannotation->intro));
        fwrite ($bf,full_tag("INTROFORMAT",4,false,$videoannotation->introformat));
        fwrite ($bf,full_tag("CLIPSELECT",4,false,$videoannotation->clipselect));
        fwrite ($bf,full_tag("GROUPMODE",4,false,$videoannotation->groupmode));
        fwrite ($bf,full_tag("TIMECREATED",4,false,$videoannotation->timecreated));
        fwrite ($bf,full_tag("TIMEMODIFIED",4,false,$videoannotation->timemodified));

        // If we've selected to backup users info, 
        // then backup mdl_videoannotation_clips, mdl_videoannotation_events, mdl_videoannotation_submissions and
        // mdl_videoannotation_tags tables also
        
        if (backup_userdata_selected($preferences,'videoannotation', $videoannotation->id)) {
            $status = videoannotation_backup_clips($bf, $preferences, $videoannotation->id, false);
            if ($status) {
                $status = videoannotation_backup_events($bf, $preferences, $videoannotation->id);
            }
            if ($status) {
                $status = videoannotation_backup_submissions($bf, $preferences, $videoannotation->id);
            }
            if ($status) {
                $status = videoannotation_backup_tags($bf, $preferences, $videoannotation->id);
            }
        } else {
            $status = videoannotation_backup_clips($bf, $preferences, $videoannotation->id, true);
        }
        
        //End mod
        
        $status =fwrite ($bf,end_tag("MOD",3,true));

        return $status;
	}
    
    function videoannotation_backup_submissions($bf, $preferences, $videoannotation) {
        global $CFG;
        
        $status = true;

        $videoannotation_submissions = get_records('videoannotation_submissions', 'videoannotationid', $videoannotation, 'id');
        
        //If there are submissions
        if ($videoannotation_submissions) {
            //Write start tag
            $status = fwrite($bf,start_tag("SUBMISSIONS",4,true));
            //Iterate over each clip
            foreach ($videoannotation_submissions as $videoannotation_submission) {
                //Start clip
                $status = fwrite($bf,start_tag("SUBMISSION",5,true));
                //Print clip contents
                fwrite ($bf,full_tag("ID",6,false,$videoannotation_submission->id));       
                fwrite ($bf,full_tag("VIDEOANNOTATIONID",6,false,$videoannotation_submission->videoannotationid));       
                fwrite ($bf,full_tag("USERID",6,false,$videoannotation_submission->userid));       
                fwrite ($bf,full_tag("GROUPID",6,false,$videoannotation_submission->groupid));       
                fwrite ($bf,full_tag("CLIPID",6,false,$videoannotation_submission->clipid));       
                fwrite ($bf,full_tag("GRADE",6,false,$videoannotation_submission->grade));       
                fwrite ($bf,full_tag("GRADECOMMENT",6,false,$videoannotation_submission->gradecomment));       
                fwrite ($bf,full_tag("TIMESUBMITTED",6,false,$videoannotation_submission->timesubmitted));       
                fwrite ($bf,full_tag("TIMEGRADED",6,false,$videoannotation_submission->timegraded));       
                fwrite ($bf,full_tag("TIMECREATED",6,false,$videoannotation_submission->timecreated));       
                fwrite ($bf,full_tag("TIMEMODIFIED",6,false,$videoannotation_submission->timemodified));       
                //End clip
                $status = fwrite($bf,end_tag("SUBMISSION",5,true));
            }
            //Write end tag
            $status = fwrite($bf,end_tag("SUBMISSIONS",4,true));
        }
        
        return $status;
    }
    
    function videoannotation_backup_tags($bf, $preferences, $videoannotation) {
        global $CFG;
        
        $status = true;

        $videoannotation_tags = get_records_sql(
        "SELECT t.*
        FROM {$CFG->prefix}videoannotation_clips c
        JOIN {$CFG->prefix}videoannotation_tags t ON c.id = t.clipid
        WHERE c.videoannotationid = " . (int) $videoannotation . "
        ORDER BY t.id");
        
        //If there are tags
        if ($videoannotation_tags) {
            //Write start tag
            $status = fwrite($bf,start_tag("TAGS",4,true));
            //Iterate over each clip
            foreach ($videoannotation_tags as $videoannotation_tag) {
                //Start clip
                $status = fwrite($bf,start_tag("TAG",5,true));
                //Print clip contents
                fwrite ($bf,full_tag("ID",6,false,$videoannotation_tag->id));
                fwrite ($bf,full_tag("CLIPID",6,false,$videoannotation_tag->clipid));
                fwrite ($bf,full_tag("USERID",6,false,$videoannotation_tag->userid));
                fwrite ($bf,full_tag("GROUPID",6,false,$videoannotation_tag->groupid));
                fwrite ($bf,full_tag("NAME",6,false,$videoannotation_tag->name));
                fwrite ($bf,full_tag("COLOR",6,false,$videoannotation_tag->color));
                fwrite ($bf,full_tag("SORTORDER",6,false,$videoannotation_tag->sortorder));
                fwrite ($bf,full_tag("TIMECREATED",6,false,$videoannotation_tag->timecreated));
                fwrite ($bf,full_tag("TIMEMODIFIED",6,false,$videoannotation_tag->timemodified));
                //End clip
                $status = fwrite($bf,end_tag("TAG",5,true));
            }
            //Write end tag
            $status = fwrite($bf,end_tag("TAGS",4,true));
        }
        
        return $status;
    }
	
	function videoannotation_check_backup_mods($course, $user_data=false, $backup_unique_code, $instances=null) {
        if (!empty($instances) && is_array($instances) && count($instances)) {
            $info = array();
            foreach ($instances as $id => $instance) {
                $info += videoannotation_check_backup_mods_instances($instance,$backup_unique_code);
            }
            return $info;
        }
        
        //First the course data
        $info[0][0] = get_string("modulenameplural","videoannotation");
        $info[0][1] = (int) videoannotation_count_activities_by_course($course);

        //Now, if requested, the user_data
        if ($user_data) {
            $info[1][0] = get_string("submissions","videoannotation");
            $info[1][1] = (int) videoannotation_count_submissions_by_course($course);
        }
        
        return $info;
	}
	
    function videoannotation_check_backup_mods_instances($instance, $backup_unique_code) {
        $info[$instance->id.'0'][0] = '<b>' . $instance->name . '</b>';
        $info[$instance->id.'0'][1] = '';
        if (!empty($instance->userdata)) {
            $info[$instance->id.'1'][0] = get_string("clips","videoannotation");
            $info[$instance->id.'1'][1] = (int) videoannotation_count_clips_by_instance($instance->id);
            $info[$instance->id.'2'][0] = get_string("tags","videoannotation");
            $info[$instance->id.'2'][1] = (int) videoannotation_count_tags_by_instance($instance->id);
            $info[$instance->id.'3'][0] = get_string("events","videoannotation");
            $info[$instance->id.'3'][1] = (int) videoannotation_count_events_by_instance($instance->id);
            $info[$instance->id.'4'][0] = get_string("submissions","videoannotation");
            $info[$instance->id.'4'][1] = (int) videoannotation_count_submissions_by_instance($instance->id);
        }
        return $info;
    }
    
    function videoannotation_count_activities_by_course($course) {
        return count_records('videoannotation', 'course', $course);
    }

    
    function videoannotation_count_clips_by_course($course) {
        global $CFG;
        $sql = "
        SELECT count(*)
        FROM {$CFG->prefix}videoannotation va
        JOIN {$CFG->prefix}videoannotation_clips c ON va.id = c.videoannotationid
        WHERE va.course = " . (int) $course;
        return count_records_sql($sql);
    }
    
    function videoannotation_count_clips_by_instance($instance) {
        return count_records('videoannotation_clips', 'videoannotationid', $instance);
    }
    
    function videoannotation_count_events_by_course($instance) {
        global $CFG;
        $sql = "
        SELECT count(*)
        FROM {$CFG->prefix}videoannotation_clips c
        JOIN {$CFG->prefix}videoannotation_tags t ON c.id = t.clipid
        JOIN {$CFG->prefix}videoannotation_events e ON t.id = e.tagid
        WHERE c.videoannotationid = " . (int) $instance;
        return count_records_sql($sql);
    }
    
    function videoannotation_count_events_by_instance($instance) {
        global $CFG;
        $sql = "
        SELECT count(*)
        FROM {$CFG->prefix}videoannotation_clips c
        JOIN {$CFG->prefix}videoannotation_tags t ON c.id = t.clipid
        JOIN {$CFG->prefix}videoannotation_events e ON t.id = e.tagid
        WHERE c.videoannotationid = " . (int) $instance;
        return count_records_sql($sql);
    }
    
    function videoannotation_count_submissions_by_course($course) {
        global $CFG;
        $sql = "
        SELECT count(*)
        FROM {$CFG->prefix}videoannotation va
        JOIN {$CFG->prefix}videoannotation_submissions s ON va.id = s.videoannotationid
        WHERE va.course = " . (int) $course;
        return count_records_sql($sql);
    }
    
    function videoannotation_count_submissions_by_instance($instance) {
        return count_records('videoannotation_submissions', 'videoannotationid', $instance);
    }
    
    function videoannotation_count_tags_by_course($course) {
        global $CFG;
        $sql = "
        SELECT count(*)
        FROM {$CFG->prefix}videoannotation va
        JOIN {$CFG->prefix}videoannotation_clips c ON va.id = c.videoannotationid
        JOIN {$CFG->prefix}videoannotation_tags t ON c.id = t.clipid
        WHERE va.course = " . (int) $course;
        return count_records_sql($sql);
    }
    
    function videoannotation_count_tags_by_instance($instance) {
        global $CFG;
        $sql = "
        SELECT count(*)
        FROM {$CFG->prefix}videoannotation_clips c
        JOIN {$CFG->prefix}videoannotation_tags t ON c.id = t.clipid
        WHERE c.videoannotationid = " . (int) $instance;
        return count_records_sql($sql);
    }
    
?>
