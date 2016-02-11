/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
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


