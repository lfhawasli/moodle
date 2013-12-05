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
 * The NanoGong TinyMCE plugin
 *
 * @author     Ning
 * @author     Gibson
 * @copyright  2012 The Gong Project
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @version    4.2.2
 */

define('NO_MOODLE_COOKIES', false); // Session not used here

// START UCLA MOD: CCLE-4156-upgrade-nanogong-work-with-Moodle-2.5
//require_once(dirname(dirname(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))))) . '/config.php');
require('../../../../../config.php');
// END UCLA MOD: CCLE-4156-upgrade-nanogong-work-with-Moodle-2.5

require_login();
$editor = get_texteditor('tinymce');
$plugin = $editor->get_plugin('nanogong');
$itemid = optional_param('itemid', '', PARAM_TEXT); 
$contextid=get_context_instance(CONTEXT_USER, $USER->id)->id;

@header('Content-Type: text/html; charset=utf-8');
// END UCLA MOD: CCLE-4156-upgrade-nanogong-work-with-Moodle-2.5

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <title>Insert NanoGong recording</title>
    <script type="text/javascript" src="<?php echo $editor->get_tinymce_base_url(); ?>tiny_mce_popup.js"></script>
    <script type="text/javascript" src="<?php echo $plugin->get_tinymce_file_url('js/nanogong.js'); ?>"></script>
    <link href="tinymce/css/nanogong.css" rel="stylesheet" type="text/css" />
</head>

<body>

<form>
    <table class="main">
        <tr><td colspan="2">Please record your voice using this applet.</td></tr>
        <tr>
            <td class="applet">
                <table>
                    <tr>
                        <td><img src="<?php echo $plugin->get_tinymce_file_url('img/nanogong.gif'); ?>" alt="NanoGong Sound" /></td>
                        <td><applet id="nanogong" archive="<?php echo "{$CFG->wwwroot}/filter/nanogong/nanogong.jar"; ?>" code="gong.NanoGong" width="180" height="40"></applet></td>
                        <td style="width: 16px">&nbsp;</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr><td colspan="2" id="nanogong_message">After you have finished recording, press Insert.</td></tr>
    </table>
    <input type="hidden" name="contextid" value= "<?php echo $contextid;?>" id="context_id">
    <input type="hidden" name="itemid" value= "<?php echo $itemid;?>" id="item_id">
    <input type="button" id="insert" name="insert" value="{#insert}" onclick="NanogongDialog.insert(<?php echo $_GET['itemid']; ?>);" />
    <input type="button" id="cancel" name="cancel" value="{#cancel}" onclick="tinyMCEPopup.close();" />
</form>

</body>

</html>
