// This file is part of the UCLA Site Invitation Plugin for Moodle - http://moodle.org/
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
 * @package    tool_uclaseniorscholar
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

YUI().use('node-event-delegate', 'event-key', function (Y) {
    var termfilterinstructor = Y.one('#tool_uclaseniorscholar_id_filter_term'),
        terminstructorsubmit = Y.one('#course_by_instructor_btn'),
		form = Y.one('#tool_uclaseniorscholar_course_by_instructor');

	// When term selected, filter instructor list.
    termfilterinstructor.delegate('change', function () {
		var filter = Y.one('#id_filter');
		filter.set('value', 'instr');
		form.submit();
    }, 'select');

	function formSubmit() {
		form.submit();
	}

    // Submit to get course list
    Y.one('#course_by_instructor_btn').on('click', formSubmit);
});