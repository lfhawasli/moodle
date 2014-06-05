<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class block_ucla_course_download_renderer extends plugin_renderer_base {
    
    public function title_heading($title) {
        $buffer = html_writer::tag('h4', $title, array('class' => 'cpanel-title'));
        return $buffer;
    }
    
    public function __call($method, $arguments) {
        global $COURSE;
        
        if ($method === 'request_unavailable') {
            $buffer = html_writer::start_tag('ul', array('class' => 'course-download'));
            $buffer .= html_writer::tag('li', $this->render_request_unavailable(), array('class' => 'arrow_box'));
            $buffer .= html_writer::end_tag('ul');
            
            return $buffer;
        } else if ($method === 'request_available') {
            $buffer = html_writer::start_tag('ul', array('class' => 'course-download'));
            $button = $this->output->single_button(
                new moodle_url('/blocks/ucla_course_download/view.php', array('courseid' => $COURSE->id, 'action' => 'files_request')), 
                get_string('request', 'block_ucla_course_download'), 
                'post'
            );
            $buffer .= html_writer::tag('li', $this->render_equest_available() . $button, array('class' => 'arrow_box'));
            $buffer .= html_writer::end_tag('ul');
            
            return $buffer;
        } else if ($method === 'request_in_progress') {
            $buffer = html_writer::start_tag('ul', array('class' => 'course-download'));
            $buffer .= html_writer::tag('li', $this->render_request_in_progress(), array('class' => 'arrow_box'));
            $buffer .= html_writer::end_tag('ul');
            
            return $buffer;
        } else if ($method == 'request_completed') {
            $buffer = html_writer::start_tag('ul', array('class' => 'course-download'));
            $buffer .= html_writer::tag('li', $this->render_request_completed(reset($arguments)), array('class' => 'arrow_box'));
            $buffer .= html_writer::end_tag('ul');
            
            return $buffer;
        }
        
        parent::__call($method, $arguments);
    }
    

    public function print_contents() {
        global $COURSE;
        
        $format = course_get_format($COURSE);
        $sections = $format->get_sections();

        $modinfo = get_fast_modinfo($COURSE);
        $resources = $modinfo->get_instances_of('resource');

        $alert = html_writer::div(get_string('instructorfilewarning', 'block_ucla_course_download'), 'alert alert-warning');
        
        $legend = html_writer::span(
            html_writer::span('', 'glyphicon glyphicon-ok') .
            'File may be included'
        );
        $legend .= html_writer::span(
            html_writer::span('', 'glyphicon glyphicon-remove') .
            'File will be omitted'
        );
        
        $buffer = $alert;
        
        $buffer .= html_writer::div($legend, 'zip-contents-legend');
        
        // Print out file contents
        $buffer .= html_writer::start_div('zip-contents');

        foreach ($sections as $section) {
            $cmids = explode(',', $section->sequence);

            $classes = empty($section->visible) ? 'omitted' : '';
            $classes .= ' section';

            $buffer .= html_writer::tag('div', $section->name, array('class' => $classes));

            $buffer .= html_writer::start_tag('ul');
            foreach ($cmids as $modid) {
                foreach ($resources as $resource) {
                    if ($resource->id == $modid) {
                        $classes = empty($resource->visible) ? 'omitted' : '';
                        $glyph = empty($resource->visible) ? 'glyphicon glyphicon-remove' : 'glyphicon glyphicon-ok';
                        $icon = html_writer::span('', $glyph);
                        $buffer .= html_writer::tag('li', $icon . $resource->name, array('class' => $classes));
                    }
                }
            }
            $buffer .= html_writer::end_tag('ul');

        }

        $buffer .= html_writer::end_div();

        return $buffer;
    }

    /**
     * Request state 'unavailable'.  When there are no files to download in a course.
     * 
     * @return type
     */
    public function render_request_unavailable() {
        $buffer = html_writer::tag('span', get_string('request_unavailable', 'block_ucla_course_download', 'course files'));
        return $buffer;
    }
    
    /**
     * Request state 'available'.  Displays when there are files to download in a course.
     * 
     * @global type $COURSE
     * @return type
     */
    public function render_equest_available() {
        global $COURSE;
        
        $buffer = html_writer::tag('p', get_string('not_requested', 'block_ucla_course_download', 'course files'));
        
        
        return $buffer;
    }
    
    /**
     * 
     * @return type
     */
    public function render_request_in_progress() {
        
        $buffer = html_writer::tag('span', get_string('request_in_progress', 'block_ucla_course_download', 'course files'));
//        $buffer .= html_writer::tag('button', get_string('in_progress', 'block_ucla_course_download'), array('class' => 'btn', 'disabled' => 'true'));
        
        return $buffer;
    }    
    
    public function render_request_completed($coursecontent) {
        // We need file storage.
        $fs = get_file_storage();

        list($timerequested, $timeupdated) = $coursecontent->get_request_update_time();
        // 
        $timeupdatedstring = userdate($timeupdated);
        $timedeletedstring = userdate($timerequested + 2592000); // TODO: Make config.
        $requestmessage = get_string('request_completed', 'block_ucla_course_download', 'course files') . ' ' .
                          get_string('request_completed_updated', 'block_ucla_course_download', $timeupdatedstring) . ' '.
                          get_string('request_completed_deletion', 'block_ucla_course_download', $timedeletedstring);
        

        $request = $coursecontent->get_request();
        $file = $fs->get_file_by_id($request->fileid);
        $url = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename());
        
        $buffer = html_writer::tag('p', $requestmessage);
        $buffer .= html_writer::link($url, get_string('download', 'block_ucla_course_download'), array('class' => 'btn btn-primary'));
        
        return $buffer;
    }

}