<?php

/*
 * @package    mod_qanda
 * @copyright 2013 UC Regents
 */

function qanda_show_entry_qanda($course, $cm, $qanda, $entry, $mode = '', $hook = '', $printicons = 1, $aliases = true, $permalink = true) {
    global $USER;
    if ($entry) {
        echo html_writer::start_div('qanda-entry');
        echo html_writer::start_div('qanda-entry-count');

        if (isset($entry->entrycount)) {
            echo $entry->entrycount . '.';
        }

        echo html_writer::end_div();
        echo html_writer::start_div('qanda-entry-table ' . $mode);
        echo html_writer::start_tag('table', array('class' => 'qanda-post faq', 'cellspacing' => '0'));
        echo html_writer::start_tag('tr', array('class' => 'question-row', 'valign' => 'top'));
        echo html_writer::start_tag('th', array('class' => 'entry-header'));

        $entry->course = $course->id;

        echo html_writer::start_div('question_alt');
        echo html_writer::start_div('question_image');
        echo get_string('qanda_q', 'qanda');
        echo html_writer::end_div();
        echo html_writer::start_div('question-text');

        qanda_print_entry_question($entry, $qanda, $cm);

        echo html_writer::end_div();
        echo html_writer::start_div('user-information');
        echo get_string('created', 'qanda') . ': ' . userdate($entry->timecreated);

        $context = context_module::instance($cm->id);

        if (has_capability('moodle/site:viewfullnames', $context)) {
            echo html_writer::empty_tag('br');
            echo get_string('postby', 'qanda') . ': ' . $entry->fullname . ' (' . $entry->useremail . ')';
        }

        echo html_writer::end_div();
        echo html_writer::end_div();
        echo html_writer::end_tag('th');
        echo html_writer::start_tag('td', array('class' => 'entry-attachment'));

        qanda_print_entry_approval($cm, $entry, $mode);

        qanda_print_entry_attachment($entry, $cm, 'html', 'right');

        echo html_writer::end_tag('td');
        echo html_writer::end_tag('tr');

        if ($entry->approved) {
            echo html_writer::start_tag('tr', array('class' => 'answer-row'));
            echo html_writer::start_tag('td', array('class' => 'entry', 'colspan' => '2'));
            echo html_writer::start_div('answer_alt');
            echo html_writer::start_div('answer_image');
            echo get_string('qanda_a', 'qanda');
            echo html_writer::end_div();
            echo html_writer::start_div('answer-text');

            qanda_print_entry_answer($entry, $qanda, $cm);

            echo html_writer::end_div();
            echo html_writer::end_div();
            echo html_writer::end_tag('td');
            echo html_writer::end_tag('tr');
        }

        echo html_writer::start_tag('tr');
        echo html_writer::start_tag('td', array('class' => 'entry-lower-section', 'colspan' => '3'));
        echo html_writer::start_div('modified-date-edit');

        if ($entry->approved) {
            echo html_writer::start_div('time');
            echo get_string('lastedited') . ': ' . userdate($entry->timemodified);
            echo html_writer::end_div();
        }

        echo html_writer::start_div('edit-button');

        qanda_print_entry_lower_section($course, $cm, $qanda, $entry, $mode, $hook, $printicons, $aliases);

        echo html_writer::end_div();
        echo html_writer::start_div('qanda-permalink');
        if ($permalink) {
            $url = new moodle_url('view.php', array('id' => $cm->id, 'mode' => 'entry', 'hook' => urlencode($entry->id)));
            echo html_writer::link($url, get_string('qanda_permalink', 'qanda'), array('id' => 'perma-link', 'class' => 'btn btn-primary btn-sm', 'title' => get_string('qanda_permalink', 'qanda')));
        }
        echo html_writer::end_div();
        echo html_writer::end_div();
        echo html_writer::end_tag('td');
        echo html_writer::end_tag('tr');
        echo html_writer::end_tag('table');
        echo html_writer::end_div();
        echo html_writer::end_div();
    } else {
        echo html_writer::start_div('', array('style' => 'text-align:center'));
        print_string('noentry', 'qanda');
        echo html_writer::end_div();
    }
}

function qanda_print_entry_qanda($course, $cm, $qanda, $entry, $mode = '', $hook = '', $printicons = 1) {

    //The print view for this format is exactly the normal view, so we use it
    //Take out autolinking in answers un print view
    //$entry->answer = '<span class='nolink'>' . $entry->answer . '</span>';
    //Call to view function (without icons, ratings and aliases) and return its result
    return qanda_show_entry_qanda($course, $cm, $qanda, $entry, $mode, $hook, false, false, false);
}
