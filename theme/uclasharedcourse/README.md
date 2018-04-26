UCLA Shared Course Theme

You need the following tools:

* Stylelint: https://stylelint.io/
* SASS: https://sass-lang.com/install

To build theme SASS:

    This is not using Moodle's own SASS compilier because that is being used for
    the SASS from the UCLA Shared theme.

    We are using SASS to compile to style/uclasharedcourse.css, which is
    automatically picked up by Moodle and included.

    To check SASS files if they following coding conventions:
    stylelint theme/uclasharedcourse/sass/*.scss --syntax scss

    To compile SASS files:
    sass -t compressed theme/uclasharedcourse/sass/uclasharedcourse.scss theme/uclasharedcourse/style/uclasharedcourse.css

    To compile SASS files while you work with them:
    sass -t compressed --watch theme/uclasharedcourse/sass/uclasharedcourse.scss:theme/uclasharedcourse/style/uclasharedcourse.css

    After you finish your changes you can commit the compiled SASS file.
