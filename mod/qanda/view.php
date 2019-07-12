<?php

/// This page prints a particular instance of qanda
require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once($CFG->libdir . '/completionlib.php');
require_once("$CFG->libdir/rsslib.php");

$id = optional_param('id', 0, PARAM_INT);           // Course Module ID
$g = optional_param('g', 0, PARAM_INT);            // qanda ID

$tab = optional_param('tab', QANDA_NO_VIEW, PARAM_ALPHA);    // browsing entries by categories?
$displayformat = optional_param('displayformat', -1, PARAM_INT);  // override of the qanda display format

$mode = optional_param('mode', 'date', PARAM_ALPHA);           // term entry cat date letter search author approval
$hook = optional_param('hook', '', PARAM_CLEAN);           // the term, entry, cat, etc... to look for based on mode
$fullsearch = optional_param('fullsearch', 0, PARAM_INT);         // full search (question and answer) when searching?
$sortkey = optional_param('sortkey', 'UPDATE', PARAM_ALPHA); // Sorted view: CREATION | UPDATE | FIRSTNAME | LASTNAME...
$sortorder = optional_param('sortorder', 'DESC', PARAM_ALPHA);   // it defines the order of the sorting (ASC or DESC)
$offset = optional_param('offset', 0, PARAM_INT);             // entries to bypass (for paging purposes)
$page = optional_param('page', 0, PARAM_INT);               // Page to show (for paging purposes)
$show = optional_param('show', '', PARAM_ALPHA);           // [ question | alias ] => mode=term hook=$show

if (!empty($id)) {
    if (!$cm = get_coursemodule_from_id('qanda', $id)) {
        print_error('invalidcoursemodule');
    }
    if (!$course = $DB->get_record("course", array("id" => $cm->course))) {
        print_error('coursemisconf');
    }
    if (!$qanda = $DB->get_record("qanda", array("id" => $cm->instance))) {
        print_error('invalidid', 'qanda');
    }
} else if (!empty($g)) {
    if (!$qanda = $DB->get_record("qanda", array("id" => $g))) {
        print_error('invalidid', 'qanda');
    }
    if (!$course = $DB->get_record("course", array("id" => $qanda->course))) {
        print_error('invalidcourseid');
    }
    if (!$cm = get_coursemodule_from_instance("qanda", $qanda->id, $course->id)) {
        print_error('invalidcoursemodule');
    }
    $id = $cm->id;
} else {
    print_error('invalidid', 'qanda');
}

require_course_login($course->id, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/qanda:view', $context);

// Prepare format_string/text options
$fmtoptions = array(
    'context' => $context);

require_once($CFG->dirroot . '/comment/lib.php');
comment::init();

/// redirecting if adding a new entry
if ($tab == QANDA_ADDENTRY_VIEW) {
    $url = new moodle_url('edit.php', array('cmid' => $cm->id, 'mode' => $mode));
    redirect($url);
    
}

/// setting the defaut number of entries per page if not set
if (!$entriesbypage = $qanda->entbypage) {
    $entriesbypage = $CFG->qanda_entbypage;
}

/// If we have received a page, recalculate offset
if ($page != 0 && $offset == 0) {
    $offset = $page * $entriesbypage;
}

/// setting the default values for the display mode of the current qanda
/// only if the qanda is viewed by the first time
if ($dp = $DB->get_record('qanda_formats', array('name' => $qanda->displayformat))) {
/// Based on format->defaultmode, we build the defaulttab to be showed sometimes
    switch ($dp->defaultmode) {
        case 'date':
            $defaulttab = QANDA_DATE_VIEW;
            break;

        default:
            $defaulttab = QANDA_DATE_VIEW;
    }
/// Fetch the rest of variables
    $printpivot = $dp->showgroup;
    if ($mode == '' and $hook == '' and $show == '') {
        $mode = $dp->defaultmode;
        $hook = $dp->defaulthook;
        $sortkey = $dp->sortkey;
        $sortorder = $dp->sortorder;
    }
} else {
    $defaulttab = QANDA_DATE_VIEW;
    $printpivot = 1;
    if ($mode == '' and $hook == '' and $show == '') {
        $mode = 'date';
        $hook = 'ALL';
    }
}

if ($displayformat == -1) {
    $displayformat = $qanda->displayformat;
}

if ($show) {
    $mode = 'term';
    $hook = $show;
    $show = '';
}
/// Processing standard security processes
if ($course->id != SITEID) {
    require_login($course);
}
if (!$cm->visible and !has_capability('moodle/course:viewhiddenactivities', $context)) {
    echo $OUTPUT->header();
    notice(get_string("activityiscurrentlyhidden"));
}
$event = \mod_qanda\event\qanda_viewed::create(array(
    'context'  => $context,
    'objectid' => $qanda->id,
    'other'    => array(
        'tab' => $tab
    )
));
$event->trigger();

// Mark as viewed
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

/// stablishing flag variables
if ($sortorder = strtolower($sortorder)) {
    if ($sortorder != 'asc' and $sortorder != 'desc') {
        $sortorder = '';
    }
}
if ($sortkey = strtoupper($sortkey)) {
    if ($sortkey != 'CREATION' and
            $sortkey != 'UPDATE' and
            $sortkey != 'FIRSTNAME' and
            $sortkey != 'LASTNAME'
    ) {
        $sortkey = '';
    }
}

switch ($mode = strtolower($mode)) {
    case 'search': /// looking for terms containing certain word(s)
        $tab = QANDA_DATE_VIEW; //QANDA_STANDARD_VIEW;
        //Clean a bit the search string
        $hook = trim(strip_tags($hook));

        break;

    case 'entry':  /// Looking for a certain entry id
        $tab = QANDA_DATE_VIEW; //QANDA_STANDARD_VIEW;
        if ($dp = $DB->get_record("qanda_formats", array("name" => $qanda->displayformat))) {
            $displayformat = $dp->popupformatname;
        }
        break;
    case 'approval':    /// Looking for entries waiting for approval
        $tab = QANDA_APPROVAL_VIEW;
        // Override the display format with the approvaldisplayformat
        if ($qanda->approvaldisplayformat !== 'default' && ($df = $DB->get_record("qanda_formats", array("name" => $qanda->approvaldisplayformat)))) {
            $displayformat = $df->popupformatname;
        }
        if (!$hook and !$sortkey and !$sortorder) {
            $hook = 'ALL';
        }
        break;
    case 'date':
        $tab = QANDA_DATE_VIEW;
        if (!$sortkey) {
            $sortkey = 'UPDATE';
        }
        if (!$sortorder) {
            $sortorder = 'desc';
        }
        break;
    default:
        $tab = QANDA_DATE_VIEW; //QANDA_STANDARD_VIEW;
        if (!$hook) {
            $hook = 'ALL';
        }
        break;
}

switch ($tab) {
    case QANDA_IMPORT_VIEW:
    case QANDA_EXPORT_VIEW:
    case QANDA_APPROVAL_VIEW:
        $showcommonelements = 0;
        break;

    default:
        $showcommonelements = 1;
        break;
}

/// Printing the heading
$strqandas = get_string("modulenameplural", "qanda");
$strqanda = get_string("modulename", "qanda");
$strallcategories = get_string("allcategories", "qanda");
$straddentry = get_string("addentry", "qanda");
$strnoentries = get_string("noentries", "qanda");
$strsearchinanswer = get_string("searchinanswer", "qanda");
$strsearch = get_string("search");
$strwaitingapproval = get_string('waitingapproval', 'qanda');

/// If we are in approval mode, prit special header
$PAGE->set_title(format_string($qanda->name));
$PAGE->set_heading($course->fullname);
$url = new moodle_url('/mod/qanda/view.php', array('id' => $cm->id));
if (isset($mode)) {
    $url->param('mode', $mode);
}
$PAGE->set_url($url);

if (!empty($CFG->enablerssfeeds) && !empty($CFG->qanda_enablerssfeeds)
        && $qanda->rsstype && $qanda->rssarticles) {

    $rsstitle = format_string($course->shortname, true, array('context' => context_course::instance($course->id))) . ': %fullname%';
    rss_add_http_header($context, 'mod_qanda', $qanda, $rsstitle);
}

if ($tab == QANDA_APPROVAL_VIEW) {
    require_capability('mod/qanda:answer', $context);
    $PAGE->navbar->add($strwaitingapproval);
    echo $OUTPUT->header();
    echo $OUTPUT->heading($strwaitingapproval);
} else { /// Print standard header
    echo $OUTPUT->header();
}

/// Info box
if ($qanda->intro && $showcommonelements) {
    echo '<div class="titleBox">';
    echo $OUTPUT->box('Q&A', 'generalbox', 'qanda-title');
    echo $OUTPUT->box(format_module_intro('qanda', $qanda, $cm->id), 'generalbox', 'intro');
    echo '</div>';
}

/// All this depends if whe have $showcommonelements
if ($showcommonelements) {
    echo html_writer::start_div('add-and-search-box');

/// To calculate available options
    $availableoptions = '';

/// Show the add entry button if allowed

    if (has_capability('mod/qanda:write', $context) && $showcommonelements) {
        $url = new moodle_url('edit.php', array('cmid' => $cm->id, 'mode' => 'approval'));
        $text = get_string('addentry', 'qanda');
        $availableoptions.= html_writer::link($url, $text, array('class' => 'btn btn-primary', 'title' => $text));
    }

/// Decide about to print the approval link
    if (has_capability('mod/qanda:answer', $context)) {
        /// Check we have pending entries
        if ($hiddenentries = $DB->count_records('qanda_entries', array('qandaid' => $qanda->id, 'approved' => 0))) {
            $url = new moodle_url('view.php', array('id' => $cm->id, 'mode' => 'approval'));
            $text = get_string('waitingapproval', 'qanda') . ' (' . $hiddenentries . ')';
            $availableoptions.= html_writer::link($url, $text, array('id' => 'approval-link', 'class' => 'btn qanda-approve btn-default', 'title' => $text));
        }
    }

/// Start to print qanda controls
    echo html_writer::start_div('qanda-control', array('style' => 'text-align: right'));
    echo $availableoptions;

/// The print icon
    if ($showcommonelements and $mode != 'search') {
        if (has_capability('mod/qanda:manageentries', $context) or $qanda->allowprintview) {
            $url = new moodle_url('print.php', array('id' => $cm->id,
                'mode' => $mode,
                'hook' => $hook,
                'sortkey' => $sortkey,
                'sortorder' => $sortorder,
                'offset' => $offset,
            ));
            echo html_writer::start_span('wrap printicon');
            echo html_writer::link($url, get_string('printerfriendly', 'qanda'), array('id' => 'printer-icon', 'class' => 'icon'));
            echo html_writer::end_span();
        }
    }
/// End qanda controls
    echo html_writer::end_div();
}

/// Search box
if ($showcommonelements) {
    echo html_writer::start_div('search-box');
    echo html_writer::start_tag('form', array('method' => 'post', 'action' => 'view.php'));
    echo html_writer::start_div('ucla-search search-wrapper qanda-search');
    echo html_writer::start_tag('button',
        array('type' => 'submit', 'class' => 'fa fa-search btn', 'name' => 'searchbutton'));
    echo html_writer::end_tag('button');
    if ($mode == 'search') {
        echo html_writer::empty_tag('input',
            array('type' => 'text', 'class' => 'form-control ucla-search-input rounded', 'name' => 'hook',
                'placeholder' => get_string('searchterms', 'qanda'), 'value' => $hook, 'alt' => $strsearch));
    } else {
        echo html_writer::empty_tag('input',
            array('type' => 'text', 'class' => 'form-control ucla-search-input rounded', 'name' => 'hook',
                'placeholder' => get_string('searchterms', 'qanda'), 'alt' => $strsearch));
    }
    if ($fullsearch || $mode != 'search') {
        $fullsearchchecked = 'checked="checked"';
    } else {
        $fullsearchchecked = '';
    }
    echo '<input type="checkbox" name="fullsearch" id="fullsearch" value="1" ' . $fullsearchchecked . ' />';
    echo '<input type="hidden" name="mode" value="search" />';
    echo '<input type="hidden" name="id" value="' . $cm->id . '" />';
    echo '<label for="fullsearch">' . $strsearchinanswer . '</label>';
    echo html_writer::end_div();
    echo html_writer::end_tag('form');
    echo html_writer::end_div();
    echo '<br />';
}

echo html_writer::end_div();

require("tabs.php");
require("sql.php");

/// printing the entries
$entriesshown = 0;
$currentpivot = '';
$paging = NULL;

if ($allentries) {

    //Decide if we must show the ALL link in the pagebar
    $specialtext = '';
    if ($qanda->showall) {
        $specialtext = get_string("allentries", "qanda");
    }
    if ($page < 0) {
        //Avoid negative Q&A pair values when listing ALL entries.
        $offset = 0;
    }

    //Build paging bar
    $paging = qanda_get_paging_bar($count, $page, $entriesbypage, "view.php?id=$id&amp;mode=$mode&amp;hook=" . urlencode($hook) . "&amp;sortkey=$sortkey&amp;sortorder=$sortorder&amp;fullsearch=$fullsearch&amp;", 9999, 10, '&nbsp;&nbsp;', $specialtext, -1);

    echo '<div class="paging">';
    echo $paging;
    echo '</div>';

    foreach ($allentries as $entry) {

        // Setting the pivot for the current entry
        $pivot = $entry->qandapivot;
        $upperpivot = core_text::strtoupper($pivot);
        $pivottoshow = core_text::strtoupper(format_string($pivot, true, $fmtoptions));
        // Reduce pivot to 1cc if necessary
        if (!$fullpivot) {
            $upperpivot = core_text::substr($upperpivot, 0, 1);
            $pivottoshow = core_text::substr($pivottoshow, 0, 1);
        }

        // if there's a group break
        if ($currentpivot != $upperpivot) {

            // print the group break if apply
            if ($printpivot) {
                $currentpivot = $upperpivot;

                echo '<div>';
                echo '<table cellspacing="0" class="qanda-category-header">';

                echo '<tr>';
                if (isset($entry->userispivot)) {
                    // printing the user icon if defined (only when browsing authors)
                    echo '<th align="left">';

                    $user = $DB->get_record("user", array("id" => $entry->userid));
                    echo $OUTPUT->user_picture($user, array('courseid' => $course->id));
                    $pivottoshow = fullname($user, has_capability('moodle/site:viewfullnames', context_course::instance($course->id)));
                } else {
                    echo '<th >';
                }

                echo $OUTPUT->heading($pivottoshow);
                echo "</th></tr></table></div>\n";
            }
        }

        /// highlight the term if necessary
        if ($mode == 'search') {
            //We have to strip any word starting by + and take out words starting by -
            //to make highlight works properly
            $searchterms = explode(' ', $hook);    // Search for words independently
            foreach ($searchterms as $key => $searchterm) {
                if (preg_match('/^\-/', $searchterm)) {
                    unset($searchterms[$key]);
                } else {
                    $searchterms[$key] = preg_replace('/^\+/', '', $searchterm);
                }
                //Avoid highlight of <2 len strings. It's a well known hilight limitation.
                if (strlen($searchterm) < 2) {
                    unset($searchterms[$key]);
                }
            }
            $strippedsearch = implode(' ', $searchterms);    // Rebuild the string
            $entry->highlight = $strippedsearch;
        }

        /// and finally print the entry.
        $entry->entrycount = $offset + $entriesshown + 1;
        $user = $DB->get_record("user", array("id" => $entry->userid));
        $entry->fullname=fullname($user);
        qanda_print_entry($course, $cm, $qanda, $entry, $mode, $hook, 1, $displayformat);
        $entriesshown++;
    }
}
if (!$entriesshown) {
    echo $OUTPUT->box(get_string("noentries", "qanda"), "generalbox box-align-center boxwidthwide");
}

if (!empty($formsent)) {
    // close the form properly if used
    echo "</div>";
    echo "</form>";
}

if ($paging) {
    echo '<hr />';
    echo '<div class="paging">';
    echo $paging;
    echo '</div>';
}
echo '<br />';
qanda_print_tabbed_table_end();

/// Finish the page
echo $OUTPUT->footer();
