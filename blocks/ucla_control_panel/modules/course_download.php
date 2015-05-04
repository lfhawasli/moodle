<?php
// This file is part of the UCLA control panel for Moodle - http://moodle.org/
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

defined('MOODLE_INTERNAL') || die();

class ucla_cp_module_course_download extends ucla_cp_module {

    /**
     * Returns capability that protects this feature.
     *
     * @return string
     */
    public function autocap() {
        return 'block/ucla_course_download:requestzip';
    }

    /**
     * Need to override this so that we can display module with no link.
     *
     * @return boolean
     */
    public function is_tag() {
        return false;
    }
}
