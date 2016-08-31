<?php

namespace Sonicfoundry;

defined('MOODLE_INTERNAL') || die();

/**
 * Class MediasiteResource
 * @package Sonicfoundry
 */
class MediasiteResource {
    function __construct($record) {
        if($record instanceof \stdClass) {
            $this->id = $record->id;
            $this->course = $record->course;
            $this->name = $record->name;
            $this->description = $record->description;
            $this->resourceid = $record->resourceid;
            $this->resourcetype = $record->resourcetype;
            $this->openaspopup = $record->openaspopup;
            $this->restrictip = $record->restrictip;
            $this->recorddateutc = $record->recorddateutc;
            $this->presenters = $record->presenters;
            $this->tags = $record->tags;
            $this->mode = $record->mode;
            $this->launchurl = $record->launchurl;
            $this->siteid = $record->siteid;
        }
    }
    public $id;
    public $course;
    public $name;
    public $description;
    public $resourceid;
    public $resourcetype;
    public $openaspopup;
    public $restrictip;
    public $recorddateutc;
    public $presenters;
    public $tags;
    public $mode;
    public $launchurl;
    public $siteid;
} 