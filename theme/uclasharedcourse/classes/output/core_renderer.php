<?php
// This file is part of Moodle - http://moodle.org/
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
 * Core renderer.
 *
 * @package     theme_uclasharedcourse
 * @copyright  UC Regents 2017
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Core renderer class.
 *
 * @package    theme_uclasharedcourse
 * @copyright  UC Regents 2017
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class theme_uclasharedcourse_core_renderer extends theme_uclashared\output\core_renderer {

    /**
     * Enables additional header logos to show up in Edit settings.
     *
     * @var bool
     */
    public $coursetheme = true;

    /**
     * Contains header classes.
     *
     * @var string
     */
    public $headerclasses = 'header-custom-logo';

    /**
     * Logo html.
     *
     * @var string
     */
    public $logo = '';

    /**
     * Public theme name.
     *
     * @var string
     */
    public $themename = 'uclasharedcourse';

    /**
     * Component.
     * @var string
     */
    private $component = 'theme_uclasharedcourse';

    /**
     * Course Logo.
     * @var string
     */
    private $filearea = 'course_logos';

    /**
     * Sets logo and returns classes to use in header.
     *
     * @return string
     */
    public function get_headerclasses() {
        $this->logo = $this->get_logo();
        return $this->headerclasses;
    }

    /**
     * Display a custom category level logo + course logos, this overrides
     * the standard CCLE logo
     *
     * @param string $pix
     * @param string $pixloc
     * @param moodle_url $address
     * @return string
     */
    public function get_logo() {
        global $CFG, $COURSE, $DB, $OUTPUT;

        $category = $DB->get_record('course_categories', array('id' => $COURSE->category));
        $category->name = strtolower(str_replace(' ', '_', trim($category->name)));

        $img = $CFG->dirroot . '/theme/uclasharedcourse/pix/' . $category->name . '/logo.png';

        // Override theme logo.
        $alternativelogo = '';
        if (file_exists($img)) {
            $pix = $category->name . '/logo';
            $address = new moodle_url($CFG->wwwroot . '/course/view.php?id=' . $COURSE->id);

            $pixurl = $this->image_url($pix, 'theme');
            $logoalt = $COURSE->fullname;
            $logoimg = html_writer::img($pixurl, $logoalt);
            $alternativelogo = html_writer::link($address, $logoimg);

            // Save the category and course short name as CSS classes.
            $categoryname = str_replace(array(' ', '-'), '-', $category->name);
            $coursename = str_replace(array(' ', '-'), '-', $COURSE->shortname);

            $this->headerclasses .= ' ' . $categoryname;
            $this->headerclasses .= ' ' . $coursename;
        }

        // If main logo is overridden, then return that html.
        if (!empty($alternativelogo)) {
            return $alternativelogo;
        } else {
            // Use default logo as a fallback.
            $context = [
                'output' => $OUTPUT,
                'system_link' => get_config('theme_uclashared', 'system_link'),
                'system_name' => get_config('theme_uclashared', 'system_name')
            ];
            return $this->render_from_template('theme_uclashared/header_logo', $context);            
        }
    }

    /**
     * Checks if a user is enrolled in the course.
     *
     * @return bool
     */
    public function is_enrolled_user() {
        global $USER, $COURSE;
        $context = context_course::instance($COURSE->id);

        // Also allow managers to view the logos.
        return (is_enrolled($context, $USER) || has_capability('moodle/course:update', $context));
    }

    /**
     * We don't want to display week.
     *
     * @return empty string
     */
    public function weeks_display() {
        return '';
    }

    /**
     * We don't want to display sublogo.
     *
     * @return empty string
     */
    public function sublogo() {
        return '';
    }

    /**
     * Save course logos.
     *
     * @param object $data
     * @return void
     */
    public function course_logo_save($data) {
        global $COURSE;
        $context = context_course::instance($COURSE->id);

        file_save_draft_area_files($data->logo_attachments, $context->id,
                $this->component, $this->filearea, $COURSE->id, $this->course_logo_config());
        return;
    }

    /**
     * Get filepicker config.
     *
     * @return int
     */
    public function course_logo_config() {
        global $COURSE;

        $maxbytes = get_max_upload_file_size(0, $COURSE->maxbytes);

        $config = array(
            'subdirs' => 0,
            'maxbytes' => $maxbytes,
            'maxfiles' => 2,
            'accepted_types' => array('*.png')
        );

        return $config;
    }

    /**
     * Retrieve logo images for a course.
     *
     * @return stored_file[] array of stored_files indexed by pathnamehash
     */
    private function course_logo_images() {
        global $COURSE;

        // Do not display course logos to non-enrolled guests.
        if (!$this->is_enrolled_user()) {
            return array();
        }

        $context = context_course::instance($COURSE->id);
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, $this->component, $this->filearea, $COURSE->id, '', false);

        return $files;
    }

    /**
     * Render HTML code for course logos.
     *
     * @return string
     */
    public function course_logo() {
        global $CFG, $COURSE;
        $logos = $this->course_logo_images($COURSE->id);

        $out = '';
        if (!empty($logos)) {

            // Sort by filename.
            if (count($logos) > 1) {
                $logo1 = array_shift($logos);
                $logo2 = array_shift($logos);

                if ($logo2->get_filename() > $logo1->get_filename()) {
                    $logos[] = $logo1;
                    $logos[] = $logo2;
                } else {
                    $logos[] = $logo2;
                    $logos[] = $logo1;
                }
            }

            foreach ($logos as $logo) {
                $url = "{$CFG->wwwroot}/pluginfile.php/{$logo->get_contextid()}/{$this->component}/{$this->filearea}";
                $fileurl = $url . $logo->get_filepath() . $logo->get_itemid() . '/' . $logo->get_filename();

                $img = html_writer::img($fileurl, null);

                $div = html_writer::tag('div', $img, array('class' => 'uclashared-course-logo'));
                $out .= $div;
            }
        }

        return $out;
    }

    /**
     * Adds the file picker for theme logos to a mform.
     *
     * @param MoodleQuickForm $mform where filepicker will be added
     * @param int $courseid
     * @param int $contextid
     * @return array of items to display in the picker.
     */
    public function edit_form_filepicker(&$mform, $courseid, $contextid) {

        // Add a file manager.
        $mform->addElement('filemanager', 'logo_attachments',
                get_string('additional_logos', 'theme_uclasharedcourse'), null,
                $this->course_logo_config());

        // Show logo guide.
        $pixurl = $this->image_url('guide', 'theme');
        $img = html_writer::img($pixurl, null);
        $mform->addElement('static', 'description', '', $img);

        // Check if we already have images.
        $draftitemid = file_get_submitted_draft_itemid('logo_attachments');

        file_prepare_draft_area($draftitemid, $contextid, $this->component,
                $this->filearea, $courseid, $this->course_logo_config());

        $data['logo_attachments'] = $draftitemid;

        return $data;
    }

}
