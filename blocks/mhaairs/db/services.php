<?php
/**
 * Block MHAAIRS Improved
 *
 * @package    block
 * @subpackage mhaairs
 * @copyright  2013 Moodlerooms inc.
 * @author     Teresa Hardy <thardy@moodlerooms.com>
 */

// We defined the web service functions to install.
$functions = array(
        'block_mhaairs_gradebookservice' => array(
                'classname'   => 'block_mhaairs_gradebookservice_external',
                'methodname'  => 'gradebookservice',
                'classpath'   => 'blocks/mhaairs/externallib.php',
                'description' => 'Runs the grade_update() function',
                'type'        => 'read',
                'testclientpath' => 'blocks/mhaairs/testclient_forms.php'
        )
);

// We define the services to install as pre-build services. A pre-build service is not editable by administrator.
$services = array(
        'MHAAIRS Gradebook Service' => array(
                'functions'         => array ('block_mhaairs_gradebookservice'),
                'restrictedusers'   => 0,
                'enabled'           => 0
        )
);
