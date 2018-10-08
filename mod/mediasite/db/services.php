<?php

// We defined the web service functions to install.
$functions = array(
        'local_mediasite_getGroupMembership' => array(
                'classname' => 'local_mediasite_external',
                'methodname' => 'getGroupMembership',
                'classpath' => 'mod/mediasite/externallib.php',     
                'description' => 'Get group membership for a specific group.',
                'type' => 'read',
                'ajax' => true
        )
);

// We define the services to install as pre-build services. A pre-build service is not editable by administrator.
$services = array(
        'Mediasite Group Membership Service' => array(
                'functions' => array ('local_mediasite_getGroupMembership'),
                'restrictedusers' => 0,
                'enabled' => 0,
                'shortname' => 'mod_mediasite_groupservice'
        )
);