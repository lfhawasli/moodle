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

defined('MOODLE_INTERNAL') OR die('not allowed');
require_once($CFG->dirroot.'/mod/feedback/item/multichoice/lib.php');
require_once($CFG->dirroot.'/blocks/competencies/lib.php');

/**
 * Class to handle multiple choice competencies in feedback module
 *
 * @package     block_mod_feedback
 * @copyright   2016 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class feedback_item_multichoicesetcompetencies extends feedback_item_multichoice {

    protected $type = "multichoicesetcompetencies";

    public function is_supported() {
        global $CFG;
        return file_exists($CFG->dirroot.'/blocks/competencies/lib.php');
    }

    public function init_lib() {
        global $CFG;

        if (!$this->is_supported())
            return false;

        require_once($CFG->dirroot.'/blocks/competencies/lib.php');
        return true;
    }

    /**
     * print the item at the edit-page of feedback
     *
     * @global object
     * @param object $item
     * @return void
     */
    public function print_item_preview($item) {
        global $OUTPUT, $DB, $COURSE;

        if (!$this->is_supported())
            return;

        $info = $this->get_info($item);
        $align = right_to_left() ? 'right' : 'left';

        $presentation = explode (FEEDBACK_MULTICHOICE_LINE_SEP, $info->presentation);
        $requiredmarkstr = '<span class="feedback_required_mark">*</span>';

        // Test if required and no value is set so we have to mark this item
        // we have to differ check and the other subtypes.
        $requiredmark = ($item->required == 1) ? $requiredmarkstr : '';

        // Print the question and label.
        echo '<div class="feedback_item_label_'.$align.'">';
        echo '('.$item->label.') ';
        echo format_text($item->name.$requiredmark, true, false, false);
        if ($item->dependitem) {
            if ($dependitem = $DB->get_record('feedback_item', array('id' => $item->dependitem))) {
                echo ' <span class="feedback_depend">';
                echo '('.$dependitem->label.'-&gt;'.$item->dependvalue.')';
                echo '</span>';
            }
        }
        echo '</div>';

        foreach (block_competencies_db::get_course_items($COURSE->id) as $competency) {
            // Print the presentation.
            echo '<div class="feedback_item_presentation_'.$align.'">';
            echo '<div style="font-weight:bold;padding:6px 0 0 4px;">'.$competency->name.'</div>';
            $index = 1;
            $checked = '';
            echo '<ul>';
            if ($info->horizontal) {
                $hv = 'h';
            } else {
                $hv = 'v';
            }

            if ($info->subtype == 'r' AND !$this->hidenoselect($item)) {
                // Print the "not_selected" item on radiobuttons.
                ?>
                <li class="feedback_item_radio_<?php echo $hv.'_'.$align;?>">
                    <span class="feedback_item_radio_<?php echo $hv.'_'.$align;?>">
                        <?php
                            echo '<input type="radio" '.
                                    'name="'.$item->typ.'_'.$item->id.'_'.$competency->id.'[]" '.
                                    'id="'.$item->typ.'_'.$item->id.'_'.$competency->id.'_xxx" '.
                                    'value="" checked="checked" />';
                        ?>
                    </span>
                    <span class="feedback_item_radiolabel_<?php echo $hv.'_'.$align;?>">
                        <label for="<?php echo $item->typ . '_' . $item->id.'_xxx';?>">
                            <?php print_string('not_selected', 'feedback');?>&nbsp;
                        </label>
                    </span>
                </li>
                <?php
            }

            $this->print_item_radio($presentation, $item, false, $info, $align, $competency);

            echo '</ul>';
            echo '</div>';
        }
    }

    /**
     * print the item at the complete-page of feedback
     *
     * @global object
     * @param object $item
     * @param string $value
     * @param bool $highlightrequire
     * @return void
     */
    public function print_item_complete($item, $value = null, $highlightrequire = false) {
        global $OUTPUT, $COURSE;

        if (!$this->is_supported())
            return;

        $info = $this->get_info($item);
        $align = right_to_left() ? 'right' : 'left';

        if ($value == null) {
            $value = array();
        }
        $presentation = explode (FEEDBACK_MULTICHOICE_LINE_SEP, $info->presentation);
        $requiredmarkstr = '<span class="feedback_required_mark">*</span>';

        // Test if required and no value is set so we have to mark this item
        // we have to differ check and the other subtypes.
        // if ($info->subtype == 'c') {
        if (is_array($value)) {
            $values = $value;
        } else {
            $values = explode(FEEDBACK_MULTICHOICE_LINE_SEP, $value);
        }
        $highlight = '';
        if ($highlightrequire AND $item->required) {
            if (count($values) == 0) {
                $highlight = ' missingrequire';
            }
        }
        $requiredmark = ($item->required == 1) ? $requiredmarkstr : '';
        // } else {
            // if ($highlightrequire AND $item->required AND intval($value) <= 0) {
                // $highlight = ' missingrequire';
            // } else {
                // $highlight = '';
            // }
            // $requiredmark = ($item->required == 1) ? $requiredmarkstr : '';
        // }

        // Print the question and label.
        echo '<div class="feedback_item_label_'.$align.$highlight.'">';
            echo format_text($item->name.$requiredmark, true, false, false);
        echo '</div>';

        echo '<div id="multichoice_set-'.$item->id.'">';
        foreach (block_competencies_db::get_course_items($COURSE->id) as $competency) {
            // Print the presentation.
            echo '<div class="feedback_item_presentation_'.$align.$highlight.'" data-competency="'.$competency->id.'">';
            echo '<div style="padding:6px 0 0 4px;">';
            echo '<strong>'.$competency->name.'</strong>';
            echo '<a href="#!" data-tooltip="'.str_replace('"', '&quot;', $competency->description).'"><span class="tooltip-icon"></span></a>';
            echo '<small class="tooltip-accessible"><br>'.$competency->description.'</small>';
            echo '</div>';

            echo '<ul>';
            if ($info->horizontal) {
                $hv = 'h';
            } else {
                $hv = 'v';
            }
            // Print the "not_selected" item on radiobuttons.
            if ($info->subtype == 'r' AND !$this->hidenoselect($item)) {
            ?>
                <li class="feedback_item_radio_<?php echo $hv.'_'.$align;?>">
                    <span class="feedback_item_radio_<?php echo $hv.'_'.$align;?>">
                        <?php
                        $checked = '';
                        // if (!$value) {
                            // $checked = 'checked="checked"';
                        // }
                        if (count($values) == 0) {
                            $checked = 'checked="checked"';
                        }
                        echo '<input type="radio" '.
                                'name="'.$item->typ.'_'.$item->id.'_'.$competency->id.'" '.
                                'id="'.$item->typ.'_'.$item->id.'_'.$competency->id.'_xxx" '.
                                'data-competency="'.$competency->id.'"'.
                                'value="" '.$checked.' />';
                        ?>
                    </span>
                    <span class="feedback_item_radiolabel_<?php echo $hv.'_'.$align;?>">
                        <label for="<?php echo $item->typ.'_'.$item->id.'_xxx';?>">
                            <?php print_string('not_selected', 'feedback');?>&nbsp;
                        </label>
                    </span>
                </li>
            <?php
            }

            $this->print_item_radio($presentation, $item, $value, $info, $align, $competency);

            echo '</ul>';
            echo '</div>';

        }
        echo '</div>';
        echo '<textarea name="'.$item->typ.'_'.$item->id.'" id="'.$item->typ.'_'.$item->id.'" style="display:none;">'.json_encode($value, JSON_FORCE_OBJECT).'</textarea>';
        ?>
        <script type="text/javascript">
        $('#multichoice_set-<?php echo $item->id; ?> input').change(function(){
            var competencyId = $(this).attr('data-competency'),
                value = $(this).val(),
                jsonObject = {},
                textareaEle = $('#<?php echo $item->typ.'_'.$item->id; ?>')
                
            try{
                jsonObject = JSON.parse(textareaEle.val())
            }catch(e){}
            
            jsonObject[competencyId] = value
            textareaEle.val(JSON.stringify(jsonObject))
        })
        </script>
        <?php
    }


    // Gets an array with three values(typ, name, XXX)
    // XXX is an object with answertext, answercount and quotient.
    public function get_analysed($item, $groupid = false, $courseid = false) {
        global $COURSE;

        if (!$this->init_lib()) {
            return null;
        }

        $info = $this->get_info($item);

        $analysedanswer = array();

        // Get the possible answers.
        $answers = null;
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

        foreach (block_competencies_db::get_course_items($COURSE->id) as $competency) {
            $sizeofanswers = count($answers);
            for ($i = 1; $i <= $sizeofanswers; $i++) {
                $ans = new stdClass();
                $ans->competencyid = $competency->id;
                $ans->subtext = $answers[$i - 1];
                $ans->answertext = $competency->name . ' - ' . trim($answers[$i - 1]);
                $ans->answercount = 0;
                foreach ($values as $value) {
                    if ($value[$competency->id] == $i) {
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

    public function get_printval($item, $value) {
        $info = $this->get_info($item);
        $answers = null;
        $answers = explode (FEEDBACK_MULTICHOICE_LINE_SEP, $info->presentation);
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
     * print the item at the complete-page of feedback
     *
     * @global object
     * @param object $item
     * @param string $value
     * @return void
     */
    public function print_item_show_value($item, $value = '') {
        global $OUTPUT;
        $align = right_to_left() ? 'right' : 'left';
        $requiredmarkstr = '<span class="feedback_required_mark">*</span>';

        $info = $this->get_info($item);
        $presentation = explode (FEEDBACK_MULTICHOICE_LINE_SEP, $info->presentation);
        $requiredmark = ($item->required == 1) ? $requiredmarkstr : '';

        // Print the question and label.
        echo '<div class="feedback_item_label_'.$align.'">';
            echo '('.$item->label.') ';
            echo format_text($item->name . $requiredmark, true, false, false);
        echo '</div>';

        // Print the presentation.
        echo $OUTPUT->box_start('generalbox boxalign'.$align);

        $items = array();
        $competencies = block_competencies_db::get_items();
        $competencyresponses = json_decode($value, true);
        foreach ($competencyresponses as $id => $response) {
            $items[] = '<strong>'.s($competencies[$id]->name).'</strong>: '.s($presentation[$response - 1]);
        }
        if (count($items))
            echo '<ul><li>'.implode('</li><li>', $items).'</li></ul>';
        echo $OUTPUT->box_end();
    }

    public function clean_input_value($value) {
        return clean_param_array(json_decode($value, true), $this->value_type());
    }

    public function check_value($value, $item) {
        global $COURSE;

        $info = $this->get_info($item);
        if ($item->required != 1) {
            return true;
        }

        if (!$value) {
            return false;
        }

        $competencies = block_competencies_db::get_course_items($COURSE->id);
        $obj = $value;
        if (count(array_diff(array_keys($competencies), array_keys($obj)))) {
            return false;
        }

        if ($obj != $value) {
            return false;
        }

        return true;
    }

    public function create_value($data) {
        return $data;
    }

    // Compares the dbvalue with the dependvalue
    // dbvalue is the value put in by the user
    // dependvalue is the value that is compared.
    public function compare_value($item, $dbvalue, $dependvalue) {
        if (count(array_diff_assoc(json_decode($dbvalue, true), json_decode($dependvalue, true)))
            + count(array_diff_assoc(json_decode($dependvalue, true), json_decode($dbvalue, true)))) {
            return false;
        }
        return true;
    }

    public function value_type() {
        return PARAM_RAW;
    }

    public function value_is_array() {
        return false;
    }

    public function get_info($item) {
        $presentation = empty($item->presentation) ? '' : $item->presentation;

        $info = new stdClass();
        // Check the subtype of the multichoice
        // it can be check(c), radio(r) or dropdown(d).
        $info->subtype = '';
        $info->presentation = '';
        $info->horizontal = false;

        $parts = explode(FEEDBACK_MULTICHOICE_TYPE_SEP, $item->presentation);
        @list($info->subtype, $info->presentation) = $parts;
        if (!isset($info->subtype)) {
            $info->subtype = 'r';
        }

        if ($info->subtype != 'd') {
            $parts = explode(FEEDBACK_MULTICHOICE_ADJUST_SEP, $info->presentation);
            @list($info->presentation, $info->horizontal) = $parts;
            if (isset($info->horizontal) AND $info->horizontal == 1) {
                $info->horizontal = true;
            } else {
                $info->horizontal = false;
            }
        }
        return $info;
    }

    private function print_item_radio($presentation, $item, $value, $info, $align, $competency = null) {

        if (is_null($competency))
            return;

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

        foreach ($presentation as $radio) {
            foreach ($values as $competencyid => $val) {
                if ($competencyid == $competency->id && $val == $index) {
                    $checked = 'checked="checked"';
                    break;
                } else {
                    $checked = '';
                }
            }
            $inputname = $item->typ . '_' . $item->id . '_' . $competency->id;
            $inputid = $inputname.'_'.$index.'_'.$competency->id;
        ?>
            <li class="feedback_item_radio_<?php echo $hv.'_'.$align;?>">
                <span class="feedback_item_radio_<?php echo $hv.'_'.$align;?>">
                    <?php
                        echo '<input type="radio" '.
                                'name="'.$inputname.'" '.
                                'id="'.$inputid.'" '.
                                'data-competency="'.$competency->id.'"'.
                                'value="'.$index.'" '.$checked.' />';
                    ?>
                </span>
                <span class="feedback_item_radiolabel_<?php echo $hv.'_'.$align;?>">
                    <label for="<?php echo $inputid;?>">
                        <?php echo text_to_html($radio, true, false, false);?>&nbsp;
                    </label>
                </span>
            </li>
        <?php
            $index++;
        }
    }

}
