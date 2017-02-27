<?php

require_once $CFG->libdir.'/formslib.php';

class grade_export_form_myucla extends moodleform {
    function definition() {
        global $CFG, $COURSE, $USER, $DB, $OUTPUT;

        $isdeprecatedui = false;

        $mform =& $this->_form;
        if (isset($this->_customdata)) {  // hardcoding plugin names here is hacky
            $features = $this->_customdata;
        } else {
            $features = array();
        }

        if (empty($features['simpleui'])) {
            debugging('Grade export plugin needs updating to support one step exports.', DEBUG_DEVELOPER);
        }

        $mform->addElement('header', 'gradeitems', get_string('gradeitemsinc', 'grades'));
        $mform->setExpanded('gradeitems', true);

        if (!empty($features['idnumberrequired'])) {
            $mform->addElement('static', 'idnumberwarning', get_string('useridnumberwarning', 'grades'));
        }

        $switch = grade_get_setting($COURSE->id, 'aggregationposition', $CFG->grade_aggregationposition);

        // Grab the grade_seq for this course
        $gseq = new grade_seq($COURSE->id, $switch);

        if ($grade_items = $gseq->items) {
            $coursetotal = get_string('coursetotal', 'grades');
            $canviewhidden = has_capability('moodle/grade:viewhidden', context_course::instance($COURSE->id));

            foreach ($grade_items as $grade_item) {
                if (!empty($features['idnumberrequired']) and empty($grade_item->idnumber)) {
                    $mform->addElement('advcheckbox', 'itemids['.$grade_item->id.']', $grade_item->get_name(), get_string('noidnumber', 'grades'));
                    $mform->hardFreeze('itemids['.$grade_item->id.']');
                } else  if ($grade_item->get_name() == $coursetotal) {
                    //Course total (final grade)
                    $mform->addElement('advcheckbox', 'itemids['.$grade_item->id.']', $grade_item->get_name(), null, array('group' => 1));
                    $mform->setDefault('itemids['.$grade_item->id.']', 1);
                    $mform->hardFreeze('itemids['.$grade_item->id.']');
                }
            }
        }
        $mform->addElement('static', 'expressgradeitemsnote', '', get_string('expressgradeitemsnote', 'gradeexport_myucla'));

        $mform->addElement('header', 'options', get_string('exportformatoptions', 'grades'));
        if (!empty($features['simpleui'])) {
            $mform->setExpanded('options', true);
        }

        $coursecontext = context_course::instance($COURSE->id);
        if (has_capability('moodle/course:viewsuspendedusers', $coursecontext)) {
            $mform->addElement('advcheckbox', 'export_onlyactive', get_string('exportonlyactive', 'grades'));
            $mform->setType('export_onlyactive', PARAM_BOOL);
            $mform->setDefault('export_onlyactive', 1);
            $mform->addHelpButton('export_onlyactive', 'exportonlyactive', 'grades');
        } else {
            $mform->addElement('hidden', 'export_onlyactive', 1);
            $mform->setType('export_onlyactive', PARAM_BOOL);
            $mform->setConstant('export_onlyactive', 1);
        }

        if (empty($features['simpleui'])) {
            $options = array('10'=>10, '20'=>20, '100'=>100, '1000'=>1000, '100000'=>100000);
            $mform->addElement('select', 'previewrows', get_string('previewrows', 'grades'), $options);
        }

        if (!empty($features['updategradesonly'])) {
            $mform->addElement('advcheckbox', 'updatedgradesonly', get_string('updatedgradesonly', 'grades'));
        }

        $groupingoptions = array();
        $allgroupings = groups_get_all_groupings($COURSE->id);
        foreach ($allgroupings as $groupingid) {
            $groupingoptions[$groupingid->id] = $groupingid->name;
        }
        $mform->addElement('select', 'groupingid', get_string('groupings', 'gradeexport_myucla'), $groupingoptions);
        $mform->setDefault('groupingid', $COURSE->defaultgroupingid);

        if (!empty($CFG->gradepublishing) and !empty($features['publishing'])) {
            $mform->addElement('header', 'publishing', get_string('publishingoptions', 'grades'));
            if (!empty($features['simpleui'])) {
                $mform->setExpanded('publishing', false);
            }
            $options = array(get_string('nopublish', 'grades'), get_string('createnewkey', 'userkey'));
            $keys = $DB->get_records_select('user_private_key', "script='grade/export' AND instance=? AND userid=?",
                array($COURSE->id, $USER->id));
            if ($keys) {
                foreach ($keys as $key) {
                    $options[$key->value] = $key->value; // TODO: add more details - ip restriction, valid until ??
                }
            }
            $mform->addElement('select', 'key', get_string('userkey', 'userkey'), $options);
            $mform->addHelpButton('key', 'userkey', 'userkey');
            $mform->addElement('static', 'keymanagerlink', get_string('keymanager', 'userkey'),
                '<a href="'.$CFG->wwwroot.'/grade/export/keymanager.php?id='.$COURSE->id.'">'.get_string('keymanager', 'userkey').'</a>');

            $mform->addElement('text', 'iprestriction', get_string('keyiprestriction', 'userkey'), array('size'=>80));
            $mform->addHelpButton('iprestriction', 'keyiprestriction', 'userkey');
            $mform->setDefault('iprestriction', getremoteaddr()); // own IP - just in case somebody does not know what user key is
            $mform->setType('iprestriction', PARAM_RAW_TRIMMED);

            $mform->addElement('date_time_selector', 'validuntil', get_string('keyvaliduntil', 'userkey'), array('optional'=>true));
            $mform->addHelpButton('validuntil', 'keyvaliduntil', 'userkey');
            $mform->setDefault('validuntil', time()+3600*24*7); // only 1 week default duration - just in case somebody does not know what user key is
            $mform->setType('validuntil', PARAM_INT);

            $mform->disabledIf('iprestriction', 'key', 'noteq', 1);
            $mform->disabledIf('validuntil', 'key', 'noteq', 1);
        }


        $mform->addElement('hidden', 'id', $COURSE->id);
        $mform->setType('id', PARAM_INT);
        $submitstring = get_string('download');
        if (empty($features['simpleui'])) {
            $submitstring = get_string('submit');
        } else if (!empty($CFG->gradepublishing)) {
            $submitstring = get_string('export', 'grades');
        }

        $this->add_action_buttons(false, $submitstring);
    }

    public function get_data() {
        global $CFG;
        $data = parent::get_data();

        return $data;
    }
}
