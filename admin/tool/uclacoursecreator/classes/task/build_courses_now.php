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
 * Build courses now task.
 *
 * @package    tool_uclacoursecreator
 * @copyright  2016 UC Regent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_uclacoursecreator\task;

defined('MOODLE_INTERNAL') || die();
require_once(dirname(__FILE__) . '/../../uclacoursecreator.class.php');

/**
 * Class file.
 *
 * @package    tool_uclacoursecreator
 * @copyright  2016 UC Regent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class build_courses_now extends \core\task\adhoc_task {

    /**
     * Respond to events that require course creator to build now.
     *
     * @param array $terms  An array of terms to run course builder for
     */
    public function execute() {
        $bcc = new \uclacoursecreator();

        // This may take a while...
        @set_time_limit(0);

        $bcc->set_terms($this->get_custom_data());
        $bcc->cron();
    }
}