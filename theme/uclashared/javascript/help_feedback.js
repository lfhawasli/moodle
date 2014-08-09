/**
 * YUI script to toggle the help dropdown.
 * 
 *  - will show the dropdown on 'hover'
 *  - will remove the dropdown if user click anywhere else on body 
 */
YUI().use('node', 'event', function(Y) {

    Y.on('domready', function() {

        var dropdown = Y.one('.help-dropdown');

        if (dropdown) {
            // Attach 'hover' event
            dropdown.on('mouseenter', function() {

                // Show the menu
                Y.one('.help-dropdown-menu').removeClass('hidden');

                // Hides the menu and detaches event.
                var hidemenu = function() {
                    Y.one('.help-dropdown-menu').addClass('hidden');
                    this.detach();
                };

                // Attach an event on the document body to remove the menu on 'click'
                Y.one('body').on('click', hidemenu);

                // Attach event to other header buttons to hide the menu on 'hover'
                Y.all('.btn-header:not(.btn-help-feedback)').on('mouseenter', hidemenu);
            });
        }
    });
});

