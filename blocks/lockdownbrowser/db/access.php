<?php
// Respondus LockDown Browser Extension for Moodle
// Copyright (c) 2011-2015 Respondus, Inc.  All Rights Reserved.
// Date: July 15, 2015.

$capabilities = array(

    'block/lockdownbrowser:addinstance' => array(
        'riskbitmask'          => RISK_SPAM | RISK_XSS,
        'captype'              => 'write',
        'contextlevel'         => CONTEXT_BLOCK,
        'archetypes'           => array(
            'guest'          => CAP_PREVENT,
            'student'        => CAP_PREVENT,
            'teacher'        => CAP_PREVENT,
            'editingteacher' => CAP_ALLOW,
            'manager'        => CAP_ALLOW
        ),
        'clonepermissionsfrom' => 'moodle/site:manageblocks'
    ),

);

