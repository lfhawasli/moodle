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


// On select copyright status store strings with file ids to an element.

YUI().use('event-delegate', function(Y){
     Y.delegate('change', function (e){
     }, '#block_ucla_copyright_status_id_cp_list', 'select');
});

// On button click save changes to database.

YUI().use('node-base', function(Y){
    var btnl_Click = function(e){
        $('#block_ucla_copyright_status_form_copyright_status_list').serialize();
    };
    Y.on('click', btnl_Click, '#block_ucla_copyright_status_btn1');
});

function uclaCopyrightTextExtraction(node) {
    node = $(node);
    listElements = node.find('li');
    if (listElements.length > 0) {
        node = listElements.first();
    }
    return node.text();
}

// On button click toggle checkboxes.

Y.one('#checkall').on('click', function() {
    Y.all('input.usercheckbox').each(function() {
        this.set('checked', 'checked');
    })
});

Y.one('#checknone').on('click', function() {
    Y.all('input.usercheckbox').each(function() {
        this.set('checked', '');
    })
});