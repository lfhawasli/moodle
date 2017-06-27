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
 * Handle multiple choice competencies in feedback module.
 *
 * @package     mod_feedback
 * @copyright   2016 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

defined('MOODLE_INTERNAL') OR die('not allowed');
require_once($CFG->dirroot.'/mod/feedback/item/multichoice/lib.php');
require_once($CFG->dirroot.'/blocks/competencies/lib.php');

/**
 * Class file.
 *
 * @package     mod_feedback
 * @copyright   2016 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class feedback_item_multichoicesetcompetencies extends feedback_item_multichoice {
    /** @var string Question type.*/
    protected $type = "multichoicesetcompetencies";

    /**
     * Helper function for collected data, both for analysis page and export to excel
     *
     * @param stdClass $item the db-object from feedback_item
     * @param int $groupid
     * @param int $courseid
     * @return array
     */
    public function get_analysed($item, $groupid = false, $courseid = false) {
        global $COURSE;

        $info = $this->get_info($item);

        $analysedanswer = array();

        // Get the possible answers.
        $answers = explode (FEEDBACK_MULTICHOICE_LINE_SEP, $info->presentation);
        if (!is_array($answers)) {
            return null;
        }
        // Get the values.
        $responses = feedback_get_group_values($item, $groupid, $courseid, $this->ignoreempty($item));
        if (!$responses) {
            return null;
        }
        $values = array();
        foreach ($responses as $response) {
            $values[] = json_decode($response->value, true);
        }

        // NOTE: need to use $COURSE because $courseid is now always set.
        foreach (block_competencies_db::get_course_items($COURSE->id) as $competency) {
            $sizeofanswers = count($answers);
            for ($i = 1; $i <= $sizeofanswers; $i++) {
                $ans = new stdClass();
                $ans->competencyid = $competency->id;
                $ans->subtext = $answers[$i - 1];
                $ans->answertext = $competency->name . ' - ' . trim($answers[$i - 1]);
                $ans->answercount = 0;
                foreach ($values as $value) {
                    if (array_key_exists($competency->id, $value) && $value[$competency->id] == $i) {
                        $ans->answercount++;
                    }
                }
                $ans->quotient = $ans->answercount / count($values);
                $analysedanswer[] = $ans;
            }
        }

        $analyseditem = array();
        $analyseditem[] = $item->typ;
        $analyseditem[] = $item->name;
        $analyseditem[] = $analysedanswer;

        return $analyseditem;
    }

    /**
     * Prepares the value for exporting to Excel.
     *
     * @param object $item The db-object from feedback_item
     * @param array $value
     * @return string
     */
    public function get_printval($item, $value) {
        $info = $this->get_info($item);
        $answers = explode(FEEDBACK_MULTICHOICE_LINE_SEP, $info->presentation);
        if (!is_array($answers)) {
            return null;
        }

        $arr = array();
        $obj = json_decode($value->value, true);
        $competencies = block_competencies_db::get_items();
        foreach ($obj as $competencyid => $value) {
            $arr[$competencies[$competencyid]->name] = trim($answers[$value - 1]);
        }
        return json_encode($arr);
    }

    /**
     * Adds an input element to the complete form
     *
     * This element has many options - it can be displayed as group or radio elements,
     * group of checkboxes or a dropdown list.
     *
     * @param stdClass $item
     * @param mod_feedback_complete_form $form
     */
    public function complete_form_element($item, $form) {
        global $COURSE, $OUTPUT, $PAGE;

        // We require JQuery for converting responses to JSON.
        if (!$PAGE->requires->is_head_done()) {
            // When editing questions header is already sent.
            $PAGE->requires->jquery();
        }

        $info = $this->get_info($item);
        $name = $this->get_display_name($item);
        $inputname = $item->typ . '_' . $item->id;
        $options = $this->get_options($item);
        $separator = !empty($info->horizontal) ? ' ' : '<br />';
        $values = $form->get_item_value($item);

        if ($values == null) {
            // Maybe user is submitting form and had some errors.
            $formdata = optional_param($inputname, null, PARAM_NOTAGS);
            if (empty($formdata)) {
                $values = array();
            } else {
                // We want to extract the data from submission so we can recover
                // user data.
                $values = json_decode($formdata, true);
            }
        }

        $align = right_to_left() ? 'right' : 'left';

        $strrequiredmark = '<img class="req" title="'.get_string('requiredelement', 'form').'" alt="'.
            get_string('requiredelement', 'form').'" src="'.$OUTPUT->pix_url('req') .'" />';
        $requiredmark = ($item->required == 1) ? $strrequiredmark : '';

        ob_start();

        // Print the question and label.
        echo '<div class="feedback_item_label_'.$align.'">';
        echo format_text($name.$requiredmark, true, false, false);
        echo '</div>';
        echo '<br />';

        echo '<div id="multichoice_set-'.$item->id.'" style="font-weight:normal">';
        foreach (block_competencies_db::get_course_items($COURSE->id) as $competency) {
            // Print the presentation.
            echo '<div class="feedback_item_presentation_'.$align.'" data-competency="'.$competency->id.'">';
            echo '<div style="display: block">';
            echo '<strong>'.$competency->name.'</strong>';
            echo '<small><br>'.$competency->description.'</small>';

            if ($item->required) {
                if (!empty($_POST) && (count($values) == 0 || !array_key_exists($competency->id, $values))) {
                    echo '<br class="error"><span id="id_error_'.$inputname.'" class="error"> '.get_string('err_required', 'form').
                        '</span><br id="id_error_break_'.$inputname.'" class="error" >';
                }
            }
            echo '</div>';

            if ($info->horizontal) {
                $hv = 'h';
            } else {
                $hv = 'v';
            }

            // Print the radio buttons.
            echo '<fieldset class="felement fgroup" style="margin-left: 15px; margin-bottom:1.0em">';
            $this->print_item_radio($options, $item, $values, $info, $align, $separator, $competency);
            echo '</fieldset>';
            echo '</div>';
        }
        echo '</div>';
        ?>
        <script type="text/javascript">
            $('#multichoice_set-<?php echo $item->id; ?> input').change(function(){
                var competencyId = $(this).attr('data-competency'),
                    value = $(this).val(),
                    jsonObject = {},
                    textareaEle = $('input[name=<?php echo $inputname; ?>]');
                try {
                    jsonObject = JSON.parse(textareaEle.val());
                } catch(e) {}

                if (value == "") {
                    delete jsonObject[competencyId];
                } else {
                    jsonObject[competencyId] = value;
                }
                textareaEle.val(JSON.stringify(jsonObject));
            })


        </script>
        <?php

        $contents = ob_get_contents();
        ob_end_clean();

        // The static elements add the compentancy questions.
        $form->add_form_element($item, ['static', $inputname.'_contents',
            $contents], false);

        // The hidden field is where JQuery will store the answers as JSON.
        $form->add_form_element($item, ['hidden', $inputname,
            json_encode($values, JSON_FORCE_OBJECT)]);
        $form->set_element_type($inputname, PARAM_NOTAGS);
    }

    /**
     * Prepares value that user put in the form for storing in DB
     * @param array $value
     * @return string
     */
    public function create_value($value) {
        return $value;
    }

    /**
     * Compares the dbvalue with the dependvalue.
     *
     * @param stdClass $item
     * @param string $dbvalue is the value input by user in the format as it is stored in the db
     * @param string $dependvalue is the value that it needs to be compared against
     */
    public function compare_value($item, $dbvalue, $dependvalue) {
        if (count(array_diff_assoc(json_decode($dbvalue, true),
                json_decode($dependvalue, true))) + count(array_diff_assoc(json_decode($dependvalue, true),
                json_decode($dbvalue, true)))) {
            return false;
        }
        return true;
    }

    /**
     * Prints radio buttons for a given competency.
     * 
     * @param array $presentation   Responses.
     * @param object $item
     * @param array $value
     * @param object $info
     * @param string $align
     * @param string $separator
     * @param object $competency
     */
    private function print_item_radio($presentation, $item, $value, $info, $align, $separator, $competency = null) {

        if (is_null($competency)) {
            return;
        }

        $index = 1;
        $checked = '';

        if (is_array($value)) {
            $values = $value;
        } else {
            $values = array($value);
        }

        if ($info->horizontal) {
            $hv = 'h';
        } else {
            $hv = 'v';
        }

        // Handle special case of not selected.
        if ($presentation[0] == get_string('not_selected', 'feedback')) {
        ?>
            <span>
                <?php
                $checked = '';
                if (count($values) == 0 || !array_key_exists($competency->id, $values)) {
                    $checked = 'checked="checked"';
                }
                echo '<input type="radio" '.
                        'name="'.$item->typ.'_'.$item->id.'_'.$competency->id.'" '.
                        'id="'.$item->typ.'_'.$item->id.'_'.$competency->id.'_xxx" '.
                        'data-competency="'.$competency->id.'"'.
                        'value="" '.$checked.' />';
                ?>
                <label for="<?php echo $item->typ.'_'.$item->id.'_'.$competency->id.'_xxx';?>" style="font-weight:normal">
                    <?php print_string('not_selected', 'feedback');?>
                </label>
            </span>
            <br />
        <?php
            // Remove option since we are printing it here.
            array_shift($presentation);
        }

        foreach ($presentation as $radio) {
            $checked = '';
            foreach ($values as $competencyid => $val) {
                if ($competencyid == $competency->id && $val == $index) {
                    $checked = 'checked="checked"';
                    break;
                }
            }
            $inputname = $item->typ . '_' . $item->id . '_' . $competency->id;
            $inputid = $inputname.'_'.$index.'_'.$competency->id;
        ?>
                <span>
                    <?php
                        echo '<input type="radio" '.
                                'name="'.$inputname.'" '.
                                'id="'.$inputid.'" '.
                                'data-competency="'.$competency->id.'" '.
                                'value="'.$index.'" '.$checked.' />';
                    ?>
                    <label for="<?php echo $inputid;?>" style="font-weight:normal">
                        <?php echo text_to_html($radio, true, false, false);?>
                    </label>
                </span>
        <?php
            echo $separator;
            $index++;
        }
    }
}
