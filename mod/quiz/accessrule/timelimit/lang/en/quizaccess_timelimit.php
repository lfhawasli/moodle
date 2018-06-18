<?php
// This file is part of Moodle - http://moodle.org/
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
 * Strings for the quizaccess_timelimit plugin.
 *
 * @package    quizaccess
 * @subpackage timelimit
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

// START UCLA MOD CCLE-5699 AND CCLE-6042 Improve Quiz pop-up Warning.
//$string['confirmstartheader'] = 'Timed quiz';
//$string['confirmstart'] = 'The quiz has a time limit of {$a}. Time will count down from the moment you start your attempt and you must submit before it expires. Are you sure that you wish to start now?';
$string['confirmstartheader'] = 'Warning: Timed Quiz';
$string['confirmstart'] = '<i class="fa fa-arrow-right" aria-hidden="true"></i>
 The quiz has a time limit of {$a->timelimit}.<br><i class="fa fa-arrow-right" aria-hidden="true"></i>
 You can ONLY attempt the quiz {$a->attempts} time(s).<br><i class="fa fa-arrow-right" aria-hidden="true"></i>
 Time will count down from the moment you start your attempt.<br><i class="fa fa-arrow-right" aria-hidden="true"></i>
 You must click the Submit button before time expires or the attempt will not count.<br><i class="fa fa-arrow-right" aria-hidden="true"></i>
 Are you sure that you wish to start now?';
$string['confirmstartnolimit'] = '<i class="fa fa-arrow-right" aria-hidden="true"></i>
 The quiz has a time limit of {$a}.<br><i class="fa fa-arrow-right" aria-hidden="true"></i>
 Time will count down from the moment you start your attempt.<br><i class="fa fa-arrow-right" aria-hidden="true"></i>
 You must click the Submit button before time expires or attempt will not count.<br><i class="fa fa-arrow-right" aria-hidden="true"></i>
 Are you sure that you wish to start now?';
$string['confirmstartsafe'] = '<i class="fa fa-arrow-right" aria-hidden="true"></i>
 The quiz has a time limit of {$a->timelimit}.<br><i class="fa fa-arrow-right" aria-hidden="true"></i>
 You can ONLY attempt the quiz {$a->attempts} time(s).<br><i class="fa fa-arrow-right" aria-hidden="true"></i>
 Time will count down from the moment you start your attempt.<br><i class="fa fa-arrow-right" aria-hidden="true"></i>
 Attempt will auto-submit if time expires.<br><i class="fa fa-arrow-right" aria-hidden="true"></i>
 Are you sure that you wish to start now?';
$string['confirmstartsafenolimit'] = '<i class="fa fa-arrow-right" aria-hidden="true"></i>
 The quiz has a time limit of {$a}.<br><i class="fa fa-arrow-right" aria-hidden="true"></i>
 Time will count down from the moment you start your attempt.<br><i class="fa fa-arrow-right" aria-hidden="true"></i>
 Attempt will auto-submit if time expires.<br><i class="fa fa-arrow-right" aria-hidden="true"></i>
 Are you sure that you wish to start now?';
// END UCLA MOD CCLE-6042 AND CCLE-5699 Fixed Quiz Confirmation.
$string['pluginname'] = 'Time limit quiz access rule';
$string['privacy:metadata'] = 'The Time limit quiz access rule plugin does not store any personal data.';
$string['quiztimelimit'] = 'Time limit: {$a}';
