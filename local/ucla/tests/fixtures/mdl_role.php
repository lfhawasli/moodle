<?php
// This file is part of the UCLA local plugin for Moodle - http://moodle.org/
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
 * Database dump of PROD's mdl_role table for use in creating roles in automated
 * test environments.
 *
 * Until we get the core role import/export feature from Moodle 2.6 we will need
 * to rely on manually creating roles.
 *
 * @package   local_ucla
 * @category  test
 * @copyright 2014 UC Regents
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

$roles = array(
  array('name' => 'Editor','shortname' => 'editor','description' => '<p><i>can</i> edit and add course content; <i>cannot</i> view and grade student work.</p>

<hr />

<ul>

<li>Type: <span>reducedediting</span></li>

<li>Spec: <a href="https://jira.ats.ucla.edu:8443/browse/CCLE-3422?focusedCommentId=102408#comment-102408" title="CCLE-3422" target="_blank">CCLE-3422</a></li>

<li>Test plan: <a href="https://jira.ats.ucla.edu:8443/browse/TESTINGCCLE-1106">TESTINGCCLE-1106</a></li>

<li><span>Assignment: Can be assigned via Invitation to Enroll to Course sites and Instructional Collab sites, or can be assigned manually at a Category level for support users</span></li>

</ul>','archetype' => 'editingteacher'),
  array('name' => 'Grader','shortname' => 'grader','description' => '<p class="p1"><i>can</i> view and grade student work; <i>cannot</i> edit or add course content.</p>

<hr />

<ul>

<li>Type: <span>reducedediting</span></li>

<li>Spec: <a href="https://jira.ats.ucla.edu:8443/browse/CCLE-3049">CCLE-3049</a><a href="https://jira.ats.ucla.edu:8443/browse/CCLE-3049"><br /></a></li>

<li>Test plan: <a href="https://jira.ats.ucla.edu:8443/browse/TESTINGCCLE-740">TESTINGCCLE-740</a></li>

<li>Assignment: Can be assigned via Invitation to Enroll to Course sites or as an add-on role in conjunction with a primary role</li>

</ul>','archetype' => 'teacher'),
  array('name' => 'Instructional Assistant','shortname' => 'instructional_assistant','description' => '<p class="p1"><i>can</i> edit site settings, add course content, invite users, view and grade student work.</p>

<hr />

<ul>

<li><span>Type: </span><span>instequiv</span></li>

<li>Spec: <a id="key-val" rel="41245" href="https://jira.ats.ucla.edu:8443/browse/CCLE-3860" style="line-height: 1.231;">CCLE-3860</a></li>

<li>Test plan: None</li>

<li>Assignment: Can be assigned via Invitation to Enroll to Course sites</li>

<li>If assigned at the Category level the Switch role to feature will not work</li>

</ul>','archetype' => 'editingteacher'),
  array('name' => 'Instructor  ','shortname' => 'editinginstructor','description' => '<ul><li>Can edit site settings</li><li>Can add/edit site materials</li><li>Can assign roles</li><li>User is listed in search results and on front page of site as "Instructor"</li></ul><hr /><ul><li>Type: <span>instequiv</span></li><li>Spec: <a id="issue_key_CCLE-2324" href="https://jira.ats.ucla.edu/browse/CCLE-2324">CCLE-2324</a></li><li>Test plan: <a href="https://jira.ats.ucla.edu:8443/browse/TESTINGCCLE-1">TESTINGCCLE-1</a></li><li>Assignment: Assigned by Registrar for regular courses, can be assigned manually to Instructional Collab sites</li></ul>','archetype' => 'editingteacher'),
  array('name' => 'Participant','shortname' => 'participant','description' => '<p class="p1"><em>can</em> participate in activities and view private course content, but <i>are not</i> officially enrolled in the course through the Registrar’s office. Does not appear in the grader report.</p>

<hr />

<ul>

<li><span>Type: </span><span>studentequiv</span></li>

<li>Spec: <a href="https://jira.ats.ucla.edu:8443/browse/CCLE-3051" title="Site Participant I">CCLE-3051</a></li>

<li>Test plan: None</li>

<li><span>Assignment: Can be assigned via Invitation to Enroll to Course sites</span></li>

</ul>','archetype' => 'student'),
  array('name' => 'Project Contributor','shortname' => 'projectcontributor','description' => '<p class="p1"><i>can</i> add site content, participate in activities, and view participant list; <i>cannot</i> edit site settings or invite users.</p>

<hr />

<ul>

<li>Type: <span>reducedediting</span></li>

<li>Spec: <a href="https://jira.ats.ucla.edu:8443/browse/CCLE-3055" title="Project Contributer">CCLE-3055</a></li>

<li>Test plan: <a href="https://jira.ats.ucla.edu:8443/browse/TESTINGCCLE-1138">TESTINGCCLE-1138</a></li>

<li><span>Assignment: Can be assigned via Invitation to Enroll to Project Collab sites</span></li>

</ul>','archetype' => 'editingteacher'),
  array('name' => 'Project Lead','shortname' => 'projectlead','description' => '<p class="p1"><i>can</i> edit site settings, add site content, view participant list, invite users, and participate in activities.</p><hr /><ul><li><span>Type: </span><span>instequiv</span></li><li>Spec: <a href="https://jira.ats.ucla.edu:8443/browse/CCLE-3054" title="Project Lead">CCLE-3054</a></li><li>Test plan: <a href="https://jira.ats.ucla.edu:8443/browse/TESTINGCCLE-1197">TESTINGCCLE-1197</a></li><li><span>Assignment: Can be assigned manually to Project Collab sites</span></li></ul>','archetype' => 'editingteacher'),
  array('name' => 'Project Participant','shortname' => 'projectparticipant','description' => '<p class="p1"><i>can</i> participate in activities and view participant list.</p>

<hr />

<ul>

<li><span>Type: </span><span>studentequiv</span></li>

<li>Spec: <a href="https://jira.ats.ucla.edu:8443/browse/CCLE-3056" title="Project Participant">CCLE-3056</a></li>

<li>Test plan: None</li>

<li><span>Assignment: Can be assigned via Invitation to Enroll to Project Collab sites</span></li>

</ul>','archetype' => 'student'),
  array('name' => 'Project Viewer','shortname' => 'projectviewer','description' => '<p class="p1"><i>can</i> access private course content and view participant list; <i>cannot</i> participate in activities.</p><hr /><ul><li><span>Type: </span><span>guestequiv</span></li><li>Spec: <a href="https://jira.ats.ucla.edu:8443/browse/CCLE-3057" title="Project Viewer">CCLE-3057</a></li><li>Test plan: <a href="https://jira.ats.ucla.edu:8443/browse/TESTINGCCLE-1240">TESTINGCCLE-1240</a></li><li><span>Assignment: Can be assigned via Invitation to Enroll to Project Collab sites</span></li></ul>','archetype' => 'guest'),
  array('name' => 'Quiz Taker Unlimited Time','shortname' => 'quiztaker_nolimit','description' => '<p>This is an add-on role to be used in conjunction with a primary role (usually the student role).</p><hr /><ul><li>Type: secondaryaddon</li><li>Spec: <a href="https://jira.ats.ucla.edu/browse/CCLE-2935" title="CCLE-2935">CCLE-2935</a></li><li>Test plan: None</li></ul>','archetype' => ''),
  array('name' => 'Shared Quiz Creator','shortname' => 'sh_quiz_creator','description' => '<p>The Shared Quiz Creator is an add-on role used in conjunction with the primary role. Gives the user the ability to add questions to a shared question bank.</p>

<hr />

<ul>

<li>Type: secondaryaddon</li>

<li>Spec: <a title="https://jira.ats.ucla.edu:8443/browse/CCLE-2340" href="https://jira.ats.ucla.edu:8443/browse/CCLE-2340">CCLE-2340</a></li>

<li>Test plan: None</li>

</ul>','archetype' => ''),
  array('name' => 'Shared Quiz Questions User','shortname' => 'qqshare','description' => '<p>This role permits users to view and use questions built by others in question banks not normally available to these users.</p>

<hr />

<ul>

<li>Type: secondaryaddon</li>

<li>Spec: <a title="https://jira.ats.ucla.edu:8443/browse/CCLE-2340" href="https://jira.ats.ucla.edu:8443/browse/CCLE-2341">CCLE-2341</a></li>

<li>Test plan: None</li>

</ul>','archetype' => ''),
  array('name' => 'Student Facilitator','shortname' => 'studentfacilitator','description' => '<ul>

<li>Can edit site settings</li>

<li>Can add/edit site materials</li>

<li>Can assign roles</li>

<li>User is not listed in search results, but shows up on front page of site as "Student Facilitator"</li>

</ul>

<hr />

<ul>

<li>Type: <span>instequiv</span></li>

<li>Spec: <a id="issue_key_CCLE-2324" href="https://jira.ats.ucla.edu/browse/CCLE-3811">CCLE-3811</a></li>

<li>Test plan: <em>pending</em></li>

<li>Assignment: Assigned by Registrar to student instructors of USIE seminars. Mapped to prof code 22.</li>

</ul>','archetype' => 'editingteacher'),
  array('name' => 'Supervising Instructor','shortname' => 'supervising_instructor','description' => '<ul><li>Can edit site settings</li><li>Can add/edit site materials</li><li>Can assign roles</li><li>User is not subscribed to forums on site.</li><li>User is not listed in search results and on front page of site as "Instructor"</li></ul><hr /><ul><li>Type: <span>instequiv</span></li><li>Spec: <a title="https://jira.ats.ucla.edu:8443/browse/CCLE-2325" href="https://jira.ats.ucla.edu:8443/browse/CCLE-2325">CCLE-2325</a></li><li>Test plan: <a href="https://jira.ats.ucla.edu:8443/browse/TESTINGCCLE-700">TESTINGCCLE-700</a></li><li><span>Assignment: Assigned by Registrar for regular courses, can be assigned manually to Instructional Collab sites</span></li></ul>','archetype' => 'editingteacher'),
  array('name' => 'TA Instructor','shortname' => 'ta_instructor','description' => '<ul><li>Can edit site settings</li><li>Can add/edit site materials</li><li>Can assign roles</li><li>User is listed in search results and on front page of site as "Instructor."</li></ul><hr /><ul><li>Type: instequiv</li><li>Spec: <a title="https://jira.ats.ucla.edu:8443/browse/CCLE-2328" href="https://jira.ats.ucla.edu:8443/browse/CCLE-2328">CCLE-2328</a></li><li>Test plan: <a href="https://jira.ats.ucla.edu:8443/browse/TESTINGCCLE-3">TESTINGCCLE-3</a></li><li><span>Assignment: Assigned by Registrar for regular courses, can be assigned manually to Instructional Collab sites</span></li></ul>','archetype' => 'editingteacher'),
  array('name' => 'Teaching Assistant','shortname' => 'ta','description' => '<ul>

<li>Can participate in activities</li>

<li>Cannot edit site or grade users</li>

<li>User is listed on front page of site as "TA"</li>

</ul>

<hr />

<ul>

<li>Type: <span>studentequiv</span></li>

<li>Spec: <a title="https://jira.ats.ucla.edu:8443/browse/CCLE-2326" href="https://jira.ats.ucla.edu:8443/browse/CCLE-2326">CCLE-2326</a></li>

<li>Test plan: <a href="https://jira.ats.ucla.edu:8443/browse/TESTINGCCLE-3">TESTINGCCLE-3</a></li>

<li><span>Assignment: Assigned by Registrar for regular courses, can be assigned manually to Instructional Collab sites</span></li>

</ul>','archetype' => 'student'),
  array('name' => 'Teaching Assistant (admin)','shortname' => 'ta_admin','description' => '<ul><li>Can edit site settings</li><li>Can add/edit site materials</li><li>Can assign roles</li><li>User is listed on front page of site as "TA"</li></ul><hr /><ul><li><span>Type: </span><span>instequiv</span></li><li>Spec: <a title="https://jira.ats.ucla.edu/browse/CCLE-2327" href="https://jira.ats.ucla.edu/browse/CCLE-2327">CCLE-2327</a></li><li>Test plan: <a href="https://jira.ats.ucla.edu:8443/browse/TESTINGCCLE-3">TESTINGCCLE-3</a></li><li><span>Assignment: Assigned by Registrar for regular courses, can be assigned manually to Instructional Collab sites</span></li></ul>','archetype' => 'editingteacher'),
  array('name' => 'Temporary Participant','shortname' => 'tempparticipant','description' => '<p class="p1">student equivalent role with restricted duration that can access course site whether visible or hidden (e.g. giving a user access to course sites from previous terms or giving a guest lecturer access to current term) .</p>

<hr />

<ul>

<li>Type: studentequiv</li>

<li>Spec: <a href="https://jira.ats.ucla.edu:8443/browse/CCLE-3787">CCLE-3787</a></li>

<li>Test plan: <a href="https://jira.ats.ucla.edu:8443/browse/TESTINGCCLE-1613">TESTINGCCLE-1613</a></li>

<li>Assignment: Assigned via Site invitation (as of Sept 2013, School of Nursing uses Editor and Temporary Participant to allow staff to access current and prior terms.)</li>

</ul>','archetype' => 'student'),
  array('name' => 'View Private','shortname' => 'viewprivate','description' => '<p>This role is to be added at the category level to allow a user to view ALL PRIVATE MATERIALS for all sites within a category and all sub-categories.</p>

<p><span style="color: #ff0000;">NB: This role is powerful, and trickles down to all sub-categories.  Instructions for how to prevent the role from being inherited in sub-category folders are available in Jira (<span></span><a href="https://jira.ats.ucla.edu:8443/browse/CCLE-4155" title="https://jira.ats.ucla.edu:8443/browse/CCLE-4155">CCLE-4155</a>).</span></p>

<hr />

<p></p>

<ul>

<li>Type: <span>guestequiv</span></li>

<li>Spec: <a href="https://jira.ats.ucla.edu:8443/browse/CCLE-4155" title="https://jira.ats.ucla.edu:8443/browse/CCLE-4155" style="font-size: 13px; line-height: 1.231;">CCLE-4155</a><span style="font-size: 13px; line-height: 1.231;"> </span></li>

<li>Test plan: None</li>

<li>Assignment: Manual only</li>

</ul>

<p><span style="color: #ff0000;"> </span></p>

<p> </p>','archetype' => ''),
  array('name' => 'Visitor','shortname' => 'visitor','description' => '<p class="p1"><i>can</i> access private course content; <i>cannot</i> participate in activities.</p>

<hr />

<ul>

<li>Type: <span>guestequiv</span></li>

<li>Spec: <a href="https://jira.ats.ucla.edu:8443/browse/CCLE-3052" title="Site Participant II">CCLE-3052</a></li>

<li>Test plan: <a href="https://jira.ats.ucla.edu:8443/browse/TESTINGCCLE-1232">TESTINGCCLE-1232</a></li>

<li><span>Assignment: Can be assigned via Invitation to Enroll to Course sites and Instructional Collab sites</span></li>

</ul>','archetype' => 'guest')
);
