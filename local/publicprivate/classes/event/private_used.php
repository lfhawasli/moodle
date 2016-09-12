<?php
// This file is part of UCLA public/private plugin for Moodle - http://moodle.org/
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
 * Contains the event class for when a course module is set to private.
 *
 * @package    local_publicprivate
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_publicprivate\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Records when a course module is set to private.
 *
 * @package    local_publicprivate
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class private_used extends \core\event\base {

    /**
     * Creates the event.
     */
    protected function init() {
        $this->data['crud'] = 'u'; // c(reate), r(ead), u(pdate), d(elete)
        $this->data['edulevel'] = self::LEVEL_TEACHING;
    }

    /**
     * Returns the name of the event.
     * @return string
     */
    public static function get_name() {
        return get_string('eventprivate_used', 'local_publicprivate');
    }

    /**
     * Returns a short description for the event.
     * @return string
     */
    public function get_description() {
        return "User '{$this->userid}' set the module to private in course with id '{$this->courseid}'.";
    }

    /**
     * Returns URL to the course page.
     * @return moodle_url
     */
    public function get_url() {
        $context = $this->get_context();
        if (!empty($context)) {
            return new \moodle_url($context->get_url());
        }
        return "";
    }

    /**
     * Add data to legacy log.
     * @return array
     */
    public function get_legacy_logdata() {
        return array($this->courseid, 'course', 'public/private',
            '/course/view.php?id=' . $this->courseid, '');
    }
}