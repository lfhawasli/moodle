/*
 * jQuery, forever alone.
 * URL is the default.
 */

// User is converting an existing url to an official UCLA syllabus.
// Hides file attachment box, shows URL option.
$(function() {
    $("#id_fileurl_1").attr('checked', true);
    $(".fitem_ffilemanager").hide();
    $("#fitem_id_syllabus_url").show();
    $("#fitem_id_insecure_url_notice").show();
    var is_uploaded_file = document.getElementById("uploadfile");
    is_uploaded_file.value = 'no';
});