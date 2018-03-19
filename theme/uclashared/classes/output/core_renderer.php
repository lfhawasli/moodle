<?php
// This file is part of the UCLA shared theme for Moodle - http://moodle.org/
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

namespace theme_uclashared\output;

defined('MOODLE_INTERNAL') || die;

/**
 * UCLA specific renderers and overrides Boost renders.
 *
 * @package    theme_uclashared
 * @copyright  2018 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class core_renderer extends \theme_boost\output\core_renderer {
    /**
     * Wrapper to get_config to prevent crashes during initial install.
     *
     * @param string $plugin
     * @param string $var
     * @return mixed    Returns false if during initial install.
     */
    public function get_config($plugin, $var) {
        if (!during_initial_install()) {
            return get_config($plugin, $var);
        }
        return false;
    }

    /**
     * Returns which environment we are running.
     *
     * @return string   Either prod, stage, test, or dev.
     */
    public function get_environment() {
        $c = $this->get_config('theme_uclashared', 'running_environment');
        if (!$c) {
            return 'prod';
        }
        return $c;
    }

    /**
     * Returns copyright information used in footer.
     *
     * @return string
     */
    public function copyright_info() {
        return get_string('copyright_information', 'theme_uclashared', date('Y'));
    }

    /**
     * Returns string of links to be used in footer.
     *
     * @return string
     */
    public function footer_links() {

        $links = array(
            'contact_ccle',
            'about_ccle',
            'privacy',
            'copyright',
            'uclalinks',
            'separator',
            'school',
            'registrar',
            'myucla',
            'disability',
            'caps'
        );

        $footerstring = '';

        $opennewwindow = false;
        foreach ($links as $link) {

            if ($link == 'separator') {
                $footerstring .= \html_writer::tag('li', ' | ');
                $opennewwindow = true;
            } else {
                $linkdisplay = get_string('foodis_' . $link, 'theme_uclashared');
                $linkhref = get_string('foolin_' . $link, 'theme_uclashared');
                if (empty($opennewwindow)) {
                    $params = array('href' => $linkhref);
                } else {
                    $params = array('href' => $linkhref, 'target' => '_blank');
                }

                $linka = \html_writer::tag('a', $linkdisplay, $params);

                $footerstring .= \html_writer::tag('li', $linka);
            }
        }
        return $footerstring;
    }
}
