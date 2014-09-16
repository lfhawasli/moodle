/**
 * YUI script to toggle the help dropdown.
 * 
 *  - will show the dropdown on 'hover'
 *  - will remove the dropdown if user 'taps' anywhere else on body 
 */
YUI().use('node', 'event', 'event-tap', function(Y) {

    Y.on('domready', function() {

        var dropdown = Y.one('.btn-help-feedback');

        if (dropdown) {
            // Toggle the down-caret
            dropdown.on('mouseenter', function(e) {
                try {
                    e.target.one('i').removeClass('fa-question-circle').addClass('fa-caret-down');
                } catch (err) {
                    /// ignore
                }
            });
            dropdown.on('mouseleave', function(e) {
                try {
                    e.target.one('i').removeClass('fa-caret-down').addClass('fa-question-circle');
                } catch (err) {
                    /// ignore
                }                
            });
            // Attach 'hover' event
            dropdown.on('mouseenter', function(e) {
                e.preventDefault();

                // Show the menu
                var dropdownmenu = Y.one('.help-dropdown-menu');
                dropdownmenu.removeClass('hidden');
                
                // Hide menu on mouseleav
                Y.one('.help-dropdown').on('mouseleave', function() {
                    dropdownmenu.addClass('hidden');
                });

            });

            // Tap hide
            Y.one('body').on('touchstart', function() {
                Y.one('.help-dropdown-menu').addClass('hidden');
            });
        }
    });
});

