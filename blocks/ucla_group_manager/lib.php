<?php
// This file is part of the UCLA group management plugin for Moodle - http://moodle.org/
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
 * Class definition for the ucla_group_manager object.
 *
 * @package    block_ucla_group_manager
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/group/lib.php');
require_once($CFG->dirroot . '/enrol/database/lib.php');
require_once($CFG->dirroot . '/blocks/ucla_group_manager/ucla_synced_group.class.php');
require_once($CFG->dirroot . '/blocks/ucla_group_manager/ucla_synced_grouping.class.php');

ucla_require_registrar();

/**
 * Class file.
 *
 * @package    block_ucla_group_manager
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ucla_group_manager {

    /**
     * Queries Registrar database.
     *
     * @var local_ucla_enrollment_helper
     */
    private $enrollmenthelper;

    /**
     * Controls output to screen.
     *
     * @var progress_trace
     */
    private $trace;

    /**
     * Constructor.
     *
     * @param progress_trace $trace
     */
    public function __construct(progress_trace $trace = null) {
        if (empty($trace)) {
            $trace = new text_progress_trace();
        }
        $this->trace = $trace;
        $enrol = enrol_get_plugin('database');
        $this->enrollmenthelper = new local_ucla_enrollment_helper($this->trace, $enrol);
    }

    // These are what are used to distinguish grouping names.

    /**
     * Hierarchical groupings.
     *
     * This is at the level of the request, per request.
     *
     * @return string
     */
    public static function crosslist_name_fields() {
        return 'subj_area coursenum acttype sectnum';
    }

    /**
     * This is at the level of the request for same course number,
     * but can have more than one lecture.
     *
     * @return string
     */
    public static function course_lecture_name_fields() {
        return 'subj_area coursenum lectnum acttype';
    }

    /**
     * Bi-lateral groupings.
     *
     * This is at the level of the request's section.
     * @return string
     */
    public static function section_type_name_fields() {
        return 'acttype sectnum';
    }

    /**
     * This is at the level of the request's section.
     *
     * This is for each section, super explicit.
     *
     * @return string
     */
    public static function name_section_fields() {
        return 'subj_area coursenum acttype sectnum';
    }

    /**
     * Function to query registrar.
     *
     * @param string $sp stored procedure call
     * @param array $requestarr containing the term and srs
     * @param bool $filter
     *
     * @return array
     */
    public function query_registrar($sp, $requestarr, $filter) {
        return registrar_query::run_registrar_query($sp, $requestarr, $filter);
    }

    /**
     * Fetches section enrollments for a particular course set.
     *
     * @param int $courseid
     */
    public function course_sectionroster($courseid) {

        $requests = ucla_get_course_info($courseid);
        $debug = debugging();

        if (!$requests) {
            return false;
        }

        $requestsarr = array_map('get_object_vars', $requests);

        if ($debug) {
            $this->trace->output("course $courseid maps to " . count($requests)
                . " request(s)", 1);
        }

        $requestsectioninfos = array();

        // Get the information needed .
        foreach ($requestsarr as $reqarr) {
            $sections = $this->query_registrar(
                            'ccle_class_sections',
                            array($reqarr['term'], $reqarr['srs']),
                            true
                        );

            $this->trace->output("* " . make_idnumber($reqarr) . " has " . count($sections)
                . " sections");

            // Check this roster against section rosters to look for
            // stragglers.
            $requestroster = $this->query_registrar(
                        'ccle_roster_class',
                        array($reqarr['term'], $reqarr['srs']),
                        true
                    );

            $indexedrequestroster = array();
            foreach ($requestroster as $student) {
                if ($student['enrl_stat_cd'] != 'D' && $student['enrl_stat_cd'] != 'C') {
                    $indexedrequestroster[] = $this->enrollmenthelper->translate_ccle_roster_class($student);
                }
            }

            $reqobj = new stdclass();
            $reqobj->roster = $indexedrequestroster;
            $reqidnumber = make_idnumber($reqarr);
            $this->trace->output("* $reqidnumber has " . count($indexedrequestroster)
                . " students.");

            $reqarr['courseid'] = $courseid;
            $reqobj->courseinfo = $reqarr;

            $sectionrosters = array();
            foreach ($sections as $section) {
                // So some translating since the SP names fields strangely
                // Maybe we should have a consistent-course object?
                $section['courseid'] = $courseid;
                $section['term'] = $reqarr['term'];
                $section['srs'] = $section['srs_crs_no'];
                $section['subj_area'] = $reqarr['subj_area'];
                // This is the discussion section info.
                $section['acttype'] = $section['cls_act_typ_cd'];
                $section['sectnum'] = ltrim($section['sect_no'], '0');
                // This is the lecture section info.
                $section['lectnum'] = ltrim($reqarr['sectnum'], '0');
                $section['lectacttype'] = $reqarr['acttype'];
                // This is the course number.
                $section['coursenum'] = ltrim($reqarr['coursenum'], '0');

                $sectioninfo = new stdClass();
                $sectioninfo->courseinfo = $section;

                $termsrsarr = $section;

                $rqa = array(
                        $termsrsarr['term'], $termsrsarr['srs']
                    );

                $sectionroster = $this->query_registrar('ccle_roster_class', $rqa, true);

                $indexedsectionroster = array();

                // Filter out students that have dropped course.
                foreach ($sectionroster as $student) {
                    if ($student['enrl_stat_cd'] != 'D' && $student['enrl_stat_cd'] != 'C') {
                        $indexedsectionroster[] = $this->enrollmenthelper->translate_ccle_roster_class($student);
                    }
                }

                if ($debug) {
                    $this->trace->output("-   " . make_idnumber($termsrsarr) . ' has '
                        . count($indexedsectionroster) . " students");
                }

                $sectioninfo->roster = $indexedsectionroster;
                $sectionrosters[make_idnumber($termsrsarr)] = $sectioninfo;
            }

            $reqobj->sectionsinfo = $sectionrosters;

            $requestsectioninfos[make_idnumber($reqarr)] = $reqobj;
        }

        return $requestsectioninfos;
    }

    /**
     * Fully sets up and synchronizes groups for a course.
     *
     * @param int $courseid
     */
    public function sync_course($courseid) {

        $reqsecinfos = self::course_sectionroster($courseid);
        if ($reqsecinfos === false) {
            return true;
        }

        $isnormalcourse = count($reqsecinfos) == 1;

        $this->trace->output("Syncing section groups...");

        // Groups created should NOT be divisible in any logical way,
        // we should try to enforce usage of groupings.
        $outputstr = '';
        $syncedgroups = [];
        $registrarconfigs = get_config('enrol_database');
        foreach ($reqsecinfos as $termsrs => &$reqinfo) {
            // If there are no sections, then we want to do something later.
            if (empty($reqinfo->sectionsinfo)) {
                if ($isnormalcourse) {
                    continue;
                }

                // Otherwise, we need to treat the crosslist as a section of
                // its own.
                $outputstr .= "crosslist-forced ";
                $fakesection = new stdClass();

                $fakesection->roster = $reqinfo->roster;
                $fakesection->courseinfo = $reqinfo->courseinfo;

                $reqinfo->sectionsinfo = array($termsrs => $fakesection);
            }

            foreach ($reqinfo->sectionsinfo as $secttermsrs => &$sectioninfo) {
                $moodleusers = array();

                // TODO speed this loop up.
                foreach ($sectioninfo->roster as $student) {
                    $moodleuser = ucla_registrar_user_to_moodle_user($student, $registrarconfigs);

                    if ($moodleuser) {
                        $moodleusers[$moodleuser->id] = $moodleuser;
                    }
                }

                $sectiongroup = new ucla_synced_group(
                        $sectioninfo->courseinfo
                    );

                $sectiongroup->sync_members($moodleusers);
                $sectiongroup->save();

                $syncedgroups[] = $sectiongroup->id;
                $sectioninfo->group = $sectiongroup;

                $outputstr .= "[{$sectiongroup->id}] {$sectiongroup->name}";
                $outputstr .= "...";
            }
        }

        if (!empty($syncedgroups)) {
            $event = \block_ucla_group_manager\event\section_groups_synced::create(array(
                        'context' => context_course::instance($courseid),
                        'other' => array(
                            'groupids' => $syncedgroups
                        )
            ));
            $event->trigger();
        }

        // When we hit that next foreach, if this is not unset, for some
        // reason PHP decides to set the address that $reqinfo is pointing
        // to to be the first object of the iterating array.
        unset($reqinfo);
        unset($sectioninfo);

        $outputstr .= "done.";
        $this->trace->output($outputstr);

        $outputstr = "Deleting obsolete groups...";
        // Delete unused groups.
        $alltrackedgroups = ucla_synced_group::get_tracked_groups(
                $courseid
            );

        // Re-index this...optimization.
        $existingtrackedgroups = array();
        foreach ($alltrackedgroups as $trackedgroup) {
            $existingtrackedgroups[$trackedgroup->groupid] = $trackedgroup;
        }

        foreach ($reqsecinfos as $reqinfo) {
            foreach ($reqinfo->sectionsinfo as $sectioninfo) {
                $secgroupid = $sectioninfo->group->id;
                if (isset($existingtrackedgroups[$secgroupid])) {
                    unset($existingtrackedgroups[$secgroupid]);
                } else {
                    $this->trace->output("ERROR: Could not find recently created group!");
                }
            }
        }

        foreach ($existingtrackedgroups as $nolongertrackedgroup) {
            $delgroupid = $nolongertrackedgroup->groupid;
            groups_delete_group($delgroupid);
            $outputstr .= "[$delgroupid] {$nolongertrackedgroup->name}...";
        }

        $outputstr .= "done.";
        $this->trace->output($outputstr);

        // Create groupings.

        // Set of all tracked groupids in course.
        $alltrackedgroupids = array();
        foreach ($alltrackedgroups as $trackedgroup) {
            $alltrackedgroupids[] = $trackedgroup->groupid;
        }

        sort($alltrackedgroupids, SORT_NUMERIC);

        // All the tracked groupings, used when checking which existing
        // tracked groupings to delete.
        $trackedgroupings = array();

        // Groupings.
        $classmethods = get_class_methods('ucla_group_manager');

        $groupingspecfns = array();

        // Dyanmically figure out which groups to create.
        foreach ($classmethods as $classmethod) {
            $matches = array();
            // Get the type, and the function name.
            if (preg_match('/(.*)_name_fields$/', $classmethod, $matches)) {
                $groupingspecfns[$matches[1]] = $classmethod;
            }
        }

        $outputstr = '';
        foreach ($groupingspecfns as $groupingtype => $groupingtypefn) {
            $organizedgroupings = array();

            $outputstr .= "Syncing groupings based on \"$groupingtype\"...";

            foreach ($reqsecinfos as $reqinfo) {
                foreach ($reqinfo->sectionsinfo as $sectioninfo) {
                    $groupfieldsid = self::get_grouping_type_key(
                                $groupingtypefn, $sectioninfo->courseinfo
                            );

                    if (!isset($organizedgroupings[$groupfieldsid])) {
                        $organizedgroupings[$groupfieldsid] = array();
                    }

                    $organizedgroupings[$groupfieldsid][] = $sectioninfo->group;
                }
            }

            foreach ($organizedgroupings as $groupingname => $groups) {
                $groupingdata = ucla_synced_grouping::get_groupingdata(
                        $groupingname, $courseid
                    );

                // We want to avoid groupings that just has ALL groups.
                // There is an unoptimization, can't simply compare
                // equality for an array of groups.
                $groupids = array();
                foreach ($groups as $group) {
                    $groupids[] = $group->id;
                }

                sort($groupids, SORT_NUMERIC);
                if ($groupids == $alltrackedgroupids) {
                    $outputstr .= "skip-all-group-grouping "
                        . $groupingdata->name . '...';
                    continue;
                }

                $trackedgrouping =
                    ucla_synced_grouping::create_tracked_grouping(
                            $groupingdata, $groups
                        );

                $sameflag = '';
                if (!isset($trackedgroupings[$trackedgrouping])) {
                    $trackedgroupings[$trackedgrouping] = true;
                } else {
                    $sameflag = 'repeat-';
                }

                $outputstr .= '[' . $sameflag . $trackedgrouping . '] '
                    . $groupingdata->name . "...";

            }

            $outputstr .= "done.";
            $this->trace->output($outputstr);
            $outputstr = '';
        }

        // Remove no-longer used groupings.
        $outputstr = "Deleting obsolete groupings...";
        $coursetrackedgroupings =
                ucla_synced_grouping::get_course_tracked_groupings(
                        $courseid
                    );

        foreach ($coursetrackedgroupings as $ctg) {
            if (!isset($trackedgroupings[$ctg->id])) {
                groups_delete_grouping($ctg->id);
                $outputstr .= "[{$ctg->id}] {$ctg->name}...";

            }
        }

        $outputstr .= "done.";
        $this->trace->output($outputstr);

        return true;
    }

    /**
     * This is how we distinguish sections.
     *
     * @param string $fn    Function name.
     * @param array $info
     */
    public static function get_grouping_type_key($fn, $info) {
        $fieldsused = explode(' ', self::$fn());

        $namestrs = array();
        foreach ($fieldsused as $field) {
            if (isset($info[$field])) {
                $namestrs[] = $info[$field];
            }
        }

        return implode(' ', $namestrs);
    }

    /**
     * Passed a grouping id or either a grouping record object or array, returns
     * true if the grouping was auto-generated by a group manager to correspond
     * to a course section, false otherwise.
     *
     * @param int|object|array $group
     * @return boolean
     */
    public static function is_auto_grouping($grouping) {
        global $DB;
        $groupingid = is_scalar($grouping)
                ? $grouping
                : (is_object($grouping) && isset($grouping->id)
                    ? $grouping->id
                    : (is_array($grouping) && isset($grouping['id'])
                            ? $grouping['id']
                            : false));

        return $groupingid !== false && $DB->record_exists('ucla_group_groupings', array('groupingid' => $groupingid));
    }
}
