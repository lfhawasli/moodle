<?php
/**
 * Block MHAAIRS AAIRS Integrated Web Services Test Client
 *
 * @package    block
 * @subpackage mhaairs
 * @copyright  2013 Moodlerooms inc.
 * @author     Teresa Hardy <thardy@moodlerooms.com>
 */

require_once($CFG->libdir . "/externallib.php");
require_once($CFG->libdir . "/gradelib.php");
require_once($CFG->dirroot."/blocks/mhaairs/block_mhaairs_util.php");

class block_mhaairs_gradebookservice_external extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function gradebookservice_parameters() {
        return new external_function_parameters(
                array('source' => new external_value(PARAM_TEXT, 'string $source source of the grade such as "mod/assignment"', VALUE_DEFAULT, 'mod/assignment')
                        ,'courseid' => new external_value(PARAM_TEXT, 'string $courseid id of course', VALUE_DEFAULT, 'NULL')
                        ,'itemtype' => new external_value(PARAM_TEXT, 'string $itemtype type of grade item - mod, block', VALUE_DEFAULT, 'mod')
                        ,'itemmodule' => new external_value(PARAM_TEXT, 'string $itemmodule more specific then $itemtype - assignment, forum, etc.; maybe NULL for some item types', VALUE_DEFAULT, 'assignment')
                        ,'iteminstance' => new external_value(PARAM_TEXT, 'ID of the item module', VALUE_DEFAULT, '0')
                        ,'itemnumber' => new external_value(PARAM_TEXT, 'int $itemnumber most probably 0, modules can use other numbers when having more than one grades for each user', VALUE_DEFAULT, '0')
                        ,'grades' => new external_value(PARAM_TEXT, 'mixed $grades grade (object, array) or several grades (arrays of arrays or objects), NULL if updating grade_item definition only', VALUE_DEFAULT, 'NULL')
                        ,'itemdetails' => new external_value(PARAM_TEXT, 'mixed $itemdetails object or array describing the grading item, NULL if no change', VALUE_DEFAULT, 'NULL')
                )
        );
    }

    /**
     * Returns welcome message
     * @return string welcome message
     */
    public static function gradebookservice($source = 'mod/assignment', $courseid ='courseid', $itemtype = 'mod', $itemmodule = 'assignment', $iteminstance = '0', $itemnumber = '0', $grades = NULL, $itemdetails = NULL) {
        global $USER;
        global $CFG;
        global $DB;

        $badchars = ";'-";

        //Parameter validation
        //REQUIRED

        $params = self::validate_parameters(self::gradebookservice_parameters(),
                array('source' => $source
                        ,'courseid' => $courseid
                ));

        //Context validation
        //OPTIONAL but in most web service it should present
        $context = get_context_instance(CONTEXT_USER, $USER->id);
        self::validate_context($context);

        //decode item details and check for problems
        $itemdetails = json_decode(urldecode($itemdetails), true);

        //Capability checking
        //OPTIONAL but in most web service it should present
        if (!has_capability('moodle/user:viewdetails', $context)) {
            throw new moodle_exception('cannotviewprofile');
        }

        if ($itemdetails != "null" && $itemdetails != null)
        {
            //$itemdetails['itemname'] = "test11";

            //check type of each parameter

            if ((!is_string($itemdetails['categoryid']) || strpbrk($itemdetails['categoryid'], $badchars) !== false) && $itemdetails['categoryid'] != null) throw new Exception('Parameter categoryid is of incorrect type');
			if (!is_string($itemdetails['courseid']) && $itemdetails['courseid'] != null) throw new Exception('Parameter courseid is of incorrect type');
			if (!is_string($itemdetails['identity_type']) && $itemdetails['identity_type'] != null) throw new Exception('Parameter identity_type is of incorrect type');
            if (!is_string($itemdetails['itemname']) && $itemdetails['itemname'] != null) throw new Exception('Parameter itemname is of incorrect type');
            if ((!is_string($itemdetails['itemtype']) || strpbrk($itemdetails['itemtype'], $badchars) !== false) && $itemdetails['itemtype'] != null) throw new Exception('Parameter itemtype is of incorrect type');
            if (!is_numeric($itemdetails['idnumber']) && ($grades == "null" || $grades == null)) throw new Exception('Parameter idnumber is of incorrect type');
            if (!is_numeric($itemdetails['gradetype']) && $itemdetails['gradetype'] != null) throw new Exception('Parameter gradetype is of incorrect type');
            if (!is_numeric($itemdetails['grademax']) && $itemdetails['grademax'] != null) throw new Exception('Parameter grademax is of incorrect type');
            if (!is_numeric($itemdetails['needsupdate']) && $itemdetails['needsupdate'] != null) throw new Exception('Parameter needsupdate is of incorrect type');

			//remove SQL chars from strings
			//$itemdetails['itemname'] = handle_illegal_chars($itemdetails['itemname']);
            //$itemdetails['categoryid'] = str_replace(array(';','-',"'"), '', clean_param($itemdetails['categoryid'], PARAM_TEXT));
            //$itemdetails['itemtype'] = str_replace(array(';','-',"'"), '', clean_param($itemdetails['itemtype'], PARAM_TEXT));

            //enable use of ID or IDnumber
            if ($itemdetails['identity_type'] === 'internal' || $itemdetails['identity_type'] === 'lti')
			{
				$course->id = $courseid;
			}
			else if (!$DB->record_exists('course', array('id'=>$courseid)))
            {

                //map to numerical courseID
                $course = $DB->get_record('course', array('idnumber'=>$courseid));
                $courseid = $course->id;
                $itemdetails['courseid'] = $course->id;
            }
			else
			{
				$course->id = $courseid;
			}

            if ($itemdetails['categoryid'] != null && $itemdetails['categoryid'] != '' && $itemdetails['categoryid'] != 'null')
            {

                //convert category into something moodle can use
                $category = $DB->get_record_sql('SELECT id FROM {grade_categories} WHERE fullname = ? and courseid = ?', array($itemdetails['categoryid'], $course->id));

                //if the category exists
                if ($category->id != null && $category->id != '')
                {
                    //use the category ID we retrieved
                    $itemdetails['categoryid'] = $category->id;

                } else
                {
                    if ($itemdetails['categoryid'] != null && $itemdetails['categoryid'] != '')
                    {
						$parent = grade_category::fetch_course_category($course->id);
											
						//to avoid the parent keyword in PHP
                        $parentname = 'parent';

                        //create new category record
                        $newcategory = new stdClass();
                        $newcategory->fullname = $itemdetails['categoryid'];
                        $newcategory->courseid = $course->id;
                        $newcategory->$parentname = $parent->id;
                        $newcategory->id = null;
                        $newcategory->timecreated = 1337064766;
                        $newcategory->timemodified = 1337064766;
                        $newcategory->hidden = 0;

                        if ($newcategory->$parentname != null && $newcategory->$parentname != '')
                        {
                            if ($itemdetails['categoryid'] != '' && $itemdetails['categoryid'] != null)
                            {
                                $itemdetails['categoryid'] = $DB->insert_record('grade_categories', $newcategory, true);
                            }
                            else
                            {
                                $itemdetails['categoryid'] = $parent->id;
                            }
                        }


                    }
                }
            }
            //return $course->id;
            //return $parent->id;
            //return $CatID;


        } else
        {
            $itemdetails = null;
        }



        if($grades != "null" && $grades != null)
        {
            $grades = json_decode(urldecode($grades), true);

            //check type of each parameter
            if ((!is_string($grades['userid']) || strpbrk($grades['userid'], $badchars) !== false) && $grades['userid'] != null) throw new Exception('Parameter userid is of incorrect type');
            if (!is_numeric($grades['rawgrade']) && $grades['rawgrade'] != null) throw new Exception('Parameter userid is of incorrect type');

            //remove SQL chars
            //$grades['userid'] = str_replace(array(';','-',"'"), '', clean_param($grades['userid'], PARAM_TEXT));


            //map userID to numerical userID
            if ($itemdetails['identity_type'] === 'lti')
			{
			}
            else if ($DB->record_exists('user', array('username'=>$grades['userid']))) {
				$user = $DB->get_record('user', array('username'=>$grades['userid']));
				$grades['userid'] = $user->id;
			}

            //get real gradeItemId

        } else
        {
            $grades = null;
        }

        //run the update grade function which creates / updates the grade
        $result = grade_update($source,$courseid,$itemtype,$itemmodule,$iteminstance,$itemnumber,$grades,$itemdetails);

        //get the ID of the grade we just created
        $grade_item = $DB->get_record_sql('SELECT id FROM {grade_items} WHERE idnumber = ? and courseid = ?', array($itemdetails['idnumber'], $courseid));

        if ($grade_item->id != null && $grade_item->id != '' && $itemdetails['categoryid'] != '' && $itemdetails['categoryid'] != null && $itemdetails['categoryid'] != 'null')
        {
            //return $grade_item->id;
            //change the category of the Grade we just updated/created
            $record = new stdClass();
            $record->id         = $grade_item->id;
            $record->categoryid = $itemdetails['categoryid'];
            $DB->update_record(grade_items, $record);

        }


        return $result;
        return $itemdetails['itemname'];
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function gradebookservice_returns() {
        return new external_value(PARAM_TEXT, '0 for success anything else for failure');
    }


}
