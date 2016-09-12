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
 * Show all posts on one page for a forum.
 *
 * @package    mod_forum
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');
require_once($CFG->dirroot.'/mod/forum/lib.php');
$forumid = required_param('forumid', PARAM_INT);        // Forum id.
$sortparam = optional_param('sort', '', PARAM_TEXT);        // Sort.
$print = optional_param('print', false, PARAM_BOOL);        // Print.
$url = new moodle_url('/mod/forum/allposts.php', array('forumid' => $forumid));
$PAGE->set_url($url);
$forum = $DB->get_record('forum', array('id' => $forumid));
$course = get_course($forum->course);
$cm = get_coursemodule_from_instance('forum', $forumid, $course->id, false, MUST_EXIST);
require_course_login($course, true, $cm);
$modcontext = context_module::instance($cm->id);
$PAGE->set_context($modcontext);
// Need to move after the page set context.
if ($print) {
    $PAGE->set_pagelayout('print');
} else {
    $PAGE->set_pagelayout('standard');
}
// Some capability checks.
if (empty($cm->visible) and !has_capability('moodle/course:viewhiddenactivities', $modcontext)) {
    notice(get_string("activityiscurrentlyhidden"));
}
if (!has_capability('mod/forum:viewdiscussion', $modcontext)) {
    notice(get_string('noviewdiscussionspermission', 'forum'));
}
$sort = 'd.timemodified DESC';
if ($sortparam == 'discussion') {
    $sort = 'd.name';
} else if ($sortparam == 'user') {
    $sort = 'u.lastname, u.firstname';
}
$discussions = forum_get_discussions($cm, $sort, true);
if (!$discussions) {
    // No forum posts to display.
    $pagetitle = get_string('noposts', 'mod_forum');
    $pageheading = get_string('pluginname', 'mod_forum');
    // Display a page letting the user know that there's nothing to display.
    $PAGE->set_title($pagetitle);
    $PAGE->set_heading($pageheading);
    echo $OUTPUT->header();
    echo $OUTPUT->heading($pagetitle);
    if (!$url->compare($PAGE->url)) {
        echo $OUTPUT->continue_button($url);
    }
    echo $OUTPUT->footer();
    die;
}
// START OUTPUT.
$PAGE->set_title("$course->shortname: ".format_string($forum->name));
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();
// START UCLA MOD: CCLE-4329 Handling public forums.
require_once($CFG->dirroot . '/local/publicprivate/lib/module.class.php');
$ppmodule = PublicPrivate_Module::build($cm);
if (!$ppmodule->is_private()) {
    $warningpublicforummsg = get_string('warningpublicforum', 'local_ucla');
    echo $OUTPUT->notification($warningpublicforummsg, 'notifywarning');
}
// END UCLA MOD: CCLE-4329 Handling public forums.
echo $OUTPUT->heading(format_string($forum->name), 2);
if (!$print) {
    // Display 3 different sorting options, discussion title, user, or time.
    // Highlight current sorting option.
    $sortoptions = array();
    if ($sortparam == 'discussion') {
        $sortoptions[] = html_writer::tag('strong',
            get_string('sortbydiscussionname', 'forum'));
    } else {
        $sortoptions[] = html_writer::link(new moodle_url('/mod/forum/allposts.php',
                array('forumid' => $forum->id, 'sort' => 'discussion')),
            get_string('sortbydiscussionname', 'forum'));
    }
    if ($sortparam == 'user') {
        $sortoptions[] = html_writer::tag('strong',
            get_string('sortbyuser', 'forum'));
    } else {
        $sortoptions[] = html_writer::link(new moodle_url('/mod/forum/allposts.php',
                array('forumid' => $forum->id, 'sort' => 'user')),
            get_string('sortbyuser', 'forum'));
    }
    if (empty($sortparam) || ($sortparam != 'discussion' && $sortparam != 'user')) {
        $sortoptions[] = html_writer::tag('strong',
            get_string('sortbytime', 'forum'));
    } else {
        $sortoptions[] = html_writer::link(new moodle_url('/mod/forum/allposts.php',
                array('forumid' => $forum->id)),
            get_string('sortbytime', 'forum'));
    }
    echo html_writer::tag('div', get_string('showallpostsby', 'forum') .
        implode(' / ', $sortoptions), array('class' => 'allposts'));
    echo html_writer::tag('div',
            html_writer::link(new moodle_url('/mod/forum/allposts.php',
                    array('forumid' => $forum->id, 'sort' => $sortparam, 'print' => true)),
            get_string('modeprint', 'forum')),
            array('class' => 'allposts'));
}
// Add export button
echo '<div class="discussioncontrols clearfix">';
if (has_capability('mod/forum:exportdiscussion', $modcontext)) {
    require_once($CFG->libdir.'/portfoliolib.php');
    $button = new portfolio_add_button();
    $button->set_callback_options('forum_portfolio_caller', array('forumid' => $forum->id, 'courseid' => $course->id, 'print' => 2), 'mod_forum');
    $button2 = $button->to_html('PORTFOLIO_ADD_FULL_FORM', get_string('modeexport', 'mod_forum'));
    $buttonextraclass = '';
    if (is_null($button2)) {
        // no portfolio plugin available.
        $button2 = '&nbsp;';
        $buttonextraclass = ' noavailable';
    }
     
    echo html_writer::tag('div', $button2, array('class' => 'discussioncontrol exporttoportfolio'.$buttonextraclass));

} else {
    echo html_writer::tag('div', '&nbsp;', array('class'=>'discussioncontrol nullcontrol'));
}
 
foreach ($discussions as $discussion) {
    $parent = $discussion->id;
    $post = forum_get_post_full($parent);
    // The id is post id, need to have discussion id as attribute id.
    $discussion->id = $discussion->discussion;
    if ($print == 1) {
        forum_print_discussion($course, $cm, $forum, $discussion, $post, FORUM_MODE_PRINT);
    } else if ($print == 2) {// Export.
        forum_print_discussion($course, $cm, $forum, $discussion, $post, FORUM_MODE_EXPORT);
    } else {
        $OUTPUT->box_start();
        forum_print_discussion($course, $cm, $forum, $discussion, $post, $CFG->forum_displaymode);
        $OUTPUT->box_end();
    }
}
echo $OUTPUT->footer();