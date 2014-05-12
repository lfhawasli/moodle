<?php
/**
 * Webservice test client for MHAAIRS Gradebook Integration
 *
 * @package    block
 * @subpackage mhaairs
 * @copyright  2013 Moodlerooms inc.
 * @author     Teresa Hardy <thardy@moodlerooms.com>
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

class block_mhaairs_generic_form extends moodleform {

    /**
     * Offers posibility to add elements to the form
     * @param MoodleQuickForm $mform
     */
    protected function custom_definition(MoodleQuickForm &$mform) {

    }

    /**
     * generate web service parameters
     * @param object $data
     * @return array
     */
    protected function format_params($data) {
        return array();
    }

    public function definition() {
        global $CFG;

        $mform = $this->_form;

        $mform->addElement('header', 'mhtestclienthdr', get_string('testclient', 'webservice'));

        //note: these values are intentionally PARAM_RAW - we want users to test any rubbish as parameters
        $data = $this->_customdata;
        if ($data['authmethod'] == 'simple') {
            $mform->addElement('text', 'mhusername', 'mhusername');
            $mform->addElement('text', 'mhpassword', 'mhpassword');
        } else  if ($data['authmethod'] == 'token') {
            $mform->addElement('text', 'token', 'token', array('size' => '32'));
        }

        $mform->addElement('hidden', 'authmethod', $data['authmethod']);
        $mform->setType('authmethod', PARAM_SAFEDIR);

        $this->custom_definition($mform);

        $mform->addElement('hidden', 'function');
        $mform->setType('function', PARAM_SAFEDIR);

        $mform->addElement('hidden', 'protocol');
        $mform->setType('protocol', PARAM_SAFEDIR);

        $this->add_action_buttons(true, get_string('execute', 'webservice'));
    }

    public function get_params() {
        if (!$data = $this->get_data()) {
            return null;
        }

        //set customfields
        if (!empty($data->customfieldtype)) {
            $data->customfields = array(array('type' => $data->customfieldtype, 'value' => $data->customfieldvalue));
        }

        // remove unused from form data
        unset($data->submitbutton);
        unset($data->protocol);
        unset($data->function);
        unset($data->mhusername);
        unset($data->mhpassword);
        unset($data->token);
        unset($data->authmethod);
        unset($data->customfieldtype);
        unset($data->customfieldvalue);

        $params = $this->format_params($data);

        return $params;
    }
}


class block_mhaairs_gradebookservice_form extends block_mhaairs_generic_form {

    protected function custom_definition(MoodleQuickForm &$mform) {
        $mform->addElement('text', 'source', 'source of grade');
        $mform->addElement('text', 'courseid', 'course id');
        $mform->addElement('text', 'itemtype', 'type of item');
        $mform->addElement('text', 'itemmodule', 'module');
        $mform->addElement('text', 'iteminstance', 'instance');
        $mform->addElement('text', 'itemnumber', 'number');
        $mform->addElement('text', 'grades', 'grades');
        $mform->addElement('text', 'itemdetails', 'item details');
    }

    protected function format_params($data) {
        return (array)$data;
    }
}

