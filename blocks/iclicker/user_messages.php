<?php
/**
 * Copyright (c) 2012 i>clicker (R) <http://www.iclicker.com/dnn/>
 *
 * This file is part of i>clicker Moodle integrate.
 *
 * i>clicker Moodle integrate is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * i>clicker Moodle integrate is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with i>clicker Moodle integrate.  If not, see <http://www.gnu.org/licenses/>.
 */
/* $Id: user_messages.php 139 2012-04-20 03:19:57Z azeckoski@gmail.com $ */

// get the messages
$infos = $cntlr->getMessages(iclicker_controller::KEY_INFO);
$alerts = $cntlr->getMessages(iclicker_controller::KEY_ERROR);
?>
<?php if (count($infos) > 0) { ?>
<div class="information user_messages alert_messages informationbox">
    <ul class="messages_list">
        <?php foreach ($infos as $message) { ?>
        <li class="user_message info_message"><?php echo $message ?></li>
        <?php } ?>
    </ul>
</div>
<?php } ?>
<?php if (count($alerts) > 0) { ?>
<div class="alertMessage user_messages info_messages errorbox">
    <ul class="messages_list">
        <?php foreach ($alerts as $message) { ?>
        <li class="user_message alert_message"><?php echo $message ?></li>
        <?php } ?>
    </ul>
</div>
<?php } ?>

<?php
    // START UCLA MOD: CCLE-2203 - i>clicker integration
    // Add links to download UCLA specific i>clicker version.
    if (basename($_SERVER['PHP_SELF']) != 'registration.php') {
        // Do not display on remote registration page.
        echo $OUTPUT->box_start('noticebox');
        echo html_writer::tag('span', 'Download UCLA version of the i>clicker software:');
        $packages = array('Win' => 'Windows version', 'Mac' => 'Mac version');
        foreach ($packages as $package => $description) {
            $packages[$package] = html_writer::link(
                    new moodle_url(sprintf('/blocks/iclicker/files/CCLE Shared server - iclicker (%s).zip',
                            $package)), $description);
        }
        echo html_writer::alist($packages);
        echo $OUTPUT->box_end();
    }
    // END UCLA MOD: CCLE-2203
?>