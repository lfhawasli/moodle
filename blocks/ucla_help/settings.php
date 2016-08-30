<?php
/**
 * Allows admin to edit settings for help block. Please note that if settings
 * are set in the block's config.php file, then use those values only and
 * do not allow admin to change them via UI.
 *
 * @package    ucla
 * @subpackage ucla_help
 * @copyright  2011 UC Regents    
 * @author     Rex Lorenzo <rex@seas.ucla.edu>                                         
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later 
 */
require_once(dirname(__FILE__) . '/ucla_help_lib.php');

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    
    // left-hand side box HTML.
    $settings->add(new admin_setting_confightmleditor('block_ucla_help/boxtext', 
        get_string('settings_boxtext', 'block_ucla_help'), 
        get_string('settings_boxtext_description', 'block_ucla_help'), ''));       
    
    // mail settings.
    $settings->add(new admin_setting_heading('block_ucla_help/email_header', 
        get_string('settings_email_header', 'block_ucla_help'), ''));
    $settings->add(new admin_setting_configtext('block_ucla_help/fromemail',
        get_string('settings_fromemail', 'block_ucla_help'),
        get_string('settings_fromemail_description', 'block_ucla_help'), ''));
    
    // jira settings.
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

    // point of contact table.
    $settings->add(new admin_setting_heading('block_ucla_help/support_contacts_header', 
        get_string('settings_support_contacts_header', 'block_ucla_help'), 
        get_string('settings_support_contacts_description', 'block_ucla_help')));
    
    // first get list of contexts already defined.
    $contexts = get_config('block_ucla_help', "contexts");
    $settings->add(new admin_setting_ucla_help_support_contact());    
}

