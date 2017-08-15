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
 * Main course enrolment management UI, this is not compatible with frontpage course.
 *
 * @package    local_ucla
 * @copyright  2010 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("$CFG->dirroot/config.php");
require_once("$CFG->dirroot/enrol/locallib.php");
require_once("$CFG->dirroot/enrol/users_forms.php");
require_once("$CFG->dirroot/group/lib.php");

$id      = required_param('id', PARAM_INT); // Course id.
$action  = optional_param('action', '', PARAM_ALPHANUMEXT);
$filter  = optional_param('ifilter', 0, PARAM_INT);
$search  = optional_param('search', '', PARAM_RAW);
$role    = optional_param('role', 0, PARAM_INT);
$fgroup  = optional_param('filtergroup', 0, PARAM_INT);
// START UCLA MOD: CCLE-4418 - Do not display inactive users by default.
// @codingStandardsIgnoreLine
//$status  = optional_param('status', -1, PARAM_INT);
$status  = optional_param('status', ENROL_USER_ACTIVE, PARAM_INT);
// END UCLA MOD: CCLE-4418.

// When users reset the form, redirect back to first page without other params.
if (optional_param('resetbutton', '', PARAM_RAW) !== '') {
    redirect('users.php?id=' . $id);
}

$firstinitial = optional_param('sifirst', null, PARAM_NOTAGS);
$lastinitial = optional_param('silast', null, PARAM_NOTAGS);

$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
$context = context_course::instance($course->id, MUST_EXIST);

if ($course->id == SITEID) {
    redirect(new moodle_url('/'));
}

require_login($course);
require_capability('moodle/course:viewparticipants', $context);
$PAGE->set_pagelayout('admin');

// Check that user can view inactive status.
if (!has_capability('moodle/course:viewsuspendedusers', $context)) {
    $status = ENROL_USER_ACTIVE;
}

$grouping = optional_param('grouping', $course->defaultgroupingid, PARAM_INT);

$manager = new local_ucla_participants($PAGE, $course, $filter, $role, $search, $fgroup, $status, $grouping);
$table = new local_ucla_course_enrolment_users_table($manager, $PAGE);
$initialparams = array('sifirst' => $firstinitial, 'silast' => $lastinitial);
$PAGE->set_url('/enrol/users.php', $manager->get_url_params() + $table->get_url_params() + $initialparams);
navigation_node::override_active_url(new moodle_url('/enrol/users.php', array('id' => $id)));

// Check if there is an action to take.
if ($action) {

    // Check if the page is confirmed (and sesskey is correct).
    $confirm = optional_param('confirm', false, PARAM_BOOL) && confirm_sesskey();

    $actiontaken = false;
    $pagetitle = '';
    $pageheading = '';
    $mform = null;
    $pagecontent = null;

    switch ($action) {
        /*
         * Removes a role from the user with this course.
         */
        case 'unassign':
            if (has_capability('moodle/role:assign', $manager->get_context())) {
                $role = required_param('roleid', PARAM_INT);
                $user = required_param('user', PARAM_INT);
                if ($confirm && $manager->unassign_role_from_user($user, $role)) {
                    redirect($PAGE->url);
                } else {
                    $user = $DB->get_record('user', array('id' => $user), '*', MUST_EXIST);
                    $allroles = $manager->get_all_roles();
                    $role = $allroles[$role];
                    $yesurl = new moodle_url($PAGE->url, array('action' => 'unassign',
                        'roleid' => $role->id, 'user' => $user->id, 'confirm' => 1, 'sesskey' => sesskey()));
                    $message = get_string('unassignconfirm', 'role',
                            array('user' => fullname($user, true), 'role' => $role->localname));
                    $pagetitle = get_string('unassignarole', 'role', $role->localname);
                    $pagecontent = $OUTPUT->confirm($message, $yesurl, $PAGE->url);
                }
                $actiontaken = true;
            }
            break;
        /*
         * Assigns a new role to a user enrolled within this course.
         * A user must be enrolled in the course in order for this script to action.
         */
        case 'assign':
            $user = $DB->get_record('user', array('id' => required_param('user', PARAM_INT)), '*', MUST_EXIST);
            if (is_enrolled($context, $user) && has_capability('moodle/role:assign', $manager->get_context())) {
                $mform = new enrol_users_assign_form(null, array('user' => $user,
                        'course' => $course, 'assignable' => $manager->get_assignable_roles()));
                $mform->set_data($PAGE->url->params());
                $data = $mform->get_data();
                if ($mform->is_cancelled() || ($data && array_key_exists($data->roleid, $manager->get_assignable_roles()) &&
                        $manager->assign_role_to_user($data->roleid, $user->id))) {
                    redirect($PAGE->url);
                } else {
                    $pagetitle = get_string('assignroles', 'role');
                }
                $actiontaken = true;
            }
            break;
        /*
         * Removes the user from the given group.
         */
        case 'removemember':
            // We want to keep the commented out code.
            // @codingStandardsIgnoreStart
            /*
             * CCLE-2302 - Remove ability to change group information from this
             * screen. Solves issue dealing with public private groups editable
             * from this screen as well as for section groups.
            if (has_capability('moodle/course:managegroups', $manager->get_context())) {
                $groupid = required_param('group', PARAM_INT);
                $userid  = required_param('user', PARAM_INT);
                $user = $DB->get_record('user', array('id'=>$userid), '*', MUST_EXIST);
                if ($confirm && $manager->remove_user_from_group($user, $groupid)) {
                    redirect($PAGE->url);
                } else {
                    $group = $manager->get_group($groupid);
                    if (!$group) {
                        break;
                    }
                    $yesurl = new moodle_url($PAGE->url, array('action' => 'removemember',
                        'group' => $groupid, 'user' => $userid, 'confirm' => 1, 'sesskey' => sesskey()));

                    $message = get_string('removefromgroupconfirm', 'group',
                            array('user'=>fullname($user, true), 'group'=>$group->name));

                    $pagetitle = get_string('removefromgroup', 'group', $group->name);
                    $pagecontent = $OUTPUT->confirm($message, $yesurl, $PAGE->url);
                }
                $actiontaken = true;
            }
            //*/
            // @codingStandardsIgnoreEnd
            break;
        /*
         * Makes the user a member of a given group
         */
        case 'addmember':
            // We want to keep the commented out code.
            // @codingStandardsIgnoreStart
            /*
             * CCLE-2302 - Remove ability to change group information from this
             * screen.
            if (has_capability('moodle/course:managegroups', $manager->get_context())) {
                $userid = required_param('user', PARAM_INT);
                $user = $DB->get_record('user', array('id'=>$userid), '*', MUST_EXIST);

                $mform = new enrol_users_addmember_form(null, array('user'=>$user,
                        'course'=>$course, 'allgroups'=>$manager->get_all_groups()));
             
                $mform->set_data($PAGE->url->params());
                $data = $mform->get_data();
                if ($mform->is_cancelled() || ($data && $manager->add_user_to_group($user, $data->groupid))) {
                    redirect($PAGE->url);
                } else {
                    $pagetitle = get_string('addgroup', 'group');
                }
                $actiontaken = true;
            }
            //*/
            // @codingStandardsIgnoreEnd
            break;
    }

    // If we took an action display we need to display something special.
    if ($actiontaken) {
        if (empty($pageheading)) {
            $pageheading = $pagetitle;
        }
        $PAGE->set_title($pagetitle);
        $PAGE->set_heading($pageheading);
        echo $OUTPUT->header();
        echo $OUTPUT->heading(fullname($user));
        if (!is_null($mform)) {
            $mform->display();
        } else {
            echo $pagecontent;
        }
        echo $OUTPUT->footer();
        exit;
    }
}

$users = $manager->get_users_for_display($manager, $table->sort,
        $table->sortdirection, $table->page, $table->perpage,
        $firstinitial, $lastinitial);
$usercount = $manager->get_usercount();
$table->set_total_users($usercount);

$bulkoperations = $table->has_bulk_operations();
$renderer = $PAGE->get_renderer('core_enrol');
$canassign = has_capability('moodle/role:assign', $manager->get_context());
$canviewlog = has_capability('report/log:view', $context);
$canloginas = has_capability('moodle/user:loginas', $context) && !\core\session\manager::is_loggedinas();
$misclinks = false;
foreach ($users as $userid => &$user) {
    if ($bulkoperations) {
        $user['select'] = html_writer::empty_tag('input', array('type' => 'checkbox',
                                                          'class' => 'usercheckbox',
                                                          'name' => 'user'.$userid,
                                                          'value' => $userid));
    }

    // Miscellaneous links.
    $links = array();
    // Activity link.
    if ($canviewlog) {
        $url = new moodle_url('/report/log/index.php', array(
            'id' => $course->id,
            'user' => $user['userid'],
            'chooselog' => true
            ));
        $links[] = html_writer::link($url, get_string('activity'));
    }
    // Log in as link.
    if ($canloginas && $USER->id != $user['userid'] && !is_siteadmin($user['userid'])) {
        $url = new moodle_url('/course/loginas.php', array(
            'id' => $course->id,
            'user' => $user['userid'],
            'sesskey' => sesskey()
            ));
        $links[] = html_writer::link($url, get_string('loginas'));
    }
    if (!empty($links)) {
        $misclinks = true;
        $user['misc'] = implode(' &middot; ', $links);
    }

    $user['picture'] = $OUTPUT->render($user['picture']);
    $user['role'] = $renderer->user_roles_and_actions($userid, $user['roles'],
            // CCLE-6809 - Expand roles that can be manually enrolled.
            $manager->get_assignable_roles(false, true), $canassign, $PAGE->url);
    $user['group'] = $renderer->user_groups_and_groupings_actions($userid, $user['groups'], $user['groupings'],
            $manager->get_all_groups(), has_capability('moodle/course:managegroups',
                    $manager->get_context()), $PAGE->url);
    $user['enrol'] = $renderer->user_enrolments_and_actions($user['enrolments']);
}

// Determine fields to show in the table.
$userdetails = array (
    'picture' => false,
    'lastname' => get_string('lastname'),
    'firstname' => get_string('firstname')
);
$extrafields = get_extra_user_fields($context);
foreach ($extrafields as $field) {
    $userdetails[$field] = get_user_field_name($field);
}
// Show miscellaneous links if they exist.
if (!empty($misclinks)) {
    $userdetails['misc'] = get_string('miscellaneous');
}

$fields = [];

if ($bulkoperations) {
    $fields['select'] = get_string('select');
}

$fields += array(
    'userdetails' => $userdetails,
    'lastcourseaccess' => get_string('lastcourseaccess'),
);

if (has_capability('moodle/role:assign', $context)) {
    $fields += array(
        'role' => get_string('roles', 'role')
    );
}
if (has_capability('moodle/course:managegroups', $context)) {
    $fields += array(
        'group' => get_string('groupsandgroupings', 'local_ucla')
    );
}
if (has_capability('moodle/course:enrolconfig', $context) or has_capability('moodle/course:enrolreview', $context)) {
    $fields += array(
        'enrol' => get_string('enrolmentinstances', 'enrol')
    );
}

// Remove hidden fields if the user has no access.
if (!has_capability('moodle/course:viewhiddenuserfields', $context)) {
    $hiddenfields = array_flip(explode(',', $CFG->hiddenuserfields));
    if (isset($hiddenfields['lastaccess'])) {
        unset($fields['lastcourseaccess']);
    }
    if (isset($hiddenfields['groups'])) {
        unset($fields['group']);
    }
}

$filterform = new local_ucla_participants_filter_form('users.php', array('manager' => $manager, 'id' => $id),
        'get', '', array('id' => 'filterform'));
$filterform->set_data(array('search' => $search, 'ifilter' => $filter, 'role' => $role,
    'filtergroup' => $fgroup, 'grouping' => $grouping, 'status' => $status, 'sifirst' => $firstinitial, 'silast' => $lastinitial));

$table->set_fields($fields, $renderer);

$table->set_users($users);

$PAGE->set_title($PAGE->course->fullname.': '.get_string('participants')." ($usercount)");
$PAGE->set_heading($PAGE->title);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('participants').get_string('labelsep', 'langconfig').$usercount, 3);

$strall = get_string('all');
$alpha  = explode(',', get_string('alphabet', 'langconfig'));

// Navigation by first/last initial.
$content = html_writer::start_tag('form', array(
    'action' => new moodle_url($PAGE->url),
    ));
$content .= html_writer::start_tag('div');

// Bar of first initials.
$content .= html_writer::start_tag('div', array('class' => 'initialbar firstinitial'));
$content .= html_writer::start_tag('label');
$content .= html_writer::tag('strong', get_string('firstname') . ': ');
$content .= html_writer::end_tag('label');
if (!empty($firstinitial)) {
    $content .= html_writer::link(new moodle_url($PAGE->url, array('sifirst' => false)), $strall);
} else {
    $content .= html_writer::tag('strong', $strall);
}
foreach ($alpha as $letter) {
    if ($letter == $firstinitial) {
        $content .= html_writer::tag('strong', $letter);
    } else {
        $content .= html_writer::link(new moodle_url($PAGE->url, array('page' => 0, 'sifirst' => $letter)), $letter);
    }
}
$content .= html_writer::end_tag('div');

// Bar of last initials.
$content .= html_writer::start_tag('div', array('class' => 'initialbar lastinitial'));
$content .= html_writer::start_tag('label');
$content .= html_writer::tag('strong', get_string('lastname') . ': ');
$content .= html_writer::end_tag('label');

if (!empty($lastinitial)) {
    $content .= html_writer::link(new moodle_url($PAGE->url, array('silast' => false)), $strall);
} else {
    $content .= html_writer::tag('strong', $strall);
}
foreach ($alpha as $letter) {
    if ($letter == $lastinitial) {
        $content .= html_writer::tag('strong', $letter);
    } else {
        $content .= html_writer::link(new moodle_url($PAGE->url, array('page' => 0, 'silast' => $letter)), $letter);
    }
}
$content .= html_writer::end_tag('div');

$content .= html_writer::end_tag('div');
$content .= html_writer::tag('div', '&nbsp;');
$content .= html_writer::end_tag('form');

echo $content;

if (!has_capability('moodle/course:enrolreview', $context) && $usercount < 1) {
    echo $OUTPUT->heading(get_string('nothingtodisplay'));
} else {
    echo $renderer->render_course_enrolment_users_table($table, $filterform);
}

echo $OUTPUT->footer();
die();
