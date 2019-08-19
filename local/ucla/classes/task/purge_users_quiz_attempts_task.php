<?php
// This file is part of the UCLA local_ucla plugin for Moodle - http://moodle.org/
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
 * Purges quiz attempts made by users that are no longer enrolled.
 *
 * @package    local_ucla
 * @copyright  2019 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_ucla\task;
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/local/ucla/lib.php');
require_once($CFG->dirroot.'/mod/quiz/locallib.php');

use context_course;
/**
 * Task class.
 *
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class purge_users_quiz_attempts_task extends \core\task\scheduled_task {

    /**
     * Executes the task.
     *
     * @throws \Exception on error
     */
    public function execute() {
        global $CFG, $DB;
        
        $listofcourses = get_config('local_ucla', 'purgefromcourses');
        $arrayofcourses = array_map('trim', explode(',', $listofcourses));

        foreach ($arrayofcourses as $courseshortname) {

            // Get courseid from shortname
            if (!$courseid = $DB->get_field('course', 'id', array('shortname' => $courseshortname))) {
                mtrace("Cannot find course with shortname {$courseshortname}. Skipping this course.");
                continue;
            }

            // Get all enrolled users and put in array
            $context = context_course::instance($courseid);
            $participants = get_enrolled_users($context, '', 0, 'u.*', null, 0, 0, true);

            $enrolledusers = [];
            foreach ($participants as $participant) {
                $enrolledusers[] = $participant->id;
            }

            // Prepare array for list
            $enrolleduserslist = implode( ',', $enrolledusers );

            // Get attempts whose user ids are not in the enrolled list     
            $sql = "SELECT qa.*
                      FROM mdl_quiz AS q
                 LEFT JOIN mdl_quiz_attempts AS qa ON q.id = qa.quiz 
                     WHERE course = :courseid 
                           AND userid NOT IN (".$enrolleduserslist.")
                     ORDER BY qa.quiz";

            $parameters = array('courseid' => $courseid);
            $attemptstopurge = $DB->get_records_sql($sql, $parameters);

            // Delete attempts
            if ($attemptstopurge) {

                $quizdata = [];
                foreach ($attemptstopurge as $attempt) {

                    if (in_array($attempt->quiz, $quizdata)) {
                        $quiz = $quizdata[$attempt->quiz];
                    } else {
                        $quiz = $DB->get_record('quiz', ['id' => $attempt->quiz], '*', MUST_EXIST);
                        $quizdata[$quiz->id] = $quiz;
                    }

                    quiz_delete_attempt($attempt, $quiz);
                    mtrace("Deleting from course {$courseshortname}, attemptid {$attempt->id} with quizid {$quiz->id}");
                }
            }
        }
        mtrace("End of course list. Exiting cron.");
    }

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('purgeusersquizattempts', 'local_ucla');
    }
}
