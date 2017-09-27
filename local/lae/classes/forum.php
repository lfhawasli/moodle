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
 * Helps with forum core edits.
 *
 * @package    local_lae
 * @copyright  2017 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Contains helper functions for anonymous forums.
 *
 * @package    local_lae
 * @copyright  2017 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_lae_forum {

    /**
     * Determines value of anonymous flag for logging event.
     *
     * @param object $forum
     * @param object $post  If null, then do not have access to post data.
     * @return boolean
     */
    static function get_anonymous_logging_flag($forum, $post = null) {
        $anonymousflag = 0;
        // Do not visibly log if forum is always anonymous or post is anonymous.
        if ($forum->anonymous == FORUM_ANONYMOUS_ALWAYS ||
                ($forum->anonymous == FORUM_ANONYMOUS_ALLOWED &&
                (isset($post->anonymous) && $post->anonymous))) {
            $anonymousflag = 1;
        } else if ($forum->anonymous == FORUM_ANONYMOUS_ALLOWED && empty($post)) {
            // Do not have access to post data, so always hide.
            $anonymousflag = 1;
        }
        return $anonymousflag;
    }
    
}