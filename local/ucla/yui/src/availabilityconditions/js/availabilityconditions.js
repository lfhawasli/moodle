// Based on popuphelp and tooltip

function AVAILABILITYCONDITIONS() {
    AVAILABILITYCONDITIONS.superclass.constructor.apply(this, arguments);
}

var SELECTORS = {
        CLOSEBUTTON: '.closebutton',
        CLICKABLELINKS: 'span.availabilitypopup > a',
        FOOTER: 'div.moodle-dialogue-ft'
    };

AVAILABILITYCONDITIONS.NAME = 'moodle-local_ucla-availabilityconditions';

Y.extend(AVAILABILITYCONDITIONS, Y.Base, {
    tooltip: null,

    initializer: function() {
        Y.one('body').delegate('click', this.display_panel, SELECTORS.CLICKABLELINKS, this);
    },

    display_panel: function(e) {
        if (!this.tooltip) {
            this.tooltip = new M.core.tooltip();
        }

        // The remainder of the function is essentially from M.core.tooltip::display_panel,
        // modified to use the data attribute (and not another AJAX request) to fetch the availability conditions.

        var clickedlink, thisevent;

        // Prevent the default click action and prevent the event triggering anything else.
        e.preventDefault();

        // Cancel any existing listeners and close the panel if it's already open.
        this.tooltip.cancel_events();

        // Grab the clickedlink - this contains the URL we fetch and we align the panel to it.
        clickedlink = e.target.ancestor('a', true);
        
        // Set availability conditions based on data attribute.
        this.tooltip.setAttrs({
            headerContent: 'Access restrictions',
            bodyContent: clickedlink.ancestor('span.availabilitypopup').getData('availabilityconditions'),
            footerContent: null
        });

        // Now that initial setup has begun, show the panel.
        this.tooltip.show(e);

        // Align with the link that was clicked.
        this.tooltip.align(clickedlink, this.tooltip.alignpoints);

        // Add some listen events to close on.
        thisevent = this.tooltip.bb.delegate('click', this.tooltip.close_panel, SELECTORS.CLOSEBUTTON, this.tooltip);
        this.tooltip.listenevents.push(thisevent);

        thisevent = Y.one('body').on('key', this.tooltip.close_panel, 'esc', this.tooltip);
        this.tooltip.listenevents.push(thisevent);

        // Listen for mousedownoutside events - clickoutside is broken on IE.
        thisevent = this.tooltip.bb.on('mousedownoutside', this.tooltip.close_panel, this.tooltip);
        this.tooltip.listenevents.push(thisevent);
    }
});

M.local_ucla = M.local_ucla || {};
M.local_ucla.availabilityconditions = M.local_ucla.availabilityconditions || null;
M.local_ucla.init_availabilityconditions = M.local_ucla.init_availabilityconditions || function(config) {
    if (!M.local_ucla.availabilityconditions) {
        M.local_ucla.availabilityconditions = new AVAILABILITYCONDITIONS(config);
    }
    return M.local_ucla.availabilityconditions;
};
