/**
 *  Skipping the YUI Blocks and going to jQuery.
 *  ML and Underscores CLASH!
 **/
// Make sure that the rearrange block exists (or not).
M.block_ucla_rearrange = M.block_ucla_rearrange || {};
M.block_ucla_easyupload = M.block_ucla_easyupload || {};

/**
 *  Changes the current datavalues in the NestedSortable object.
 **/
M.block_ucla_easyupload.change_active_sortable = function() {

    var sectionId = $('#id_section').val();

    $("#reorder-container").slideUp("slow",
        function() {
            // Destroy previous functionality.
            M.block_ucla_rearrange.destroy_nested_sortable();

            // Refill with existing sections.
            var sectionInsides = '';
            if (sectionId != null) {
                sectionInsides = M.block_ucla_rearrange.sections[sectionId];

                sectionInsides = sectionInsides + M.block_ucla_rearrange.empty_item;
            } else {
                alert('faulty section spec');
            }

            // Replace all the HTML content for the section.
            var targetjqo = $(M.block_ucla_rearrange.targetjq);
            targetjqo.html(sectionInsides);

            M.block_ucla_easyupload.update_new_element_name();

            M.block_ucla_rearrange.create_nested_sortable();

            M.block_ucla_rearrange.serialize_target(targetjqo.get(0).id);

            $(this).slideDown("slow");
        }
    );
};

/**
 *  Hook for initialization of functionality.
 **/
M.block_ucla_easyupload.initiate_sortable_content = function() {
    var hookfn = M.block_ucla_easyupload.change_active_sortable;

    // This is a special case for subheadings...
    M.block_ucla_easyupload.displayname_field = '#id_' + $('#id_default_displayname_field').val();

    // Assign the event hook.
    $('#id_section').change(hookfn);
    $(M.block_ucla_easyupload.displayname_field).change(
        M.block_ucla_easyupload.update_new_element_name
    );

    // Run the event.
    hookfn();
};

/**
 *  Update the element name. TODO see if there is any better way
 **/
M.block_ucla_easyupload.update_new_element_name = function() {
    var value = $(M.block_ucla_easyupload.displayname_field).val();
    var type = $("#id_type").val();

    $("#ele-new").html("<b>" + value + "</b> " + "<span class='ele-new-paren'>" + "(Your new " + type + ")" + "</span>");
};

/**
 * SSC-1928 - Make any upload with "syllabus" in the title default to public.
 **/
M.block_ucla_easyupload.syllabus_default_public = function() {
    // Hide the help button by hiding the div with the class name "fgrouplabel"!
    // This is sort of hacky, but then again the help button for public/private
    // selector is probably the only element that will have the "fgrouplabel" class.
    $(".fgrouplabel").hide();

    // The main body of the trigger to open the function.
    $("input").keyup(function () {
        var name_value = $("#id_name").val();
        var syllabuspattern = new RegExp(".*[Ss5$]\\s*(y|Y|'/)\\s*(l|I|L|1|\\|_|\\|)\\s*(l|I|L|1|\\|_|\\|)\\s*(a|A|4|@|/-\\\\|/\\\\)\\s*(b|B|8|\\|3|\\|o)\\s*(u|U|\\|_\\|)\\s*[sS5$].*");
        var coursedescpattern = new RegExp(".*[Cc]\\s*(O|o|0)\\s*(U|u)\\s*(R|r|(I|i|l|\|)2)\\s*(S|s|5|$)\\s*(E|e|)\\s*(D|d)\\s*(E|e)\\s*(S|s|5|$)\\s*(C|c)\\s*(R|r)\\s*(I|i|1|l|\|)\\s*(P|p)\\s*(T|t|7)\\s*(I|i|1|l|\|)\\s*(O|o|0)\\s*[Nn].*");
        var defaultchangestring = M.util.get_string('default_change' , 'block_ucla_easyupload');
        var syllabus_body = M.util.get_string('syllabus_box_body', 'block_ucla_easyupload');

        // Logic that sets the "Public" radio button if:
        // - "Private" radio button is currently set &&
        // - Their is text similar to "syllabus" and/or "course description in the "Name" field.
        if( syllabuspattern.test(name_value) || coursedescpattern.test(name_value) ) {
            if( !( $("#id_publicprivateradios_publicprivate_public").is(':checked')) ) {
                $("#id_publicprivateradios_publicprivate_public").prop("checked", true);
                $("#id_public_warning").text( defaultchangestring );
                var modal = '<br/><br/> <div id="open-modal" class="modal-dialog"><div class="alert alert-info" role="alert"><p>' + syllabus_body + '</p></div></div>';

                // As long as the modal box was never created... create one!
                // This is to prevent multiple boxes from being created if they
                // they somehow do for whatever reason.
                if ($('#open-modal').length === 0) {
                    $("#id_syllabus_prompt").append(modal);
                }
                // Also, show the help button (make it visible)!
                $(".fgrouplabel").show();
            }
        }
    }).keyup();
}
