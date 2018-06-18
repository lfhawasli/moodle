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
 * Group of date and time input element
 *
 * Contains class for a group of elements used to input a date and time.
 *
 * @package   core_form
 * @copyright 2006 Jamie Pratt <me@jamiep.org>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CFG;
require_once($CFG->libdir . '/form/group.php');
require_once($CFG->libdir . '/formslib.php');

/**
 * Element used to input a date and time.
 *
 * Class for a group of elements used to input a date and time.
 *
 * @package   core_form
 * @category  form
 * @copyright 2006 Jamie Pratt <me@jamiep.org>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class MoodleQuickForm_date_time_selector extends MoodleQuickForm_group {

    /**
     * Options for the element.
     *
     * startyear => int start of range of years that can be selected
     * stopyear => int last year that can be selected
     * defaulttime => default time value if the field is currently not set
     * timezone => int|float|string (optional) timezone modifier used for edge case only.
     *      If not specified, then date is caclulated based on current user timezone.
     *      Note: dst will be calculated for string timezones only
     *      {@link http://docs.moodle.org/dev/Time_API#Timezone}
     * step => step to increment minutes by
     * START UCLA MOD: CCLE-6768 - Revamp date picker.
     * placeholder => placeholder text to place in the input field for the calendar
     * time24hr => boolean, if true, displays the time in 24-hour format. Defaults to user
     *                      preference.
     * timeformat => string, the time format. Defaults to site/user preference.
     * locale => string, affects the language of the selector. Defaults to current language.
     * clear => boolean, if true adds a "clear" button next to the flatpickr, which
     *              resets the flatpickr to 0. If false, the button will not show, and the
     *              flatpickr will submit defaulttime if it is cleared with backspace or DEL.
     * optional => if true, show a checkbox beside the date to turn it on (or off)
     *              (Note - this option does not work with moodleform::repeat_elements())
     * END UCLA MOD: CCLE-6768.
     * @var array
     */
    protected $_options = array();

    /**
     * @var array These complement separators, they are appended to the resultant HTML.
     */
    protected $_wrap = array('', '');

    /**
     * @var null|bool Keeps track of whether the date selector was initialised using createElement
     *                or addElement. If true, createElement was used signifying the element has been
     *                added to a group - see MDL-39187.
     */
    protected $_usedcreateelement = true;

    /**
     * Class constructor
     *
     * @param string $elementName Element's name
     * @param mixed $elementLabel Label(s) for an element
     * @param array $options Options to control the element's display
     * @param mixed $attributes Either a typical HTML attribute string or an associative array
     */
    public function __construct($elementName = null, $elementLabel = null, $options = array(), $attributes = null) {
        // Get the calendar type used - see MDL-18375.
        $calendartype = \core_calendar\type_factory::get_calendar_instance();
        // START UCLA MOD: CCLE-6868 - Revamp date picker.
        /*
        $this->_options = array('startyear' => $calendartype->get_min_year(), 'stopyear' => $calendartype->get_max_year(),
            'defaulttime' => 0, 'timezone' => 99, 'step' => 5, 'optional' => false);
        */
        // Get locale default timeformat and check if it is in 24 or 12 hour format.
        $timeformat = get_string('strftimetime');
        $time24hr = true;
        if (strpos($timeformat, '%p')) {
            $time24hr = false;
        }
        // Note: flatpickr does not support non-gregorian calendar.
        $this->_options = array('startyear' => $calendartype->get_min_year(), 'stopyear' => $calendartype->get_max_year(),
                'defaulttime' => time(), 'timezone' => 99, 'step' => 1, 'optional' => false, 'clear' => false,
                'placeholder' => 'Select date...', 'time24hr' => $time24hr, 'timeformat' => $timeformat, 'locale' => current_language());
        // END UCLA MOD: CCLE-6868.

        // TODO MDL-52313 Replace with the call to parent::__construct().
        HTML_QuickForm_element::__construct($elementName, $elementLabel, $attributes);
        $this->_persistantFreeze = true;
        $this->_appendName = true;
        $this->_type = 'date_time_selector';
        // set the options, do not bother setting bogus ones
        if (is_array($options)) {
            foreach ($options as $name => $value) {
                if (isset($this->_options[$name])) {
                    if (is_array($value) && is_array($this->_options[$name])) {
                        $this->_options[$name] = @array_merge($this->_options[$name], $value);
                    } else {
                        $this->_options[$name] = $value;
                    }
                }
            }
        }

        // START UCLA MOD: CCLE-6868 - Revamp date picker.
        /*
        // The YUI2 calendar only supports the gregorian calendar type.
        if ($calendartype->get_name() === 'gregorian') {
            form_init_date_js();
        }
        */
        // END UCLA MOD: CCLE-6868.
    }

    /**
     * Old syntax of class constructor. Deprecated in PHP7.
     *
     * @deprecated since Moodle 3.1
     */
    public function MoodleQuickForm_date_time_selector($elementName = null, $elementLabel = null, $options = array(), $attributes = null) {
        debugging('Use of class name as constructor is deprecated', DEBUG_DEVELOPER);
        self::__construct($elementName, $elementLabel, $options, $attributes);
    }

    // START UCLA MOD: CCLE-6868 - Revamp date picker.
    /**
     * Utility function to translate PHP strftime date/time tokens to the type of
     * JavaScript tokens used by flatpickr.
     * @param $datestring string php strftime formatted datetime string
     * @return string
     */
    function translate_date_tokens($datestring) {
        return strtr($datestring, array(
            '%' => '',  'a' => 'D', 'A' => 'l', 'e' => 'j',
            'j' => '',  'u' => 'w', 'U' => '',  'V' => '',
            'W' => '',  'b' => 'M', 'B' => 'M', 'h' => 'M',
            'Z' => '',  'g' => 'y', 'G' => 'Y', 'k' => 'H',
            'I' => 'h', 'L' => 'h', 'M' => 'i', 'P' => 'K',
            'p' => 'K', 's' => 'U', 'z' => '',  'Z' => '',
            'n' => '',  't' => ' ',
            'r' => 'h:i:S K', 'R' => 'H:i',
            'T' => 'H:i:S',   'x' => 'h:i:S K',
            'X' => 'h:i:S K', 'c' => 'h:i:S K',
            'D' => 'm/d/y',   'F' => 'Y-m-d'
        ));
    }
    // END UCLA MOD: CCLE-6868.

    /**
     * This will create date group element constisting of day, month and year.
     *
     * @access private
     */
    function _createElements() {
        // START UCLA MOD: CCLE-6868 - Revamp date picker.
        global $OUTPUT, $PAGE;
        $PAGE->requires->js('/lib/flatpickr/flatpickr.min.js');
        $PAGE->requires->js('/lib/flatpickr/plugins/confirmDate/confirmDate.js');
        $PAGE->requires->js('/lib/flatpickr/plugins/displayTimezone/displayTimezone.js');        
        // Custom CSS for flatpickr is added in theme/uclashared/styles/.

        // Support different locales.
        if ($this->_options['locale'] != 'en') {
            try {
                $PAGE->requires->js('/lib/flatpickr/l10n/'. $this->_options['locale'] .'.js');
            } catch (moodle_exception $e) {
                // If language is not found, fall back on en.
                $this->_options['locale'] = 'en';
            }
        }

        $inputname = $this->getName();
        $time24hr = $this->_options['time24hr'] ? 'true' : 'false';
        $dateformat = get_string('strftimedate', 'langconfig');
        $timeformat = $this->_options['timeformat'];
        $datetimeformat = $dateformat . ', ' . $timeformat;
        $placeholder = (isset($this->_options['placeholder'])) ? $this->_options['placeholder'] : 'Select date...';
        // Force the timezone to server time unless the moodleform creating the datetimepicker has specified otherwise.
        $timezone = ($this->_options['timezone'] == 99) ?
                (core_date::get_server_timezone_object()->getOffset(new DateTime()) / 3600) :
                ($this->_options['timezone']);
        // Convert to correct UTC format.
        $timezoneminutes = abs($timezone - floor($timezone)) * 60;
        $timezone .= ($timezoneminutes < 10) ? (":0" . $timezoneminutes) : (":" . $timezoneminutes);

        // For compatibility with names that characters like [] created by moodleform functions such
        // as repeat_elements, we use getElementsByName instead of getElementsbyId, since HTML ids cannot contain [].
        $flatpickrdefinition = '
            flatpickr(document.getElementsByName("'. $inputname .'_flatpickr")[0], {'.
                'enableTime: true,'.
                'dateFormat: "Y-m-dTH:i'. $timezone .'",'.
                'altInput: true,'.
                'altFormat: "'. self::translate_date_tokens($datetimeformat) .'",'.
                'wrap: true,'.
                'time_24hr: '. $time24hr . ','.
                'locale: "'. $this->_options['locale'] .'",'.
                'minuteIncrement: ' . $this->_options['step'] . ','.
                'minDate: new Date('. $this->_options['startyear'] .' , 0, 1),'.
                'maxDate: new Date('. $this->_options['stopyear'] .' , 11, 31),'.
                'onReady: function(dateObj, dateStr, fp) {'.
                    'fp.altInput.name = "'.$inputname .'_flatpickr_display";'.
                '},'.
                'plugins: [new confirmDatePlugin({}), new displayTimezonePlugin({})]'.
            '});';
        $this->_elements[] = $this->createFormElement('html',
                '<div style="display: inline; margin-right: 10px;" class="flatpickr" name ="'. $inputname .'_flatpickr">');
        $this->_elements[] = $this->createFormElement('text', 'date_time_selector', '',
                array('data-input' => 'data-input', 'placeholder' => $placeholder));
        $this->_elements[] = $this->createFormElement('static', 'flatpickrscript', '',
            '<a style="text-decoration: none;" class="input-button" title="Calendar" data-toggle>
                <i class="fa fa-calendar" aria-hidden="true"></i>
            </a>' .
            // Only display the clear button if it is enabled.
            ($this->_options['clear'] ? 
            '<a style="text-decoration: none;" class="input-button" title="Clear" data-clear>
                <i class="fa fa-eraser" aria-hidden="true"></i>
            </a>' : '') . '
            </div>
            <script>
                if (window.addEventListener) {
                    window.addEventListener("load", function(){'. $flatpickrdefinition .'});
                } else {
                    // Handle IE8 and below.
                    window.attachEvent("onload", function(){'. $flatpickrdefinition .'});
                }
            </script>');
        /*
        // Get the calendar type used - see MDL-18375.
        $calendartype = \core_calendar\type_factory::get_calendar_instance();

        for ($i = 0; $i <= 23; $i++) {
            $hours[$i] = sprintf("%02d", $i);
        }
        for ($i = 0; $i < 60; $i += $this->_options['step']) {
            $minutes[$i] = sprintf("%02d", $i);
        }

        $this->_elements = array();
        $dateformat = $calendartype->get_date_order($this->_options['startyear'], $this->_options['stopyear']);
        if (right_to_left()) {   // Display time to the right of date, in RTL mode.
            $this->_elements[] = $this->createFormElement('select', 'minute', get_string('minute', 'form'),
                $minutes, $this->getAttributes(), true);
            $this->_elements[] = $this->createFormElement('select', 'hour', get_string('hour', 'form'),
                $hours, $this->getAttributes(), true);
            // Reverse date element (Should be: Day, Month, Year), in RTL mode.
            $dateformat = array_reverse($dateformat);
        }
        foreach ($dateformat as $key => $date) {
            // E_STRICT creating elements without forms is nasty because it internally uses $this
            $this->_elements[] = $this->createFormElement('select', $key, get_string($key, 'form'), $date, $this->getAttributes(), true);
        }
        if (!right_to_left()) {   // Display time to the left of date, in LTR mode.
            $this->_elements[] = $this->createFormElement('select', 'hour', get_string('hour', 'form'), $hours,
                $this->getAttributes(), true);
            $this->_elements[] = $this->createFormElement('select', 'minute', get_string('minute', 'form'), $minutes,
                $this->getAttributes(), true);
        }
        // The YUI2 calendar only supports the gregorian calendar type so only display the calendar image if this is being used.
        if ($calendartype->get_name() === 'gregorian') {
            $image = $OUTPUT->pix_icon('i/calendar', get_string('calendar', 'calendar'), 'moodle');
            $this->_elements[] = $this->createFormElement('link', 'calendar',
                    null, '#', $image,
                    array('class' => 'visibleifjs'));
        }
        */
        // END UCLA MOD: CCLE-6868.
        // If optional we add a checkbox which the user can use to turn if on
        if ($this->_options['optional']) {
            $this->_elements[] = $this->createFormElement('checkbox', 'enabled', null, get_string('enable'), $this->getAttributes(), true);
        }
        foreach ($this->_elements as $element){
            if (method_exists($element, 'setHiddenLabel')){
                $element->setHiddenLabel(true);
            }
        }

    }

    /**
     * Called by HTML_QuickForm whenever form event is made on this element
     *
     * @param string $event Name of event
     * @param mixed $arg event arguments
     * @param object $caller calling object
     * @return bool
     */
    function onQuickFormEvent($event, $arg, &$caller) {
        $this->setMoodleForm($caller);
        switch ($event) {
            case 'updateValue':
                // Constant values override both default and submitted ones
                // default values are overriden by submitted.
                $value = $this->_findValue($caller->_constantValues);
                if (null === $value) {
                    // If no boxes were checked, then there is no value in the array
                    // yet we don't want to display default value in this case.
                    if ($caller->isSubmitted()) {
                        $value = $this->_findValue($caller->_submitValues);
                    } else {
                        $value = $this->_findValue($caller->_defaultValues);
                    }
                }
                $requestvalue=$value;
                if ($value == 0) {
                    $value = $this->_options['defaulttime'];
                    if (!$value) {
                        $value = time();
                    }
                }
                if (!is_array($value)) {
                    // START UCLA MOD: CCLE-6868 - Revamp date picker.
                    // This code is called whenever the datetimepicker is initialized. It's responsible for
                    // setting the default, initial value of the datetimepicker.
                    /*
                    $calendartype = \core_calendar\type_factory::get_calendar_instance();
                    $currentdate = $calendartype->timestamp_to_date_array($value, $this->_options['timezone']);
                    // Round minutes to the previous multiple of step.
                    $currentdate['minutes'] -= $currentdate['minutes'] % $this->_options['step'];
                    $value = array(
                        'minute' => $currentdate['minutes'],
                        'hour' => $currentdate['hours'],
                        'day' => $currentdate['mday'],
                        'month' => $currentdate['mon'],
                        'year' => $currentdate['year']);
                    */
                    $value -= $value % ($this->_options['step'] * 60); // Round datetime to previous multiple of step.

                    // If DST is in effect, apply offset.
                    if (date('I', time())) {
                        $value -= 3600;
                    }

                    // Convert datetime from Unix time to UTC in server timezone.
                    $value = (new DateTime('@' . $value)); // Convert from Unix time to php DateTime.
                    $servertimezoneoffset = core_date::get_server_timezone_object()->getOffset(new DateTime());
                    $value->setTimeZone(new DateTimeZone(timezone_name_from_abbr('', $servertimezoneoffset, 0)));
                    $value = $value->format('Y-m-d\TH:i'); // Convert to UTC.

                    $value = array('date_time_selector' => $value);
                    // END UCLA MOD: CCLE-6868.
                    // If optional, default to off, unless a date was provided.
                    if ($this->_options['optional']) {
                        $value['enabled'] = $requestvalue != 0;
                    }
                } else {
                    $value['enabled'] = isset($value['enabled']);
                }
                if (null !== $value) {
                    $this->setValue($value);
                }
                break;
            case 'createElement':
                // START UCLA MOD: CCLE-6868 - Revamp date picker.
                /*
                if (isset($arg[2]['optional']) && $arg[2]['optional']) {
                    // When using the function addElement, rather than createElement, we still
                    // enter this case, making this check necessary.
                    if ($this->_usedcreateelement) {
                        $caller->disabledIf($arg[0] . '[day]', $arg[0] . '[enabled]');
                        $caller->disabledIf($arg[0] . '[month]', $arg[0] . '[enabled]');
                        $caller->disabledIf($arg[0] . '[year]', $arg[0] . '[enabled]');
                        $caller->disabledIf($arg[0] . '[hour]', $arg[0] . '[enabled]');
                        $caller->disabledIf($arg[0] . '[minute]', $arg[0] . '[enabled]');
                    } else {
                        $caller->disabledIf($arg[0], $arg[0] . '[enabled]');
                    }
                }
                */
                // When using the function addElement, rather than createElement, we still
                // enter this case, making this check necessary.
                if (isset($arg[2]['optional']) && !$this->_usedcreateelement) {
                    $caller->disabledIf($arg[0] . '[date_time_selector]', $arg[0] . '[enabled]');
                    $caller->disabledIf($arg[0] . '_flatpickr_display', $arg[0] . '[enabled]');
                }
                // END UCLA MOD: CCLE-6868.
                return parent::onQuickFormEvent($event, $arg, $caller);
                break;
            case 'addElement':
                $this->_usedcreateelement = false;
                return parent::onQuickFormEvent($event, $arg, $caller);
                break;
            default:
                return parent::onQuickFormEvent($event, $arg, $caller);
        }
    }

    /**
     * Returns HTML for advchecbox form element.
     *
     * @return string
     */
    function toHtml() {
        include_once('HTML/QuickForm/Renderer/Default.php');
        $renderer = new HTML_QuickForm_Renderer_Default();
        $renderer->setElementTemplate('{element}');
        parent::accept($renderer);

        $html = $this->_wrap[0];
        if ($this->_usedcreateelement) {
            $html .= html_writer::tag('span', $renderer->toHtml(), array('class' => 'fdate_time_selector'));
        } else {
            $html .= $renderer->toHtml();
        }
        $html .= $this->_wrap[1];

        return $html;
    }

    /**
     * Accepts a renderer
     *
     * @param HTML_QuickForm_Renderer $renderer An HTML_QuickForm_Renderer object
     * @param bool $required Whether a group is required
     * @param string $error An error message associated with a group
     */
    function accept(&$renderer, $required = false, $error = null) {
        $renderer->renderElement($this, $required, $error);
    }

    /**
     * Output a timestamp. Give it the name of the group.
     *
     * @param array $submitValues values submitted.
     * @param bool $assoc specifies if returned array is associative
     * @return array
     */
    function exportValue(&$submitValues, $assoc = false) {
        $valuearray = array();
        foreach ($this->_elements as $element){
            $thisexport = $element->exportValue($submitValues[$this->getName()], true);
            if ($thisexport!=null){
                $valuearray += $thisexport;
            }
        }
        if (count($valuearray)){
            if($this->_options['optional']) {
                // If checkbox is on, the value is zero, so go no further
                if(empty($valuearray['enabled'])) {
                    return $this->_prepareValue(0, $assoc);
                }
            }
            // START UCLA MOD: CCLE-6868 - Revamp date picker.
            /*
            // Get the calendar type used - see MDL-18375.
            $calendartype = \core_calendar\type_factory::get_calendar_instance();
            $gregoriandate = $calendartype->convert_to_gregorian($valuearray['year'],
                                                                 $valuearray['month'],
                                                                 $valuearray['day'],
                                                                 $valuearray['hour'],
                                                                 $valuearray['minute']);
            $value = make_timestamp($gregoriandate['year'],
                                                      $gregoriandate['month'],
                                                      $gregoriandate['day'],
                                                      $gregoriandate['hour'],
                                                      $gregoriandate['minute'],
                                                      0,
                                                      $this->_options['timezone'],
                                                      true);
            */

            $value = $valuearray['date_time_selector'];
            $value = strtotime($value); // Convert from UTC to unix time.

            // Make any necessary adjustments for DST.
            $currentdst = date('I', time()); // Are we currently in DST?
            $valuedst = date('I', $value); // Is the selected time in DST?
            if ($valuedst && !$currentdst) {
                $value -= 3600;
            } else if (!$valuedst && $currentdst) {
                $value += 3600;
            }

            // Validate $value. The user could have modified it in-browser.
            $value -= $value % ($this->_options['step'] * 60);
            $mincheck = strtotime('1' . ' January ' . $this->_options['startyear']) > $value;
            $maxcheck = strtotime('1' . ' January ' . $this->_options['stopyear']) < $value;
            // Silently set value to default if it failed validation.
            if (empty($value) || $mincheck || $maxcheck) {
                $value = null;
            }
            // END UCLA MOD: CCLE-6868.
            return $this->_prepareValue($value, $assoc);
        } else {
            return null;
        }
    }
}
