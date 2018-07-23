define(['jquery', 'jwplayer'], function($, jwplayer) {

    return {
        init: function(preferencename, playerid, httpurl, rtmpurl, isvideo) {
            // We need the id of the jwplayer on the page.
            var id = 'player-' + playerid;
            var playerinstance = jwplayer(id);

            if (isvideo === 1) {
                playerinstance.setup({
                    'autostart': true,
                    'width': '100%',
                    'aspectratio': '3:2',
                    'playlist': [{
                        'sources': [
                            {'file': httpurl},
                            {'file': rtmpurl}
                        ]
                    }],
                    'primary': 'html5'
                });
            } else {
                playerinstance.setup({
                    'autostart': true,
                    'image': M.cfg.wwwroot + '/theme/uclashared/pix/ucla-logo.png',
                    'sources': [
                        {'file': rtmpurl},
                        {'file': httpurl}
                    ],
                    'rtmp': {
                        'bufferlength': 3
                    },
                    'modes': [
                        { 'type': 'html5' },
                        { 'type': 'flash' }
                    ],
                    'height': 200
                });
            }

            var offset = 0;
            var shouldPlay = false;
            var audioSeeked = false;

            // Resume previous playback if the Resume button was clicked.
            var params = (new URL(window.location)).searchParams;
            if (params.get('offset')) {
                offset= params.get('offset');
                shouldPlay = true;
            }

            playerinstance.on('ready', function() {
                if (shouldPlay === true) {
                    playerinstance.play();
                }
            });

            playerinstance.on('firstFrame', function() {
                playerinstance.seek(offset);
            });

            // Workaround for resuming audio playback.
            if (!isvideo && !audioSeeked) {
                playerinstance.on('time', function() {
                    if (!audioSeeked && isFinite(playerinstance.getDuration())) {
                        playerinstance.seek(offset);
                        audioSeeked = true;
                    }
                });
            }

            // Store timestamp and set user preference every 30 seconds 
            // and right before page closes.
            var storeTimestamp = function() {
                var timestamp = parseInt(playerinstance.getPosition(), 10);
                if (timestamp === parseInt(playerinstance.getDuration(), 10)) {
                    timestamp = 'FINISHED';
                }
                M.util.set_user_preference(preferencename, timestamp);
            };
            setInterval(storeTimestamp, 30000);

            $(window).on('beforeunload', function() {
                storeTimestamp();
            });
        }
    };
});