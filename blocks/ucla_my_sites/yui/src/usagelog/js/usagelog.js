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
 * My Sites Block JS
 *
 * @package    block_ucla_my_sites
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

M.block_ucla_my_sites = M.block_ucla_my_sites || {};

M.block_ucla_my_sites.usagelog = {
    init: function () {
        Y.log('Loading tool usage log', 'info', 'block_ucla_my_sites');

        Y.one('.ucla-support-tools-mysites-favorites').delegate('click', function (e) {
            var toolId = e.currentTarget.ancestor('.ucla-support-tool').getData('id');
            Y.io(M.cfg.wwwroot + '/local/ucla_support_tools/rest.php', {
                method: 'POST',
                data: {
                    action: 'logtooluse',
                    json: JSON.stringify({id: toolId}),
                    sesskey: M.cfg.sesskey
                }
            });
        }, '.tool-link');
    }
};