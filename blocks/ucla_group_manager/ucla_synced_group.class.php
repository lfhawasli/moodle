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
 * Handles membership to UCLA course section groups.
 *
 * @package    block_ucla_group_manager
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/group/lib.php');

/**
 * Class file.
 *
 * @package    block_ucla_group_manager
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ucla_synced_group {
    /**
     * Internal caching mechanism, used when deleting group enrolments.
     *
     * @var array
     */
    private $membershipids = array();

    /**
     * This is the tracked memberships.
     *
     * @var array
     */
    public $memberships = array();

    /**
     * Memberships that need to be removed.
     *
     * @var array
     */
    private $removedmemberships = array();
    
    /**
     * Contains term, srs, courseid.
     *
     * @var array
     */
    private $sectioninfo;

    /**
     * Creates a new tracked group.
     *
     * @param array $sectioninfo
     * @param bool $autoload
     */
    public function __construct($sectioninfo, $autoload=true) {
        $this->term = $sectioninfo['term'];
        $this->srs = $sectioninfo['srs'];
        $this->courseid = $sectioninfo['courseid'];
        $this->idnumber = $this->srs;

        $this->sectioninfo = $sectioninfo;

        if ($autoload) {
            $this->load();
        }
    }

    /**
     * Add user to group.
     *
     * @param int $moodleuserid
     * @param int $membershipid
     */
    public function add_membership($moodleuserid, $membershipid=null) {
        if (!isset($this->memberships[$moodleuserid])) {
            $this->memberships[$moodleuserid] = $moodleuserid;
        }

        if ($membershipid !== null) {
            $this->membershipids[$moodleuserid] = $membershipid;
        }
    }

    /**
     * Adds user to moodle group and returns ucla_group_members id or false
     *
     * @param int $moodleuserid
     * @return int/bool Returns newly added record id or the existing id.
     *                  Returns false on an error.
     */
    public function create_membership($moodleuserid) {
        global $DB;

        $groupid = $this->id;
        groups_add_member($groupid, $moodleuserid);

        // Since the above public function returns nothing, have to go in and
        // find the group enrolment.
        $groupmember = $DB->get_record('groups_members',
            array('groupid' => $groupid, 'userid' => $moodleuserid));
        if (!$groupmember) {
            return false;
        }

        return self::new_membership($groupmember->id);
    }

    /**
     * Delete member from group.
     *
     * @param int $membershipid
     */
    public static function delete_membership($membershipid) {
        global $DB;

        $DB->delete_records('ucla_group_members',
            array('id' => $membershipid));
    }

    /**
     * Gets all the tracked group info.
     *
     * @param int $courseid
     * @return array
     */
    public static function get_tracked_groups($courseid) {
        global $DB;

        return $DB->get_records_sql('
            SELECT ugs.*, g.name, g.courseid
              FROM {ucla_group_sections} ugs
        INNER JOIN {groups} g ON g.id = ugs.groupid
             WHERE g.courseid = ?', array($courseid));
    }
    
    /**
     * Gets group memberships, but only those with data about ucla
     * group membership tracking.
     *
     * @param int $groupid
     * @return array
     */
    public static function get_tracked_memberships($groupid) {
        global $DB;

        return $DB->get_records_sql(
            'SELECT u.*, ugm.id AS ucla_tracked_id
               FROM {groups_members} gm
              INNER JOIN {user} u
                 ON u.id = gm.userid
         INNER JOIN {ucla_group_members} ugm
                 ON ugm.groups_membersid = gm.id
              WHERE gm.groupid = ?', array($groupid)
        );
    }

    /**
     * Loads group membership.
     *
     * @return boolean
     */
    public function load() {
        global $DB;

        $params = array(
                'term' => $this->term,
                'srs' => $this->srs,
                'courseid' => $this->courseid
            );

        $dbsaved = $DB->get_record_sql('
            SELECT gr.*, ugs.id AS ucla_group_sections_id
              FROM {groups} gr
        INNER JOIN {ucla_group_sections} ugs
                ON ugs.groupid = gr.id
             WHERE ugs.term = :term AND
                   ugs.srs = :srs AND
                   gr.courseid = :courseid', $params);

        if (!$dbsaved) {
            return false;
        }

        foreach ($dbsaved as $field => $val) {
            if ($field == 'idnumber') {
                continue;
            }
            $this->{$field} = $val;
        }

        $this->load_members();
    }

    /**
     * Loads group members.
     */
    public function load_members() {
        if (!isset($this->id)) {
            return;
        }

        $groupmembers = self::get_tracked_memberships($this->id);

        foreach ($groupmembers as $groupmember) {
            $this->add_membership($groupmember->id,
                $groupmember->ucla_tracked_id);
        }
    }
    
    /**
     * Ensures that record in ucla_group_members exists for given
     * group member id.
     *
     * @param int $groupsmembersid
     * @return int      Returns newly added record id or the existing id.
     *                  Returns false on an error.
     */
    public static function new_membership($groupsmembersid) {
        global $DB;
        $retval = false;

        $tracker = new stdClass();
        $tracker->groups_membersid = $groupsmembersid;

        try {
            $retval = $DB->insert_record('ucla_group_members', $tracker);
        } catch (dml_write_exception $e) {
            // Found a write exception, must be trying insert a duplicate row,
            // so record already exists.
            $record = $DB->get_record('ucla_group_members',
                    array('groups_membersid' => $tracker->groups_membersid));
            if (!empty($record)) {
                $retval = $record->id;
            }
        }

        return $retval;
    }

    /**
     * Remove user from group.
     *
     * @param int $moodleuserid
     */
    public function remove_membership($moodleuserid) {
        if (isset($this->memberships[$moodleuserid])) {
            unset($this->memberships[$moodleuserid]);
            $this->removedmemberships[$moodleuserid] = $moodleuserid;
        }
    }

    /**
     * Save memberships.
     *
     * @return boolean
     */
    public function save() {
        global $DB;

        $retval = false;

        if (isset($this->id)) {
            $retval = $this->update();
        } else {
            $tsi = $this->sectioninfo;

            // Slightly not DRY.
            $groupnamefields = array('subj_area', 'coursenum', 'lectacttype',
                'lectnum', 'acttype', 'sectnum');

            $namestrs = array();
            foreach ($groupnamefields as $groupnamefield) {
                if (isset($tsi[$groupnamefield])) {
                    $namestrs[] = $tsi[$groupnamefield];
                }
            }

            $namestr = implode(' ', $namestrs);

            // The name of this won't change...
            $this->name = $namestr;
            $this->description = get_string('group_desc',
                'block_ucla_group_manager', $tsi);
            
            $this->id = groups_create_group($this);

            $uclagroupsection = new stdClass();
            $uclagroupsection->groupid = $this->id;
            $uclagroupsection->term = $this->term;
            $uclagroupsection->srs = $this->srs;

            $this->ucla_group_sections_id = $DB->insert_record(
                'ucla_group_sections', $uclagroupsection);

            $this->save_memberships();

            if ($this->id && $this->ucla_group_sections_id) {
                $retval = true;
            }
        }

        return $retval;
    }

    /**
     * Synchronizes the group memberships with moodle groups.
     * This public function has to assume that $this->memberships is in its desired
     * state, and will not change its value.
     */
    public function save_memberships() {
        // Remove memberships in the DB.
        foreach ($this->removedmemberships as $moouid) {
            groups_remove_member($this->id, $moouid);

            if (isset($this->membershipids[$moouid])) {
                self::delete_membership($this->membershipids[$moouid]);
                unset($this->membershipids[$moouid]);
            }
        }

        $this->removedmemberships = array();

        // Create new memberships in the DB if needed.
        foreach ($this->memberships as $moouid) {
            $uclamembershipid = $this->create_membership($moouid);
            if ($uclamembershipid) {
                if (!isset($this->membershipids[$moouid])) {
                    $this->membershipids[$moouid] = $uclamembershipid;
                }
            }
        }
    }

    /**
     * Sychronize users.
     *
     * @param array $moodleusers
     */
    public function sync_members($moodleusers) {
        foreach ($moodleusers as $moouid => $moodleuser) {
            $this->add_membership($moouid);
        }

        foreach ($this->memberships as $membership) {
            if (!isset($moodleusers[$membership])) {
                $this->remove_membership($membership);
            }
        }
    }

    /**
     * Saves membership and updates group.
     *
     * @return bool
     */
    public function update() {
        $this->save_memberships();
        return groups_update_group($this);
    }
}
