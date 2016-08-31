<?php
defined('MOODLE_INTERNAL') || die;

$ADMIN->add('root', new admin_category('tweaks', 'Custom tweaks'));
$ADMIN->add('tweaks', new admin_externalpage('mediasite_courses', 'Tweak something',
            $CFG->wwwroot.'/local/mediasite_courses/setuppage.php'));
?>