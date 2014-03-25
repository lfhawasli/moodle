<?php
/**
 * Block MHAAIRS Improved
 *
 * @package    block
 * @subpackage mhaairs
 * @copyright  2013 Moodlerooms inc.
 * @author     Teresa Hardy <thardy@moodlerooms.com>
 */

require_once($CFG->libdir.'/blocklib.php');
require_once($CFG->dirroot.'/blocks/mhaairs/lib.php');
require_once($CFG->dirroot.'/blocks/mhaairs/block_mhaairs_util.php');

class block_mhaairs extends block_base {
    const LINK_SERVICES = 'services';
    const LINK_HELP     = 'help';

    public function init() {
        $this->title = get_string('pluginname', __CLASS__, null, true);
    }

    public function has_config() {
        return true;
    }

    public function get_content() {
        global $CFG, $COURSE, $USER;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        if (!isloggedin()) {
            return $this->content;
        }

        $context = context_course::instance($COURSE->id);
        $msg_html = '';
        $can_manipulate_block = has_capability('block/mhaairs:addinstance', $context);
        if ($can_manipulate_block) {
            $msg_html = html_writer::tag('div',
                                         get_string('blocknotconfig', __CLASS__),
                                         array('class' => 'block_mhaairs_warning'));
            $msg_html .= html_writer::empty_tag('br');
        }

        $this->content->text = $msg_html;

        if (empty($CFG->block_mhaairs_customer_number) ||
            empty($CFG->block_mhaairs_shared_secret)   ||
            empty($CFG->block_mhaairs_base_address)
           ) {
            return $this->content;
        }

        $servicelinks = $this->get_service_links();
        if ($servicelinks === false) {
            return $this->content;
        }

        $block_links = '';
        $imagealt = get_string('imagealt');
        $targetw = array('target' => '_blank');
        foreach ($servicelinks as $aserv) {
            $block_links  .= html_writer::tag('img', '',
                                              array('src' => $aserv['ServiceIconUrl'],
                                                    'height' => '16',
                                                    'width' => '16',
                                                    'hspace' => '4',
                                                    'alt' => $imagealt));
            $lurl = new moodle_url('../blocks/mhaairs/redirect.php', array('url' => mh_hex_encode($aserv['ServiceUrl']), 'id' => mh_hex_encode($aserv['ServiceID'])));
            $block_links .= html_writer::link($lurl->out(false), $aserv['ServiceName'], $targetw);
            $block_links .= html_writer::empty_tag('br');
        }
        // START UCLA MOD: CCLE-4453 - Install and evaluate McGraw-Hill Campus
		//session_start();
        // END UCLA MOD: CCLE-4453
		$_SESSION['course'] = $COURSE;

        $this->content->text = $block_links;

        // Can we see the help links at all?
        $adminhelp = has_capability('block/mhaairs:viewadmindoc', $context);
        $teacherhelp = has_capability('block/mhaairs:viewteacherdoc', $context);
        if (empty($CFG->block_mhaairs_display_helplinks) || !($adminhelp || $teacherhelp)) {
            return $this->content;
        }

        // Get Help links
        $helplinks = block_mhaairs_getlinks(self::LINK_HELP);
        if ($helplinks === false) {
            $this->content->footer = $msg_html;
            return $this->content;
        }

        // Show help links
        if ($adminhelp) {
            $adminhelplink = html_writer::link($helplinks['AdminHelpUrl'],
                                                get_string('adminhelplabel', __CLASS__),
                                                $targetw);
            $this->content->footer .= html_writer::empty_tag('br');
            $this->content->footer .= $adminhelplink;
        }

        if ($teacherhelp) {
            $instrhelplink = html_writer::link($helplinks['InstructorHelpUrl'],
                                               get_string('instrhelplabel', __CLASS__),
                                               $targetw);
            $this->content->footer .= html_writer::empty_tag('br');
            $this->content->footer .= $instrhelplink;
        }

        return $this->content;
    }

    public function get_service_links() {
        global $CFG;

        $result = false;
        if (empty($CFG->block_mhaairs_display_services)) {
            return $result;
        }

        // Get the links.
        $services = block_mhaairs_getlinks(self::LINK_SERVICES);
        if ($services === false) {
            return $result;
        }

        // Show the links
        $permittedlist = explode(',', $CFG->block_mhaairs_display_services);
        asort($permittedlist);
        $finallist = $permittedlist;
        if (!empty($this->config)) {
            $local_elements = array_keys(get_object_vars($this->config), true);
            if (empty($local_elements)) {
                return $result;
            }
            $finallist = array_intersect($permittedlist, $local_elements);
        }
        natcasesort($finallist);

        $result = array();
        foreach ($finallist as $serviceid) {
            foreach ($services['Tools'] as $vset) {
                if ($vset['ServiceID'] == $serviceid) {
                    $result[] = $vset;
                }
            }
        }

        return $result;
    }
}
