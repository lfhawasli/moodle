/**
 * Message for unsupported safari browsers. (<= 6.1.4)
 */

YUI().use('node', 'event', function(Y) {
    Y.on('domready', function() {
        var template = '<div class="unsupported-browser">' +
                            '<div class="container" >' +
                                '<h1>Please note that Safari 6.1.4 and lower has problems with the UCLA authentication system</h1>' +
                                '<p>We recommend using the latest ' +
                                    '<a href="https://support.apple.com/en-us/HT204416">Safari</a>,' +
                                    '<a href="http://ie.microsoft.com">Internet Explorer</a>, ' +
                                    '<a href="http://chrome.google.com">Google Chrome</a>, or ' +
                                    '<a href="http://www.mozilla.org/firefox">Firefox</a>.</p> ' +
                            '</div>' +
                        '</div>';

        Y.one('#page').insert(Y.Node.create(template), 'before');
    })
})
