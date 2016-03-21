M.block_ucla_modify_coursemenu = M.block_ucla_modify_coursemenu || {};
M.block_ucla_modify_coursemenu.strings = M.block_ucla_modify_coursemenu.strings || {};
M.block_ucla_modify_coursemenu.pix= M.block_ucla_modify_coursemenu.pix || {};

M.block_ucla_modify_coursemenu.drag_handle = 'drag-handle';

// Begin UCLA SSC Modification 606
//New function that hides the option of making something the home page if it is going to be deleted or hidden
M.block_ucla_modify_coursemenu.catchHidden = function(section_id) {
    // Check to see if the current section is set to be the home page
    if ($("#hp-"+section_id+":checked").val() == section_id) {
        $('#hp-0').click();  // If it is, switch it to the default
        $("#hp-"+section_id).hide();  // Hide the button to set as default
    } else {
        // Otherwise, just hide the button to set as default
        $("#hp-"+section_id).hide();
    }

    // Check to see if the hidden and deletes aren't checked, if they aren't, unhide the set as default button
    if ($("#hidden-"+section_id+":checked").val() != "on" && $("#delete-"+section_id+":checked").val() != "on") {
        $("#hp-"+section_id).show();
    }
}
// End UCLA SSC Modification 606

/**
 *  Establishes logic for making reorder-able tables.
 *  Use M.block_ucla_modify_coursemenu globals.
 **/
M.block_ucla_modify_coursemenu.make_dnd_table = function() {
    $('#' + M.block_ucla_modify_coursemenu.table_id).tableDnD({
        dragHandle: M.block_ucla_modify_coursemenu.drag_handle,
        onDragClass: "always-row-dragging",
        onDragStart: function (table, cell) {
            // Handle special colors
            var tabrow = $(cell).parent();
            if (tabrow.hasClass('delete-section')) {
                tabrow.addClass('delete-section-dragging');
            } else if (tabrow.hasClass('new-section')) {
                tabrow.addClass('new-section-dragging');
            } else {
                tabrow.addClass('regular-row-dragging');
            }
        }, 
        onDrop: function (table, row) {
            $(row).removeClass('delete-section-dragging');
            $(row).removeClass('new-section-dragging');
            $(row).removeClass('regular-row-dragging');
        }
    });
}

M.block_ucla_modify_coursemenu.add_new_table_row = function() {
    var bumc = M.block_ucla_modify_coursemenu;
    var jqlid = '#' + bumc.table_id;
    
    bumc.new_sections["new" + bumc.new_index] = true;

    // Generate and attach the html
    $(jqlid + ' > tbody').append(bumc.generate_row_html());

    var newtr = $(jqlid + ' > tbody > tr:last');

    // Animate the particular row
    newtr.find('td').each(function(index, Element) {
            var curhtml = $(Element).html();
            $(Element).html('<div class="animate-me">'
                + curhtml + '</div>');
        });

    $(jqlid + ' > tbody .animate-me').slideDown('fast',
        function() {
            $(this).removeClass('animate-me');
        });

    return newtr;
}

M.block_ucla_modify_coursemenu.get_new_index = function() {
    var newindex = this.new_index++;
    return newindex;
}

M.block_ucla_modify_coursemenu.generate_row_html = function(sectiondata) {
    var is_new = false;

    // Is new section
    if (sectiondata == undefined) {
        is_new = true;
        sectiondata = {
            'name': M.str.block_ucla_modify_coursemenu.newsection,
            'section': M.block_ucla_modify_coursemenu.get_new_index()
        };
    }

    if (sectiondata.no == undefined) {
        sectiondata.no = {};
    }

    // New sections have special classes
    var sectionident = sectiondata.section;
    var trclasssupp = '';
    var sectionnumdisp = sectiondata.section;
    var canmove = (sectiondata.no['move'] == undefined);

    if (sectiondata.no['sectionnumdisp']) {
        sectionnumdisp = '';
    }

    if (!canmove) {
        trclasssupp += ' nodrag nodrop ';
    }

    if (is_new) {
        sectionident = 'new_' + sectionident;
        trclasssupp = 'new-section ';
        sectionnumdisp 
            = M.str.block_ucla_modify_coursemenu.new_sectnum;
    }

    // The row html, the beginning tr tag is written later, since
    // we may need to add classes
    // TODO maybe use js dom objects?
    var row_html = '';
    if (canmove) {
        row_html += '<td class="' + M.block_ucla_modify_coursemenu.drag_handle
            + '">' + '<img src="' + M.block_ucla_modify_coursemenu.pix.handle 
            + '" class="hidden-handle" />' + '</td>'
    } else {
        row_html += '<td></td>';
    }

    row_html +=  '<td class="col-section-num">' + sectionnumdisp + '</td>';

    row_html += '<td class="col-section-title">';
    if (sectiondata.no['name'] == undefined) {
        row_html += '<input type="text" name="title-' + sectionident 
            + '" value="' + sectiondata.name + '" />'; 
    } else {
        row_html += sectiondata.name;
    }
    row_html += '</td>';

    row_html += '<td class="col-section-hide">';
    if (sectiondata.no['hide'] == undefined) {
        var is_checked = '';
        if (sectiondata.visible == 0) {
            is_checked = 'checked';
            trclasssupp += 'hidden-section ';
        }

        row_html += '<input class="hidden-checkbox" id="hidden-'
            + sectionident + '" name="hidden-' + sectionident
            + '" type="checkbox" ' + is_checked + ' />';
    }
    row_html += '</td>';

    row_html += '<td class="col-section-delete">';
    if (sectiondata.no['delete'] == undefined) {
        row_html += '<input type="checkbox" class="delete-checkbox" '
            + 'id="delete-' + sectionident + '" name="delete-' + sectionident
            + '" />';
    }
    row_html += '</td>';

    row_html += '<td class="col-section-landing">';
    if (sectiondata.no['landingpage'] == undefined) {
        row_html += '<input id="landing-page-' + sectionident 
            + '" type="radio" name="landingpageradios" />';
    }
    row_html += '</td>';

    row_html += '</tr>';
    
    row_html = '<tr class="' + trclasssupp 
        + 'section-row" id="section-' + sectionident + '">' + row_html;

    return row_html;
}

/**
 *  Attach the events that happen when hide and delete are clicked.
 **/
M.block_ucla_modify_coursemenu.attach_row_listeners = function(jq) {
    // TODO generalize and DRY
    $(jq).find(".delete-checkbox").change(function() {
        var deleting = this.checked;
        var parentjq = $(this).parents('tr');

        var dclass = 'delete-section';

        if (deleting) {
            parentjq.addClass(dclass);
        } else {
            parentjq.removeClass(dclass);
        }

        M.block_ucla_modify_coursemenu.set_landingpageradio_visible(
                parentjq
            );

        return true;
    });

    // If hidden is checked, disable delete.
    $(jq).find(".hidden-checkbox").change(function() {
        var hiding = this.checked;
        var parentjq = $(this).parents('tr');

        var dclass = 'delete-section';

        if (hiding) {
            parentjq.removeClass(dclass);
        }

        M.block_ucla_modify_coursemenu.set_landingpageradio_visible(
                parentjq
            );

        return true;
    });

    var hiddenlistener = function() {
        var hidden = this.checked;
        var parentjq = $(this).parents('tr');

        var hclass = 'hidden-section';

        if (hidden) {
            parentjq.addClass(hclass);
        } else {
            parentjq.removeClass(hclass);
        }

        M.block_ucla_modify_coursemenu.set_landingpageradio_visible(
                parentjq
            );

        return true;
    };
    
    // initialize states
    var hiddencheckbox = jq.find(".hidden-checkbox");
    hiddenlistener.apply(hiddencheckbox);

    hiddencheckbox.change(hiddenlistener);
    
    // Add row listeners
    jq.hover(function () {
        $(this).find('.drag-handle img').removeClass('hidden-handle');
    }, function () {
        $(this).find('.drag-handle img').addClass('hidden-handle');
    });

}

M.block_ucla_modify_coursemenu.check_reset_landingpage = function() {
    if (!this.check_landingpageradio()) {
        this.set_landingpageradio_default();
    }
}

M.block_ucla_modify_coursemenu.check_landingpageradio = function() {
    return $('[name=landingpageradios]').filter(':checked').length > 0;
}

M.block_ucla_modify_coursemenu.set_landingpageradio_default = function() {
    var courseprefval = $('[name=landingpage]').val();
    // TODO normalize building of names
    var coursepreflandingsection = $('tr#section-' + courseprefval);
    var destinationradio = coursepreflandingsection.find(
            '[name=landingpageradios]:enabled'
        );

    // If the one we're looking for is not found, then just use the first one
    if (destinationradio.length <= 0) {
        destinationradio = $('[name=landingpageradios]:first');
    }

    destinationradio.attr('checked', true);
}

M.block_ucla_modify_coursemenu.set_landingpageradio_visible = function(
        parentjq) {
    var lpr = parentjq.find('[name=landingpageradios]');

    // If any of the chckboxes are checked, the section cannot be set as
    // landing pages
    if ($(parentjq).find(':checked').length > 0) {
        lpr.removeAttr('checked').attr('disabled', true).hide();
    } else {
        lpr.removeAttr('disabled').show();
    }

    // If hidden or delete are checked, the other is disabled.
    $('[name^="hidden-"]').click(function() {
        var id = $(this).attr('name').replace(/^hidden-/, '');
        $('[name="delete-' + id + '"]').attr('checked', false);
    });

    $('[name^="delete-"]').click(function() {
        var id = $(this).attr('name').replace(/^delete-/, '');
        $('[name="hidden-' + id + '"]').attr('checked', false);
    });
    M.block_ucla_modify_coursemenu.check_reset_landingpage();
}

/**
 *  Initialize a bunch of stuff...
 **/
M.block_ucla_modify_coursemenu.start = function() {
    M.block_ucla_modify_coursemenu.deleted_sections = [];
    M.block_ucla_modify_coursemenu.new_sections = [];
    M.block_ucla_modify_coursemenu.new_index = 0;

    // Shortcut pointers
    var bumc = M.block_ucla_modify_coursemenu;
    var containerid = bumc.primary_id;
    var thetableid = bumc.table_id;
    var courseformat = bumc.course_format;

    // Use this for non-editable sections
    var noneditablecfg = {
        'delete': true,
        'hide': true,
        'name' : true,
        'move': true,
        'sectionnumdisp': true
    };
   
    // Tell people that they need javascript, or if they have javascript, turn
    // it off
    $('#' + containerid).hide();

    // Clear the table
    $('#' + thetableid + ' tbody').html('');
    // Prevent some js errors
    $('#' + thetableid + ' thead tr').addClass('nodrag').addClass('nodrop');
    
    // CCLE-3685 - If the course has a syllabus, add it to the table as the first row
    syllabusdata = bumc.syllabusdata;
    if (syllabusdata.can_host_syllabi) {
        var syllabussection = {
            'name': syllabusdata.display_name,
            'section': syllabusdata.section,
            'no': noneditablecfg
        };
        
        $('#' + thetableid + ' > tbody').append(
            bumc.generate_row_html(
                syllabussection
            )
        );
    }

    // Generate the existing sections
    for (var sectionindex in bumc.sectiondata) {
        sectiondatum = bumc.sectiondata[sectionindex];

        // Site info section cannot be modified
        if (sectionindex == 0) {
            sectiondatum.name = M.str[courseformat]['section0name'];
            sectiondatum.section = 0;
            sectiondatum.no = noneditablecfg;
        }

        $('#' + thetableid + ' > tbody').append(
            bumc.generate_row_html(
                sectiondatum
            )
        );

        // Insert pseudo-section for 'show all'
        if (sectionindex == 0 && M.str[courseformat].show_all != undefined) {
            var showallsection = {
                'name': M.str[courseformat]['show_all'],
                'section': bumc.showallsection,
                'no': noneditablecfg
            };

            $('#' + thetableid + ' > tbody').append(
                bumc.generate_row_html(
                    showallsection 
                )
            );
        } else {
            // Attach hide-delete listeners
            M.block_ucla_modify_coursemenu.attach_row_listeners(
                    $('#' + thetableid + ' > tbody > tr:last')
                );
        }
    }

    // Initialize logic
    bumc.make_dnd_table(thetableid);

    // Attach global listeners
    $("#add-section-button").click(function () {
        // Create new row and animate
        var newtr = M.block_ucla_modify_coursemenu.add_new_table_row();

        // Update global table
        $('#' + M.block_ucla_modify_coursemenu.table_id).tableDnDUpdate();

        // Add events
        bumc.attach_row_listeners(newtr);
    });
    
    bumc.set_landingpageradio_default();

    // Form submission, transfer fake form data onto MForm fields
    $("#id_submitbutton").click(function () {				
        var mbumc = M.block_ucla_modify_coursemenu;

        $('#' + mbumc.newsections_id).val(mbumc.get_sections_jq(
                '.new-section'
            ).join());

        // Maybe use a more useful algorithm?
        $('#' + mbumc.sectionsorder_id).val(
            $('#' + mbumc.table_id + ' tbody').attr(
                    'id', mbumc.sectionsorder_id
                ).tableDnDSerialize()
        );

        $('#' + mbumc.landingpage_id).val(
                mbumc.parse_sectionid($('[name=landingpageradios]:checked'), 2)
            );
        
        $('#' + mbumc.serialized_id).val(
                $('#' + mbumc.table_id 
                    + ' input[name!=landingpageradios]').serialize()
            );

        return true;
    });
}

M.block_ucla_modify_coursemenu.parse_sectionid = function(section_dom, 
                                                          sliceindex) {
    if (sliceindex == undefined) {
        sliceindex = 1;
    }

    return $(section_dom).attr('id').split('-').slice(sliceindex).join('-');
}

M.block_ucla_modify_coursemenu.get_sections_jq = function(jq) {
    var sectionswithclass = [];

    $(jq).each(function () {
        // Get the section id parsed out
        sectionswithclass.push(
                M.block_ucla_modify_coursemenu.parse_sectionid(this)
            );
    });

    return sectionswithclass;
}

/**
 *  Function to call to initialize everything using JQuery's 
 *  $(document).ready() callback.
 **/
M.block_ucla_modify_coursemenu.initialize = function() {
    $(document).ready(function() {M.block_ucla_modify_coursemenu.start()});
}