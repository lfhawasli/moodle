<?php

defined('MOODLE_INTERNAL') || die();

$capabilities = array(
    'local/ucla_support_tools:view' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'manager' => CAP_ALLOW,
        )
    ),
    'local/ucla_support_tools:edit' => array(
        'riskbitmask' => RISK_DATALOSS | RISK_SPAM | RISK_CONFIG,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'manager' => CAP_PROHIBIT,
        )
    ),
);
