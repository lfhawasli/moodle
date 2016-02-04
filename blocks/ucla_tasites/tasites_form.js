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


