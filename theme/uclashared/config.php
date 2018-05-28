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

// Include Boost config so that we can override layouts.
require_once(__DIR__ . '/../boost/config.php');

$THEME->name = 'uclashared';

// Parent theme.
$THEME->parents = array('boost');

// Style sheets from our current theme.
$THEME->sheets = array(
    'flatpickr.min'
);

// Boost does not support dock, so neither will we.
$THEME->enable_dock = false;

// YUI CSS we want to include (we don't want any!).
$THEME->yuicssmodules = array();

// Most themes will use this rendererfactory as this is the one that allows the
// theme to override any other renderer.
$THEME->rendererfactory = 'theme_overridden_renderer_factory';

// Boost does not require any nav blocks because it provides other ways to
// navigate built into the theme.
$THEME->requiredblocks = '';

// This is a feature that tells the blocks library not to use the "Add a block"
// block. We don't want this in boost based themes because it forces a block
// region into the page when editing is enabled and it takes up too much room.
$THEME->addblockposition = BLOCK_ADDBLOCK_POSITION_DEFAULT;

// Override Boost configs for layouts.
$THEME->layouts['mydashboard']['options']['noheader'] = true;
// To prevent blocks from being displayed on non-section course pages.
$THEME->layouts['report']['regions'] = array();
$THEME->layouts['admin']['regions'] = array();
// The incourse layout is the default for modules and some modules place content
// in the block region.
global $PAGE;
switch ($PAGE->pagetype) {
    // All these pages call add_fake_block().
    case 'mod-book-delete':
    case 'mod-book-edit':
    case 'mod-book-view':
    case 'mod-lesson-continue':
    case 'mod-lesson-view':
    case 'mod-oublog-allposts':
    case 'mod-oublog-view':
    case 'mod-quiz-attempt':
    case 'mod-quiz-review':
    case 'mod-quiz-summary':
        $THEME->layouts['incourse']['regions'] = array('side-pre');
        break;
    default:
        $THEME->layouts['incourse']['regions'] = array();
}

// Add new SASS styles to an include file in theme/uclashared/scss/moodle.scss.
$THEME->scss = 'moodle';

// Add javascript files.
$THEME->javascripts = ['hamburger_icon', 'frontpage'];

$THEME->layouts['frontpage'] = array(
  'file' => 'frontpage.php',
);
