<?php
/**
 * Block MHAAIRS Improved
 *
 * @package    block
 * @subpackage mhaairs
 * @copyright  2013 Moodlerooms inc.
 * @author     Teresa Hardy <thardy@moodlerooms.com>
 */

class block_mhaairs_edit_form extends block_edit_form {

    protected function specific_definition($mform) {
        global $CFG;

        // Section header title according to language file.
        $mform->addElement('header',
                           'configheader',
                           get_string('availableservices', 'block_mhaairs'));

        if (empty($CFG->block_mhaairs_display_services)) {
            $content = html_writer::tag('div',
                                        get_string('noservicesmsg', 'block_mhaairs'),
                                        array('class' => 'block_mhaairs_warning notifyproblem'));
            $mform->addElement('html', $content);
            return;
        }

        $first = true;
        $services_arr = array_map('trim', explode(',', $CFG->block_mhaairs_display_services));
        natcasesort($services_arr);
        $prelabel = get_string('edit_prelabel', 'block_mhaairs');
        foreach ($services_arr as $service) {
            $settingname = "config_$service";
            $mform->addElement('advcheckbox',
                               $settingname,
                               $prelabel,
                               "&nbsp;$service");
            $mform->setDefault($settingname, 1);
            if ($first) {
                $prelabel = '&nbsp;';
                $first = false;
            }
        }
    }

}
