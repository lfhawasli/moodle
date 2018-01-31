<?php
// This file is part of the UCLA shared course theme for Moodle - http://moodle.org/
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
 * Configuration for UCLA's Shared Server theme.
 *
 * For full information about creating Moodle themes, see:
 *  http://docs.moodle.org/en/Development:Themes_2.0
 * @package    theme_uclashared
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2010 UC Regents
 */
defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    // Footer links.
    $themename = 'theme_uclashared';

    $options = array(
        'prod' => get_string('env_prod', $themename),
        'stage' => get_string('env_stage', $themename),
        'test' => get_string('env_test', $themename),
        'dev' => get_string('env_dev', $themename)
    );

    $thesetting = 'running_environment';
    $name = $themename . '/' . $thesetting;
    $title = get_string('setting_title_' . $thesetting, $themename);
    $description = get_string('setting_desc_' . $thesetting, $themename);
    $default = get_string('setting_default_' . $thesetting, $themename);
    $setting = new admin_setting_configselect($name, $title, $description,
            $default, $options);
    $settings->add($setting);

    $thesetting = 'footer_links';
    $name = $themename . '/' . $thesetting;
    $title = get_string('setting_title_' . $thesetting, $themename);
    $description = get_string('setting_desc_' . $thesetting, $themename);
    $default = get_string('setting_default_' . $thesetting, $themename);
    $setting = new admin_setting_configtextarea($name, $title, $description,
            $default, PARAM_RAW);
    $settings->add($setting);

    $thesetting = 'system_name';
    $name = $themename . '/' . $thesetting;
    $title = get_string('setting_title_' . $thesetting, $themename);
    $description = get_string('setting_desc_' . $thesetting, $themename);
    $default = get_string('setting_default_' . $thesetting, $themename);
    $setting = new admin_setting_configtext($name, $title, $description,
            $default);
    $settings->add($setting);

    $thesetting = 'system_link';
    $name = $themename . '/' . $thesetting;
    $title = get_string('setting_title_' . $thesetting, $themename);
    $description = get_string('setting_desc_' . $thesetting, $themename);
    $default = get_string('setting_default_' . $thesetting, $themename);
    $setting = new admin_setting_configtext($name, $title, $description,
            $default);
    $settings->add($setting);

    // CCLE-6512 - Profile Course details doesn't match My page Class sites.
    $thesetting = 'alternative_sharedsystem_name';
    $name = $themename . '/' . $thesetting;
    $title = get_string('setting_title_' . $thesetting, $themename);
    $description = get_string('setting_desc_' . $thesetting, $themename);
    $default = get_string('setting_default_' . $thesetting, $themename);
    $setting = new admin_setting_configtext($name, $title, $description,
            $default);
    $settings->add($setting);

    $thesetting = 'alternative_sharedsystem_link';
    $name = $themename . '/' . $thesetting;
    $title = get_string('setting_title_' . $thesetting, $themename);
    $description = get_string('setting_desc_' . $thesetting, $themename);
    $default = get_string('setting_default_' . $thesetting, $themename);
    $setting = new admin_setting_configtext($name, $title, $description,
            $default);
    $settings->add($setting);
}
