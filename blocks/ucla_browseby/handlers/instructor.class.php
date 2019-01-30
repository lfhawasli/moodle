<?php
// This file is part of the UCLA browse-by plugin for Moodle - http://moodle.org/
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
 * Class file to handle Browse-By instructor listings.
 *
 * @package    block_ucla_browseby
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class definition for browsing by instructor.
 *
 * @package    block_ucla_browseby
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class instructor_handler extends browseby_handler {
    /**
     * Returns what parameters are required for this handler.
     *
     * @return Array('alpha') strings that we should require_param.
     **/
    public function get_params() {
        return array('alpha');
    }

    /**
     * Builds a sub-table that combines all available users, limited
     * by local machine.
     **/
    static public function combined_select_sql_helper() {
        $sql = "(
            SELECT
                us.id       AS userid,
                us.idnumber AS idnumber,
                us.firstname,
                us.lastname,
                us.alternatename,
                us.firstnamephonetic,
                us.lastnamephonetic,
                us.middlename,
                term,
                srs,
                profcode
            FROM {user} us
            INNER JOIN {ucla_browseall_instrinfo} ubii
                ON ubii.uid = us.idnumber
            WHERE
                us.suspended = 0
                AND us.deleted = 0
        )";

        return $sql;
    }

    /**
     * Breadcrumb navigation.
     **/
    static public function alter_navbar() {
        global $PAGE;

        // The breadcrumb logic is kind of disorganized.
        $urlobj = clone($PAGE->url);
        $urlobj->remove_params('alpha');
        $urlobj->params(array('type' => 'instructor'));
        $PAGE->navbar->add(get_string('instructorsall',
                'block_ucla_browseby'), $urlobj);
    }

    /**
     * Fetches a list of instructors with an alphabetized index.
     *
     * @param array $args
     **/
    public function handle($args) {
        global $OUTPUT, $PAGE;
        $cache = cache::make('block_ucla_browseby', 'browsebycache');
        $s = '';

        $params = array();
        $term = false;
        $termwhere = '';
        if (isset($args['term'])) {
            $term = $args['term'];
            $params['term'] = $term;
            $termwhere = 'WHERE ubi.term = :term';
            $prettyterm = ucla_term_to_text($term);
        }

        // Used for restricting choices.
        $letter = null;
        $letterwhere = '';

        // These are the letters that are available for filtering.
        $lastnamefl = array();
        for ($l = 'A'; $l <= 'Z' && strlen($l) == 1; $l++) {
            // Do we need to strtoupper, for super duper safety?
            $lastnamefl[$l] = false;
        }

        // Figure out what letters we're displaying.
        if (isset($args['alpha'])) {
            $rawletter = $args['alpha'];

            $letter = strtoupper($rawletter);
            $params['letter'] = $letter . '%';
            $letterwhere = "WHERE us.lastname like :letter";

            if ($term) {
                $t = get_string('instructorswith', 'block_ucla_browseby',
                    $letter);
            }

            self::alter_navbar();
        }

        if (!isset($t)) {
            $t = get_string('instructorsall', 'block_ucla_browseby');
        }

        // Check cache for title and contents.
        $cachekey = str_replace(' ', '_', implode('_', $args));
        $users = $cache->get('users_' . $cachekey);

        if (!$users) {
            // Show all users form local and browseall tables
            // CCLE-3989 - Supervising Instructor Shown On Course List:
            // Filter out instructors of type '03' (supervising instructor)
            // in WHERE clause.
            $sql = "
                SELECT
                    CONCAT(
                        ubi.userid, '-', ubi.term, '-', ubi.srs
                    ) AS rsid,
                    ubi.userid,
                    ubi.term,
                    ubi.srs,
                    ubi.firstname,
                    ubi.lastname,
                    ubi.alternatename,
                    ubi.firstnamephonetic,
                    ubi.lastnamephonetic,
                    ubi.middlename,
                    ubci.catlg_no AS course_code,
                    ubci.activitytype,
                    ubci.subjarea
                FROM " . self::combined_select_sql_helper() . " ubi
                JOIN {ucla_browseall_classinfo} ubci
                    USING (term, srs)
                $termwhere
                AND ubi.profcode != '03'
                ORDER BY ubi.lastname, ubi.firstname
            ";
            $users = $this->get_records_sql($sql, $params);

            // Cache instructors.
            $cache->set('users_' . $cachekey, $users);
        }

        $nodisplayhack = 0;
        foreach ($users as $k => $user) {
            if (isset($user->profcode) && $user->profcode == '03') {
                $users[$k]->no_display = true;
                $nodisplayhack++;
                continue;
            }

            $user->fullname = fullname($user);
            $lnletter = core_text::strtoupper(substr($user->lastname, 0, 1));

            // If a term is selected and we need to limit instructor last
            // name letter choices.
            $lastnamefl[$lnletter] = true;

            if ($letter !== null && $lnletter != $letter) {
                unset($users[$k]);
            }
        }

        $lettertable = array();
        foreach ($lastnamefl as $lnletter => $exists) {
            if ($exists) {
                $urlobj = clone($PAGE->url);
                $urlobj->params(array('alpha' => strtolower($lnletter)));
                $content = html_writer::link($urlobj, ucwords($lnletter));
            } else {
                $content = html_writer::tag('span',
                    $lnletter, array('class' => 'dimmed_text'));
            }

            $lettertable[$lnletter] = $content;
        }

        // Query for available terms (for the terms dropdown)
        // Filter by division, if a division selected.
        $sql = "SELECT DISTINCT term
                FROM {user} us
                INNER JOIN {ucla_browseall_instrinfo} ubii
                    ON ubii.uid = us.idnumber
                $letterwhere";

        $s .= block_ucla_browseby_renderer::render_terms_selector($term,
            $sql, $params);

        // This case can be reached if the current term has no instructors.
        if (empty($users) || count($users) == $nodisplayhack) {
            $s .= $OUTPUT->notification(get_string('noinstructors',
                    'block_ucla_browseby'));
            return array($t, $s);
        } else {
            if ($letter == null) {
                $s .= html_writer::tag('div', get_string(
                    'selectinstructorletter', 'block_ucla_browseby'));
            }

            $s .= block_ucla_browseby_renderer::ucla_custom_list_render(
                $lettertable, 0, 1, 'instructor-list');

            if ($letter !== null) {
                $table = $this->list_builder_helper($users, 'userid',
                    'fullname', 'course', 'user', $term);

                $s .= block_ucla_browseby_renderer::ucla_custom_list_render(
                    $table);
            }
        }

        return array($t, $s);
    }
}
