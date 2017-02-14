// This file is part of the UCLA Media block for Moodle - http://moodle.org/
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
 * Displays Library music reserves
 *
 * @package    block_ucla_media
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


$('.button').click(function(event) {
    var newline = document.createElement('br');
    newline.className = "added";
    var iframe = document.createElement('iframe');
    iframe.className = "added";
    iframe.src = 'albumview.php?id=' + event.target.id;
    if ($(event.target).hasClass('video')) {
        iframe.height = 500;
        iframe.width = 740;
    } else {
        iframe.height = 60;
        iframe.width = 530;
    }
    iframe.scrolling = 'no';
    iframe.overflow = 'hidden';
    iframe.frameBorder = 'none';
    // Removing any previously added player.
    $('.added').remove();
    // Adding player.
    insertAfter(newline, event.target);
    insertAfter(iframe, newline);
});

function insertAfter(newNode, reference) {
    reference.parentNode.insertBefore(newNode, reference.nextSibling);
}
