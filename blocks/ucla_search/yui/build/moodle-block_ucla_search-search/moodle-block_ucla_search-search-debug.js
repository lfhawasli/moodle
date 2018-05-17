YUI.add('moodle-block_ucla_search-search', function (Y, NAME) {

M.block_ucla_search = M.block_ucla_search || {};

M.block_ucla_search.search = {

    /**
     * Maximum number of results to return.
     * @type Number
     */
    RESULTLIMIT: 10,

    /**
     * Callback url.
     *
     * @type String
     */
    resturl: M.cfg.wwwroot + '/blocks/ucla_search/rest.php',

    /**
     * Sets up search results to display inline.
     * @param String searchname     Name of search box.
     */
    init: function(searchname) {

        var inputid = '#ucla-search-' + searchname;
        var showlist = false;

        var collabcheck = Y.one('.ucla-search.' + searchname + ' input[name="collab"]');
        var coursecheck = Y.one('.ucla-search.' + searchname + ' input[name="course"]');
        var bytitlecheck = Y.one('.ucla-search.' + searchname + ' input[name="bytitle"]');
        var bydescriptioncheck = Y.one('.ucla-search.' + searchname + ' input[name="bydescription"]');

        var template = '<div class="search-result">' +
                            '<div class="shortname">' +
                                '{shortname}' +
                            '</div>' +
                            '<div class="fullname">' +
                                '{fullname}' +
                            '</div>' +
                            '<div class="summary">' +
                                '{summary}' +
                            '</div>' +
                        '</div>';

        var formatter = function(query, results) {
            return Y.Array.map(results, function(result) {
                var out = result.raw;

                return Y.Lang.sub(template, {
                    shortname: out.shortname,
                    fullname: result.highlighted,
                    summary: out.summary
                });
            });
        };

        var params = function() {
            var collab = '&collab=1';
            var course = '&course=1';
            var bytitle = '&bytitle=1';
            var bydescription = '&bydescription=1';
            var limit = '&limit=' + M.block_ucla_search.search.RESULTLIMIT;

            if (searchname === 'block-search') {
                collab = collabcheck.get('checked') ? '&collab=1' : '&collab=0';
                course = coursecheck.get('checked') ? '&course=1' : '&course=0';
            } else if (searchname === 'collab-search') {
                collab = '&collab=1';
                course = '&course=0';
            } else if (searchname === 'course-search') {
                collab = '&collab=0';
                course = '&course=1';
            }

            bytitle = bytitlecheck.get('checked') ? '&bytitle=1' : '&bytitle=0';
            bydescription = bydescriptioncheck.get('checked') ? '&bydescription=1' : '&bydescription=0';
            return (collab + course + bytitle + bydescription + limit);
        };

        Y.one(inputid).plug(Y.Plugin.AutoComplete, {
            resultFormatter:    formatter,
            alwaysShowList:     showlist,
            maxResults:         this.RESULTLIMIT + 1,
            resultHighlighter:  'phraseMatch',
            minQueryLength:     3,
            scrollIntoView:     true,
            resultListLocator:  'results',
            resultTextLocator:  'text',
            queryDelay:         200,

            requestTemplate:    function(query) {
                return '?q=' + query + params();
            },

            source:             this.resturl,

            on: {
                select: function(e) {
                    window.location = e.result.raw.url;
                }
            }
        });
    }
};

}, '@VERSION@', {"requires": ["autocomplete", "autocomplete-highlighters", "autocomplete-filters"]});
