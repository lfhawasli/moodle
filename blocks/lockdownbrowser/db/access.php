<?php
// Respondus LockDown Browser Extension for Moodle
// Copyright (c) 2011-2019 Respondus, Inc.  All Rights Reserved.
// Date: October 02, 2019.

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

