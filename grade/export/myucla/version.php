<?PHP 

$plugin->version  = 2016052300;
$plugin->requires = 2016051900;
$plugin->component = 'gradeexport_myucla'; // Full name of the plugin (used for diagnostics)

$plugin->dependencies = array( 
    'local_ucla' => 2011112800 //Uses the ucla_validator function in order to determine whether or not a UID is valid
);