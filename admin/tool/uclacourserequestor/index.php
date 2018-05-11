<?php
// This file is part of the UCLA course creator plugin for Moodle - http://moodle.org/
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
 * The course requestor. This code can use some good refactoring.
 *
 * @package    tool_uclacourserequestor
 * @copyright  2011 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
$uccdirr = '/tool/uclacoursecreator/uclacoursecreator.class.php';
require_once($CFG->dirroot . '/' . $CFG->admin . $uccdirr);
$thisdir = '/' . $CFG->admin . '/tool/uclacourserequestor/';
require_once($CFG->dirroot . $thisdir . 'lib.php');
require_once($CFG->dirroot . '/local/ucla/lib.php');

global $DB, $ME, $USER;

ucla_require_registrar();
require_login();

$syscontext = context_system::instance();
$rucr = 'tool_uclacourserequestor';

// Adding 'Support Admin' capability to course requestor.
if (!has_capability('tool/uclacourserequestor:edit', $syscontext)) {
    print_error('accessdenied', 'admin');
}

// Find whatever term we want to view and pass it to all the forms.
$selectedterm = get_term();

$thisfile = $thisdir . 'index.php';

// Used to determine if course is already been requested.
$existingcourse = null;

// Used to determine if cross-listed course is already been requested.
$existingaliascourse = null;

// Damn, sorry for all the naming inconsistencies.
define('UCLA_CR_SUBMIT', 'submitrequests');

// Initialize $PAGE.
$PAGE->set_url($thisdir . $thisfile);
$PAGE->set_context($syscontext);
$PAGE->set_heading(get_string('pluginname', $rucr));
$PAGE->set_pagetype('admin-uclacourserequestor');
$PAGE->set_pagelayout('admin');
$PAGE->requires->js_init_call('M.tool_uclacourserequestor.init');

// Prepare and load Moodle Admin interface.
admin_externalpage_setup('uclacourserequestor');

$subjareas = registrar_query::run_registrar_query('cis_subjectareagetall',
        array('term' => $selectedterm));

$prefieldsdata = get_requestor_view_fields();

$topforms = array(
    UCLA_REQUESTOR_FETCH => array('srs', 'subjarea'),
    UCLA_REQUESTOR_VIEW => array('view', 'hidden_srs_view')
);

// Merge future and existing terms.
$terms = $prefieldsdata['term'] + get_active_terms();
$terms = terms_arr_sort($terms, true);
if (empty($terms)) {
    $terms[$selectedterm] = $selectedterm;
}

// This will be passed to each form.
$nvcd = array(
    'subjareas' => $subjareas,
    'selterm' => $selectedterm,
    'terms' => $terms,
    'prefields' => $prefieldsdata
);

// We're going to display the forms, but later.
$cachedforms = array();

// This is the courses we want to display.
$requests = null;

// This is to know which form type we came from :(.
$groupid = null;

// This is the data that is to be displayed in the center form.
$uclacrqs = null;

// This is a holder that will maintain the original data
// (for use when clicking 'checkchanges').
$passuclacrqs = null;

// This is the global requestor previous value.
$requestorglobal = '';

// These are the messages that are requestor errors.
$errormessages = array();

// This is the field in the postdata that should represent the state of
// data in the current database for the requestors.
$uf = 'unchangeables';

foreach ($topforms as $gk => $group) {
    foreach ($group as $form) {
        $classname = 'requestor_' . $form . '_form';
        $filename = $CFG->dirroot . $thisdir . $classname . '.php';

        // OK, it appears we need all of them.
        require_once($filename);

        $fl = new $classname(null, $nvcd);

        $cachedforms[$gk][$form] = $fl;

        if ($requests === null && $recieved = $fl->get_data()) {
            $requests = $fl->respond($recieved);
            if (empty($requests)) {
                $errormessages[] = 'norequestsfound';
            }

            $groupid = $gk;

            if ($requests === false) {
                $errormessages[] = 'registrarunavailable';
            } else {
                // Place into our holder.
                $uclacrqs = new ucla_courserequests();
                foreach ($requests as $setid => $set) {
                    // This may get us strangeness.
                    $uclacrqs->add_set($set);
                }
            }
        }
    }
}

// Special catch for our single term-srs viewer.
$getsrs = optional_param('srs', false, PARAM_ALPHANUM);
if ($getsrs && $requests === null) {
    $termsrsform = $cachedforms[UCLA_REQUESTOR_VIEW]['hidden_srs_view'];
    $termsrsobj = new stdClass();
    $termsrsobj->{$termsrsform->groupname} = array(
            'srs' => $getsrs,
            'term' => $selectedterm
        );

    $requests = $termsrsform->respond($termsrsobj);
    $groupid = UCLA_REQUESTOR_VIEW;
    $uclacrqs = new ucla_courserequests();
    foreach ($requests as $request) {
        $uclacrqs->add_set($request);
    }
}

// Special catch for request viewer via paging links.
$viewformparam = optional_param('requestor_view_form', false, PARAM_ALPHANUM);
$termparam = optional_param('term', false, PARAM_TEXT);
$departmentparam = optional_param('department', false, PARAM_TEXT);
$actionparam = optional_param('action', false, PARAM_TEXT);

if ($viewformparam && $termparam && $departmentparam && $actionparam) {
    // If user clicked on a page, we need to setup data.
    $requestorviewform = $cachedforms[UCLA_REQUESTOR_VIEW]['view'];
    $requestviewobj = new stdClass();
    $requestviewobj->{$requestorviewform->groupname} = array(
        'term' => $termparam,
        'department' => $departmentparam,
        'action' => $actionparam,
        'submit' => get_string('viewcourses', 'tool_uclacourserequestor')
    );

    $requests = $requestorviewform->respond($requestviewobj);
    $groupid = UCLA_REQUESTOR_VIEW;
    $uclacrqs = new ucla_courserequests();
    foreach ($requests as $request) {
        $uclacrqs->add_set($request);
    }
} else if ($cachedforms[UCLA_REQUESTOR_VIEW]['view']->is_submitted()) {
    // User clicked on "View/Edit existing requests".
    $viewformdata = $cachedforms[UCLA_REQUESTOR_VIEW]['view']->get_data();
    $termparam = urlencode($viewformdata->requestgroup['term']);
    $departmentparam = urlencode($viewformdata->requestgroup['department']);
    $actionparam = urlencode($viewformdata->requestgroup['action']);
}

// None of the forms took input, so maybe the center form?
// In this situation, we are assuming all information is
// logically correct but properly sanitized.
$saverequeststates = false;

$coursebuilder = new uclacoursecreator();
$forcebuild = false;

$changes = array();
if ($requests === null) {
    $prevs = data_submitted();
    if (!empty($prevs)) {
        if (!empty($prevs->formcontext)) {
            $groupid = $prevs->formcontext;
        }

        // Save all these requests.
        if (!empty($prevs->{UCLA_CR_SUBMIT})) {
            $saverequeststates = true;
        }

        if (!empty($prevs->{'buildcourses'}) &&
                !$coursebuilder->lock_exists()) {
            $forcebuild = true;
        }
        $requests = array();
        $rkeyset = array();
        // Unchangables.
        if (!empty($prevs->{$uf})) {
            $uclacrqs = unserialize(base64_decode($prevs->{$uf}));
            unset($prevs->{$uf});
        } else {
            debugging('no prev data');
        }

        // Overwrite with Changables.
        foreach ($prevs as $key => $prev) {
            $att = request_parse_input($key, $prev);

            if ($att) {
                list($set, $var, $val) = $att;

                if (!empty($changes[$set])) {
                    $changes[$set][$var] = $val;
                } else {
                    $changes[$set] = array($var => $val);
                }
            } else {
                continue;
            }
        }

        // Replace entries without a requestor contact with the value for
        // the global requestor.
        if (!empty($prevs->requestorglobal)) {
            $requestorglobal = $prevs->requestorglobal;
        }

        foreach ($changes as $setid => $changeset) {
            if (empty($changeset['requestoremail'])) {
                $changeset['requestoremail'] = $requestorglobal;
            }

            $changes[$setid] = $changeset;

            // If we are crosslisting a course, make sure the srs is correct.
            if ( isset($changes[$setid]['add-crosslist']) ) {
                $listsrs = $changes[$setid]['crosslists'];
                $newestsrs = count($listsrs) - 1;
                $hc = ($uclacrqs->setindex[$setid]);
                $hcourse = array_pop($hc);

                // This assumes that crosslisting courses requires the
                // crosslisted courses to be in the same term (quarter).
                $crssrs = ucla_courserequests::get_main_srs($hcourse['term'], $listsrs[$newestsrs]);
                $changes[$setid]['crosslists'][$newestsrs] = $crssrs;
            }

        }
    }
}

// Save the requests before applying changes.
if ($uclacrqs !== null) {
    $passuclacrqs = clone($uclacrqs);
}

if (!empty($changes)) {
    $changed = $uclacrqs->apply_changes($changes, $groupid);
}

// These are the options that can be applied globally.
$globaloptions = array();

// These are the messages that reflect positive changes.
$changemessages = array();

$processrequests = isset($uclacrqs) && !$uclacrqs->is_empty();

if ($processrequests) {
    // This is the form data before the save.
    $requestswitherrors = $uclacrqs->validate_requests($groupid);
    if ($saverequeststates) {
        $successfuls = $uclacrqs->commit();

        // Figure out changes that have occurred.
        foreach ($requestswitherrors as $setid => $set) {
            if (isset($successfuls[$setid])) {
                $retcode = $successfuls[$setid];
                $strid = ucla_courserequests::commit_flag_string($retcode);

                $hostcourse = array();
                $coursedescs = array();
                foreach ($set as $course) {
                    $coursedescs[] = requestor_dept_course($course);
                    if ($course['hostcourse']) {
                        $hostcourse = $course;
                    }
                }

                $coursedescstr = implode(' + ', $coursedescs);
                $retmess = get_string($strid, $rucr, $coursedescstr);

                // We care only for updates.
                if ($retcode == ucla_courserequests::SAVESUCCESS) {
                    if (!empty($changed[$setid])) {
                        $fieldstr = '';
                        $fieldstrs = array();

                        // Kludge to handle crosslisting changes.
                        $thechanges = $changed[$setid];
                        if (!empty($thechanges['crosslists'])) {
                            $cldelta = $thechanges['crosslists'];
                            unset($thechanges['crosslists']);

                            foreach ($cldelta as $action => $cls) {
                                $actionstr = get_string(
                                    'clchange_' . $action, $rucr);
                                foreach ($cls as $cl) {
                                    $fieldstrs[] = $actionstr
                                    . make_idnumber($cl) . ' '
                                    . requestor_dept_course($cl);

                                    // Update (remove) MyUCLA urls.
                                    if ($action == 'removed' && !empty($hostcourse)) {

                                        $idtermsrs = array(
                                            make_idnumber($hostcourse) => array(
                                                'term' => $hostcourse['term'],
                                                'srs' => $hostcourse['srs'])
                                            );

                                        $urlupdater = new myucla_urlupdater();
                                        $results = $urlupdater->send_MyUCLA_urls($idtermsrs, false);
                                        $classurl = array_pop($results);

                                        // Check host url to make sure it is on
                                        // the same server.
                                        if (strpos($classurl, $CFG->wwwroot) !== false) {
                                            update_myucla_urls($cl['term'], $cl['srs'], '');
                                        }
                                    }
                                }
                            }
                        }

                        foreach ($thechanges as $field => $val) {
                            $fieldstrs[] = get_string($field, $rucr)
                                . get_string('changedto', $rucr)
                                . $val;
                        }

                        $fieldstr = implode(', ', $fieldstrs);

                        $changemessages[$setid] = "$retmess -- $fieldstr";
                    }
                } else if ($retcode == ucla_courserequests::INSERTSUCCESS) {
                    if ( isset($changed[$setid]['crosslists']['added']) ) {
                        // Adding new crosslist courses
                        // If we need to get the course from the registrar.
                        foreach ($changed[$setid]['crosslists']['added'] as $crosscourse) {
                            $cterm = $crosscourse['term'];
                            $csrs = ucla_courserequests::get_main_srs($cterm, $crosscourse['srs']);

                            if ($crosscourse['hostcourse'] == 0 && $crosscourse['action'] == 'build') {
                                crosslist_course_from_registrar($cterm, $csrs);
                            }

                            // Update MyUCLA urls for the newly updated crosslist.
                            if (!empty($hostcourse)) {
                                $hostcourseid = $hostcourse['courseid'];
                                if ($hostcourse['nourlupdate'] == 0 && !empty($hostcourseid)) {
                                    if (get_config('local_ucla', 'friendly_urls_enabled')) {
                                        $shortname = $DB->get_field('course', 'shortname', array('id' => $hostcourseid));
                                        $friendlyhostcourseurl = '/course/view/' . rawurlencode($shortname);
                                        $curl = $CFG->wwwroot . "$friendlyhostcourseurl";
                                    } else {
                                        $curl = $CFG->wwwroot . "/course/view.php?id=$hostcourseid";
                                    }
                                    update_myucla_urls($cterm, $csrs, $curl);
                                }
                            }
                        }
                    } else {
                        // Adding in new courses
                        // update MyUCLA urls.
                        foreach ($set as $newcourse) {
                            if ($newcourse['nourlupdate'] == 0 && !empty($newcourse['courseid'])) {
                                $newurl = $CFG->wwwroot . '/course/view.php?id=' . $newcourse['courseid'];
                                update_myucla_urls($newcourse['term'], $newcourse['srs'], $newurl);
                            }
                        }
                    }
                    $changemessages[$setid] = $retmess;
                } else {
                    $changemessages[$setid] = $retmess;
                }
            }
        }

        $requestswitherrors = $uclacrqs->validate_requests($groupid);
        $requeststodisplay = array();
        foreach ($requestswitherrors as $setid => $set) {
            if (!isset($successfuls[$setid])) {
                $requeststodisplay[$setid] = $set;
            }
        }

        if (empty($changed) && empty($requeststodisplay)) {
            $changemessages[] = get_string('nochanges', $rucr);
        }

        // Apply to version that best represents the database.
        $passuclacrqs = clone($uclacrqs);

        // Reloading the 3rd form.
        $nvcd['prefields'] = get_requestor_view_fields();
        $cachedforms[UCLA_REQUESTOR_VIEW]['view']
            = new requestor_view_form(null, $nvcd);
    }

    // If nobody has determined which requests to display, then disply
    // all of them.
    if (!isset($requeststodisplay)) {
        $requeststodisplay = $requestswitherrors;
    }

    // Check to see if we need global options.
    if (!empty($requestswitherrors)) {
        $allcoursesbuilt = true;
        $anycoursehastype = false;
        foreach ($requestswitherrors as $setid => $set) {
            foreach ($set as $course) {
                // Check if all courses have been built.
                if ($course['action'] == UCLA_COURSE_TOBUILD
                    || $course['action'] == UCLA_COURSE_FAILED) {
                    $allcoursesbuilt = false;
                    // Check if there are any courses that have a type associated with them.
                    if (!empty($course['type'])) {
                        $anycoursehastype = true;
                        break;
                    }
                }
            }
        }

        if (!$allcoursesbuilt) {
            // Add options for global email to contact.
            $requestorlabel = html_writer::tag('label', get_string(
                'requestorglobal', $rucr), array(
                    'for' => 'requestorglobal'
                ));

            $requestorinput = html_writer::tag('input', '', array(
                    'type' => 'email',
                    'value' => $requestorglobal,
                    'name' => 'requestorglobal',
                    'class' => 'form-control'
                ));

            $requestor = array($requestorlabel, $requestorinput);
            $globaloptions[] = $requestor;

            // We can only provide course filters if there is a type.
            if ($anycoursehastype) {
                // Add option to email instructors.
                $emailinstructorlabel = html_writer::tag('label', get_string(
                    'mailinsttoggle', $rucr), array(
                        'for' => 'emailinstructor',
                        'class' => 'form-check-label'
                    ));

                // Create array for attributes of checkbox element.
                $emailinstructorcheckboxattributes = array(
                        'type' => 'checkbox',
                        'value' => 'mailinst',
                        'id' => 'emailinstructor',
                        'class' => 'form-check-input check-all check-all-instructors',
                );

                // Use default checked value from config file.
                if (get_config('tool_uclacourserequestor', 'mailinst_default')) {
                    $emailinstructorcheckboxattributes['checked'] = 'checked';
                }

                // Write the HTML for checkbox element.
                $emailinstructorinput = html_writer::tag('input', '', $emailinstructorcheckboxattributes);

                $emailinstructor = array($emailinstructorlabel, $emailinstructorinput);
                $globaloptions[] = $emailinstructor;

                // Add build filter options.
                $filterslabel = html_writer::tag('label', get_string(
                    'buildfilters', $rucr));
                $filterstoggles = html_writer::tag('span',
                        html_writer::checkbox('', 'ugrad', true, 'ugrad', array('class' => 'check-all')),
                        array('class' => 'label ugrad'));
                $filterstoggles .= html_writer::tag('span',
                        html_writer::checkbox('', 'grad', true, 'grad', array('class' => 'check-all')),
                        array('class' => 'label grad'));
                $filterstoggles .= html_writer::tag('span',
                        html_writer::checkbox('', 'tut', true, 'tut', array('class' => 'check-all')),
                        array('class' => 'label tut'));
                $filters = array($filterslabel, $filterstoggles);
                $globaloptions[] = $filters;
            }
        }
    }

    // User wants to build courses now.
    if ($forcebuild == true) {
        $termlist = array();
        foreach ($requestswitherrors as $course) {
            foreach ($course as $value) {
                if ($value['action'] == UCLA_COURSE_TOBUILD) {
                    $termlist[] = $value['term'];
                }
            }
        }

        $termlist = array_unique($termlist);
        // Creating the instance of the trigger.
        $builder = new \tool_uclacoursecreator\task\build_courses_now();
        $builder->set_custom_data($termlist);
        \core\task\manager::queue_adhoc_task($builder);
    }

    $tabledata = prepare_requests_for_display($requeststodisplay, $groupid);
    $rowclasses = array();
    foreach ($tabledata as $key => $data) {
        if (!empty($data['errclass'])) {
            $rowclasses[$key] = $data['errclass'];

            // We do not need to display this in the table.
            unset($tabledata[$key]['errclass']);
        }
    }

    // Get the error values as a set.
    $errormessages = array_keys(array_flip($rowclasses));

    // Add class type to row for styling.
    foreach ($tabledata as $key => $data) {
        // Add class type to row for styling.
        if (!isset($data['type'])) {
            // Requests that are already built do not have a type.
            continue;
        }

        if (!isset($rowclasses[$key]) ||
                !in_array($rowclasses[$key], array('warning', 'error'))) {
            // If row has an error, it will already have style set.
            $rowclasses[$key] = $data['type'];
        }
        unset($tabledata[$key]['type']);
    }

    // Get the headers to display strings.
    $possfields = array();
    $requestfields = reset($tabledata);
    if (is_array($requestfields)) {
        foreach ($requestfields as $f => $v) {
            $possfields[$f] = get_string($f, $rucr);
        }
    }

    $requeststable = new html_table();
    $requeststable->id = 'uclacourserequestor_requests';
    $requeststable->head = $possfields;
    $requeststable->data = $tabledata;

    // For errors and class types.
    $requeststable->rowclasses = $rowclasses;
}

$registrarlink = new moodle_url(
        get_config('local_ucla', 'registrarurl')) . '/ro/public/soc';

// Start rendering.
echo $OUTPUT->header();
echo html_writer::start_tag('div', array('id' => $rucr));
echo $OUTPUT->heading(get_string('pluginname', $rucr), 2, 'headingblock');

// Generate build schedule/notice (if any).
$buildnotes = get_config($rucr, 'build_notes');
if ($coursebuilder->lock_exists()) { // If course build is in progress, let user know.
    if (!empty($buildnotes)) {
        $buildnotes .= html_writer::empty_tag('br');
    }
    $buildnotes .= get_string('alreadybuild', $rucr);
} else if (course_build_queued()) {
    if (!empty($buildnotes)) {
        $buildnotes .= html_writer::empty_tag('br');
    }
    $buildnotes .= get_string('queuebuild', $rucr);
}
if (!empty($buildnotes)) {
    $buildnotice = html_writer::tag('div', $buildnotes,
            array('id' => 'uclacourserequestor_notice'));
    echo $OUTPUT->notification($buildnotice, 'notifymessage');
}

foreach ($cachedforms as $gn => $group) {
    echo $OUTPUT->box_start('generalbox');
    echo $OUTPUT->heading(get_string($gn, $rucr), 3);

    foreach ($group as $form) {
         $form->display();
    }

    if ('fetch' == $gn) {
        echo html_writer::link(
            $registrarlink,
            get_string('srslookup', $rucr),
            array('target' => '_blank')
        );
    }
    echo $OUTPUT->box_end();
}

// Display notice to user regarding their requests.
if (!empty($changemessages)) {
    $messagestr = implode(html_writer::empty_tag('br'), $changemessages);

    if (!empty($messagestr)) {
        echo $OUTPUT->notification($messagestr, 'notifymessage');
    }
}

// Display error to user regarding their requests.
if (!empty($errormessages)) {
    $sm = get_string_manager();
    $errorlist = array();
    foreach ($errormessages as $message) {
        if (!empty($message)) {
            $contextspecificm = $message . '-' . $groupid;

            if ($sm->string_exists($contextspecificm, $rucr)) {
                $viewstr = $contextspecificm;
            } else {
                $viewstr = $message;
            }

            $errorlist[] = get_string($viewstr, $rucr);
        }
    }

    echo $OUTPUT->notification(html_writer::alist($errorlist), 'notifyproblem');
}

if (!empty($requeststable->data)) {
    echo html_writer::start_tag('form', array(
        'method' => 'POST',
        'action' => $PAGE->url
    ));

    if (!empty($globaloptions)) {
        $globaloptionstable = new html_table();
        $globaloptionstable->id = 'ucrgeneraloptions';
        $globaloptionstable->head = array(get_string('optionsforall', $rucr));
        $globaloptionstable->headspan = array(2);
        $globaloptionstable->data = $globaloptions;
        echo html_writer::table($globaloptionstable);
    }

    echo html_writer::tag('input', '', array(
            'type' => 'hidden',
            'value' => $groupid,
            'name' => 'formcontext'
        ));

    echo html_writer::tag('input', '', array(
            'type' => 'hidden',
            'value' => base64_encode(serialize($passuclacrqs)),
            'name' => $uf
        ));

    // Only display build now button any "View existing requests" are set  to
    // "to be built".
    $showbutton = false;
    if ('views' == $groupid) {
        foreach ($requestswitherrors as $course) {
            foreach ($course as $value) {
                if (isset($value['action']) && $value['action'] == "build") {
                    $showbutton = true;
                    break;
                }
            }
        }
    }

    // Only display built now button for non-prod environments.
    $configprod = get_config('theme_uclashared', 'running_environment');
    if ($configprod != 'prod' && $showbutton) {
        if (!$coursebuilder->lock_exists() && !course_build_queued()) {
            echo html_writer::tag('input', '', array(
                'type' => 'submit',
                'name' => 'buildcourses',
                'value' => get_string('buildcoursenow', $rucr),
                'class' => 'right',
                'id' => 'buildcourses'
            ));
        } else {
            $buttonstatus = '';
            if ($coursebuilder->lock_exists()) {
                $buttonstatus = get_string('alreadybuild', $rucr);
            } else if (course_build_queued()) {
                $buttonstatus = get_string('queuebuild', $rucr);
            }

            // If course build is happening/queued, disable button.
            echo html_writer::tag('input', '', array(
                'type' => 'submit',
                'name' => 'buildcourses',
                'value' => $buttonstatus,
                'class' => 'right',
                'disabled' => true
            ));
        }
        echo html_writer::empty_tag('br');
    }

    // Only display paging bar for requested courses.
    if ($viewformparam || $cachedforms[UCLA_REQUESTOR_VIEW]['view']->is_submitted()) {
        $baseurl = new moodle_url('/admin/tool/uclacourserequestor/index.php',
                array('requestor_view_form' => '1', 'term' => $termparam,
                      'department' => $departmentparam, 'action' => $actionparam));
        $coursesperpage = $cachedforms[UCLA_REQUESTOR_VIEW]['view']->coursesperpage;
        $page = $cachedforms[UCLA_REQUESTOR_VIEW]['view']->page;
        echo $OUTPUT->paging_bar($cachedforms[UCLA_REQUESTOR_VIEW]['view']->totalcourses,
                $page, $coursesperpage, $baseurl);
    }
    echo html_writer::table($requeststable);

    echo html_writer::tag('input', '', array(
            'type' => 'submit',
            'name' => UCLA_CR_SUBMIT,
            'id' => UCLA_CR_SUBMIT,
            'value' => get_string('submit' . $groupid, $rucr),
            'class' => 'right'
        ));

    echo html_writer::end_tag('form');
}

echo html_writer::end_tag('div');
echo $OUTPUT->footer();

// Script functions.

/**
 * Looks in the event tables and checks if a request to build courses now has
 * been submitted.
 *
 * @return boolean
 */
function course_build_queued() {
    global $DB;
    return $DB->record_exists('task_adhoc', array('classname' => '\tool_uclacoursecreator\task\build_courses_now'));
}

/**
 * Returns the selected term.
 *
 * First looks for the term in the url. Then checks if term was passed as part
 * of a request.
 *
 * @return string
 */
function get_term() {
    global $CFG;

    $selectedterm = optional_param('term', false, PARAM_ALPHANUM);
    if (empty($selectedterm)) {
        // If not passed via GET parameter, maybe via POST as requestgroup.
        $requestgroup = optional_param_array('requestgroup', array(), PARAM_TEXT);
        if (!empty($requestgroup) && isset($requestgroup['term'])) {
            $selectedterm = $requestgroup['term'];
        }
    }

    // If still empty, default to current term.
    if (empty($selectedterm)) {
        $selectedterm = $CFG->currentterm;
    }

    return $selectedterm;
}