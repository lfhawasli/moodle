/*
 * jQuery, forever alone.
 * File is the default.
 */

// User is converting an existing file to an official UCLA syllabus.
// Hides URL option, shows file attachment box.
$(function() {
    $("#id_fileurl_0").attr('checked', true);
    $(".fitem_ffilemanager").show();
    $("#fitem_id_syllabus_url").hide();
    $("#fitem_id_insecure_url_notice").hide();
    var is_uploaded_file = document.getElementById("uploadfile");
    is_uploaded_file.value = 'yes';
});