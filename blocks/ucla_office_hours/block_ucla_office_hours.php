<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/moodleblock.class.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/local/ucla/registrar/registrar_query.base.php');
require_once($CFG->dirroot . '/' . $CFG->admin . '/tool/uclacourserequestor/lib.php');

class block_ucla_office_hours extends block_base {
    const DISPLAYKEY_PREG = '/([0-9]+[_])(.*)/';

    // This is a hack for displaying table-dependent header
    const TITLE_FLAG = '01__title__';

    public function init() {
        $this->title = get_string('pluginname', 'block_ucla_office_hours');
    }

    public function get_content() {
        if($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass;

        return $this->content;
    }

    public function applicable_formats() {
        return array(
            'site-index' => false,
            'course-view' => false,
            'my' => false,
            'block-ucla_office_hours' => false,
            'not-really-applicable' => true
        );
    }

    /**
    * Makes sure that $edit_user is an instructing role for $course. Also makes
    * sure that user initializing editing has the ability to edit office hours.
    *
    * @param mixed $course_context  Course context
    * @param mixed $edit_user_id    User id we are editing
    *
    * @return boolean
    */
    public static function allow_editing($course_context, $edit_user_id) {
        global $CFG, $USER;

        // do capability check (but always let user edit their own entry)
        if ($edit_user_id != $USER->id  &&
                !has_capability('block/ucla_office_hours:editothers', $course_context)) {
            //debugging('failed capability check');
            return false;
        }

        /**
        * Course and edit_user must be in the same course and must be one of the
        * roles defined in $CFG->instructor_levels_roles, which is currently:
        *
        * $CFG->instructor_levels_roles = array(
        *   'Instructor' => array(
        *       'editinginstructor',
        *       'ta_instructor'
        *   ),
        *   'Teaching Assistant' => array(
        *       'ta',
        *       'ta_admin'
        *   )
        * );
        */

        // Format $CFG->instructor_levels_roles so it is easier to search.
        $allowed_roles = call_user_func_array('array_merge', $CFG->instructor_levels_roles);
 
        // get user's roles
        $roles = get_user_roles($course_context, $edit_user_id);

        // now see if any of those roles match anything in
        // $CFG->instructor_levels_roles
        foreach ($roles as $role) {
            if (in_array($role->shortname, $allowed_roles)) {
                return true;
            }
        }

        //debugging('role not in instructor_levels_roles');
        return false;
    }

    /**
     * Renders the office hours and contact information table to be displayed
     * on the course webpage.
     *
     * @param array     $instructors        Array of instructors
     * @param mixed     $course             Current course
     * @param mixed     $context            Course context
     *
     * @return string HTML code
     */
    public static function render_office_hours_table($instructors,
                                                     $course, $context) {
        global $DB, $OUTPUT, $PAGE, $USER, $CFG;

        $instr_info_table = '';

        $appended_info = self::blocks_office_hours_append($instructors,
                $course, $context);

        list($table_headers, $ohinstructors) =
            self::combine_blocks_office_hours($appended_info);

        // Optionally remove some instructors from display
        $block_filtered_users = self::blocks_office_hours_filter_instructors(
                $instructors, $course, $context
            );

        // Flatten out results
        $filtered_users = array();
        foreach ($block_filtered_users as $block => $filtered_user_keys) {
            foreach ($filtered_user_keys as $filtered_user_key) {
                $filtered_users[$filtered_user_key] = $filtered_user_key;
            }
        }

        // Gather all default rolenames from config
        $instructor_types = $CFG->instructor_levels_roles;

        // Query the database for course context specific roles.
        $fixedroles = role_fix_names(get_all_roles(), $context);

        /**
         * Filter and organize users here.
         *
         * This code includes logic that allows renamed roles to appear in
         * office hour blocks. This will rename the roles for the role
         * groups, depending on the renamed name of the Instructor, the
         * Teaching Assistant, and the Student Facilitator.
         *
         * The "$listedusers" variable prevents users from being listed more
         * than once on the office hours block.
         */

        $listedusers = array();
        foreach ($instructor_types as $title => $rolenames) {
            foreach ($fixedroles as $fixedrole) {
                if ($fixedrole->shortname == $rolenames[0]) {
                    $title = $fixedrole->localname;
                }
            }

            $type_table_headers = $table_headers;
            $goal_users = array();

            foreach ($instructors as $uk => $user) {
                if (in_array($uk, $filtered_users)) {
                    continue;
                }
                if (in_array($user->shortname, $rolenames)
                        && !in_array($user->id, $listedusers)) {
                    $goal_users[$user->id] = $ohinstructors[$uk];
                    $listedusers[] = $user->id;
                }
            }

            if (empty($goal_users)) {
                continue;
            }

            $table = new html_table();
            $table->width = '*';

            $cdi = count($table_headers);
            $aligns = array();
            for ($i = 0; $i < $cdi; $i++) {
                $aligns[] = 'left';
            }

            $table->align = $aligns;

            $table->attributes['class'] =
                    'boxalignleft generaltable cellborderless office-hours-table ' .
                    $rolenames[0]; // This appendation allows Behat testing to work

            $table->head = array();

            // Cleaning headers
            foreach ($type_table_headers as $field => $header) {
                $found = false;
                foreach ($goal_users as $user) {
                    if (!empty($user->{$field})) {
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    unset($type_table_headers[$field]);
                }
            }

            // Determine which data to display.
            foreach ($goal_users as $user) {
                $user_row = array();

                foreach ($type_table_headers as $field => $header) {
                    $value = '';
                    if (isset($user->{$field})) {
                        $value = $user->{$field};
                        if ($header == 'Email' && validate_email($value) != 0) {
                            $value = html_writer::link("mailto:" . $value, $value);
                        }
                    }

                    // We need to attach attribute in order to make
                    // this table responsive
                    $cell = new html_table_cell($value);
                    if ($header == self::TITLE_FLAG) {
                        $header = $title;
                    }
                    // Put in the header title in a special attribute
                    $cell->attributes['data-content'] = $header;

                    $user_row[$field] = $cell;
                }

                $table->data[] = $user_row;
            }

            // use array_values, to remove array keys, which are
            // mistaken as another css class for given column
            foreach ($type_table_headers as $table_header) {
                if ($table_header == self::TITLE_FLAG) {
                    $table_header = $title;
                }

                $table->head[] = $table_header;
            }

            $instr_info_table .= html_writer::table($table);
        }

        return $instr_info_table;
    }

    /**
     *  Turns a set of combined instructor informations and
     *  detemines potential headers.
     **/
    static function combine_blocks_office_hours($appended_info) {
        $instructors = array();
        // append to $desired_info and delegate values
        foreach ($appended_info as $blockname => $instructor_data) {
            foreach ($instructor_data as $instkey => $instfields) {
                if (!isset($instructors[$instkey])) {
                    $instructors[$instkey] = new stdClass();
                }

                foreach ($instfields as $field => $value) {
                    $fieldname = self::blocks_process_displaykey(
                            $field, $blockname
                        );
                    $stripfield = self::blocks_strip_displaykey_sort($field);

                    if (!isset($desired_info[$fieldname])) {
                        // Hack for titles
                        if ($field == self::TITLE_FLAG) {
                             $infoheader = self::TITLE_FLAG;
                        } else {
                            $infoheader = get_string(
                                $stripfield,
                                'block_' . $blockname
                            );
                        }

                        $desired_info[$fieldname] = $infoheader;
                    }

                    if (empty($instructors[$instkey])) {
                        debugging('got a custom office hours field'
                            . ' for non-existant instructor: ' . $instkey);
                    }

                    $instructors[$instkey]->{
                            self::blocks_strip_displaykey_sort($fieldname)
                        } = $value;
                }
            }
        }

        ksort($desired_info);

        $table_headers = array();

        // Clean up the keys to match
        foreach ($desired_info as $dispkey => $dispval) {
            $stripdispkey = self::blocks_strip_displaykey_sort($dispkey);
            $table_headers[$stripdispkey] = $dispval;
        }

        return array($table_headers, $instructors);
    }

    /**
     *  Gets the blocks to iterate for.
     **/
    static function load_blocks() {
        global $PAGE;

        return $PAGE->blocks->get_installed_blocks();
    }

    /**
     *  Maybe move this somewhere more useful?
     **/
    static function all_blocks_method_results($function, $param,
                                              $filter=array()) {
        $blocks = self::load_blocks();
        $blockresults = array();

        foreach ($blocks as $block) {
            // http://en.wikipedia.org/wiki/Brock_%28Pok%C3%A9mon%29
            $blockname = $block->name;
            $blockres = @block_method_result($blockname, $function, $param);

            if ($blockres) {
                $blockresults[$blockname] = $blockres;
            }
        }

        return $blockresults;
    }

    /**
     *  Polling Hook API.
     *  Calls block::office_hours_append()
     *
     *  Allows blocks to specify arbitrary fields to add onto the display
     *      in section 0 of the course site.
     *  @param Array(
     *      'instructors' => array the instructors that have been selected
     *          to be in the office hours,
     *      'course'  => object the course,
     *      'context' => object the context
     *    )
     *  @return Array(
     *      <key in $instructors> => array(
     *          <field name to be appended> => <value>,
     *          ...
     *      ),
     *      ...
     *  ); -- the field names will be computed over, and any unique
     *      entry with a field name will result in the field name displayed
     *      in the table header, while the other users without a value for
     *      said field will have no value for said field.
     *  NOTE: Use blocks_process_displaykey() to set
     *      <field name to be appended>.
     *  NOTE: You can force sorting by APPENDING a 2-digit integer to the
     *      name of the key.
     *
     **/
    static function blocks_office_hours_append($instructors, $course,
                                               $context) {
        return self::all_blocks_method_results('office_hours_append',
            array(
                'instructors' => $instructors,
                'course' => $course,
                'context' => $context
            ));
    }

    /**
     *  Calculates the field in $instructor that is displayed per
     *  display field in a block. Blocks implementing
     *  office_hours_append() should use this function.
     **/
    static function blocks_process_displaykey($displaykey, $blockname) {
        return $displaykey . '_' . $blockname;
    }

    static function blocks_strip_displaykey_sort($displaykey) {
        $retval = $displaykey;
        if (preg_match(self::DISPLAYKEY_PREG, $displaykey)) {
            $retval = preg_replace(self::DISPLAYKEY_PREG, '$2',
                    $displaykey);
        }

        return $retval;
    }

    /**
     *  Polling hook API.
     *  Calls block::office_hours_filter_instructors()
     *
     *  Allows blocks to specify that a certain instructor should NOT
     *      be displayed in the office hours block of the course site.
     *  @param Array()
     *  @return Array(<key for $instructors>, ...)
     **/
    static function blocks_office_hours_filter_instructors($instructors,
                                                           $course, $context) {
        return self::all_blocks_method_results(
            'office_hours_filter_instructors',
            array(
                'instructors' => $instructors,
                'course' => $course,
                'context' => $context
            ));
    }

    function office_hours_append($params) {
        global $CFG, $OUTPUT, $PAGE, $USER;
        require_once($CFG->dirroot . '/mod/url/locallib.php');

        extract($params);

        $has_capability_edit_office_hours = has_capability(
                'block/ucla_office_hours:editothers', $context);
        $editing = $PAGE->user_is_editing();
        $editing_office_hours = $editing && $has_capability_edit_office_hours;

        // Determine if the user is enrolled in the course or is an admin
        // Assuming 'moodle/course:update' is a sufficient capability to
        // to determine if a user is an admin or not
        $enrolled_or_admin = is_enrolled($context, $USER)
                || has_capability('moodle/course:update', $context);

        $streditsummary     = get_string('update', 'block_ucla_office_hours');
        $link_options = array('title' => get_string('editofficehours',
            'format_ucla'), 'class' => 'editing_instr_info');

        // The number is an informal sorting system.
        // Note the naming schema
        $fullname = '01_fullname';
        $defaultinfo = array(
            $fullname,
            '02_sections',
            '03_email',
            '04_officelocation',
            '05_officehours',
            '06_phone'
        );

        // Add another column for the "Update" link
        if ($editing_office_hours) {
            // This get_string should be blank
            $defaultinfo[] =  '00_update_icon';
        }

        $defaults = array();
        foreach ($defaultinfo as $defaultdata) {
            $defaults[self::blocks_strip_displaykey_sort($defaultdata)] =
                $defaultdata;
        }

        // custom hack for fullname
        $defaults[self::blocks_strip_displaykey_sort($fullname)] =
            self::TITLE_FLAG;

        // calculate invariants, and locally dependent data
        foreach ($instructors as $uk => $user) {
            // Name field
            $fullname = fullname($user);

            // Try be be lenient on URL, because Moodle doesn't enforce adding
            // http://.
            if (!empty($user->url) && !validateUrlSyntax($user->url, 's+')) {
                // See if it failed because is missing the http:// at the beginning.
                if (validateUrlSyntax('http://' . $user->url, 's+')) {
                    // It was.
                    $user->url = 'http://' . $user->url;
                }
            }

            if (!empty($user->url) && url_appears_valid_url($user->url)) {
                $fullname = html_writer::link(
                    new moodle_url($user->url),
                    $fullname,
                    array('target' => '_blank')
                );
            }

            $user->fullname = $fullname;

            // Update button
            if ($editing_office_hours) {
                $user->update_icon = html_writer::tag(
                    'span',
                    $OUTPUT->render(
                        new action_link(
                            new moodle_url(
                                '/blocks/ucla_office_hours/officehours.php',
                                array(
                                    'courseid' => $course->id,
                                    'editid' => $user->id
                                )
                            ),
                            new pix_icon(
                                't/edit',
                                $link_options['title'],
                                'moodle',
                                array(
                                    'class' => 'icon edit iconsmall',
                                    'alt' => $streditsummary
                                )
                            ),
                            null,
                            $link_options
                        )
                    ),
                    array('class' => 'editbutton')
                );
            }

            // Determine if we should display the instructor's email:
            // 2 - Allow only other course members to see my email address
            // 1 - Allow everyone to see my email address
            // 0 - Hide my email address from everyone
            $email_display = $user->maildisplay;
            $display_email = ($email_display == 2 && $enrolled_or_admin) || ($email_display == 1);
            if ($display_email && !empty($user->officeemail)) {
                $user->email = $user->officeemail;
            } else if (!$display_email) {
                unset($user->email);
            }
        }

        $officehoursusers = array();
        foreach ($instructors as $uk => $user) {
            $user->sections = self::get_ta_sections($course->id, $user->id);
            foreach ($user as $field => $value) {
                if (isset($defaults[$field])) {
                    $officehoursusers[$uk][$defaults[$field]] = $value;
                }
            }
        }
        return $officehoursusers;
    }

    /**
     * Returns formatted location/time for given section.
     *
     * @param int $courseid
     * @param int $userid
     * @return string
     */
    static private function get_ta_sections($courseid, $userid) {
        global $DB;
        if ($user = $DB->get_record('ucla_officehours', array('courseid' => $courseid, 'userid' => $userid))) {
            // Return empty string if no sections is stored.
            if (empty($user->encodedsections)) {
                return '';
            }

            // Process data.
            $decodedsections = json_decode($user->encodedsections);
            $sections = '';
            $calendar = array();
            $calendar['M'] = get_string('monday', 'calendar');
            $calendar['T'] = get_string('tuesday', 'calendar');
            $calendar['W'] = get_string('wednesday', 'calendar');
            $calendar['R'] = get_string('thursday', 'calendar');
            $calendar['F'] = get_string('friday', 'calendar');
            foreach ($decodedsections as $srs => $section) {
                $sections .= html_writer::tag('strong',
                        ltrim($section->sect_no, '0') . ': ');

                // If there are more than one section, the add a starting <br>.
                $sectionhours = (array) $section->sect_hr_to_loc;
                if (count($sectionhours) > 1) {
                    $sections .= '<br/>';
                }
                foreach ($sectionhours as $hr => $loc) {
                    // Have special case for varies sections.
                    if ($hr == 'VAR') {
                        $sections .= $loc . '<br/>';
                        continue;
                    }

                    $sections .= $loc;
                    $sections .= ' / ';

                    // Split $hr, because should be in <days>/<hours> format.
                    $dayhours = explode('/', $hr);
                    if (count($dayhours) == 1) {
                        // This is for backwards compability for sites that had
                        // office hours created before CCLE-5415 - Indicate
                        // online sections.
                        $days = substr($dayhours[0], 0, 1);
                        $hours = substr($dayhours[0], 1);
                    } else {
                        $days = $dayhours[0];
                        $hours = $dayhours[1];
                    }

                    // Process sections hours string.
                    $hours = preg_replace('/ ?AM?/', 'am', $hours);
                    $hours = preg_replace('/ ?PM?/', 'pm', $hours);

                    // Process days string.
                    $strlen = strlen($days);
                    $daysarray = array();
                    for ($i=0; $i<$strlen; $i++) {
                        $daysarray[] = $calendar[$days[$i]];
                    }

                    // Combine everything together.
                    $sections .= implode(', ', $daysarray);
                    $sections .= ' / ' . $hours;
                    $sections .= '<br/>';
                }
            }
            return $sections;
        } else {
            // Cannot get data from database.
            return '';
        }
    }

    /**
     * Updating TA sections.
     *
     * @param int $courseid
     * @return void
     */
    static public function update_ta_sections($courseid) {
        global $USER, $DB;
        $termsrses = ucla_map_courseid_to_termsrses($courseid);
        if (!$termsrses) {
            return null;
        }

        $section2ta = array();
        foreach ($termsrses as $termsrs) {
            $section2ta = array_merge($section2ta, \registrar_query::run_registrar_query(
                'ccle_CourseInstructorsGet',
                array(
                    'term' => $termsrs->term,
                    'srs' => $termsrs->srs
                )
            ));
        }

        // Remove non-ta roles from the array.
        if (!empty($section2ta)) {
            foreach ($section2ta as $key => $value) {
                if ($value['role'] !== '02') {
                    unset($section2ta[$key]);
                }
            }
        }

        // If no TAs found, then return.
        if (empty($section2ta)) {
            return null;
        }

        $tasections = array();
        foreach ($termsrses as $termsrs) {
            $tasections = array_merge($tasections, \registrar_query::run_registrar_query(
                'ccle_class_Sections',
                array(
                    'term' => $termsrs->term,
                    'srs' => $termsrs->srs
                )
            ));
        }

        // If no sections found, then see if course is crosslisted.
        if (empty($tasections)) {
            if (count($termsrses) > 1) {
                $termsrs = reset($termsrses);
                $results = get_crosslisted_courses($termsrs->term, $termsrs->srs);
                // Make sure course is not officially cross-listed. We only want
                // to show sections for unofficial crosslists, because then it
                // makes sense to show which section a TA is associated with.
                if (empty($results)) {
                    foreach ($termsrses as $termsrs) {
                        $tasection = array();
                        $result = ucla_get_reg_classinfo($termsrs->term, $termsrs->srs);
                        $tasection['sect_no'] = sprintf('%s %s-%s',
                                $result->subj_area, $result->coursenum, $result->sectnum);
                        $tasection['srs_crs_no'] = $termsrs->srs;
                        $tasections[] = $tasection;
                    }
                }
            }
        }

        $sections = array();
        $classcalendarcache = array();
        foreach ($section2ta as $key => &$value) {
            foreach ($tasections as $k => &$session) {
                if ($value['srs'] == $session['srs_crs_no']) {
                    $value['sect_no'] = isset($session['sect_no']) ? $session['sect_no'] : '';
                }
            }

            if (!isset($value['sect_no'])) {
                continue;
            }

            if (!isset($sections[$value['ucla_id']])) {
                $sections[$value['ucla_id']] = array();
            }
            if (!isset($sections[$value['ucla_id']][$value['srs']])) {
                $newrecord = array();
                $newrecord['sect_no'] = $value['sect_no'];

                if (!isset($classcalendarcache[$value['term']][$value['srs']])) {
                    $tacalendar = \registrar_query::run_registrar_query(
                            'ccle_classCalendar',
                            array(
                                'term' => $value['term'],
                                'srs' => $value['srs']
                            )
                        );
                    $classcalendarcache[$value['term']][$value['srs']] = $tacalendar;
                } else {
                    $tacalendar = $classcalendarcache[$value['term']][$value['srs']];
                }
                $talocationandhour = array();
                foreach ($tacalendar as $k => $v) {
                    if ($v['day_of_wk_cd'] == 'VAR') {
                        // If day of week varies, just list building.
                        $talocationandhour[$v['day_of_wk_cd']] = $v['meet_bldg'];
                    } else if (!isset($talocationandhour[$v['day_of_wk_cd'].'/'.$v['meet_strt_tm'].
                                                    '-'.$v['meet_stop_tm']])) {
                        // If section is online, ignore meet_room.
                        $location = '';
                        if ($v['meet_bldg'] == 'ONLINE') {
                            $location = $v['meet_bldg'];
                        } else {
                            $location = $v['meet_bldg'].' '.$v['meet_room'];
                        }
                        $talocationandhour[$v['day_of_wk_cd'].'/'.$v['meet_strt_tm'].
                                        '-'.$v['meet_stop_tm']] = $location;
                    }
                }
                $newrecord['sect_hr_to_loc'] = $talocationandhour;
                if (!in_array($newrecord, $sections[$value['ucla_id']])) {
                    $sections[$value['ucla_id']][$value['srs']] = $newrecord;
                }
            }
        }

        $idtoidnumber = array();
        foreach ($sections as $key => &$value) {
            if ($userid = $DB->get_field('user', 'id', array('idnumber' => $key))) {
                $idtoidnumber[$userid] = $key;
            }
        }

        foreach ($idtoidnumber as $id => $idnumber) {
            $officehoursentry = $DB->get_record('ucla_officehours',
                array('courseid' => $courseid, 'userid' => $id));

            $newofficehoursentry = new stdClass();
            $newofficehoursentry->userid          = $id;
            $newofficehoursentry->courseid        = $courseid;
            $newofficehoursentry->modifierid      = $USER->id;
            $newofficehoursentry->timemodified    = time();
            $newofficehoursentry->encodedsections = json_encode($sections[$idnumber]);

            if (empty($officehoursentry)) {
                // Need to insert new record.
                $DB->insert_record('ucla_officehours', $newofficehoursentry);
            } else if ($officehoursentry->encodedsections !=
                    $newofficehoursentry->encodedsections) {
                // Use existing record id to update if sections changed.
                $newofficehoursentry->id = $officehoursentry->id;
                $newofficehoursentry->officehours     = $officehoursentry->officehours;
                $newofficehoursentry->officelocation  = $officehoursentry->officelocation;
                $newofficehoursentry->email           = $officehoursentry->email;
                $newofficehoursentry->phone           = $officehoursentry->phone;

                $DB->update_record('ucla_officehours', $newofficehoursentry);
            }
        }

        return null;
    }

    /**
     * Updates office hours and office location.
     *
     * @param object $newofficehoursentry New office information
     * @param int    $courseid            Course ID
     * @param int    $editid              User ID of editing user
     */
    public static function update_office_hours($newofficehoursentry, $courseid, $editid) {
        global $DB;

        $newofficehoursentry->courseid = $courseid;
        $oldofficehoursentry = $DB->get_record('ucla_officehours',
                array('courseid' => $courseid, 'userid' => $editid));

        try {
            if (empty($oldofficehoursentry)) {
                // Need to insert new record.
                $DB->insert_record('ucla_officehours', $newofficehoursentry);
            } else {
                // Update existing record.
                $newofficehoursentry->id = $oldofficehoursentry->id;
                $DB->update_record('ucla_officehours', $newofficehoursentry);
            }
        } catch (dml_exception $e) {
            print_error('cannotinsertrecord');
        }
    }
}