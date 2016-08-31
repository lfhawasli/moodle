<?php
require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once("mod_mediasite_site_form.php");
require_once("$CFG->dirroot/mod/mediasite/exceptions.php");

$context = context_system::instance();

require_login();
require_capability('mod/mediasite:addinstance', $context);
admin_externalpage_setup('activitysettingmediasite');

global $PAGE;

$PAGE->set_context($context);
$PAGE->set_url($CFG->wwwroot . '/mod/mediasite/site/add.php');
$PAGE->set_pagelayout('admin');
$PAGE->requires->js(new moodle_url('/mod/mediasite/js/mod_mediasite_site_form.js'), true);

$addform = new mod_mediasite_site_form();
$mform =& $addform;

if ($mform->is_cancelled()) {
    redirect("configuration.php");
}
$data = $mform->get_data();
if($data) {
    $navInstalled = $mform->is_navigation_installed();
    $record = new stdClass();
    $url = $data->siteurl;
    $record->sitename = $data->sitename;
    if(!preg_match('%\bhttps?:\/\/%si',$data->siteurl)) {
        $data->siteurl = 'http://'.$data->siteurl;
    }
    $record->endpoint = $data->siteurl;
    $record->lti_consumer_key = $data->sitelti_consumer_key;
    $record->lti_consumer_secret = $data->sitelti_consumer_secret;
    $record->lti_custom_parameters = $data->sitelti_custom_parameters;
    if ($navInstalled) {
        $record->show_integration_catalog = $data->show_integration_catalog;
        $record->integration_catalog_title = $data->integration_catalog_title;
        $record->openpopup_integration_catalog = $data->openpopup_integration_catalog;
        $record->show_my_mediasite = $data->show_my_mediasite;
        $record->my_mediasite_title = $data->my_mediasite_title;
        $record->my_mediasite_placement = $data->my_mediasite_placement;
        $record->openaspopup_my_mediasite = $data->openaspopup_my_mediasite;
    }
    $record->lti_debug_launch = $data->lti_debug_launch;
    // embed_formats is a bitmask
    $record->embed_formats = $data->lti_embed_type_thumbnail;
    $record->embed_formats |= $data->lti_embed_type_abstract_only;
    $record->embed_formats |= $data->lti_embed_type_abstract_plus_player;
    $record->embed_formats |= $data->lti_embed_type_link;
    $record->embed_formats |= $data->lti_embed_type_embed;
    $record->embed_formats |= $data->lti_embed_type_presentation_link;
    $record->embed_formats |= $data->lti_embed_type_player_only;
    global $DB;
    // Add new record
    $siteid = $DB->insert_record('mediasite_sites', $record);

    // Go home
    redirect("configuration.php");
}

global $OUTPUT;

echo $OUTPUT->header();

echo "<table border=\"0\" style=\"margin-left:auto;margin-right:auto\" cellspacing=\"3\" cellpadding=\"3\" width=\"640\">";
echo "<tr>";
echo "<td colspan=\"2\">";

$mform->display();

echo '</td></tr></table>';

echo $OUTPUT->footer();
