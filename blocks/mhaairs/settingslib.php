<?php
/**
 * Block MHAAIRS Improved
 *
 * @package    block
 * @subpackage mhaairs
 * @copyright  2013 Moodlerooms inc.
 * @author     Teresa Hardy <thardy@moodlerooms.com>
 * @author     Darko Miletic
 */

require_once($CFG->dirroot.'/blocks/mhaairs/lib.php');

class admin_setting_configmulticheckbox_mhaairs extends admin_setting_configmulticheckbox {

    public function __construct($name, $heading, $description) {
        parent::__construct($name, $heading, $description, null, null);
    }

    public function load_choices() {
        if (is_array($this->choices)) {
            return true;
        }
        $result = false;
        $services = block_mhaairs_getlinks('services');
        if (is_array($services) && isset($services['Tools'])) {
            foreach ($services['Tools'] as $item) {
                $choices[$item['ServiceID']] = '&nbsp;&nbsp;'.$item['ServiceName'];
            }
            asort($choices);
            $this->choices = $choices;
            $result = true;
        }
        return $result;
    }

    public function output_html($data, $query='') {
        if ($this->load_choices()) {
            return parent::output_html($data, $query);
        }

        $visiblename = get_string('services_displaylabel', 'block_mhaairs');
        $description = get_string('service_down_msg'     , 'block_mhaairs');
        return format_admin_setting($this, $visiblename, '', $description, false, '', '');
    }
}
