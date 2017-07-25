/*
 * jQuery, forever alone.
 * URL is the default.
 */

// Hides and shows File/URL syllabus selection options when radio buttons are clicked.
$(function() {
    // Initially hide file upload option so URL is the default.
    $(".fitem_ffilemanager").hide();

    // Hide file attachment box, show URL option.
    $("#id_fileurl_0:radio").click(function(e) {
        $(".fitem_ffilemanager").hide();
        $("#fitem_id_syllabus_url").show();
        $("#fitem_id_insecure_url_notice").show();
        var is_uploaded_file = document.getElementById("uploadfile");
        is_uploaded_file.value = 'no';
    });

    // Hide URL option, show file attachment box.
    $("#id_fileurl_1:radio").click(function(e) {
        $(".fitem_ffilemanager").show();
        $("#fitem_id_syllabus_url").hide();
        $("#fitem_id_insecure_url_notice").hide();
        var is_uploaded_file = document.getElementById("uploadfile");
        is_uploaded_file.value = 'yes';
    });
});