M.block_ucla_modify_coursemenu = M.block_ucla_modify_coursemenu || {};
M.block_ucla_modify_coursemenu.strings = M.block_ucla_modify_coursemenu.strings || {};
M.block_ucla_modify_coursemenu.pix = M.block_ucla_modify_coursemenu.pix || {};

M.block_ucla_modify_coursemenu.drag_handle = 'drag-handle';

// Begin UCLA SSC Modification 606.
// New function that hides the option of making something the home page if it is going to be deleted or hidden.
M.block_ucla_modify_coursemenu.catchHidden = function(section_id) {
    // Check to see if the current section is set to be the home page.
    if ($("#hp-" + section_id + ":checked").val() == section_id) {
        $('#hp-0').click();  // If it is, switch it to the default.
        $("#hp-" + section_id).hide();  // Hide the button to set as default.
    } else {
        // Otherwise, just hide the button to set as default.
        $("#hp-" + section_id).hide();
    }

    // Check to see if the hidden and deletes aren't checked, if they aren't, unhide the set as default button.
    if ($("#hidden-" + section_id + ":checked").val() != "on" && $("#delete-" + section_id + ":checked").val() != "on") {
        $("#hp-" + section_id).show();
    }
}
// End UCLA SSC Modification 606.

/**
 *  Establishes logic for making reorder-able tables.
 *  Use M.block_ucla_modify_coursemenu globals.
 **/
M.block_ucla_modify_coursemenu.make_dnd_table = function() {
    $('#' + M.block_ucla_modify_coursemenu.table_id).tableDnD({
        dragHandle: "." + M.block_ucla_modify_coursemenu.drag_handle,
        onDragClass: ".always-row-dragging",
        onDragStart: function (table, cell) {
            // Handle special colors.
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

    // Generate and attach the html.
    $(jqlid + ' > tbody').append(bumc.generate_row_html());

    var newtr = $(jqlid + ' > tbody > tr:last');

    // Animate the particular row.
    newtr.find('td').each(function(index, Element) {
            var curhtml = $(Element).html();
            $(Element).html('<div class="animate-me">' + curhtml + '</div>');
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

    // Is new section.
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

    // New sections have special classes.
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
    // we may need to add classes.
    // TODO maybe use js dom objects?
    var row_html = '';
    if (canmove) {
        row_html += '<td class="' + M.block_ucla_modify_coursemenu.drag_handle + '">' + '<img src="' + M.block_ucla_modify_coursemenu.pix.handle + '" class="hidden-handle" />' + '</td>'
    } else {
        row_html += '<td></td>';
    }

    row_html += '<td class="col-section-num">' + sectionnumdisp + '</td>';

    row_html += '<td class="col-section-title">';
    if (sectiondata.no['name'] == undefined) {
        row_html += '<input type="text" name="title-' + sectionident + '" value="' + sectiondata.name + '" />';
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

        row_html += '<input class="hidden-checkbox" id="hidden-' + sectionident + '" name="hidden-' + sectionident + '" type="checkbox" ' + is_checked + ' />';
    }
    row_html += '</td>';

    row_html += '<td class="col-section-delete">';
    if (sectiondata.no['delete'] == undefined) {
        row_html += '<input type="checkbox" class="delete-checkbox" ' + 'id="delete-' + sectionident + '" name="delete-' + sectionident + '" />';
    }
    row_html += '</td>';

    row_html += '<td class="col-section-landing">';
    if (sectiondata.no['landingpage'] == undefined) {
        row_html += '<input id="landing-page-' + sectionident + '" type="radio" name="landingpageradios" />';
    }
    row_html += '</td>';

    // Add checkboxes and input fields for "Landing Page by Dates" range selectors.
    row_html += '<td class="col-section-landing-auto">';
    if (sectiondata.no['landingpage'] == undefined) {
        row_html += '<input id="datepicker_' + sectionident + '" type="checkbox" name="lpdatebox"' +
            'class="landing-page-auto-box"/>';
        var dateInputStart = '<div class="datepicker-container datepicker-container-start">' +
            '<input type="text" id="date-start-' + sectionident + '" name="datestart" class="datepicker">' + '</div>';
        var dateInputEnd = '<div class="datepicker-container">' +
            '<input type="text" id="date-end-' + sectionident + '" name="dateend" class="datepicker">' + '</div>';
        row_html += '<div class="datepicker-range">' +
            '<span style="color: red;"> From*</span>' +
            dateInputStart + ' ' + M.util.get_string('landingpagebydatesto', 'block_ucla_modify_coursemenu') +
            ' ' + dateInputEnd + '</div>';
        row_html += '<div class="datepicker-tooltip-text">"text"</div>';
    }
    row_html += '</td>';

    row_html += '</tr>';

    row_html = '<tr class="' + trclasssupp + 'section-row" id="section-' + sectionident + '">' + row_html;

    return row_html;
}

/**
 *  Attach the events that happen when hide and delete are clicked.
 **/
M.block_ucla_modify_coursemenu.attach_row_listeners = function(jq) {
    // TODO generalize and DRY.
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
        
        // Set proper visibility for "Landing Page by Dates" column when delete is checked.
        M.block_ucla_modify_coursemenu.set_landingpagebydates_visible(
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

        // Set proper visibility for "Landing Page by Dates" column when hidden is checked.
        M.block_ucla_modify_coursemenu.set_landingpagebydates_visible(
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

        // Set proper visibility for "Landing Page by Dates" columns when hidden is checked.
        M.block_ucla_modify_coursemenu.set_landingpagebydates_visible(
                parentjq
            );
        return true;
    };

    // Initialize states.
    var hiddencheckbox = jq.find(".hidden-checkbox");
    hiddenlistener.apply(hiddencheckbox);

    hiddencheckbox.change(hiddenlistener);

    // Add row listeners.
    jq.hover(function () {
        $(this).find('.drag-handle img').removeClass('hidden-handle');
    }, function () {
        $(this).find('.drag-handle img').addClass('hidden-handle');
    });

    // Add date picker to "Landing Page by Dates" date range selectors.
    M.block_ucla_modify_coursemenu.add_date_range_listener(jq);
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
    // TODO normalize building of names.
    var coursepreflandingsection = $('tr#section-' + courseprefval);
    var destinationradio = coursepreflandingsection.find(
            '[name=landingpageradios]:enabled'
        );

    // If the one we're looking for is not found, then just use the first one.
    if (destinationradio.length <= 0) {
        destinationradio = $('[name=landingpageradios]:first');
    }

    destinationradio.attr('checked', true);
}

M.block_ucla_modify_coursemenu.set_landingpageradio_visible = function(
        parentjq) {
    var lpr = parentjq.find('[name=landingpageradios]');

    // If any of the chckboxes are checked, the section cannot be set as
    // landing pages.
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

// SSC-1205 - Set the appropriate visibility for "Landing Page by Dates" setting.
M.block_ucla_modify_coursemenu.set_landingpagebydates_visible = function(
        parentjq) {
    var lpd = parentjq.find('[name=lpdatebox]');

    // If any of the chckboxes are checked, the section cannot have a "Landing Page by Dates" setting.
    if ($(parentjq).find('.hidden-checkbox').is(":checked") || $(parentjq).find('.delete-checkbox').is(":checked")) {
        lpd.attr('checked', false).hide().change();
    } else {
        lpd.show();
    }
}

/**
 *  Initialize a bunch of stuff...
 **/
M.block_ucla_modify_coursemenu.start = function() {
    M.block_ucla_modify_coursemenu.deleted_sections = [];
    M.block_ucla_modify_coursemenu.new_sections = [];
    M.block_ucla_modify_coursemenu.new_index = 0;

    // Shortcut pointers.
    var bumc = M.block_ucla_modify_coursemenu;
    var containerid = bumc.primary_id;
    var thetableid = bumc.table_id;
    var courseformat = bumc.course_format;

    // Use this for non-editable sections.
    var noneditablecfg = {
        'delete': true,
        'hide': true,
        'name' : true,
        'move': true,
        'sectionnumdisp': true
    };

    // Tell people that they need javascript, or if they have javascript, turn it off.
    $('#' + containerid).hide();

    // Clear the table.
    $('#' + thetableid + ' tbody').html('');
    // Prevent some js errors.
    $('#' + thetableid + ' thead tr').addClass('nodrag').addClass('nodrop');

    // Create a pointer to the passed-through "Landing Page by Dates" date range JSON object.
    bumc.date_range = JSON.parse($('#' + bumc.daterange_id).val());

    // CCLE-3685 - If the course has a syllabus, add it to the table as the first row.
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

        // Add "Landing Page by Dates" datepicker listeners if there is a syllabus.
        var syllabusRowJq = $('#' + thetableid + ' > tbody > #section-' + syllabusdata.section);
        bumc.add_date_range_listener(syllabusRowJq);
    }

    // Generate the existing sections.
    for (var sectionindex in bumc.sectiondata) {
        sectiondatum = bumc.sectiondata[sectionindex];

        // Site info section cannot be modified.
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

        // Insert pseudo-section for 'show all'.
        if (sectionindex == 0 && M.str[courseformat].show_all != undefined) {
            // Add "Landing Page by Dates" datepicker listeners for site info.
            bumc.add_date_range_listener($('#' + thetableid + ' > tbody > tr:last'));

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

            // Add "Landing Page by Dates" datepicker listeners for show all.
            bumc.add_date_range_listener($('#' + thetableid + ' > tbody > tr:last'));
        } else {
            // Attach hide-delete listeners.
            M.block_ucla_modify_coursemenu.attach_row_listeners(
                    $('#' + thetableid + ' > tbody > tr:last')
                );
        }
    }

    // Initialize logic.
    bumc.make_dnd_table();

    // Initialize "Landing Page by Dates".
    bumc.initialize_landing_page_by_dates();

    // Attach global listeners.
    $("#add-section-button").click(function () {
        // Create new row and animate.
        var newtr = M.block_ucla_modify_coursemenu.add_new_table_row();

        // Update global table.
        $('#' + M.block_ucla_modify_coursemenu.table_id).tableDnDUpdate();

        // Add events.
        bumc.attach_row_listeners(newtr);
    });

    bumc.set_landingpageradio_default();

    // Form submission, transfer fake form data onto MForm fields.
    $("#id_submitbutton").click(function (e) {
        var mbumc = M.block_ucla_modify_coursemenu;

        // Check validity of "Landing Page by Dates" date ranges.
        if (mbumc.landing_page_by_dates_valid_dates()) {
            // Valid, so append option back to formset.
            var additionalOptions = $('#id_additional_options');
            additionalOptions.hide();
            additionalOptions.append($('#fitem_id_enablelandingpagebydates'));
        } else {
            // Invalid dates, so prevent form submission.
            e.preventDefault();
            return false;
        }

        $('#' + mbumc.newsections_id).val(mbumc.get_sections_jq(
                '.new-section'
            ).join());

        // Maybe use a more useful algorithm?
        /*$('#' + mbumc.sectionsorder_id).val(
            $('#' + mbumc.table_id + ' tbody').attr(
                    'id', mbumc.sectionsorder_id
                ).tableDnDSerialize()
        );*/

        $('#' + mbumc.landingpage_id).val(
                mbumc.parse_sectionid($('[name=landingpageradios]:checked'), 2)
            );

        $('#' + mbumc.serialized_id).val(
            $('#' + mbumc.table_id + ' input').not(
                '[name="landingpageradios"], ' +
                '[name="lpdatebox"], ' +
                '[name="datestart"], ' +
                '[name="dateend"]'
            ).serialize()
        );

        // Transfer the "Landing Page by Dates" data to specific variable on the backend.
        $('#' + mbumc.daterange_id).val((function() {
            var dateRangeJson = {};
            $('#' + mbumc.table_id + ' input[name=lpdatebox]:checked').each(function(index, value) {
                var id = value.id.split('_')[1]; // Split according to the separator we used earlier.
                dateRangeJson[id] = {};
                $(value).siblings().find('input[name="datestart"], input[name="dateend"]')
                    .each(function(index, value) {
                        dateRangeJson[id][value.name] = value.value;
                    });
            });
            return JSON.stringify(dateRangeJson);
        })());

        return true;
    });

    Y.use('moodle-core-formchangechecker', function() {
        M.core_formchangechecker.get_form_dirty_state = function() {
            var state = M.core_formchangechecker.stateinformation,
                editor;

            // If the form was submitted, then return a non-dirty state.
            if (state.formsubmitted) {
                return 0;
            }

            // If any fields have been marked dirty, return a dirty state.
            if (state.formchanged) {
                return 1;
            }

           /* M.block_ucla_modify_coursemenu.savedata;
            var newdata = $('#' + M.block_ucla_modify_coursemenu.table_id + ' tbody').tableDnDSerialize();
            var savesplit = M.block_ucla_modify_coursemenu.savedata.split("&");
            var newsplit = newdata.split("&");
            if (savesplit.length !== newsplit.length) {
                return 1;
            }
            var savestr;
            var newstr;
            for (var i = 0; i < savesplit.length; i++) {
                savestr = savesplit[i].substring(savesplit[i].lastIndexOf("=") + 1);
                newstr = newsplit[i].substring(newsplit[i].lastIndexOf("=") + 1);
                if (savestr !== newstr) {
                    return 1;
                }
            }*/

            // If a field has been focused and changed, but still has focus then the browser won't fire the
            // onChange event. We check for this eventuality here.
            if (state.focused_element) {
                if (state.focused_element.element.get('value') !== state.focused_element.initial_value) {
                    return 1;
                }
            }

            // Handle TinyMCE editor instances.
            // We can't add a listener in the initializer as the editors may not have been created by that point
            // so we do so here instead.
            if (typeof window.tinyMCE !== 'undefined') {
                for (editor in window.tinyMCE.editors) {
                    if (window.tinyMCE.editors[editor].isDirty()) {
                        return 1;
                    }
                }
            }

            // If we reached here, then the form hasn't met any of the dirty conditions.
            return 0;
        }
    });
}

M.block_ucla_modify_coursemenu.parse_sectionid = function(section_dom, sliceindex) {
    if (sliceindex == undefined) {
        sliceindex = 1;
    }

    return $(section_dom).attr('id').split('-').slice(sliceindex).join('-');
}

M.block_ucla_modify_coursemenu.get_sections_jq = function(jq) {
    var sectionswithclass = [];

    $(jq).each(function () {
        // Get the section id parsed out.
        sectionswithclass.push(
                M.block_ucla_modify_coursemenu.parse_sectionid(this)
            );
    });

    return sectionswithclass;
}

// SSC-1205 - Function to add a listener for the Landing Page by Date datepicker.
M.block_ucla_modify_coursemenu.add_date_range_listener = function(jq) {
    var bumc = M.block_ucla_modify_coursemenu;
    var datepickerInput = jq.find('input.landing-page-auto-box')[0];
    var sectionId = datepickerInput.id.split('_')[1];

    if (bumc.date_range[sectionId]) { // Initialize datepickers with our database data.
        datepickerInput.checked = true;
        $(datepickerInput).siblings('.datepicker-range').css('display', 'inline-block');
        jq.find('input.datepicker[name=datestart]').flatpickr({
            enableTime: true,
            enableSeconds: true,
            minuteIncrement: 1,
            altInput: true,
            altFormat: "M j, Y h:i:S K",
            dateFormat: "U",
            defaultDate: bumc.date_range[sectionId].datestart
        });
        jq.find('input.datepicker[name=dateend]').flatpickr({
            enableTime: true,
            enableSeconds: true,
            minuteIncrement: 1,
            altInput: true,
            altFormat: "M j, Y h:i:S K",
            dateFormat: "U",
            defaultDate: bumc.date_range[sectionId].dateend
        });
    } else { // Initialize blank datepickers for sections without database data.
        jq.find('input.datepicker[name=datestart]').flatpickr({
            altInput: true,
            altFormat: "M j, Y h:i:S K",
            dateFormat: "U",
            enableTime: true,
            enableSeconds: true,
            minuteIncrement: 1,
            onReady: function() {
                if (this.amPM) {
                    this.amPM.textContent = 'AM';
                }
            }
        });
        jq.find('input.datepicker[name=dateend]').flatpickr({
            altInput: true,
            altFormat: "M j, Y h:i:S K",
            dateFormat: "U",
            enableTime: true,
            enableSeconds: true,
            minuteIncrement: 1,
            defaultHour: 11,
            defaultMinute: 59,
            defaultSeconds: 59,
            onReady: function() {
                if (this.amPM) {
                    this.amPM.textContent = 'PM';
                }
            }
        });
    }

    // Change display of date range depending on checkbox state.
    $(datepickerInput).change(function() {
        if (this.checked) {
            $(this).siblings('.datepicker-range').css('display', 'inline-block');
        } else {
            $(this).siblings('.datepicker-range').css('display', 'none');
        }
    });
}

// SSC-1205 - Initialize "Landing Page By Dates" option, set initial visibility and create listener for visibility changes.
M.block_ucla_modify_coursemenu.initialize_landing_page_by_dates = function() {
    // Initialize and "cache" jQuery selectors that we can.
    var landingpageByDatesHeader = $('th:contains("Landing page by Dates")');
    var landingpageHeader = $('th:contains("Landing page")').filter(function() { return $(this).text() === 'Landing page'});
    var landingpageByDatesColumn = $('.col-section-landing-auto');
    var landingpageColumn = $('.col-section-landing');
    var enableLandingpageByDatesCheckbox = $('#fitem_id_enablelandingpagebydates');

    // Hide "Landing Page by Dates" column on startup if option was not checked.
    // Append its enable checkbox (#fitem_id_...) to the proper column.
    if (!$('#id_enablelandingpagebydates').is(':checked')) {
        landingpageHeader.append(enableLandingpageByDatesCheckbox);
        landingpageByDatesHeader.hide();
        landingpageByDatesColumn.hide();
    } else {
        landingpageByDatesHeader.append(enableLandingpageByDatesCheckbox);
        landingpageHeader.hide();
        landingpageColumn.hide();
    }

    // Create listener for changing "Landing Page by Dates" visiblity when option is checked on and off.
    $('#id_enablelandingpagebydates').change(function() {
        if (this.checked) {
            landingpageByDatesHeader.append(enableLandingpageByDatesCheckbox);
            landingpageHeader.hide();
            landingpageColumn.hide();

            // Automatically enable landing page by dates for the current set landing page.
            var parentRow = $("[name='landingpageradios']:checked").closest("tr");
            var flatpickrInstance = parentRow.find('[name="datestart"]').get(0)._flatpickr;
            parentRow.find("[name='lpdatebox']").attr('checked', true).change();
            // If the current landing page's date doesn't have a date range then set the start date
            // to the current date.
            if (!parentRow.find('[name="datestart"]').val()) {
                var d = new Date();
                flatpickrInstance.setDate(d);
            }

            landingpageByDatesHeader.show();
            landingpageByDatesColumn.show();
        } else {
            landingpageHeader.append(enableLandingpageByDatesCheckbox);
            landingpageByDatesHeader.hide();
            landingpageByDatesColumn.hide();
            landingpageHeader.show();
            landingpageColumn.show();
        }
    });
}

// SSC-1205 - Date Validation and error setting for "Landing Page by Dates". Returns true if there are errors.
M.block_ucla_modify_coursemenu.landing_page_by_dates_valid_dates = function() {
    // Clear all Landing Page by Date error messages for now.
    $('.datepicker-tooltip-text').css({"display": "none"});

    var noErrors = true;
    var autoLandingPageInputs = $('input[name=lpdatebox]:checked');

    // Landing Page by Date jQuery code that finds empty start date range date boxes that are enabled.
    var emptyAutoLandingPageInputs = $(autoLandingPageInputs)
        .siblings()
        .find('input[name=datestart]')
        .filter(function() { return !this.value; });
    if (emptyAutoLandingPageInputs.length) {
        for (var i = 0; i < emptyAutoLandingPageInputs.length; i++) {
            var tooltip = $(emptyAutoLandingPageInputs[i]).closest('tr').find('.datepicker-tooltip-text');
            tooltip.text(M.util.get_string('landingpagebydatesempty', 'block_ucla_modify_coursemenu'));
            tooltip.css({"display": "block"});
        }
        noErrors = false;
    } else {
        // Date validation block for our date ranges. Note that we never have to check for null start dates
        // because this code block is only executed if these are non-empty.

        // Create date objects for comparison purposes.
        var startDateArray = new Array(autoLandingPageInputs.length);
        var endDateArray = new Array(autoLandingPageInputs.length);
        for (var i = 0; i < autoLandingPageInputs.length; i++) {
            var currentStart = $.trim($(autoLandingPageInputs[i]).closest('tr').find('input[name=datestart]').val());
            var currentEnd = $.trim($(autoLandingPageInputs[i]).closest('tr').find('input[name=dateend]').val());

            startDateArray[i] = currentStart;
            if (currentEnd != '') {
                endDateArray[i] = currentEnd;
            }
        }

        // Perform naive validation for date objects.
        for (var i = 0; i < autoLandingPageInputs.length; i++) {
            // Check date sequentiality.
            if (endDateArray[i] != null && startDateArray[i] >= endDateArray[i]) {
                var tooltip = $(autoLandingPageInputs[i]).closest('tr').find('.datepicker-tooltip-text');
                tooltip.text(M.util.get_string('landingpagebydatessequential', 'block_ucla_modify_coursemenu'));
                tooltip.css({"display": "block"});
                noErrors = false;
            }
            for (var j = 0; j < autoLandingPageInputs.length; j++) {
                if (i != j) {
                    // Check that start date isn't the same as any other start dates.
                    if (startDateArray[i] == startDateArray[j]) {
                        var tooltip = $(autoLandingPageInputs[i]).closest('tr').find('.datepicker-tooltip-text');
                        tooltip.text(M.util.get_string('landingpagebydatesequivalent', 'block_ucla_modify_coursemenu'));
                        tooltip.css({"display": "block"});
                        noErrors = false;
                    }
                    // Check that the start date is not in another date range.
                    if (endDateArray[j] != null && startDateArray[i] >= startDateArray[j] && startDateArray[i] <= endDateArray[j]) {
                        var tooltip = $(autoLandingPageInputs[i]).closest('tr').find('.datepicker-tooltip-text');
                        tooltip.text(M.util.get_string('landingpagebydatesstartoverlap', 'block_ucla_modify_coursemenu'));
                        tooltip.css({"display": "block"});
                        noErrors = false;
                    }
                    // Check that date ranges do not overlap.
                    if (endDateArray[i] != null && endDateArray[j] != null &&
                        startDateArray[i] <= endDateArray[j] && startDateArray[j] <= endDateArray[i]) {
                        var tooltip = $(autoLandingPageInputs[i]).closest('tr').find('.datepicker-tooltip-text');
                        tooltip.text(M.util.get_string('landingpagebydatesrangeoverlap', 'block_ucla_modify_coursemenu'));
                        tooltip.css({"display": "block"});
                        noErrors = false;
                    }
                }
            }
        }
    }
    return noErrors;
}

/**
 *  Function to call to initialize everything using JQuery's
 *  $(document).ready() callback.
 **/
M.block_ucla_modify_coursemenu.initialize = function() {
    $(document).ready(function() {M.block_ucla_modify_coursemenu.start()});
}
