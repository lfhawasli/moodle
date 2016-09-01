<?php

/**
 * Settings definition page
 *
 * @package    block_competencies
 * @copyright  2012 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Eric Bollens <ebollens@ucla.edu>
 */

require_once(dirname(__FILE__).'/lib.php');

if (!$PAGE->headerprinted) {
    $PAGE->requires->jquery();
}
$settings->add(new block_competencies_admin_setting_manager('competencies/competencies'));