<?php
/**
 * Block MHAAIRS Improved
 *
 * @package    block
 * @subpackage mhaairs
 * @copyright  2013 Moodlerooms inc.
 * @author     Teresa Hardy <thardy@moodlerooms.com>
 */

defined('MOODLE_INTERNAL') || die();

if (!$ADMIN->fulltree) {
    return;
}

global $CFG, $PAGE;

require_once($CFG->dirroot.'/blocks/mhaairs/settingslib.php');

$settings->add(new admin_setting_configcheckbox(
        'block_mhaairs_sslonly',
        get_string('sslonlylabel', 'block_mhaairs', null, true),
        '', 0
));

$settings->add(new admin_setting_configtext(
        'block_mhaairs_customer_number',
        get_string('customernumberlabel', 'block_mhaairs', null, true),
        '',
        '',
        PARAM_ALPHANUMEXT
));

$settings->add(new admin_setting_configtext(
        'block_mhaairs_shared_secret',
        get_string('secretlabel', 'block_mhaairs', null, true),
        '',
        '',
        PARAM_ALPHANUMEXT
));

$settings->add(new admin_setting_configtext(
        'block_mhaairs_base_address',
        get_string('baseaddresslabel', 'block_mhaairs', null, true),
        '',
        'https://aairs-connectors.tegrity.com/sso/aairs/',
        PARAM_URL
));

$adminurl = new moodle_url('/admin/settings.php');
if ($PAGE->url->compare($adminurl, URL_MATCH_BASE)) {
    $settings->add(new admin_setting_configmulticheckbox_mhaairs (
            'block_mhaairs_display_services',
            get_string('services_displaylabel', 'block_mhaairs', null, true),
            get_string('services_desc', 'block_mhaairs', null, true)
    ));
}

$settings->add(new admin_setting_configcheckbox(
        'block_mhaairs_display_helplinks',
        get_string('mhaairs_displayhelp', 'block_mhaairs', null, true),
        get_string('mhaairs_displayhelpdesc', 'block_mhaairs', null, true),
        1
));

$settings->add(new admin_setting_configcheckbox(
        'block_mhaairs_sync_gradebook',
        get_string('mhaairs_syncgradebook', 'block_mhaairs', null, true),
        get_string('mhaairs_syncgradebookdesc', 'block_mhaairs', null, true),
        1
));
