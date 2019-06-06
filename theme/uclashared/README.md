UCLA Shared Theme

You need the following tools:

* Stylelint: https://stylelint.io/
* SASS: https://sass-lang.com/install

To build plugins SASS:

    For example, if you want to compile the SASS files for the UCLA course format
    do the following:

    To check SASS files if they following coding conventions:
    stylelint course/format/ucla/sass/*.scss --syntax scss

    To compile SASS files:
    sass -s compressed course/format/ucla/sass/styles.scss course/format/ucla/styles.css

    To compile SASS files while you work with them:
    sass -s compressed --watch course/format/ucla/sass/styles.scss:course/format/ucla/styles.css

    After you finish your changes you can commit the compiled SASS file. Change the
    paths to the appropriate plugin you are editing.

Developing theme SASS:
    
    Add new or change existing SASS files in theme/uclashared/scss directory.

    Moodle includes it's own built-in SASS compiler for themes.

    If you wish to add variables to be used in SASS files, add them to:
    theme/uclashared/scss/pre.scss

    If you wish to change SASS files, find the corresponding SASS file in
    theme/uclashared/scss. Then link the new file to:
    theme/uclashared/scss/post.scss

    If you need to add more SASS files make sure to @import them in this file.

    To reflect your changes be sure to purge the Moodle cache.