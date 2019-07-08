/**
 * JS to have fixed headers and first column for tables.
 *
 * @module     local_ucla/stickyheader
 * @package    local_ucla
 * @copyright  2019 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$(document).ready(function() {
    // Stickyheader can be customized to each page, identified by id of body.
    var body_id = {
        'page-admin-roles-allow': false,
        'page-blocks-ucla_modify_coursemenu-modify_coursemenu': false
    }

    body_id[$('body').attr('id')] = true;

    // Detect a scroll event on the tbody.
    // Taken from https://jsfiddle.net/RMarsh/bzuasLcz/3/
    $('.stickyheader tbody').scroll(function() {
        // Setting the thead left value to the negative valule of
        // tbody.scrollLeft will make it track the movement of the tbody
        // element. Setting an elements left value to that of the
        // tbody.scrollLeft left makes it maintain it's relative position
        // at the left of the table.

        // Fix the thead relative to the body scrolling.
        $('.stickyheader thead').css("left", -$(".stickyheader tbody").scrollLeft());
        
        if (body_id['page-admin-roles-allow']) {
            // Fix the first cell of the header.
            $('.stickyheader thead th:nth-child(1)').css("left", $(".stickyheader tbody").scrollLeft());
            // Fix the first column of tdbody.
            $('.stickyheader tbody td:nth-child(1)').css("left", $(".stickyheader tbody").scrollLeft());
        }
    });

    // Resize table to fit viewport.
    function resizeTable() {
        // Getting height/width of viewport from:
        // https://stackoverflow.com/a/8876069/6001
        let viewportheight = Math.max(document.documentElement.clientHeight, window.innerHeight || 0);
        let viewportwidth = Math.max(document.documentElement.clientWidth, window.innerWidth || 0);

        // Leave some space for the save button.
        let tableheight = viewportheight / 1.5;
        // Leave some space for gutter on right side.
        let tablewidth = viewportwidth - 50;

        if (body_id['page-admin-roles-allow'] || body_id['page-admin-uclacourserequestor']) {
            $('table.stickyheader').css("width", tablewidth);
            $('.stickyheader thead').css("width", tablewidth);
            $('.stickyheader tbody').css("width", tablewidth);
        }
        
        $('.stickyheader tbody').css("height", tableheight);
    }

    window.addEventListener('load', resizeTable, false);

    if (body_id['page-admin-roles-allow'] || body_id['page-admin-uclacourserequestor']) {
        window.addEventListener('resize', resizeTable, false);
    }
});
