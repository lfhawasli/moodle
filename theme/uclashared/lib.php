<?php

/**
 * Process a CSS directive to load a font.  Currently works for Bootstrap3 glyphicons.
 * SSC-2778: This filter now also gives choice for the frontpage image! The image
 * is specified in the config file, where its title is set in the config variable
 * "$CFG->forced_plugin_settings['theme_uclashared']['frontpage_image'];".
 * 
 * @global type $CFG
 * @param type $css
 * @param type $theme
 * @return type
 */
function uclashared_process_css($css, $theme) {
    global $CFG;
    // Load Boostrap glyphicon fonts.
    $tag = '[[uclashared:font|glyphicons-halflings-regular]]';
    $replacement = $CFG->wwwroot . '/theme/uclashared/vendor/twbs/bootstrap-sass/assets/fonts/bootstrap/glyphicons-halflings-regular';
    $css = str_replace($tag, $replacement, $css);
    
    // Load font-awesome fonts.
    $tag = '[[uclashared:font|fontawesome]]';
    $replacement = $CFG->wwwroot . '/theme/uclashared/vendor/fortawesome/font-awesome/fonts';
    $css = str_replace($tag, $replacement, $css);
    
    $tag = 'frontpage-image';
    $replacement = get_config('theme_uclashared', 'frontpage_image');
    $css = str_replace($tag, $replacement, $css);
    return $css;
}
