<?php
// Respondus LockDown Browser Extension for Moodle
// Copyright (c) 2011-2018 Respondus, Inc.  All Rights Reserved.
// Date: March 13, 2018.

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/accessrule/accessrulebase.php');

$lockdownbrowser_locklib_file =
  $CFG->dirroot . '/blocks/lockdownbrowser/locklib.php';
if (is_readable($lockdownbrowser_locklib_file)) {
    require_once($lockdownbrowser_locklib_file);
}

// our rule class
class quizaccess_lockdownbrowser extends quiz_access_rule_base {

    public static function make(quiz $quizobj, $timenow, $canignoretimelimits) {
        if ($quizobj->get_quiz()->browsersecurity !==
          get_string('browsersecuritychoicekey', 'quizaccess_lockdownbrowser')) {
            return null;
        }
        return new self($quizobj, $timenow);
    }

    public function prevent_access() {
        $extfunc = "lockdownbrowser_check_for_lock";
        if (!function_exists($extfunc)) {
            print_error("errnofunc", "quizaccess_lockdownbrowser", "", $extfunc);
        }
        $result = self::check_plugin_dependencies();
        if ($result === false) {
            $result = lockdownbrowser_check_for_lock($this->quizobj);
        }
        return $result;
    }

    public function description() {
        return get_string('lockdownbrowsernotice', 'quizaccess_lockdownbrowser');
    }

    public static function get_browser_security_choices() {
        return array(get_string('browsersecuritychoicekey', 'quizaccess_lockdownbrowser') =>
          get_string('requirelockdownbrowser', 'quizaccess_lockdownbrowser'));
    }

    public static function delete_settings($quiz) {
        try {
            lockdownbrowser_delete_options($quiz->id);
        } catch (Exception $ex) {
            // ignore possible exceptions
        }
    }

    /**
     * Whether the plugin dependencies are valid or not.
     * @return string false if dependencies are valid, a message explaining the
     *      reason if they are not.
     */
    protected static function check_plugin_dependencies() {
        $component = 'quizaccess_lockdownbrowser';
        $blockversion = self::get_block_version();
        if ($blockversion === false) {
            return get_string('noblockversion', $component);
        }
        $ruleversion = self::get_rule_version();
        if ($ruleversion === false) {
            return get_string('noruleversion', $component);
        }
        if (self::compare_plugin_versions($ruleversion, $blockversion) === false) {
            return get_string('invalidversion', $component);
        }
        return false;
    }

    /**
     * Retrieve the plugin version for this rule.
     * @return string the version string, or false if the version cannot be retrieved.
     */
    protected static function get_rule_version() {
        global $CFG;
        $plugin = new stdClass;
        $version_file = $CFG->dirroot . '/mod/quiz/accessrule/lockdownbrowser/version.php';
        if (is_readable($version_file)) {
            include($version_file);
        }
        $version = false;
        if (isset($plugin)) {
            if (!empty($plugin->version)) {
                $version = $plugin->version;
            }
        }
        return $version;
    }

    /**
     * Retrieve the plugin version for the LDB block.
     * @return string the version string, or false if the version cannot be retrieved.
     */
    protected static function get_block_version() {
        global $CFG;
        $plugin = new stdClass;
        $version_file = $CFG->dirroot . '/blocks/lockdownbrowser/version.php';
        if (is_readable($version_file)) {
            include($version_file);
        }
        $version = false;
        if (isset($plugin)) {
            if (!empty($plugin->version)) {
                $version = $plugin->version;
            }
        }
        return $version;
    }

    /**
     * Whether the plugin versions are considered equal or not, for the purpose
     * of dependency checking.
     * @return string true if versions are considered equal, false if they are not.
     */
    protected static function compare_plugin_versions($ruleversion, $blockversion) {
        $comparelength = 8; // yyyymmddxx, but only consider first 8 digits
        if (strlen($ruleversion) < $comparelength
          || strlen($blockversion) < $comparelength ) {
            return false;
        }
        if(substr($ruleversion, 0, $comparelength)
          == substr($blockversion, 0, $comparelength)) {
            return true;
        } else {
            return false;
        }
    }

}
