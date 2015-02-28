// This file is part of the UCLA support tools plugin for Moodle - http://moodle.org/
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

M.local_ucla_support_tools = M.local_ucla_support_tools || {};

M.local_ucla_support_tools.favorite = {
    init: function() {
        Y.log('Loading tool favorite', 'info', 'local_ucla_support_tools');

        // Set up event delegate for tool favoriting.
        Y.one('#region-main-box').delegate('click', function(e) {
            e.preventDefault();
            var id = e.currentTarget.getAttribute('data-id');
            this.toggle_favorite(id);
        }, 'a[data-action="favorite"]', this);
    },
    /**
     * Toggles the favorite state for a given tool.
     *
     * @param {int} id
     */
    toggle_favorite: function(id) {
        Y.log('Toggle favorite tool with ID: ' + id, 'info', 'local_ucla_support_tools');

        Y.io(M.cfg.wwwroot + '/local/ucla_support_tools/rest.php', {
            method: 'POST',
            data: {
                action: 'togglefavorite',
                sesskey: M.cfg.sesskey,
                json: JSON.stringify({id: id})
            },
            on: {
                success: function (id, result) {
                    var data = JSON.parse(result.responseText);

                    Y.all('a[data-id="' + data.id + '"]').each(function (node) {
                        if (data.status) {
                            // If true, then tool is now a favorite, so mark it.
                            node.one('i').replaceClass('fa-star-o', 'fa-star');
                        } else {
                            // Tool is now un-favorited.
                            node.one('i').replaceClass('fa-star', 'fa-star-o');
                        }
                    });
                }
            }
        });
        return true;
    }
};