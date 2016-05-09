/**
 * Javascript for TA sites form.
 *
 * @package    block_ucla_tasites
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$(function() {
    $('#id_submitbutton').click(function() {

        $('#id_submitbutton').attr('disabled', 'disabled');

        $('.tasites_form').submit();
    });
});

$(document).ready(function() {
    if(!$('#id_tainitialchoice_byta').is(':checked')) {
        $('#id_bytaheader').hide();
    }
    if(!$('#id_tainitialchoice_bysection').is(':checked')) {
        $('#id_bysectionheader').hide();
    }

    $('#id_tainitialchoice_byta').click(function() {
       $('#id_bytaheader').show();
       $('#id_bysectionheader').hide();
    });

    $('#id_tainitialchoice_bysection').click(function() {
       $('#id_bysectionheader').show();
       $('#id_bytaheader').hide();
    });
});
