<?php

namespace Sonicfoundry;

defined('MOODLE_INTERNAL') || die();

class MediasiteContentTypes {
    const PRESENTATION = 'Presentation';
    const CATALOG = 'CatalogFolderDetails';
}
class MediasiteEmbedFormatValues {
    const THUMBNAIL = 1;
    const ABSTRACT_ONLY = 2;
    const ABSTRACT_PLUS_PLAYER = 4;
    const LINK = 8;
    const EMBED = 16;
    const PRESENTATION_LINK = 32;
    const PLAYER_ONLY = 64;
}
class MediasiteEmbedFormatTypes {
    const THUMBNAIL = 'MetadataLight';
    const ABSTRACT_ONLY = 'MetadataOnly';
    const ABSTRACT_PLUS_PLAYER = 'MetadataPlusPlayer';
    const LINK = 'BasicLTI';
    const EMBED = 'iFrame';
    const PRESENTATION_LINK = 'PresentationLink';
    const PLAYER_ONLY = 'PlayerOnly';    
}
class MediasiteEmbedFormat {
    public $contentType;
    public $formatValue;
    public $formatType;
    public $enabled;

    function __construct($contentType = null, $formatValue = null, $formatType = null, $enabled = null) {
        $this->contentType = $contentType;
        $this->formatValue = $formatValue;
        $this->formatType = $formatType;
        $this->enabled = $enabled;
    }
}


/**
 * Class MediasiteSite
 * @package Sonicfoundry
 */
class MediasiteSite {
    private $id;
    private $sitename;
    private $endpoint;
    private $lti_consumer_key;
    private $lti_consumer_secret;
    private $lti_custom_parameters;
    private $show_integration_catalog;
    private $integration_catalog_title;
    private $openpopup_integration_catalog;
    private $show_my_mediasite;
    private $my_mediasite_title;
    private $lti_debug_launch;
    private $my_mediasite_placement;
    private $openaspopup_my_mediasite;
    private $embed_formats;

    function __construct($record = null) {
        if(!is_null($record)) {
            if($record instanceof MediasiteSite) {
                $this->id = $record->id;
                $this->sitename = $record->sitename;
                $this->endpoint = $record->endpoint;
                $this->lti_consumer_key = $record->lti_consumer_key;
                $this->lti_consumer_secret = $record->lti_consumer_secret;
                $this->lti_custom_parameters = $record->lti_custom_parameters;
                $this->embed_formats = $record->embed_formats;
                $this->show_integration_catalog = $record->show_integration_catalog;
                $this->integration_catalog_title = $record->integration_catalog_title;
                $this->openpopup_integration_catalog = $record->openpopup_integration_catalog;
                $this->show_my_mediasite = $record->show_my_mediasite;
                $this->my_mediasite_title = $record->my_mediasite_title;
                $this->my_mediasite_placement = $record->my_mediasite_placement;
                $this->openaspopup_my_mediasite = $record->openaspopup_my_mediasite;
                $this->lti_debug_launch = $record->lti_debug_launch;
            } elseif($record instanceof \stdClass) {
                $this->id = $record->id;
                $this->sitename = $record->sitename;
                $this->endpoint = $record->endpoint;
                $this->lti_consumer_key = $record->lti_consumer_key;
                $this->lti_consumer_secret = $record->lti_consumer_secret;
                $this->lti_custom_parameters = $record->lti_custom_parameters;
                $this->embed_formats = $record->embed_formats;
                $this->show_integration_catalog = $record->show_integration_catalog;
                $this->integration_catalog_title = $record->integration_catalog_title;
                $this->openpopup_integration_catalog = $record->openpopup_integration_catalog;
                $this->show_my_mediasite = $record->show_my_mediasite;
                $this->my_mediasite_title = $record->my_mediasite_title;
                $this->my_mediasite_placement = $record->my_mediasite_placement;
                $this->openaspopup_my_mediasite = $record->openaspopup_my_mediasite;
                $this->lti_debug_launch = $record->lti_debug_launch;
            } elseif(is_numeric($record)) {
                global $DB;
                $record = $DB->get_record('mediasite_sites', array('id'=>$record));
                if($record) {
                    $this->id = $record->id;
                    $this->sitename = $record->sitename;
                    $this->endpoint = $record->endpoint;
                    $this->lti_consumer_key = $record->lti_consumer_key;
                    $this->lti_consumer_secret = $record->lti_consumer_secret;
                    $this->lti_custom_parameters = $record->lti_custom_parameters;
                    $this->embed_formats = $record->embed_formats;
                    $this->show_integration_catalog = $record->show_integration_catalog;
                    $this->integration_catalog_title = $record->integration_catalog_title;
                    $this->openpopup_integration_catalog = $record->openpopup_integration_catalog;
                    $this->show_my_mediasite = $record->show_my_mediasite;
                    $this->my_mediasite_title = $record->my_mediasite_title;
                    $this->my_mediasite_placement = $record->my_mediasite_placement;
                    $this->openaspopup_my_mediasite = $record->openaspopup_my_mediasite;
                    $this->lti_debug_launch = $record->lti_debug_launch;
                }
            }
        }
    }
    function update_database() {
        $record = new \stdClass();
        $record->id = $this->id;
        $record->sitename = $this->sitename;
        $record->endpoint = $this->endpoint;
        $record->lti_consumer_key = $this->lti_consumer_key;
        $record->lti_consumer_secret = $this->lti_consumer_secret;
        $record->lti_custom_parameters = $this->lti_custom_parameters;
        $record->embed_formats = $this->embed_formats;
        $record->show_integration_catalog = $this->show_integration_catalog;
        $record->integration_catalog_title = $this->integration_catalog_title;
        $record->openpopup_integration_catalog = $this->openpopup_integration_catalog;
        $record->show_my_mediasite = $this->show_my_mediasite;
        $record->my_mediasite_title = $this->my_mediasite_title;
        $record->my_mediasite_placement = $this->my_mediasite_placement;
        $record->openaspopup_my_mediasite = $this->openaspopup_my_mediasite;
        $record->lti_debug_launch = $this->lti_debug_launch;
        
        global $DB;
        $DB->update_record('mediasite_sites', $record);
    }
    function get_embed_capabilities($includeDisabled = false, $contentTypeFilter = null) {
        // based on $embed_formats, return an array of MediasiteEmbedFormats
        $result = array();
        if (($includeDisabled || $this->embed_formats & MediasiteEmbedFormatValues::PRESENTATION_LINK) && ($contentTypeFilter == null || $contentTypeFilter == MediasiteContentTypes::PRESENTATION)) {
            $result[] = new MediasiteEmbedFormat(MediasiteContentTypes::PRESENTATION, MediasiteEmbedFormatValues::PRESENTATION_LINK, MediasiteEmbedFormatTypes::PRESENTATION_LINK, $this->embed_formats & MediasiteEmbedFormatValues::PRESENTATION_LINK);
        }
        if (($includeDisabled || $this->embed_formats & MediasiteEmbedFormatValues::THUMBNAIL) && ($contentTypeFilter == null || $contentTypeFilter == MediasiteContentTypes::PRESENTATION)) {
            $result[] = new MediasiteEmbedFormat(MediasiteContentTypes::PRESENTATION, MediasiteEmbedFormatValues::THUMBNAIL, MediasiteEmbedFormatTypes::THUMBNAIL, $this->embed_formats & MediasiteEmbedFormatValues::THUMBNAIL);
        }
        if (($includeDisabled || $this->embed_formats & MediasiteEmbedFormatValues::ABSTRACT_ONLY) && ($contentTypeFilter == null || $contentTypeFilter == MediasiteContentTypes::PRESENTATION)) {
            $result[] = new MediasiteEmbedFormat(MediasiteContentTypes::PRESENTATION, MediasiteEmbedFormatValues::ABSTRACT_ONLY, MediasiteEmbedFormatTypes::ABSTRACT_ONLY, $this->embed_formats & MediasiteEmbedFormatValues::ABSTRACT_ONLY);
        }
        if (($includeDisabled || $this->embed_formats & MediasiteEmbedFormatValues::PLAYER_ONLY) && ($contentTypeFilter == null || $contentTypeFilter == MediasiteContentTypes::PRESENTATION)) {
            $result[] = new MediasiteEmbedFormat(MediasiteContentTypes::PRESENTATION, MediasiteEmbedFormatValues::PLAYER_ONLY, MediasiteEmbedFormatTypes::PLAYER_ONLY, $this->embed_formats & MediasiteEmbedFormatValues::PLAYER_ONLY);
        }
        if (($includeDisabled || $this->embed_formats & MediasiteEmbedFormatValues::ABSTRACT_PLUS_PLAYER) && ($contentTypeFilter == null || $contentTypeFilter == MediasiteContentTypes::PRESENTATION)) {
            $result[] = new MediasiteEmbedFormat(MediasiteContentTypes::PRESENTATION, MediasiteEmbedFormatValues::ABSTRACT_PLUS_PLAYER, MediasiteEmbedFormatTypes::ABSTRACT_PLUS_PLAYER, $this->embed_formats & MediasiteEmbedFormatValues::ABSTRACT_PLUS_PLAYER);
        }
        if (($includeDisabled || $this->embed_formats & MediasiteEmbedFormatValues::LINK) && ($contentTypeFilter == null || $contentTypeFilter == MediasiteContentTypes::CATALOG)) {
            $result[] = new MediasiteEmbedFormat(MediasiteContentTypes::CATALOG, MediasiteEmbedFormatValues::LINK, MediasiteEmbedFormatTypes::LINK, $this->embed_formats & MediasiteEmbedFormatValues::LINK);
        }
        if (($includeDisabled || $this->embed_formats & MediasiteEmbedFormatValues::EMBED) && ($contentTypeFilter == null || $contentTypeFilter == MediasiteContentTypes::CATALOG)) {
            $result[] = new MediasiteEmbedFormat(MediasiteContentTypes::CATALOG, MediasiteEmbedFormatValues::EMBED, MediasiteEmbedFormatTypes::EMBED, $this->embed_formats & MediasiteEmbedFormatValues::EMBED);
        }
        return $result;
    }
    function get_siteid() {
        return $this->id;
    }
    function set_sitename($value) {
        $this->sitename = $value;
    }
    function get_sitename() {
        return $this->sitename;
    }
    function set_endpoint($value) {
        $this->endpoint = $value;
    }
    function get_endpoint() {
        return $this->endpoint;
    }
    function set_lti_consumer_key($value) {
        $this->lti_consumer_key = $value;
    }
    function get_lti_consumer_key() {
        return $this->lti_consumer_key;
    }
    function set_lti_consumer_secret($value) {
        $this->lti_consumer_secret = $value;
    }
    function get_lti_consumer_secret() {
        return $this->lti_consumer_secret;
    }
    function set_lti_custom_parameters($value) {
        $this->lti_custom_parameters = $value;
    }
    function get_lti_custom_parameters() {
        return $this->lti_custom_parameters;
    }
    function set_show_integration_catalog($value) {
        $this->show_integration_catalog = $value;
    }
    function get_show_integration_catalog() {
        return $this->show_integration_catalog;
    }
    function set_integration_catalog_title($value) {
        $this->integration_catalog_title = $value;
    }
    function get_integration_catalog_title() {
        return $this->integration_catalog_title;
    }
    function get_openpopup_integration_catalog() {
        return $this->openpopup_integration_catalog;
    }
    function set_openpopup_integration_catalog($value) {
        $this->openpopup_integration_catalog = $value;
    }
    function set_show_my_mediasite($value) {
        $this->show_my_mediasite = $value;
    }
    function get_show_my_mediasite() {
        return $this->show_my_mediasite;
    }
    function set_my_mediasite_title($value) {
        $this->my_mediasite_title = $value;
    }
    function get_my_mediasite_title() {
        return $this->my_mediasite_title;
    }
    function set_my_mediasite_placement($value) {
        $this->my_mediasite_placement = $value;
    }
    function get_my_mediasite_placement() {
        return $this->my_mediasite_placement;
    }
    function get_openaspopup_my_mediasite() {
        return $this->openaspopup_my_mediasite;
    }
    function set_openaspopup_my_mediasite($value) {
        $this->openaspopup_my_mediasite = $value;
    }
    function set_lti_debug_launch($value) {
        $this->lti_debug_launch = $value;
    }
    function get_lti_debug_launch() {
        return $this->lti_debug_launch;
    }
    function get_lti_embed_type_thumbnail() {
        return $this->embed_formats & MediasiteEmbedFormatValues::THUMBNAIL;
    }
    function set_lti_embed_type_thumbnail($value) {
        $this->set_lti_embed_type_bitmask($value, MediasiteEmbedFormatValues::THUMBNAIL);
    }
    function get_lti_embed_type_abstract_only() {
        return $this->embed_formats & MediasiteEmbedFormatValues::ABSTRACT_ONLY;
    }
    function set_lti_embed_type_abstract_only($value) {
        $this->set_lti_embed_type_bitmask($value, MediasiteEmbedFormatValues::ABSTRACT_ONLY);
    }
    function get_lti_embed_type_abstract_plus_player() {
        return $this->embed_formats & MediasiteEmbedFormatValues::ABSTRACT_PLUS_PLAYER;
    }
    function set_lti_embed_type_abstract_plus_player($value) {
        $this->set_lti_embed_type_bitmask($value, MediasiteEmbedFormatValues::ABSTRACT_PLUS_PLAYER);
    }
    function get_lti_embed_type_link() {
        return $this->embed_formats & MediasiteEmbedFormatValues::LINK;
    }
    function set_lti_embed_type_link($value) {
        $this->set_lti_embed_type_bitmask($value, MediasiteEmbedFormatValues::LINK);
    }
    function get_lti_embed_type_embed() {
        return $this->embed_formats & MediasiteEmbedFormatValues::EMBED;
    }
    function set_lti_embed_type_embed($value) {
        $this->set_lti_embed_type_bitmask($value, MediasiteEmbedFormatValues::EMBED);
    }
    function get_lti_embed_type_presentation_link() {
        return $this->embed_formats & MediasiteEmbedFormatValues::PRESENTATION_LINK;
    }
    function set_lti_embed_type_presentation_link($value) {
        $this->set_lti_embed_type_bitmask($value, MediasiteEmbedFormatValues::PRESENTATION_LINK);
    }
    function get_lti_embed_type_player_only() {
        return $this->embed_formats & MediasiteEmbedFormatValues::PLAYER_ONLY;
    }
    function set_lti_embed_type_player_only($value) {
        $this->set_lti_embed_type_bitmask($value, MediasiteEmbedFormatValues::PLAYER_ONLY);
    }

    function set_lti_embed_type_bitmask($value, $bit) {
        if ($value == 0) {
            $this->embed_formats = $this->embed_formats & ~$bit;
        } else {
            $this->embed_formats = $this->embed_formats | $bit;
        }        
    }


    static function loadbyname($name) {
        global $DB;
        if($record = $DB->get_record('mediasite_sites', array('sitename'=>$name))) {
            $site = new MediasiteSite($record);
            return $site;
        } else {
            return FALSE;
        }
    }


}
