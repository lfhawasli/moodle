// Hides and show different options when different roles are selected.

$(function () {
    // Remove extra &nbsp between the radio options.
    $("div.col-md-9.form-inline.felement").contents().filter(function () {
        return this.nodeType === Node.TEXT_NODE;
    }).remove();

    $("#fitem_id_daysexpire_string").hide();
    $("#fitem_id_daysexpire_string .fitemtitle").hide();
    $("#fitem_id_ifroleexpire_string select").val(0);
    $("#fitem_id_ifroleexpire_string select").change(function (e) {
        if (this.value == '1') {
            // Set default days expire to 3.
            $("#fitem_id_daysexpire_string").show();
            $("#fitem_id_daysexpire_string select").val(3);
        } else {
            // Set value to null if chose never expire.
            $("#fitem_id_daysexpire_string").hide();
            $("#fitem_id_daysexpire_string select").val(null);
        }
    });

    // If choose any role but temporary, make sure ifroleexpire option is showing.
    $("input[type='radio']").not("span:contains('Temporary Participant')").click(function (e) {
        if ($("#fitem_id_ifroleexpire_string").is(':hidden')) {
            $("#fitem_id_ifroleexpire_string select").val(0);
            $("#fitem_id_daysexpire_string").hide();
            $("#fitem_id_daysexpire_string select").val(null);
        }
        $("#fitem_id_ifroleexpire_string").show();
        $("#fitem_id_daysexpire_string .fitemtitle").hide();
    });
    // If choose temporary participants. Hide ifroleexpire option and default that to true.
    // Also set deault of daysexpire to 3.
    $("label:contains('Temporary Participant')").click(function (e) {
        $("#fitem_id_ifroleexpire_string select").val(1);
        $("#fitem_id_ifroleexpire_string").hide();
        $("#fitem_id_daysexpire_string").show();
        $("#fitem_id_daysexpire_string .fitemtitle").show();
        $("#fitem_id_daysexpire_string select").val(3);
    });

    if ($("#fitem_id_ifroleexpire_string select").value == '1') {
        $("#fitem_id_daysexpire_string").show();
    } else {
        $("#fitem_id_daysexpire_string").hide();
        $("#fitem_id_daysexpire_string select").val(null);
    }
});