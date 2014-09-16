# UCLA theme SASS
# 
# Usage:
# 
# For a one-off build (default debug output)
# 
#   $ compass compile
# 
# Or for automated builds that watch project files
# 
#   $ compass watch
# 
# For production environments with compressed stylesheets
# 
#   $ compass compile -e production --force 

#require 'sass-css-importer'
#add_import_path Sass::CssImporter::Importer.new("vendor")

## Location of CSS dir
css_dir = "style"

## Location of SASS dir
sass_dir = "sass"

## Location of images
images_dir = "pix"

## Location of javascript
javascripts_dir = "javascript"

## Import boostrap package
additional_import_paths = ["vendor/twbs/bootstrap-sass/assets/stylesheets", "vendor/fortawesome", sass_dir]

## For production environment, use compressed CSS
output_style = (environment == :production) ? :compressed : :expanded
