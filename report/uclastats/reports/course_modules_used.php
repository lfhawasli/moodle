<?php
/**
 * Report to get the number of course modules used for course sites for a given
 * term.
 *
 * @package    report
 * @subpackage uclastats
 * @copyright  UC Regents
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/report/uclastats/locallib.php');

class course_modules_used extends uclastats_base {
    /**
     * Returns an array of form elements used to run report.
     */
    public function get_parameters() {
        return array('term');
    }

    /**
     * Query for course modules used for by courses for given term
     *
     * @param array $params
     * @param return array
     */
    public function query($params) {
        global $DB;

        // make sure that term parameter exists
        if (!isset($params['term']) ||
                !ucla_validator('term', $params['term'])) {
            throw new moodle_exception('invalidterm', 'report_uclastats');
        }

        $sql = "SELECT  m.name AS module,
               COUNT(cm.id) AS count"
                . $this->from_filtered_courses() .
                "
                JOIN    {course_modules} cm ON
                        (cm.course = c.id)
                JOIN    {modules} m ON
                        (m.id = cm.module)
                WHERE cm.deletioninprogress=0
                GROUP BY m.id
                ORDER BY m.name";
        $results = $DB->get_records_sql($sql, $params);
        foreach ($results as &$result) {
           $result->module=get_string('pluginname', 'mod_' . $result->module); 
        }

        // Find how many assignments are using TurnItIn.
        $sql = "SELECT count(ptc.id)"
            . $this->from_filtered_courses() .
            "     JOIN {course_modules} cm ON c.id = cm.course
                  JOIN {plagiarism_turnitin_config} ptc ON (ptc.cm=cm.id)
                 WHERE ptc.name='use_turnitin' 
                   AND ptc.value=1
                   AND cm.deletioninprogress=0";
        $assignturnitin = $DB->get_field_sql($sql, $params);

        // Add in Turnitin plagiarism.
        if (!empty($assignturnitin) && isset($results['assign'])) {
            $results['assign']->count -= $assignturnitin;
            $turnitinobj = new stdClass();
            $turnitinobj->module = get_string('assignturnitin', 'report_uclastats');
            $turnitinobj->count = $assignturnitin;
            $results['assignturnitin'] = $turnitinobj;
        }

        array_alphasort($results, 'module');
        return $results;
    }
}
