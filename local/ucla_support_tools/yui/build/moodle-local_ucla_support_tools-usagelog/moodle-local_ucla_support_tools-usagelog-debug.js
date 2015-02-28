YUI.add('moodle-local_ucla_support_tools-usagelog', function (Y, NAME) {

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
 * UCLA support tools plugin.
 *
 * @package    local_ucla_support_tools
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

M.local_ucla_support_tools = M.local_ucla_support_tools || {};

M.local_ucla_support_tools.usagelog = {
    init: function () {
        Y.log('Loading usage log', 'info', 'local_ucla_support_tools');

        Y.one('#region-main-box').delegate('click', function (e) {            
            var toolId =
                    e.currentTarget.ancestor('.ucla-support-tool').getData('id');
            Y.io(M.cfg.wwwroot + '/local/ucla_support_tools/rest.php', {
                method: 'POST',
                data: {
                    action: 'logtooluse',
                    json: JSON.stringify({id: toolId}),
                    sesskey: M.cfg.sesskey
                }
            });
        }, '.ucla-support-tool .tool-link');
    }
};

}, '@VERSION@', {"requires": ["base", "node"]});
