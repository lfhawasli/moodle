<?php
require_once(dirname(__FILE__) . '/../../../config.php');
require_once("$CFG->dirroot/mod/mediasite/exceptions.php");

function xmldb_mediasite_upgrade($oldversion=0) {
	
	global $CFG,$DB;
    $dbman = $DB->get_manager();
	
	$result = true;

    $plugin = new stdClass();
    include("$CFG->dirroot/mod/mediasite/version.php");

    // Upgrade
    if($result && $oldversion == 2012032900)
    {
        $result = mediasite_upgrade_from_2012032900($oldversion, $dbman, $plugin);
    }

    // if($result && $oldversion == 2014042900) {
    //     upgrade_mod_savepoint(true, $plugin->version, 'mediasite');
    // }

    if ($result && $oldversion <= 2016041803) {
        $result = mediasite_upgrade_from_2014042900($oldversion, $dbman, $plugin);
    }

    return $result;
}

function mediasite_upgrade_from_2012032900($oldversion, $dbman, $plugin) {
    global $CFG,$DB;
    // Define table mediasite_status to be created.
    $table = new xmldb_table('mediasite_status');

    // Adding fields to table mediasite_status.
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('sessionid', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, '0');
    $table->add_field('processed', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
    $table->add_field('status', XMLDB_TYPE_TEXT, 'small', null, XMLDB_NOTNULL, null, null);

    // Adding keys to table mediasite_status.
    $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

    // Conditionally launch create table for mediasite_status.
    if (!$dbman->table_exists($table)) {
        $dbman->create_table($table);
    }
    //Conditionally launch create index for mediasite_status.
    $index = new xmldb_index('sessionid');
    $index->set_attributes(XMLDB_INDEX_UNIQUE, array('sessionid'));
    if(!$dbman->index_exists($table, $index)){
            $dbman->add_index($table, $index);
    }

    // Define table mediasite_sites to be created.
    $table = new xmldb_table('mediasite_sites');

    // Adding fields to table mediasite_sites.
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('sitename', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, 'Default');
    $table->add_field('endpoint', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
    $table->add_field('apikey', XMLDB_TYPE_CHAR, '36', null, XMLDB_NOTNULL, null, null);
    $table->add_field('username', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, 'MediasiteAdmin');
    $table->add_field('password', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
    $table->add_field('passthru', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
    $table->add_field('siteclient', XMLDB_TYPE_CHAR, '16', null, XMLDB_NOTNULL, null, null);
    $table->add_field('sslselect', XMLDB_TYPE_INTEGER, '1',   null, XMLDB_NOTNULL, null, '0');
    $table->add_field('cert',      XMLDB_TYPE_BINARY,  null,  null, null,          null, null);

    // Adding keys to table mediasite_sites.
    $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
    $table->add_key('sitename', XMLDB_KEY_UNIQUE, array('sitename'));

    // Conditionally launch create table for mediasite_sites.
    if (!$dbman->table_exists($table)) {
        $dbman->create_table($table);
    }

    // Define table mediasite_config to be created.
    $table = new xmldb_table('mediasite_config');

    // Adding fields to table mediasite_config.
    $table = new xmldb_table('mediasite_config');
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('siteid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
    $table->add_field('openaspopup', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
    $table->add_field('duration', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '300');
    $table->add_field('restrictip', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');

    // Adding keys to table mediasite_config.
    $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
    $table->add_key('siteid', XMLDB_KEY_UNIQUE, array('siteid'));
    
    // Conditionally launch create table for mediasite_config.
    if (!$dbman->table_exists($table)) {
        $dbman->create_table($table);
        //foreign key for mediasite_config
        $key = new xmldb_key('defaultsiteidforeignkey', XMLDB_KEY_FOREIGN, array('siteid'), 'mediasite_sites', array('id'));
        // Launch add key defaultsiteidforeignkey.
        $dbman->add_key($table, $key);
    }

    // Define field siteid & description & duration & restrictip to be added to mediasite.
    $table = new xmldb_table('mediasite');
    $field = new xmldb_field('siteid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
    
    // Conditionally launch add field siteid.
    if (!$dbman->field_exists($table, $field)) {
        $dbman->add_field($table, $field);
    }
    $field = new xmldb_field('description', XMLDB_TYPE_TEXT, 'small', null, null, null, null);
    // Conditionally launch add field intro.
    if (!$dbman->field_exists($table, $field)) {
        $dbman->add_field($table, $field);
    }
    $field = new xmldb_field('duration', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '300');
    if (!$dbman->field_exists($table, $field)) {
        $dbman->add_field($table, $field);
    }
    $field = new xmldb_field('restrictip', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
    if (!$dbman->field_exists($table, $field)) {
        $dbman->add_field($table, $field);
    }
    $key = new xmldb_key('siteidforeignkey', XMLDB_KEY_FOREIGN, array('siteid'), 'mediasite_sites', array('id'));
    // Launch add key siteidforeignkey.
    $dbman->add_key($table, $key);

    // At this point we have the new table and have updated the old table with the new field.
    // Update new table with records in table config.
    $default_record = array();
    $site_record = array();
    $site_record['sitename'] = 'Default';
    $site_record['passthru'] = '0';
    $whereclause = 'name LIKE \'mediasite%\'';

    $config_records = $DB->get_records_sql("SELECT * FROM {config} WHERE $whereclause");
    foreach($config_records as $config_record) {
            if($config_record->name == 'mediasite_username') {
                    $site_record['username'] = $config_record->value;
            } elseif($config_record->name == 'mediasite_password') {
                    $site_record['password'] = $config_record->value;
            } elseif($config_record->name == 'mediasite_serverurl') {
                    //$site_record['endpoint'] = preg_replace('/6_1_7\/?$/', 'main', $config_record->value);
            $site_record['endpoint'] = $config_record->value;
            } elseif($config_record->name == 'mediasite_ticketduration') {
            $default_record['duration'] = $config_record->value;
            } elseif($config_record->name == 'mediasite_restricttoip') {
            $default_record['restrictip'] = $config_record->value;
            } elseif($config_record->name == 'mediasite_openaspopup') {
            $default_record['openaspopup'] = $config_record->value;
            }
    }

    //When site is configured in old version of plugin. If not, will be configured using add.php
    if(array_key_exists("endpoint", $site_record) && array_key_exists("username", $site_record) && array_key_exists("password", $site_record)) {
            // TODO: get version of Mediasite (6 or 7)
            $soapclient = Sonicfoundry\MediasiteClientFactory::MediasiteClient('soap',$site_record['endpoint'], $site_record['username'], $site_record['password'], null);
            $siteproperties = $soapclient->QuerySiteProperties();
            $version = $siteproperties->SiteVersion;
            $soapclient->Logout();
            $matches = array();
            if(preg_match('/(6|7)\.(\d+)\.(\d+)/i', $version, $matches)) {
                    if($matches[1] == 6) {
                            $client = Sonicfoundry\MediasiteClientFactory::MediasiteClient('soap',$site_record['endpoint'], $site_record['username'], $site_record['password'], null);
                            $apiKeyNeeded = false;
                            $site_record['siteclient'] = 'soap';
                    }
                    elseif($matches[1] == 7) {
                            $client = Sonicfoundry\MediasiteClientFactory::MediasiteClient('odata',$site_record['endpoint'], $site_record['username'], $site_record['password'], null);
                            $apiKeyNeeded = true;
                            $site_record['siteclient'] = 'odata';
                    }
            }       
            // Try to get the apiKey
            try {
                    if($apiKeyNeeded) {
                            if(!($apiKey = $client->GetApiKeyById())) {
                                    if(!($apiKey = $client->CreateApiKey())) {
                                            return false;
                                    }
                            }
                            $site_record['apikey'] = $apiKey->Id;
                    }
            } catch(\Sonicfoundry\SonicfoundryException $se) {
                    if(!($apiKey = $client->CreateApiKey())) {
                            return false;
                    }
                    $site_record['apikey'] = $apiKey->Id;
            } catch(Exception $e) {
                    if(!($apiKey = $client->CreateApiKey())) {
                            return false;
                    }
                    $site_record['apikey'] = $apiKey->Id;
            }
    }
    
    // Now we are modifying the database records

    $DB->delete_records_select('config', $whereclause);
    
    // Try not inserting duplicate records to database.
    try{
            $site_id = $DB->insert_record('mediasite_sites', $site_record, true);       
            $default_record['siteid'] = $site_id;
            $DB->insert_record('mediasite_config', $default_record, true);

            $mediasite_rs = $DB->get_recordset('mediasite');
            if($mediasite_rs->valid()){
                    foreach ($mediasite_rs as $mediasite_record) {
                            $record = new stdClass();
                            $record->id = $mediasite_record->id;
                            if($mediasite_record->resourcetype == get_string('presentation', 'mediasite')) {
                                    $presentation = $client->QueryPresentationById($mediasite_record->resourceid);
                                    $record->description = $presentation->Description;

                            } elseif($mediasite_record->resourcetype == get_string('catalog', 'mediasite')) {
                                    $catalog = $client->QueryCatalogById($mediasite_record->resourceid);
                                    $record->description = $catalog->Description;

                            }
                            $record->siteid = $site_id;
                            if(isset($default_record['openaspopup'])) {
                                    $record->openaspopup = $default_record['openaspopup'];
                            }
                            if(isset($default_record['duration'])) {
                                    $record->duration = $default_record['duration'];
                            }
                            if(isset($default_record['restrictip'])) {
                                    $record->restrictip = $default_record['restrictip'];
                            }
                            $DB->update_record('mediasite', $record, true);
                            //Be aware that from Moodle 2.6 onwards modinfo + sectioncache have been
                            //removed from the mdl_course table - they are now stored in the Moodle cache.
                            //This means that the only safe way to clear them is via
                            rebuild_course_cache($mediasite_record->course, true);
                    }
            }
            $mediasite_rs->close();
    }catch(Exception $e){
            //ignore
    }

    upgrade_mod_savepoint(true, $plugin->version, 'mediasite');    
    return true;
} 

function mediasite_upgrade_from_2014042900($oldversion, $dbman, $plugin) {
    // add key & secret columns to site
    $sitesTable = new xmldb_table('mediasite_sites');
    $contentTable = new xmldb_table('mediasite');
    $configTable = new xmldb_table('mediasite_config');
    $courseConfigTable = new xmldb_table('mediasite_course_config');

    if ($dbman->table_exists($sitesTable)) {
        // mediasite_site should exist from previous upgrades, if not throw a fit
        // now see if the key and secret columns exist, if they don't then create them
        $keyField = new xmldb_field('lti_consumer_key', XMLDB_TYPE_CHAR, '255', null, null, null);
        conditionally_add_field_to_table($sitesTable, $keyField, $dbman);

        $secretField = new xmldb_field('lti_consumer_secret', XMLDB_TYPE_CHAR, '255', null, null, null);
        conditionally_add_field_to_table($sitesTable, $secretField, $dbman);

        $customParamField = new xmldb_field('lti_custom_parameters', XMLDB_TYPE_TEXT, 'small', null, null, null, null);
        conditionally_add_field_to_table($sitesTable, $customParamField, $dbman);

        $showIntegrationCatalogField = new xmldb_field('show_integration_catalog', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        conditionally_add_field_to_table($sitesTable, $showIntegrationCatalogField, $dbman);

        $integrationCatalogTitleField = new xmldb_field('integration_catalog_title', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, 'Mediasite Catalog');
        conditionally_add_field_to_table($sitesTable, $integrationCatalogTitleField, $dbman);

        $integrationCatalogOpenAsPopupField = new xmldb_field('openpopup_integration_catalog', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        conditionally_add_field_to_table($sitesTable, $integrationCatalogOpenAsPopupField, $dbman);

        $showMyMediasiteField = new xmldb_field('show_my_mediasite', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        conditionally_add_field_to_table($sitesTable, $showMyMediasiteField, $dbman);

        $myMediasiteTitleField = new xmldb_field('my_mediasite_title', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, 'My Mediasite');
        conditionally_add_field_to_table($sitesTable, $myMediasiteTitleField, $dbman);

        $myMediasitePlacementField = new xmldb_field('my_mediasite_placement', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        conditionally_add_field_to_table($sitesTable, $myMediasitePlacementField, $dbman);

        $myMediasiteOpenAsPopupField = new xmldb_field('openaspopup_my_mediasite', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        conditionally_add_field_to_table($sitesTable, $myMediasiteOpenAsPopupField, $dbman);

        $debugLaunchField = new xmldb_field('lti_debug_launch', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        conditionally_add_field_to_table($sitesTable, $debugLaunchField, $dbman);

        $embedFormatsField = new xmldb_field('embed_formats', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '31');
        conditionally_add_field_to_table($sitesTable, $embedFormatsField, $dbman);

        // remove columns that are no longer in use
        $usernameField = new xmldb_field('username', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, 'MediasiteAdmin');
        conditionally_drop_field_from_table($sitesTable, $usernameField, $dbman);

        $passwordField = new xmldb_field('password', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        conditionally_drop_field_from_table($sitesTable, $passwordField, $dbman);

        $apikeyField = new xmldb_field('apikey', XMLDB_TYPE_CHAR, '36', null, XMLDB_NOTNULL, null, null);
        conditionally_drop_field_from_table($sitesTable, $apikeyField, $dbman);

        $passthruField = new xmldb_field('passthru', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        conditionally_drop_field_from_table($sitesTable, $passthruField, $dbman);

        $siteclientField = new xmldb_field('siteclient', XMLDB_TYPE_CHAR, '16', null, XMLDB_NOTNULL, null, null);
        conditionally_drop_field_from_table($sitesTable, $siteclientField, $dbman);

        $sslselectField = new xmldb_field('sslselect', XMLDB_TYPE_INTEGER, '1',   null, XMLDB_NOTNULL, null, '0');
        conditionally_drop_field_from_table($sitesTable, $sslselectField, $dbman);

        $certField = new xmldb_field('cert',      XMLDB_TYPE_BINARY,  null,  null, null,          null, null);
        conditionally_drop_field_from_table($sitesTable, $certField, $dbman);

    } else {
        return false;
    }

    if ($dbman->table_exists($contentTable)) {
        $timecreatedField = new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null);
        conditionally_rename_field($contentTable, $timecreatedField, 'recorddateutc', $dbman);
        // the timecreated field should no longer be present, but let's make sure
        conditionally_drop_field_from_table($contentTable, $timecreatedField, $dbman);

        $presentersField = new xmldb_field('presenters', XMLDB_TYPE_TEXT, 'small', null, null, null, null);
        conditionally_add_field_to_table($contentTable, $presentersField, $dbman);

        $tagsField = new xmldb_field('tags', XMLDB_TYPE_TEXT, 'small', null, null, null, null);
        conditionally_add_field_to_table($contentTable, $tagsField, $dbman);

        $modeField = new xmldb_field('mode', XMLDB_TYPE_CHAR, '50', null, null, null, null);
        conditionally_add_field_to_table($contentTable, $modeField, $dbman);

        $launchUrlField = new xmldb_field('launchurl', XMLDB_TYPE_CHAR, '1000', null, null, null);
        conditionally_add_field_to_table($contentTable, $launchUrlField, $dbman);

        $sitesForeignKey = new xmldb_key('siteidforeignkey', XMLDB_KEY_FOREIGN, array('siteid'), 'mediasite_sites', array('id'));
        conditionally_add_foreign_key_to_table($contentTable, $sitesForeignKey, $dbman);

        $timemodifiedField = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null);
        conditionally_drop_field_from_table($contentTable, $timemodifiedField, $dbman);
    }

    if ($dbman->table_exists($configTable)) {
        $durationField = new xmldb_field('duration', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '300');
        conditionally_drop_field_from_table($configTable, $durationField, $dbman);

        $restrictIpField = new xmldb_field('restrictip', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        conditionally_drop_field_from_table($configTable, $restrictIpField, $dbman);
    } else {
        return false;
    }
    
    // exposing the 'show description' field, set the flag on mdl_course_modules
    update_show_description($dbman);

    $statusTable = new xmldb_table('mediasite_status');
    conditionally_drop_table($statusTable, $dbman);

    // add new course config table
    if (!$dbman->table_exists($courseConfigTable)) {
        $courseConfigTable->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $courseConfigTable->add_field('course', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $courseConfigTable->add_field('mediasite_site', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $courseConfigTable->add_field('mediasite_courses_enabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null);

        $courseConfigTable->add_key('primary', XMLDB_KEY_PRIMARY, array('id'), null, null);
        $courseConfigTable->add_key('foreignkey_course', XMLDB_KEY_FOREIGN, array('course'), 'course', array('id'));
        $courseConfigTable->add_key('foreignkey_mediasite_sites', XMLDB_KEY_FOREIGN, array('mediasite_site'), 'mediasites_sites', array('id'));

        $dbman->create_table($courseConfigTable);
    } 

    upgrade_mod_savepoint(true, $plugin->version, 'mediasite');
    return true;
}

function conditionally_add_field_to_table($table, $field, $dbman) {
    // Conditionally launch add field intro.
    if (!$dbman->field_exists($table, $field)) {
        $dbman->add_field($table, $field);
    }    
}

function conditionally_drop_field_from_table($table, $field, $dbman) {
    if ($dbman->field_exists($table, $field)) {
        $dbman->drop_field($table, $field);
    }
}

function conditionally_add_foreign_key_to_table($table, $key, $dbman) {
    // NOT IMPLEMENTED BY DATABASE MANAGER
    // if (!$dbman->key_exists($table, $key)) {
    //     $dbman->add_key($table, $key);
    // }
    try {
        $dbman->add_key($table, $key);
    } catch (Exception $e) {
        // swallow it
    }
}

function conditionally_drop_table($table, $dbman) {
    if ($dbman->table_exists($table)) {
        $dbman->drop_table($table);
    }
}

function conditionally_rename_field($table, $field, $newname, $dbman) {
    // $dbman->rename_field($table, $field, $newname, $continue=true, $feedback=true)
    // public function __construct($name, $type=null, $precision=null, $unsigned=null, $notnull=null, $sequence=null, $default=null, $previous=null)
    $targetField = new xmldb_field($newname, $field->getType(), $field->getLength(), $field->getUnsigned(), $field->getNotNull(), $field->getSequence(), $field->getDefault());
    if ($dbman->field_exists($table, $field) && !$dbman->field_exists($table, $targetField)) {
        $dbman->rename_field($table, $field, $newname);
    }
}

function update_show_description($dbman) {
    global $DB;
    $sql = "
UPDATE {course_modules}
   SET showdescription = 1
 WHERE module IN (
                  SELECT M.id
                    FROM {modules} M
                   WHERE M.name = 'mediasite'
                ) 
   AND showdescription = 0
   AND id > 0
";
    $DB->execute($sql);
}

?>
