# UCLA theme SASS compilation.  This 
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

require './compass.rb'
require 'sass-css-importer'
add_import_path Sass::CssImporter::Importer.new("vendor")

## UCLA theme directory
theme_dir = "theme/uclashared"

## Location of CSS dir
css_dir = "#{theme_dir}/style"

## Location of SASS dir
sass_dir = "#{theme_dir}/sass"

## Location of images
images_dir = "#{theme_dir}/pix"

## Location of javascript
javascripts_dir = "#{theme_dir}/javascript"

## Import boostrap package
additional_import_paths = ["#{theme_dir}/vendor/twbs/bootstrap-sass/assets/stylesheets", "#{theme_dir}/vendor/fortawesome", sass_dir]

## For production environment, use compressed CSS
output_style = (environment == :production) ? :compressed : :expanded


# Set up compass 'watch' for Moodle modules
# 
# When you run 'compass watch', it will listen for changes to a 'styles.scss' file in 
# the 'sass' folder of any Moodle module and compile it to a 'styles.css' file 
# that Moodle automatically loads.
# 
# This scheme also gives module sass access to all the mixins defined for the project theme, 
# so that it's possible to require any dependency in your local module styles.scss file
# 
watch "**/sass/*.scss" do |project_dir, relative_path|
  if File.exists?(File.join(project_dir, relative_path))
    
    ## Compile compass inside compass, whoa!
    
    # Print a 'modified filename' message
    print " modified ".brown
    print relative_path
    print "\n"

    # Create the SASS path
    module_sass = relative_path.sub(/sass\/_?.*\.scss/, "sass/")
    # Create the CSS path
    module_css = relative_path.sub(/sass\/_?.*\.scss/, "")

    # Generate the compile command and run system call.
    cmd = "compass compile --sass-dir #{module_sass} --css-dir #{module_css} -I #{sass_dir} -e production"
    system(cmd)

    # Print a 'wrote styles.css' message
    print "    write ".green
    print "#{module_css}styles.css"
    print "\n"

  end
end


# To enable relative paths to assets via compass helper functions. Uncomment:
# relative_assets = true

# To disable debugging comments that display the original location of your selectors. Uncomment:
# line_comments = false

