<?php
// This file is part of the UCLA local help plugin for Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Settings editor for help block.
 *
 * Allows admin to edit settings for help block. Please note that if settings
 * are set in the block's config.php file, then use those values only and
 * do not allow admin to change them via UI.
 *
 * @package    block_ucla_help
 * @author     Rex Lorenzo <rex@seas.ucla.edu>
 * @copyright  2011 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once(dirname(__FILE__) . '/ucla_help_lib.php');

if ($ADMIN->fulltree) {

    // Left-hand side box HTML.
    $settings->add(new admin_setting_confightmleditor('block_ucla_help/boxtext',
        get_string('settings_boxtext', 'block_ucla_help'),
        get_string('settings_boxtext_description', 'block_ucla_help'), ''));

    // Mail settings.
    $settings->add(new admin_setting_heading('block_ucla_help/email_header',
        get_string('settings_email_header', 'block_ucla_help'), ''));
    $settings->add(new admin_setting_configtext('block_ucla_help/fromemail',
        get_string('settings_fromemail', 'block_ucla_help'),
        get_string('settings_fromemail_description', 'block_ucla_help'), ''));

    // Jira settings.
    $settings->add(new admin_setting_heading('block_ucla_help/jira_header',
        get_string('settings_jira_header', 'block_ucla_help'),
        get_string('settings_jira_description', 'block_ucla_help')));
    $settings->add(new admin_setting_configtext('block_ucla_help/jira_endpoint',
        get_string('settings_jira_endpoint', 'block_ucla_help'), '', ''));
    $settings->add(new admin_setting_configtext('block_ucla_help/jira_user',
        get_string('settings_jira_user', 'block_ucla_help'), '', ''));
    $settings->add(new admin_setting_configpasswordunmask('block_ucla_help/jira_password',
        get_string('settings_jira_password', 'block_ucla_help'), '', ''));
    $settings->add(new admin_setting_configtext('block_ucla_help/jira_pid',
        get_string('settings_jira_pid', 'block_ucla_help'), '', ''));

    // File attachement settings.
    $settings->add(new admin_setting_heading('block_ucla_help/upload_header',
        get_string('settings_upload_header', 'block_ucla_help'), ''));
    $settings->add(new admin_setting_configcheckbox('block_ucla_help/enablefileuploads',
        get_string('settings_enablefileuploads', 'block_ucla_help'),
        get_string('settings_enablefileuploads_description', 'block_ucla_help'), 1));

    // Recaptcha settings.
    $recaptchaenabled = !empty($CFG->recaptchapublickey) && !empty($CFG->recaptchaprivatekey);
    $description = '';
    if (!$recaptchaenabled) {
        $description = get_string('settings_recaptcha_description', 'block_ucla_help');
    }
    $settings->add(new admin_setting_heading('block_ucla_help/recaptcha_header',
        get_string('settings_recaptcha_header', 'block_ucla_help'),
        $description));
    if ($recaptchaenabled) {
        $settings->add(new admin_setting_configcheckbox('block_ucla_help/enablerecaptcha',
            get_string('settings_enablerecaptcha', 'block_ucla_help'),
            get_string('settings_enablerecaptcha_description', 'block_ucla_help'), 0));
    }

    // Point of contact table.
    $settings->add(new admin_setting_heading('block_ucla_help/support_contacts_header',
        get_string('settings_support_contacts_header', 'block_ucla_help'),
        get_string('settings_support_contacts_description', 'block_ucla_help')));

    // First get list of contexts already defined.
    $contexts = get_config('block_ucla_help', "contexts");
    $settings->add(new admin_setting_ucla_help_support_contact());
}

