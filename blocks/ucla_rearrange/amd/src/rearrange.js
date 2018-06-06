define(['core/str', 'jquery', 'block_ucla_rearrange/jquery.mjs.nestedSortable'], function(str, $) {
    var SELECTORS = {
        section: '.section-item',
        sectionList: '#s-list',
        siteInfo: '.section-zero',
        expand: '.expand-button',
        expandall: '.expandall',
    };
    // Initialized asynchronously in initStrings.
    var STRINGS;

    var initNestedSortable = function() {
        $(SELECTORS.sectionList).nestedSortable({
            // Per sample usage at https://github.com/ilikenwf/nestedSortable.
            handle: 'div',
            items: 'li',
            listType: 'ul',
            toleranceElement: '> div',

            // Site info should not be sortable.
            cancel: SELECTORS.siteInfo + ' > div',
            doNotClear: true,
            // Don't allow any section to be moved before site info.
            isAllowed: function(placeholder, parent, current) {
                if (current.is(SELECTORS.section + ':not(' + SELECTORS.siteInfo + ')')) {
                    var isBeforeSiteInfo = placeholder.nextAll(SELECTORS.siteInfo).length > 0;
                    return !isBeforeSiteInfo;
                }

                return true;
            },
            // Sections can only be reordered, not nested.
            protectRoot: true,

            forcePlaceholderSize: true,
            opacity: 0.6,
            placeholder: 'placeholder',
            revert: 200,
            tabSize: 32,

            excludeRoot: true,
            // Update serialized hidden field when sorting takes place.
            stop: updateSerialized,
        });

        // Set initial serialized hidden field.
        updateSerialized();
    };

    // Return a promise that completes after setting STRINGS.
    var initStrings = function() {
        return str.get_strings([
            { key: 'sectionexpand', component: 'block_ucla_rearrange' },
            { key: 'sectioncollapse', component: 'block_ucla_rearrange' },
            { key: 'allexpand', component: 'block_ucla_rearrange' },
            { key: 'allcollapse', component: 'block_ucla_rearrange' },
        ]).then(function(strings) {
            STRINGS = {
                expand: strings[0],
                collapse: strings[1],
                expandall: strings[2],
                collapseall: strings[3],
            };
        });
    };

    var updateSerialized = function() {
        var serialized = JSON.stringify($(SELECTORS.sectionList).nestedSortable('toHierarchy'));
        $('#serialized').val(serialized);
    };

    // Toggle section corresponding to the passed Expand/Collapse button.
    var toggleSection = function(ecbutton) {
        var section = ecbutton.closest(SELECTORS.section);
        section.children('ul').slideToggle();
        ecbutton.text(ecbutton.text() == STRINGS.collapse ? STRINGS.expand : STRINGS.collapse);

        // If all sections can only be expanded/collapsed, set expand all/collapse all button.
        var allSame;
        $(SELECTORS.expand).each(function(index) {
            if (index == 0) {
                allSame = $(this).text();
                return;
            }

            if (allSame != $(this).text()) {
                allSame = false;
            }
        });
        if (allSame) {
            $(SELECTORS.expandall).val(allSame == STRINGS.expand ? STRINGS.expandall : STRINGS.collapseall);
        }
    };

    var toggleAll = function() {
        var ecallbutton = $(SELECTORS.expandall);
        if (ecallbutton.first().val() == STRINGS.expandall) {
            $(SELECTORS.section).children('ul').slideDown();
            $(SELECTORS.expand).text(STRINGS.collapse);
            ecallbutton.val(STRINGS.collapseall);
        } else {
            $(SELECTORS.section).children('ul').slideUp();
            $(SELECTORS.expand).text(STRINGS.expand);
            ecallbutton.val(STRINGS.expandall);
        }
    };

    return {
        init: function(topicNumber, secId) {
            initNestedSortable();

            initStrings().then(function() {
                // Event listeners for expand/collapse.
                $(SELECTORS.expand).click(function() {
                    toggleSection($(this));
                });
                $(SELECTORS.expandall).click(toggleAll);

                // CCLE-2926 - Selective expand/collapse in rearrange tool.
                if (topicNumber >= 0) {
                    // Collapse all but given section.
                    var otherSections = $(SELECTORS.section + ':not(#s-section-' + secId + ')');
                    otherSections.children('ul').hide();
                    otherSections.find(SELECTORS.expand).text(STRINGS.expand);
                    $(SELECTORS.expandall).val(STRINGS.expandall);
                }

                // Replace JavaScript warning with list of sections.
                $('.js-show').show();
                $('.js-hide').hide();
                // Enable submit buttons.
                $('.btn-submit input').prop('disabled', false);
            });
        },
    };
});
