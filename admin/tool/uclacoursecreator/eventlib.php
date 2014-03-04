<?php
/*
 * Library hold functions that will be called for event handling
 */
require_once(dirname(__FILE__) . '/uclacoursecreator.class.php');

/**
 * Respond to events that require course creator to build now.
 *
 * @param array $terms  An array of terms to run course builder for
 */
function build_courses_now($terms) {
    $bcc = new uclacoursecreator();

    // This may take a while...
    @set_time_limit(0);

    $bcc->set_terms($terms);
    $bcc->cron();
}

/**
 * Responds to course deletion event. Does the following things:
 *
 * 1) Delete course request in ucla_request_classes.
 * 2) Check MyUCLA url web service to see if course has urls.
 * 2a) If has urls and they aren't pointing to current server, skip them.
 * 2b) If has urls and they are pointing to the current server, then clear them.
 * 3) Delete entries in ucla_reg_classinfo.
 * 4) Trigger ucla_course_deleted event.
 *
 *
 * @param object $course    Course object.
 * @return boolean          False on error, otherwise true.
 */
function handle_course_deleted($course) {
    global $CFG, $DB;

    // Check if course exists in ucla_request_classes.
    $uclarequestclasses = ucla_map_courseid_to_termsrses($course->id);
    if (empty($uclarequestclasses)) {
        return true;
    }
    $uclaclassinfo = ucla_get_course_info($course->id);

    // 1) Delete course request in ucla_request_classes
    $DB->delete_records('ucla_request_classes', array('courseid' => $course->id));

    // 2) Check MyUCLA url web service to see if course has urls
    $cc = new uclacoursecreator();
    $myucla_urlupdater = $cc->get_myucla_urlupdater();
    if (empty($myucla_urlupdater)) {
        return true;    // not installed
    }

    $has_error = false;
    foreach ($uclarequestclasses as $request) {
        $result = $myucla_urlupdater->set_url_if_same_server($request->term,
                $request->srs, '');
        if ($result == $myucla_urlupdater::url_set) {
            // Url cleared.
        } else if ($result == $myucla_urlupdater::url_notset) {
            // Url didn't belong to current server.
        } else {
            $has_error = true;
        }

        // 3) Delete entries in ucla_reg_classinfo.
        $DB->delete_records('ucla_reg_classinfo',
                array('term' => $request->term, 'srs' => $request->srs));
    }

    // Since mappings changed, purge all caches.
    $cache = cache::make('local_ucla', 'urcmappings');
    $cache->purge();

    // 4) Trigger ucla_course_deleted event.
    $eventdata = new stdClass();
    $eventdata->courseid = $course->id;
    $eventdata->ucla_request_classes = $uclaclassinfo;
    $eventdata->ucla_reg_classinfo = $uclaclassinfo;
    events_trigger('ucla_course_deleted', $eventdata);

    return !$has_error;
}
