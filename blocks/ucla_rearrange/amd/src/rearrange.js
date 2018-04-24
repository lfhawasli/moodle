define(['jquery', 'block_ucla_rearrange/jquery.mjs.nestedSortable'], function($) {
    var initNestedSortable = function() {
        // TODO remove all nonnesting/invisible
        $('#s-list').nestedSortable({
            handle: 'div',
            items: 'li',
            listType: 'ul',
            toleranceElement: '> div',

            // TODO site info selector
            // TODO allow sorting within site info
            cancel: '#s-list > li:first-child()',
            doNotClear: true,
            isAllowed: function(placeholder, parent, current) {
                var isSectionItem = current.hasClass('s-list-item');
                var isBeforeSiteInfo = placeholder.nextAll('#s-section-1901').length > 0;
                return !(isSectionItem && isBeforeSiteInfo);
            },
            protectRoot: true,

            forcePlaceholderSize: true,
            opacity: 0.6,
            placeholder: 'placeholder',
            revert: 200,
            tabSize: 32,

            excludeRoot: true,
            stop: updateSerialized
        });

        updateSerialized();
    };

    var updateSerialized = function() {
        serialized = JSON.stringify($('#s-list').nestedSortable('toHierarchy'));
        $('#serialized').val(serialized);
    };

    // Toggle section(s) corresponding to the passed Expand/Collapse button(s).
    var toggleSection = function(ecbutton) {
        var section = ecbutton.closest('.section-item');
        section.children('ul').slideToggle();
        ecbutton.text(ecbutton.text() == 'Collapse' ? 'Expand' : 'Collapse');

        // If all sections can only be expanded/collapsed, set expand all/collapse all button.
        var allSame;
        $('.expand-button').each(function(index) {
            if (index == 0) {
                allSame = $(this).text();
                return;
            }

            if (allSame != $(this).text()) {
                allSame = false;
            }
        });
        if (allSame) {
            $('.expandall').val(allSame == 'Expand' ? 'Expand all' : 'Collapse all');
        }
    };

    var toggleAll = function() {
        var ecallbutton = $('.expandall');
        if (ecallbutton.first().val() == 'Expand all') {
            $('.section-item').children('ul').slideDown();
            $('.expand-button').text('Collapse');
            ecallbutton.val('Collapse all');
        } else {
            $('.section-item').children('ul').slideUp();
            $('.expand-button').text('Expand');
            ecallbutton.val('Expand all');
        }
    };

    return {
        init: function(topicNumber, secId) {
        // "JavaScript required" warning?
//            $('#major-ns-container').html(sections);

            initNestedSortable();

            // expand/collapse
            $('.expand-button').click(function() {
                toggleSection($(this));
            });
            $('.expandall').click(toggleAll);

            // CCLE-2926 - Selective expand/collapse in rearrange tool.
            if (topicNumber >= 0) {
                // Collapse all but given section.
                var otherSections = $('.section-item:not(#s-section-' + secId + ')');
                otherSections.children('ul').hide();
                otherSections.find('.expand-button').text('Expand');
                $('.expandall').val('Expand all');
            }
        },
    };
});
