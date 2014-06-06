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

            $area = reset($arguments);
            $buffer = html_writer::tag('span', get_string('request_unavailable', 'block_ucla_course_download', $area));

        } else if ($method === 'request_available') {
            
            $area = reset($arguments);
            $button = $this->output->single_button(
                new moodle_url('/blocks/ucla_course_download/view.php', array('courseid' => $COURSE->id, 'action' => 'files_request')), 
                get_string('request', 'block_ucla_course_download'), 'post'
            );
            $buffer = html_writer::tag('p', get_string('request_available', 'block_ucla_course_download', $area));
            $buffer .= $button;
            
        } else if ($method === 'request_in_progress') {
            
            $area = reset($arguments);
            $buffer = html_writer::tag('span', get_string('request_in_progress', 'block_ucla_course_download', $area));
            
        } else if ($method == 'request_completed') {
            
            $area = $arguments[0];
            $coursecontent = $arguments[1];
            // Generate message.
            list($timerequested, $timeupdated) = $coursecontent->get_request_update_time();
            // 
            $timeupdatedstring = userdate($timeupdated);
            $timedeletedstring = userdate($timerequested + 2592000); // TODO: Make config.
            $a = array(
                'area' => $area,
                'timeupdated' => $timeupdatedstring,
                'timedelete' => $timedeletedstring
            );
            $requestmessage = get_string('request_completed', 'block_ucla_course_download', (object)$a);


            $request = $coursecontent->get_request();
            // We need file storage.
            $fs = get_file_storage();
            $file = $fs->get_file_by_id($request->fileid);
            $url = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename());

            $buffer = html_writer::tag('p', $requestmessage);
            $buffer .= html_writer::div(
                html_writer::div(
                        html_writer::tag('label', 
                                html_writer::tag('input', '', array('type' => 'checkbox', 'class' => 'course-download-copyright')) . get_string('copyrightagreement', 'block_ucla_course_download')
                        ), 
                    'checkbox'
                ),
                'form-group'
            );
            $buffer .= html_writer::link($url, get_string('download', 'block_ucla_course_download', $a['area']), array('class' => 'btn btn-primary btn-copyright-check'));

            // YUI script to enable/disable 'download' button on copyright check.
            // Script assumes ONLY files for now.  
            $yui = <<<END
Y.use('node','event', function(Y) {

    var copyrightbutton = Y.one('.btn-copyright-check');
    copyrightbutton.setAttribute('disabled', 'disabled');
    
    Y.one('.course-download-copyright').on('change', function(e) {
        var checked = e.target.get('checked');
        if (checked) {
            copyrightbutton.removeAttribute('disabled');
        } else {
            copyrightbutton.setAttribute('disabled', 'disabled');
        }
    });
}); 
END;

            $buffer .= html_writer::script($yui);
        }
        
        // Send back any printable html we've generated.
        if (!empty($buffer)) {
            return $buffer;
        }
        
        // Or return output from parent.
        return parent::__call($method, $arguments);
    }
    
    public function course_download_status($status, $area, $coursecontent) {
        
        $buffer = html_writer::start_tag('ul', array('class' => 'course-download'));
        $buffer .= html_writer::tag('li', call_user_func(array($this, $status), $area, $coursecontent), array('class' => 'arrow_box'));
        $buffer .= html_writer::end_tag('ul');
     
        return $buffer;
    }

    public function instructor_file_contents_view() {
        global $COURSE;
        
        // Get sections
        $format = course_get_format($COURSE);
        $sections = $format->get_sections();

        // Get files (resources)
        $modinfo = get_fast_modinfo($COURSE);
        $resources = $modinfo->get_instances_of('resource');

        // Alert message
        $alert = html_writer::div(get_string('instructorfilewarning', 'block_ucla_course_download'), 'alert alert-warning');
        
        // Legend
        $legend = html_writer::span(
            html_writer::span('', 'glyphicon glyphicon-ok') .
                get_string('filemaybeincluded', 'block_ucla_course_download')
        );
        $legend .= html_writer::span(
            html_writer::span('', 'glyphicon glyphicon-remove') .
                get_string('filewillbeexcluded', 'block_ucla_course_download')
        );
        
        // Print contents
        $buffer = $alert;
        $buffer .= html_writer::div($legend, 'zip-contents-legend');
        
        // Print out file contents
        $buffer .= html_writer::start_div('zip-contents');

        // Iterate through sections, and for each section print out
        // all the files (resources) with a visibility indicator.
        foreach ($sections as $section) {
            $cmids = explode(',', $section->sequence);

            $classes = empty($section->visible) ? 'omitted' : '';
            $classes .= ' section';

            // Section name
            $buffer .= html_writer::tag('div', $section->name, array('class' => $classes));

            // List of files
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

        // Output.
        return $buffer;
    }

}