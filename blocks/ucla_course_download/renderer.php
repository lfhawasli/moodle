<?php
// This file is part of the UCLA course download plugin for Moodle - http://moodle.org/
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
 * Renderer class file.
 *
 * @package     block_ucla_course_download
 * @copyright   2014 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Renderer class to display course download requests.
 *
 * @package     block_ucla_course_download
 * @copyright   2014 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_ucla_course_download_renderer extends plugin_renderer_base {

    public function title_heading($title) {
        $buffer = html_writer::tag('h4', $title,
                        array('class' => 'cpanel-title'));
        return $buffer;
    }

    public function __call($method, $arguments) {
        global $COURSE;

        if ($method === 'request_unavailable') {

            $area = reset($arguments);
            $buffer = html_writer::tag('span',
                            get_string('request_unavailable',
                                    'block_ucla_course_download', $area));
        } else if ($method === 'request_available') {

            $area = reset($arguments);
            $button = $this->output->single_button(
                    new moodle_url('/blocks/ucla_course_download/view.php',
                    array('courseid' => $COURSE->id, 'action' => 'files_request')),
                    get_string('request', 'block_ucla_course_download'), 'post'
            );
            $buffer = html_writer::tag('p',
                            get_string('request_available',
                                    'block_ucla_course_download', $area));
            $buffer .= $button;
        } else if ($method === 'request_in_progress') {

            $area = reset($arguments);
            $buffer = html_writer::tag('span',
                            get_string('request_in_progress',
                                    'block_ucla_course_download', $area));
        } else if ($method == 'request_completed') {

            $area = $arguments[0];
            $coursecontent = $arguments[1];
            // Generate message.
            $request = $coursecontent->get_request();
            $expiration = $coursecontent->get_request_expiration();
            $a = array(
                'area' => $area,
                'timeupdated' => userdate($request->timeupdated),
                'timedelete' => userdate($expiration)
            );
            $requestmessage = get_string('request_completed',
                    'block_ucla_course_download', (object) $a);

            // See if we need to indicate to user that the file has changed
            // since the last time they downloaded it.
            if (!empty($request->timedownloaded) &&
                    $request->timeupdated > $request->timedownloaded) {
                $zipchanged = get_string('request_completed_changed',
                        'block_ucla_course_download',
                        userdate($request->timedownloaded));
                $requestmessage .= html_writer::tag('p', $zipchanged);
            }

            $request = $coursecontent->get_request();
            // We need file storage.
            $fs = get_file_storage();
            $file = $fs->get_file_by_id($request->fileid);
            $url = moodle_url::make_pluginfile_url($file->get_contextid(),
                            $file->get_component(), $file->get_filearea(),
                            $file->get_itemid(), $file->get_filepath(),
                            $file->get_filename());

            $buffer = html_writer::tag('p', $requestmessage);

            global $COURSE;
            $context = context_course::instance($COURSE->id);

            // Only display copyright notice for students.
            $script = '';

            if ($area === 'files' && !has_capability('moodle/course:manageactivities',
                            $context)) {
                // Print checkbox
                $buffer .= html_writer::div(
                                html_writer::div(
                                        html_writer::tag('label',
                                                html_writer::tag('input', '',
                                                        array('type' => 'checkbox',
                                                    'class' => 'course-download-copyright')) . get_string('copyrightagreement',
                                                        'block_ucla_course_download')
                                        ), 'checkbox'
                                ), 'form-group'
                );

                // YUI script to enable/disable 'download' button on copyright check.
                // Script assumes ONLY files for now.  
                $yui = <<<END
Y.use('node','event', function(Y) {

    var copyrightbutton = Y.one('.files .btn-copyright-check');
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

                $script = html_writer::script($yui);
            }

            // Print download button
            $buffer .= html_writer::link($url,
                            get_string('download', 'block_ucla_course_download',
                                    $a['area']),
                            array('class' => 'btn btn-primary btn-copyright-check'));
            $buffer .= $script;

            // Print any post messages.
            $postmessage = get_string('request_completed_post',
                    'block_ucla_course_download', (object) $a);
            $buffer .= html_writer::empty_tag('p');
            $buffer .= html_writer::tag('p', $postmessage);
        }

        // Send back any printable html we've generated.
        if (!empty($buffer)) {
            return $buffer;
        }

        // Or return output from parent.
        return parent::__call($method, $arguments);
    }

    public function course_download_status($status, $area, $coursecontent) {

        $buffer = html_writer::start_tag('ul',
                        array('class' => 'course-download ' . $area));
        $buffer .= html_writer::tag('li',
                        call_user_func(array($this, $status), $area,
                                $coursecontent), array('class' => 'arrow_box'));
        $buffer .= html_writer::end_tag('ul');

        return $buffer;
    }

    public function instructor_file_contents_view($content) {

        // Alert message
        $alert = html_writer::div(get_string('instructorfilewarning',
                                'block_ucla_course_download'),
                        'alert alert-warning');

        // Legend
        $legend = html_writer::span(
                        html_writer::span('', 'glyphicon glyphicon-ok') .
                        get_string('filemaybeincluded',
                                'block_ucla_course_download')
        );
        $legend .= html_writer::span(
                        html_writer::span('', 'glyphicon glyphicon-remove') .
                        get_string('filewillbeexcluded',
                                'block_ucla_course_download')
        );

        $maxsize = get_config('block_ucla_course_download', 'maxfilesize');
        // Convert bytes to MB
        $maxsize = $maxsize * pow(1024, 2);

        $legend .= html_writer::span(
                        get_string('fileoversizeexclusion',
                                'block_ucla_course_download',
                                display_size($maxsize))
        );

        // Print content.  Start with alert.
        $buffer = $alert;
        $buffer .= html_writer::div($legend, 'zip-contents-legend');

        // Print out file contents
        $buffer .= html_writer::start_div('zip-contents');

        // Iterate through sections, and for each section print out
        // all the files (resources) with a visibility indicator.
        foreach ($content as $section) {

            // List of files in section.
            $files = $section['files'];
            $folders = $section['folders'];

            // Only print sections with content.
            if (empty($files) && empty($folders)) {
                continue;
            }

            $classes = empty($section['visible']) ? 'omitted' : '';
            $classes .= ' section';

            // Section name
            $buffer .= html_writer::tag('div', $section['name'],
                            array('class' => $classes));

            // Start file list.
            $buffer .= html_writer::start_tag('ul');
            foreach ($files as $file) {

                // File will be excluded when it's hidden, or larger than allowed max size.
                $visible = empty($file['visible']) || $file['size'] > $maxsize;
                $classes = $visible ? 'omitted' : '';
                $glyph = $visible ? 'glyphicon glyphicon-remove' : 'glyphicon glyphicon-ok';
                $icon = html_writer::span('', $glyph);
                $size = html_writer::span(display_size($file['size']),
                                'filesize');

                $buffer .= html_writer::tag('li', $icon . $file['name'] . $size,
                                array('class' => $classes));
            }
            foreach ($folders as $folderfile) {

                // File will be excluded when it's hidden, or larger than allowed max size.
                $visible = empty($folderfile['visible']) || $folderfile['size'] > $maxsize;
                $classes = $visible ? 'omitted' : '';
                $glyph = $visible ? 'glyphicon glyphicon-remove' : 'glyphicon glyphicon-ok';
                $icon = html_writer::span('', $glyph);
                $size = html_writer::span(display_size($folderfile['size']),
                                'filesize');

                $buffer .= html_writer::tag('li', $icon . $folderfile['name'] . $size,
                                array('class' => $classes));
            }
            $buffer .= html_writer::end_tag('ul');
        }

        $buffer .= html_writer::end_div();

        // Output.
        return $buffer;
    }

}
