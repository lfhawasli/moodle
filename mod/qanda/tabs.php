<?php
    if (!isset($sortorder)) {
        $sortorder = '';
    }
    if (!isset($sortkey)) {
        $sortkey = '';
    }

    //make sure variables are properly cleaned
    $sortkey   = clean_param($sortkey, PARAM_ALPHA);// Sorted view: CREATION | UPDATE | FIRSTNAME | LASTNAME...
    $sortorder = clean_param($sortorder, PARAM_ALPHA);   // it defines the order of the sorting (ASC or DESC)

    $toolsrow = array();
    $browserow = array();
    $inactive = array();
    $activated = array();

    if (!has_capability('mod/qanda:answer', $context) && $tab == QANDA_APPROVAL_VIEW) {
    /// Non-teachers going to approval view go to defaulttab
        $tab = $defaulttab;
    }


    $browserow[] = new tabobject(QANDA_STANDARD_VIEW,
                                 $CFG->wwwroot.'/mod/qanda/view.php?id='.$id.'&amp;mode=letter',
                                 get_string('standardview', 'qanda'));



    $browserow[] = new tabobject(QANDA_DATE_VIEW,
                                 $CFG->wwwroot.'/mod/qanda/view.php?id='.$id.'&amp;mode=date',
                                 get_string('dateview', 'qanda'));
/*
        $browserow[] = new tabobject(qanda_CATEGORY_VIEW,
                                 $CFG->wwwroot.'/mod/qanda/view.php?id='.$id.'&amp;mode=cat',
                                 get_string('categoryview', 'qanda'));
 $browserow[] = new tabobject(qanda_AUTHOR_VIEW,
                                 $CFG->wwwroot.'/mod/qanda/view.php?id='.$id.'&amp;mode=author',
                                 get_string('authorview', 'qanda'));
 *  */


    if ($tab < QANDA_STANDARD_VIEW ) {//|| $tab > qanda_AUTHOR_VIEW   // We are on second row
        $inactive = array('edit');
        $activated = array('edit');

        $browserow[] = new tabobject('edit', '#', get_string('edit'));
    }

/// Put all this info together

    $tabrows = array();
    $tabrows[] = $browserow;     // Always put these at the top
    if ($toolsrow) {
        $tabrows[] = $toolsrow;
    }


?>
  <div class="qanda-display">


<?php 
    // Print tabs.
    if ($showcommonelements) { print_tabs($tabrows, $tab, $inactive, $activated); }

    // Print add and search box.
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
?>

  <div class="entrybox">

<?php

    if (!isset($category)) {
        $category = "";
    }


    switch ($tab) {
        case QANDA_APPROVAL_VIEW:
            qanda_print_approval_menu($cm, $qanda, $mode, $hook, $sortkey, $sortorder);
        break;
        case QANDA_IMPORT_VIEW:
            $search = "";
            $l = "";
            qanda_print_import_menu($cm, $qanda, 'import', $hook, $sortkey, $sortorder);
        break;
        case QANDA_EXPORT_VIEW:
            $search = "";
            $l = "";
            qanda_print_export_menu($cm, $qanda, 'export', $hook, $sortkey, $sortorder);
        break;
        case QANDA_DATE_VIEW:
            if (!$sortkey) {
                $sortkey = 'UPDATE';
            }
            if (!$sortorder) {
                $sortorder = 'desc';
            }
            qanda_print_alphabet_menu($cm, $qanda, "date", $hook, $sortkey, $sortorder);
        break;
        case QANDA_STANDARD_VIEW:
        default:
            qanda_print_alphabet_menu($cm, $qanda, "letter", $hook, $sortkey, $sortorder);
            if ($mode == 'search' and $hook) {
                echo "<h3>$strsearch: $hook</h3>";
            }
        break;
    }
    /*
        case qanda_CATEGORY_VIEW:
            qanda_print_categories_menu($cm, $qanda, $hook, $category);
        break;
        case QANDA_APPROVAL_VIEW:
            qanda_print_approval_menu($cm, $qanda, $mode, $hook, $sortkey, $sortorder);
        break;
        case qanda_AUTHOR_VIEW:
            $search = "";
            qanda_print_author_menu($cm, $qanda, "author", $hook, $sortkey, $sortorder, 'print');
        break;
        case QANDA_IMPORT_VIEW:
            $search = "";
            $l = "";
            qanda_print_import_menu($cm, $qanda, 'import', $hook, $sortkey, $sortorder);
        break;
        case QANDA_EXPORT_VIEW:
            $search = "";
            $l = "";
            qanda_print_export_menu($cm, $qanda, 'export', $hook, $sortkey, $sortorder);
        break;
        case QANDA_DATE_VIEW:
            if (!$sortkey) {
                $sortkey = 'UPDATE';
            }
            if (!$sortorder) {
                $sortorder = 'desc';
            }
            qanda_print_alphabet_menu($cm, $qanda, "date", $hook, $sortkey, $sortorder);
        break;
        case QANDA_STANDARD_VIEW:
        default:
            qanda_print_alphabet_menu($cm, $qanda, "letter", $hook, $sortkey, $sortorder);
            if ($mode == 'search' and $hook) {
                echo "<h3>$strsearch: $hook</h3>";
            }
        break;
     */
    
    echo '<hr />';
?>
