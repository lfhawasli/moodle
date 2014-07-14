<?php 
// Responsive frontpage layout
global $CFG, $PAGE;

// We need alert & search blocks
require_once($CFG->dirroot . '/blocks/ucla_search/block_ucla_search.php');
require_once($CFG->dirroot . '/blocks/ucla_alert/locallib.php');
require_once($CFG->dirroot . '/blocks/ucla_browseby/browseby_handler_factory.class.php');

// Load advanced search module
$PAGE->requires->yui_module('moodle-block_ucla_search-search', 'M.ucla_search.init', 
        array(array('name' => 'frontpage-search')));

?>

<div class="row frontpage-layout">
    <div class="col-md-4 frontpage-banner" >
        <!--banner lives here-->
        <div class="row">
            <div class=" frontpage-shared-server hidden-xs" >
                <h4 class="visible-md visible-lg">
                    <span class="glyphicon glyphicon-link"></span>
                    <?php echo get_config('theme_uclashared', 'system_name') ?>
                </h4>
                <h4 class="server-link hidden-md hidden-lg">
                    <span class="glyphicon glyphicon-chevron-up"></span>
                    <?php echo get_config('theme_uclashared', 'system_name') ?>
                </h4>                                                      
                <?php
                $subtextstring = get_config('theme_uclashared', 'logo_sub_text');
                if (!empty($subtextstring)) { ?>
                    <div class="shared-server-list">
                        <?php echo get_string('setting_default_logo_sub_text', 'theme_uclashared'); ?>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
    
    <!--front page content-->
    <div class="col-md-8 frontpage-content" >
        <div class="row">
            <div class="col-xs-12 visible-md visible-lg" style="height: 100px"></div>
        </div>
        <div class="row frontpage-search">
            <div class="col-xs-12">
                <?php 
                    echo block_ucla_search::search_form('frontpage-search');
                ?>
            </div>
        </div>
        <div class="row frontpage-navigation ">
            
            <div class="col-lg-10 col-lg-offset-1">
                <div class="row">
                    <div class="col-lg-4">
                        <h3>
                            <!--<span class="glyphicon glyphicon-book "></span>-->
                            Browse CCLE
                        </h3>
                        <ul class="frontpage-browseby">
                            <?php
                                $types = browseby_handler_factory::get_available_types();
                                foreach ($types as $type) {
                                    $chevron = html_writer::tag('span', '', 
                                            array('class' => 'glyphicon glyphicon-chevron-right'));
                                    $innercontent = $chevron .
                                            get_string('link_'.$type, 'block_ucla_browseby');
                                    $link = html_writer::link(
                                            new moodle_url('blocks/ucla_browseby/view.php', array('type' => $type)),
                                            $innercontent,
                                            array('class' => 'btn btn-link'));
                                    echo html_writer::tag('li', $link);
                                }
                            ?>
                        </ul>

                    </div>
                    <div class="col-lg-4">
                        <h3>
                            <!--<span class="glyphicon glyphicon-log-in pull-left"></span>-->
                            Login and ...
                        </h3>
                        <ul class="frontpage-login-and">
                            <li>
                                <a target="_blank" href="https://archive.ccle.ucla.edu/" class="btn btn-link">
                                    <span class="glyphicon glyphicon-chevron-right"></span>
                                    View sites created prior to Summer 2012
                                </a>
                            </li>
                            <li>
                                <a target="_blank" href="<?php echo new moodle_url('/course/request.php');?>" class="btn btn-link">
                                    <span class="glyphicon glyphicon-chevron-right"></span>
                                    Request a collaboration site
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="col-lg-4">
                        <h3>
                            <!--<span class="glyphicon glyphicon-question-sign"></span>-->
                            Help & Feedback
                        </h3>
                        <ul class="frontpage-help-feedback">
                            <li>
                                <a href="<?php echo new moodle_url('/blocks/ucla_help/index.php');?>" class="btn btn-link">
                                    <span class="glyphicon glyphicon-chevron-right"></span>
                                    Submit a help request
                                </a>
                            </li>
                            <li>
                                <a target="_blank" href="https://docs.ccle.ucla.edu" class="btn btn-link">
                                    <span class="glyphicon glyphicon-chevron-right"></span>
                                    View self-help articles
                                </a>
                            </li>
                        </ul>

                    </div>
                </div>
            </div>
        </div>
        <div class="row " >
            <div class="col-lg-10 col-lg-offset-1 frontpage-alert">
                <?php
                    $alertblock = new ucla_alert_block(SITEID);
                    echo $alertblock->render();
                ?>
            </div>
        </div>
    </div>
</div>
