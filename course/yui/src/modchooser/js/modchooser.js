/**
 * The activity chooser dialogue for courses.
 *
 * @module moodle-course-modchooser
 */

var CSS = {
    PAGECONTENT : 'body',
    SECTION: null,
    SECTIONMODCHOOSER : 'span.section-modchooser-link',
    SITEMENU : 'div.block_site_main_menu',
    SITETOPIC : 'div.sitetopic'
};

var MODCHOOSERNAME = 'course-modchooser';

/**
 * The activity chooser dialogue for courses.
 *
 * @constructor
 * @class M.course.modchooser
 * @extends M.core.chooserdialogue
 */
var MODCHOOSER = function() {
    MODCHOOSER.superclass.constructor.apply(this, arguments);
};

Y.extend(MODCHOOSER, M.core.chooserdialogue, {
    /**
     * The current section ID.
     *
     * @property sectionid
     * @private
     * @type Number
     * @default null
     */
    sectionid : null,

    // START UCLA MOD: CCLE-6379 - Add ability to pin tools
    /**
     * The user preferences for pinned tools.
     *
     * @property userpinnedtools
     * @private
     * @type array
     * @default empty array
     */
    userpinnedtools : [],

    /**
     * The user preferences for unpinned top tools.
     *
     * @property unpinnedtoptools
     * @private
     * @type array
     * @default empty array
     */
    unpinnedtoptools : [],
    // END UCLA MOD: CCLE-6379

    /**
     * Set up the activity chooser.
     *
     * @method initializer
     */
    // START UCLA MOD: CCLE-6379 - Add ability to pin tools
    initializer : function(config) {
    // END UCLA MOD: CCLE-6379
        var sectionclass = M.course.format.get_sectionwrapperclass();
        if (sectionclass) {
            CSS.SECTION = '.' + sectionclass;
        }
        var dialogue = Y.one('.chooserdialoguebody');
        var header = Y.one('.choosertitle');
        var params = {};
        this.setup_chooser_dialogue(dialogue, header, params);

        // Initialize existing sections and register for dynamically created sections
        this.setup_for_section();
        M.course.coursebase.register_module(this);

        // START UCLA MOD: CCLE-6379 - Add ability to pin tools
        // Save preferences for pinned tools
        if (config.userpinnedtools) {
            this.userpinnedtools = config.userpinnedtools.split(",");
        }

        // Save preferences for unpinned top tools
        if (config.unpinnedtoptools) {
            this.unpinnedtoptools = config.unpinnedtoptools.split(",");
        }
        // END UCLA MOD: CCLE-6379

        // Catch the page toggle
        Y.all('.block_settings #settingsnav .type_course .modchoosertoggle a').on('click', this.toggle_mod_chooser, this);
    },

    /**
     * Update any section areas within the scope of the specified
     * selector with AJAX equivalents
     *
     * @method setup_for_section
     * @param baseselector The selector to limit scope to
     */
    setup_for_section : function(baseselector) {
        if (!baseselector) {
            baseselector = CSS.PAGECONTENT;
        }

        // Setup for site topics
        Y.one(baseselector).all(CSS.SITETOPIC).each(function(section) {
            this._setup_for_section(section);
        }, this);

        // Setup for standard course topics
        if (CSS.SECTION) {
            Y.one(baseselector).all(CSS.SECTION).each(function(section) {
                this._setup_for_section(section);
            }, this);
        }

        // Setup for the block site menu
        Y.one(baseselector).all(CSS.SITEMENU).each(function(section) {
            this._setup_for_section(section);
        }, this);
    },

    /**
     * Update any section areas within the scope of the specified
     * selector with AJAX equivalents
     *
     * @method _setup_for_section
     * @private
     * @param baseselector The selector to limit scope to
     */
    _setup_for_section : function(section) {
        var chooserspan = section.one(CSS.SECTIONMODCHOOSER);
        if (!chooserspan) {
            return;
        }
        var chooserlink = Y.Node.create("<a href='#' />");
        chooserspan.get('children').each(function(node) {
            chooserlink.appendChild(node);
        });
        chooserspan.insertBefore(chooserlink);
        chooserlink.on('click', this.display_mod_chooser, this);
    },
    /**
     * Display the module chooser
     *
     * @method display_mod_chooser
     * @param {EventFacade} e Triggering Event
     */
    display_mod_chooser : function (e) {
        // Set the section for this version of the dialogue
        if (e.target.ancestor(CSS.SITETOPIC)) {
            // The site topic has a sectionid of 1
            this.sectionid = 1;
        } else if (e.target.ancestor(CSS.SECTION)) {
            var section = e.target.ancestor(CSS.SECTION);
            this.sectionid = section.get('id').replace('section-', '');
        } else if (e.target.ancestor(CSS.SITEMENU)) {
            // The block site menu has a sectionid of 0
            this.sectionid = 0;
        }
        this.display_chooser(e);

        // START UCLA MOD: CCLE-6378 - Show only top tools
        var activitytoggle = this.container.one('#showallactivities');
        var resourcetoggle = this.container.one('#showallresources');

        // Toggle between top activities or all activities.
        var thisevent = activitytoggle.on('click', function(e){
            // Show/hide unpinned tools.
            this.container.all(".mod-0:not(.pinned)").each(function(tool) {
                tool.toggleView();
            });

            // Show/hide pin links. Only display links when in view all mode.
            this.container.all(".mod-0 .unpin-link, .mod-0 .pin-link").each(function(link) {
                link.toggleView();
            });

            var showtopactivities = M.util.get_string('showcategory', 'moodle', 'top activities');
            var showallactivities = M.util.get_string('showall', 'moodle', 'activities');

            // Change link text to show all activities or show top activities.
            if (activitytoggle.getContent() === showtopactivities) {
                activitytoggle.setContent(showallactivities);
            } else {
                activitytoggle.setContent(showtopactivities);
            }

            e.preventDefault();
        }, this);
        this.listenevents.push(thisevent);

        // Toggle between top resources or all resources.
        thisevent = resourcetoggle.on('click', function(e){
            // Show/hide unpinned tools.
            this.container.all(".mod-1:not(.pinned)").each(function(tool) {
                tool.toggleView();
            });

            // Show/hide pin links. Only display links when in view all mode.
            this.container.all(".mod-1 .unpin-link, .mod-1 .pin-link").each(function(link) {
                link.toggleView();
            });

            var showtopresources = M.util.get_string('showcategory', 'moodle', 'top resources');
            var showallresources = M.util.get_string('showall', 'moodle', 'resources');

            // Change link text to show all resources or show top resources.
            if (resourcetoggle.getContent() === showtopresources) {
                resourcetoggle.setContent(showallresources);
            } else {
                resourcetoggle.setContent(showtopresources);
            }

            e.preventDefault();
        }, this);
        this.listenevents.push(thisevent);
        // END UCLA MOD: CCLE-6378

        // START UCLA MOD: CCLE-6379 - Add ability to pin tools
        // Create variable for click callback functions to access.
        var pinnedtools = this.userpinnedtools;
        var unpinnedtop = this.unpinnedtoptools;

        // Listen to pin links.
        thisevent = this.container.delegate('click', function(e) {
            // Stop link redirection and any further propagation.
            e.preventDefault();
            e.stopImmediatePropagation();

            // Get module details.
            var module = this.ancestor('.mod-0, .mod-1');
            var moduletitle = this.previous().getContent();

            if (module.hasClass('top-tool')) {
                // Remove module from unpinned top tools.
                unpinnedtop = unpinnedtop.filter(function(tool) {
                    return tool !== moduletitle;
                });

                // Update user preferences.
                M.util.set_user_preference('unpinnedtop', unpinnedtop.join(','));

            } else {
                // Add module to pinned tools preference.
                pinnedtools.push(moduletitle);

                // Update user preferences.
                M.util.set_user_preference('pinnedtools', pinnedtools.join(','));
            }

            // Add pinned class.
            module.addClass('pinned');

            // Change pin link to unpin link.
            this.removeClass('pin-link');
            this.addClass('unpin-link');
            this.setContent('unpin');
        }, '.pin-link');
        this.listenevents.push(thisevent);

        // Listen to unpin links.
        thisevent = this.container.delegate('click', function (e) {
            // Stop link redirection and any further propagation.
            e.preventDefault();
            e.stopImmediatePropagation();

            // Get module details.
            var module = this.ancestor('.pinned');
            var moduletitle = this.previous().getContent();

            if (module.hasClass('top-tool')) {
                // Add module to unpinned top tools.
                unpinnedtop.push(moduletitle);

                // Update user preferences.
                M.util.set_user_preference('unpinnedtop', unpinnedtop.join(','));
            } else {
                // Remove module from pinned tools preference.
                pinnedtools = pinnedtools.filter(function(tool) {
                    return tool !== moduletitle;
                });

                // Update user preferences.
                M.util.set_user_preference('pinnedtools', pinnedtools.join(','));
            }

            // Remove pinned class, add tool class to allow hiding on toggle.
            module.removeClass('pinned');

            // Change unpin link to pin link
            this.removeClass('unpin-link');
            this.addClass('pin-link');
            this.setContent('pin');
        }, '.unpin-link');
        this.listenevents.push(thisevent);
        // END UCLA MOD: CCLE-6379
    },

    /**
     * Toggle availability of the activity chooser.
     *
     * @method toggle_mod_chooser
     * @param {EventFacade} e
     */
    toggle_mod_chooser : function(e) {
        // Get the add section link
        var modchooserlinks = Y.all('div.addresourcemodchooser');

        // Get the dropdowns
        var dropdowns = Y.all('div.addresourcedropdown');

        if (modchooserlinks.size() === 0) {
            // Continue with non-js action if there are no modchoosers to add
            return;
        }

        // We need to update the text and link
        var togglelink = Y.one('.block_settings #settingsnav .type_course .modchoosertoggle a');

        // The actual text is in the last child
        var toggletext = togglelink.get('lastChild');

        var usemodchooser;
        // Determine whether they're currently hidden
        if (modchooserlinks.item(0).hasClass('visibleifjs')) {
            // The modchooser is currently visible, hide it
            usemodchooser = 0;
            modchooserlinks
                .removeClass('visibleifjs')
                .addClass('hiddenifjs');
            dropdowns
                .addClass('visibleifjs')
                .removeClass('hiddenifjs');
            toggletext.set('data', M.util.get_string('modchooserenable', 'moodle'));
            togglelink.set('href', togglelink.get('href').replace('off', 'on'));
        } else {
            // The modchooser is currently not visible, show it
            usemodchooser = 1;
            modchooserlinks
                .addClass('visibleifjs')
                .removeClass('hiddenifjs');
            dropdowns
                .removeClass('visibleifjs')
                .addClass('hiddenifjs');
            toggletext.set('data', M.util.get_string('modchooserdisable', 'moodle'));
            togglelink.set('href', togglelink.get('href').replace('on', 'off'));
        }

        M.util.set_user_preference('usemodchooser', usemodchooser);

        // Prevent the page from reloading
        e.preventDefault();
    },

    /**
     * Helper function to set the value of a hidden radio button when a
     * selection is made.
     *
     * @method option_selected
     * @param {String} thisoption The selected option value
     * @private
     */
    option_selected : function(thisoption) {
        // Add the sectionid to the URL.
        this.hiddenRadioValue.setAttrs({
            name: 'jump',
            value: thisoption.get('value') + '&section=' + this.sectionid
        });
    }
},
{
    NAME : MODCHOOSERNAME,
    ATTRS : {
        /**
         * The maximum height (in pixels) of the activity chooser.
         *
         * @attribute maxheight
         * @type Number
         * @default 800
         */
        maxheight : {
            value : 800
        }
    }
});
M.course = M.course || {};
M.course.init_chooser = function(config) {
    return new MODCHOOSER(config);
};
