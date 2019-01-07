YUI.add('moodle-course-modchooser', function (Y, NAME) {

/**
 * The activity chooser dialogue for courses.
 *
 * @module moodle-course-modchooser
 */

var CSS = {
    PAGECONTENT: 'body',
    SECTION: null,
    SECTIONMODCHOOSER: 'span.section-modchooser-link',
    SITEMENU: '.block_site_main_menu',
    SITETOPIC: 'div.sitetopic'
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
    sectionid: null,
    // START UCLA MOD: CCLE-7143 - Reimplement CCLE-5967 Simplify activity chooser.
    /**
     * The user preferences for pinned tools.
     *
     * @property userpinnedtools
     * @private
     * @type array
     * @default empty array
     */
    userpinnedtools: [],

    /**
     * Default top tools.
     *
     * @property defaulttools
     * @private
     * @type string
     * @default empty string
     */
    defaulttools: '',

    /**
     * Config for whether or not to use new modchooser.
     *
     * @property newmodchooser
     * @private
     * @type int
     * @default 0
     */
    newmodchooser: 0,
    // END UCLA MOD: CCLE-7143.
    /**
     * Set up the activity chooser.
     *
     * @method initializer
     */
    // START UCLA MOD: CCLE-7143 - Reimplement CCLE-5967 Simplify activity chooser.
    initializer: function(config) {
    // END UCLA MOD: CCLE-7143.
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
        // START UCLA MOD: CCLE-7143 - Reimplement CCLE-5967 Simplify activity chooser.
        // Save preferences for pinned tools
        if (config.userpinnedtools) {
            this.userpinnedtools = config.userpinnedtools.split(",");
        }

        // Save default tools
        if (config.defaulttools) {
            this.defaulttools = config.defaulttools;
        }

        // Save preference for new modchooser.
        if (config.newmodchooser) {
            this.newmodchooser = config.newmodchooser;
        }
    // END UCLA MOD: CCLE-7143.
    },

    /**
     * Update any section areas within the scope of the specified
     * selector with AJAX equivalents
     *
     * @method setup_for_section
     * @param baseselector The selector to limit scope to
     */
    setup_for_section: function(baseselector) {
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
    _setup_for_section: function(section) {
        var chooserspan = section.all(CSS.SECTIONMODCHOOSER);
        if (!chooserspan) {
            return;
        }
        chooserspan.on('click', this.display_mod_chooser, this);
    },
    /**
     * Display the module chooser
     *
     * @method display_mod_chooser
     * @param {EventFacade} e Triggering Event
     */
    display_mod_chooser: function(e) {
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
        // START UCLA MOD: CCLE-7143 - Reimplement CCLE-5967 Simplify activity chooser.
        // Prevent double click on star from redirecting
        Y.delegate('dblclick', function(e) {
            // Stop link redirection and any further propagation.
            e.preventDefault();
            e.stopImmediatePropagation();
        }, '.star, .star_empty');
        // END UCLA MOD: CCLE-7143.
        this.display_chooser(e);
        // START UCLA MOD: CCLE-7143 - Reimplement CCLE-5967 Simplify activity chooser.
        if (this.newmodchooser) {
            // Toggle between top tools or all tools.
            var thisevent = this.container.delegate('click', function(e) {
                // Show/hide unpinned tools.
                this.container.all(".tool:not(.pinned)").each(function(tool) {
                    tool.toggleView();
                });

                // Show/hide favorite stars. Only display links when in view all mode.
                this.container.all(".star, .star_empty").each(function(link) {
                    link.toggleView();
                });

                // Show/hide reset tools link. Only display when in view all mode.
                this.container.all(".resettools").toggleView();

                var showtoptools = M.util.get_string('showcategory', 'moodle', 'less');
                var showalltools = M.util.get_string('setfavoritetools', 'moodle');
                Y.all(".tooltoggle").each(function(tooltoggle) {
                    // Change link text to show all tools or show top tools.
                    if (tooltoggle.getContent() === showtoptools) {
                        tooltoggle.setContent(showalltools);
                    } else {
                        tooltoggle.setContent(showtoptools);
                    }
                });
                e.preventDefault();
            }, '.tooltoggle', this);
            this.listenevents.push(thisevent);

            // Create variable for click callback functions to access.
            var pinnedtools = this.userpinnedtools;

            // Listen to pin links.
            thisevent = this.container.delegate('click', function(e) {
                // Stop link redirection and any further propagation.
                e.preventDefault();
                e.stopImmediatePropagation();

                // Get module details.
                var module = this.ancestor('.tool');
                var moduleid = this.ancestor().previous('input').getAttribute('id').split("item_")[1];

                // Add module to pinned tools preference.
                pinnedtools.push(moduleid);

                // Update user preferences.
                M.util.set_user_preference('pinnedtools', pinnedtools.join(','));

                // Add pinned class.
                module.addClass('pinned');

                // Change empty star to filled star.
                MODCHOOSER.toggle_star(this);
            }, '.star_empty');
            this.listenevents.push(thisevent);

            // Listen to unpin links.
            thisevent = this.container.delegate('click', function(e) {
                // Stop link redirection and any further propagation.
                e.preventDefault();
                e.stopImmediatePropagation();

                // Get module details.
                var module = this.ancestor('.pinned');
                var moduleid = this.ancestor().previous('input').getAttribute('id').split("item_")[1];

                // Remove module from pinned tools preference.
                pinnedtools = pinnedtools.filter(function(tool) {
                    return tool !== moduleid;
                });

                // Update user preferences.
                M.util.set_user_preference('pinnedtools', pinnedtools.join(','));

                // Remove pinned class.
                module.removeClass('pinned');

                // Change filled star to empty star.
                MODCHOOSER.toggle_star(this);
            }, '.star');
            this.listenevents.push(thisevent);

            // Listen to reset tools link.
            var defaulttools = this.defaulttools;
            thisevent = this.container.delegate('click', function(e) {
                // Stop link redirection and any further propagation.
                e.preventDefault();
                e.stopImmediatePropagation();

                // Update user preferences to defaults.
                M.util.set_user_preference('pinnedtools', defaulttools);

                var defaultarray = defaulttools.split(',');
                // Pin/show default tools. Unpin nondefault tools.
                Y.all(".tool").each(function(tool) {
                    var moduleid = tool.one('input').getAttribute('id').split("item_")[1];
                    var link;
                    if (defaultarray.indexOf(moduleid) !== -1) {
                        // Pin default tool.
                        tool.addClass('pinned');
                        tool.show();

                        // Change empty star to filled star.
                        link = tool.one('.star_empty');
                        if (link) {
                            MODCHOOSER.toggle_star(link);
                        }
                    } else {
                        // Unpin nondefault tool.
                        tool.removeClass('pinned');

                        // Change filled star to empty star.
                        link = tool.one('.star');
                        if (link) {
                            MODCHOOSER.toggle_star(link);
                        }
                    }
                });
            }, '.resettools');
            this.listenevents.push(thisevent);
        }
    // END UCLA MOD: CCLE-7143.
    },

    /**
     * Helper function to set the value of a hidden radio button when a
     * selection is made.
     *
     * @method option_selected
     * @param {String} thisoption The selected option value
     * @private
     */
    option_selected: function(thisoption) {
        // Add the sectionid to the URL.
        this.hiddenRadioValue.setAttrs({
            name: 'jump',
            value: thisoption.get('value') + '&section=' + this.sectionid
        });
    }
},
{
    NAME: MODCHOOSERNAME,
    ATTRS: {
        /**
         * The maximum height (in pixels) of the activity chooser.
         *
         * @attribute maxheight
         * @type Number
         * @default 800
         */
        maxheight: {
            value: 800
        }
    // START UCLA MOD: CCLE-7143 - Reimplement CCLE-5967 Simplify activity chooser.
    },

    /**
     * Static helper function to swap filled and empty stars.
     *
     * @method toggle_star
     * @param {Object} container The wrapper around the star image.
     * @private
     */
    toggle_star: function(container) {
        var star;
        if (container.hasClass('star_empty')) {
            // Toggle from empty star to filled star.
            container.removeClass('star_empty');
            container.addClass('star');
            star = container.one('img');
            star.setAttribute('title', M.util.get_string('removetool', 'moodle'));
            star.setAttribute('src', M.util.image_url('i/star'));
        } else {
            // Toggle from filled star to empty star.
            container.removeClass('star');
            container.addClass('star_empty');
            star = container.one('img');
            star.setAttribute('title', M.util.get_string('addtool', 'moodle'));
            star.setAttribute('src', M.util.image_url('i/star_empty'));
    // END UCLA MOD: CCLE-7143.
        }
    // START UCLA MOD: CCLE-7143 - Reimplement CCLE-5967 Simplify activity chooser.
    }
    // END UCLA MOD: CCLE-7143.
});
M.course = M.course || {};
M.course.init_chooser = function(config) {
    return new MODCHOOSER(config);
};

}, '@VERSION@', {"requires": ["moodle-core-chooserdialogue", "moodle-course-coursebase"]});
