define(['jquery', 'jwplayer'], function($, jwplayer) {

    return {
        init: function(preferencename, playerid, httpurl, isvideo, mediatype, rtmpurl, srtpath, mbrjs) {
            // We need the id of the jwplayer on the page.
            var id = 'player-' + playerid;
            var playerinstance = jwplayer(id);
            var playbackrates = M.util.get_string('playbackrates', 'filter_oidwowza');
            var rewind = M.util.get_string('rewind', 'filter_oidwowza');
            var options = {
                localization: {
                    settings: playbackrates,
                    rewind: rewind
                }
            };

            if (isvideo === 1) {
                options.autostart = true;
                options.width = '100%';
                options.aspectratio = '3:2';
                options.primary = 'html5';
                options.playlist = [{
                    sources: [
                        { file: httpurl },
                        { file: rtmpurl }
                    ]
                }];

                if (mediatype === 'vidres') {
                    if (srtpath) {
                        options.playlist[0].tracks = [{
                            file: srtpath,
                            kind: 'captions',
                            default: true
                        }];
                    }

                    if (mbrjs) {
                        options.plugins = {
                            'hd-2': {
                                file: mbrjs,
                                state: true
                            }
                        };
                    }
                }
            } else {
                options.image = M.cfg.wwwroot + '/filter/oidwowza/pix/audio-only.png';
                options.height = 200;
                options.autostart = true;
                options.sources = [
                    { file: rtmpurl },
                    { file: httpurl }
                ];
                options.rtmp = {
                    bufferlength: 3
                };
                options.modes = [
                    { type: 'html5' },
                    { type: 'flash' }
                ];
            }

            playerinstance.setup(options);

            var offset = 0;
            var shouldPlay = false;
            var movedbuttons = false;
            var audioSeeked = false;

            // Add button to skip ahead 10 seconds.
            var forward_10_callback = function() {
                playerinstance.seek(playerinstance.getPosition() + 10);
            };
            var skipstring = M.util.get_string('skipahead', 'filter_oidwowza');
            playerinstance.addButton(M.cfg.wwwroot + '/filter/oidwowza/pix/fast-forward.svg', skipstring,
                    forward_10_callback, 'jw-btn-fastforward', 'jw-fastforward');

            playerinstance.on('beforePlay', function() {
                if (!movedbuttons) {
                    $('.jw-fastforward.jw-icon-inline').insertAfter('.jw-icon-playback');
                    $('.jw-icon-rewind.jw-icon-inline').insertBefore('.jw-icon-playback');
                    movedbuttons = true;
                }
            });

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
