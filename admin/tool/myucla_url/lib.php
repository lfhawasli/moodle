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
 * @package   tool_myucla_url
 * @copyright 2018 UC Regents
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
/**
 * Adds MyUCLA links to admin panel.
 *
 * @param navigation_node $navigation The navigation node to extend.
 * @param stdClass        $course     The course object for the tool.
 * @param context         $context    The context of the course.
 */
function tool_myucla_url_extend_navigation_course($navigation, $course, $context) {
    $courseinfo = ucla_get_course_info($course->id);

    if (!empty($courseinfo) && has_capability('moodle/course:viewparticipants',
            $context)) {
        $container = navigation_node::create(get_string('myucla', 'tool_myucla_url'),
                null, navigation_node::TYPE_CONTAINER, null, 'myucla');

        foreach ($courseinfo as $singlecourseinfo) {
            $courseterm = $singlecourseinfo->term;
            $coursesrs = $singlecourseinfo->srs;
            $params = array('term' => $courseterm, 'srs' => $coursesrs);

            $gradebook = navigation_node::create(get_string('gradebook', 'tool_myucla_url'),
                    new moodle_url('https://be.my.ucla.edu/login/directLink.aspx?featureID=75',
                            $params), navigation_node::TYPE_SETTING);
            $emailroster = navigation_node::create(get_string('emailroster', 'tool_myucla_url'),
                    new moodle_url('https://be.my.ucla.edu/login/directLink.aspx?featureID=73',
                            $params), navigation_node::TYPE_SETTING);
            $downloadroster = navigation_node::create(get_string('downloadroster', 'tool_myucla_url'),
                    new moodle_url('https://be.my.ucla.edu/login/directLink.aspx?featureID=74',
                            $params), navigation_node::TYPE_SETTING);
            $photoroster = navigation_node::create(get_string('photoroster', 'tool_myucla_url'),
                    new moodle_url('https://be.my.ucla.edu/login/directLink.aspx?featureID=148&spp=30&sd=true',
                            $params), navigation_node::TYPE_SETTING);
            $asuclatextbooks = navigation_node::create(get_string('asuclatextbooks', 'tool_myucla_url'),
                    new moodle_url('http://ucla.verbacompare.com/compare?catids='
                            . $courseterm . $coursesrs), navigation_node::TYPE_SETTING);

            if (count($courseinfo) > 1) {
                $coursecontainer = navigation_node::create(
                        $singlecourseinfo->subj_area . ' ' . $singlecourseinfo->coursenum,
                        null, navigation_node::TYPE_CONTAINER);
                $coursecontainer->add_node($gradebook);
                $coursecontainer->add_node($emailroster);
                $coursecontainer->add_node($downloadroster);
                $coursecontainer->add_node($photoroster);
                $coursecontainer->add_node($asuclatextbooks);
                $container->add_node($coursecontainer);
            } else {
                $container->add_node($gradebook);
                $container->add_node($emailroster);
                $container->add_node($downloadroster);
                $container->add_node($photoroster);
                $container->add_node($asuclatextbooks);
            }
        }

        $navigation->add_node($container);
    }
};
