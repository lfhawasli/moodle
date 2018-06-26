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
 * Copyright information.
 *
 * @package   theme_uclashared
 * @copyright 2018 UC Regents
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
?>

<h1 class="headingblock">CCLE copyright information</h1>
<h2>Distributing copyrighted materials through CCLE</h2>
<p>
    All materials distributed through the CCLE system must be in compliance with
    <a href="http://www.copyright.gov/title17/">Title 17</a> of the US Code,
    generally known as the <a href="http://www.copyright.gov/title17/">1976 Copyright Law</a>.
</p>
<p>
    Every reproduction of a copyrighted work (which includes the act of uploading
    materials to CCLE) should be done in compliance with this law, and it is the
    user's responsibility to determine compliance. The instructor of record is
    responsible for all materials uploaded by teaching assistants, staff, or
    others doing so at the instructor's request.
</p>
<p>
    All users of CCLE should become familiar with <a href="http://policy.ucop.edu/advanced-search.php?action=search&op=results&lookup=1&keywords=copyright&subject_area=0&audience=0&responsible_office=0&search=Search">
    UC Systemwide Copyright Policies and Resources</a>, and can find additional
    resources and local information at the <a href="http://www.library.ucla.edu/support/publishing-data-management/scholarly-communication-resources-education/ucla-library-copyright-policies">
    UCLA Library Copyright site</a> or the <a href="http://copyright.universityofcalifornia.edu/">
    UC Copyright site</a>. Within these sites you'll find lots of helpful tools and guides.
</p>

<h2>Copyright ownership of course materials</h2>
<p>
    Though there are important exceptions included within the <a href="http://policy.ucop.edu/doc/2100004/CourseMaterials">
    2003 Policy on Ownership of Course Materials</a>, generally "ownership of the
    rights to Course Materials, including copyright, shall reside with the
    Designated Instructional Appointee who creates them." Anyone wishing to copy
    or redistribute materials found on CCLE should secure permission from the
    copyright owner before doing so.
</p>

<h2>Obtaining assistance</h2>
<p>
    Instructors are highly encouraged to use the following systems for posting
    materials to CCLE. Each one of these services has safeguards for insuring
    that materials are used in compliance with copyright law.
</p>
<ul>
    <li>
        <a href="http://www.library.ucla.edu/support/publishing-data-management/scholarly-communication-resources-education/ucla-library-copyright-policy/ucla-library-copyright-policy-electronic-reserves">
        UCLA Library's Course Reserves system</a> (for journal articles, texts, and more)
    </li>
    <li>
        <a href="https://unitproj.library.ucla.edu/dlib/audio_reserves">
        UCLA Music Library's audio reserves system</a> (for streaming audio)
    </li>
    <li>
        <a href="http://www.oid.ucla.edu/units/imcs">OID's Instructional Media Lab</a> (for streaming video)
    </li>
</ul>
<p>
    All users of CCLE are encouraged to contact
    <a href="mailto:martinjbrennan@library.ucla.edu?subject=CCLE Copyright Question">Martin Brennan</a>,
    the CCLE Copyright and Licensing Librarian, with any questions about copyright
    law, using copyrighted works within CCLE, and related concerns. Martin can
    provide copyright training for your department or unit, or consult with you
    individually on copyright issues.
</p>

<?php
$configweek = get_config('local_ucla', 'student_access_ends_week');
defined('MOODLE_INTERNAL') || die();
if (!empty($configweek)) {
    // Display past course access policy.
    echo '<h2>Past course access</h2>
    <p>
        To insure copyright compliance, students only have access to materials
        from a course during the term and for two weeks afterward; after that,
        materials are restricted from view. Therefore, we recommend you download
        any materials you’d like to retain before that time. Please note, such
        material is meant for your personal educational use, and should not be
        shared with others outside this course, posted online, or otherwise
        distributed without permission from the copyright owner.
    </p>';
}
