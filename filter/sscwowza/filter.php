<?php

require_once($CFG->libdir.'/filelib.php');

class filter_sscwowza extends moodle_text_filter {
    public function filter($text, array $options = array()) {
        global $CFG, $COURSE;
	    static $eolas_fix_applied = false;

	    // You should never modify parameters passed to a method or function, it's BAD practice. Create a copy instead.
        // The reason is that you must always be able to refer to the original parameter that was passed.
        // For this reason, I changed $text = preg_replace(..,..,$text) into $newtext = preg.... (NICOLAS CONNAULT)
        // Thanks to Pablo Etcheverry for pointing this out! MDL-10177

        // We're using the UFO technique for flash to attain XHTML Strict 1.0
        // See: http://www.bobbyvandersluis.com/ufo/
        if (!is_string($text)) {
            // non string data can not be filtered anyway
            return $text;
        }
        $newtext = $text; // fullclone is slow and not needed here

        if ($CFG->filter_sscwowza_enable_mp4) {
            $search = '/\{wowza:(.*?),(.*?),(.*?),(.*?),(.*?)\}/';
            $newtext = preg_replace_callback($search, 'sscwowza_filter_mp4_callback', $newtext);
        }

        if (is_null($newtext) or $newtext === $text) {
            // error or not filtered
            return $text;
        }

        if (!$eolas_fix_applied) {
            $newtext .= '<script defer="defer" src="' . $CFG->wwwroot .
                    '/filter/sscwowza/eolas_fix.js" type="text/javascript">// <![CDATA[ ]]></script>';
            $eolas_fix_applied = true;
        }

        return $newtext;
    }
}

function sscwowza_filter_mp4_callback($link, $autostart = false) {
	global $CFG, $COURSE, $USER;

	/* CLEAN URL AND GET VARIABLES */
	$type = clean_param($link[1], PARAM_NOTAGS);
	$url 	= clean_param($link[2], PARAM_NOTAGS);
	$file 	= clean_param($link[3], PARAM_NOTAGS);
	$width 	= clean_param($link[4], PARAM_NOTAGS);
	$height = clean_param($link[5], PARAM_NOTAGS);

	/* Handle VOD and Live streams */
	$app = $COURSE->id;
	
	if (strpos($file,'*') !== false) {
		$files = explode('*',$file);
		$file  = $files[0];
		$srt   = $files[1];
	} else {
	  $srt = '';
	  $timeline = '';
	}

		$format = substr($file, -3);
		
  $format = strtolower($format);
  switch($format) {
    case 'mov':
    case 'm4v':
    case 'm4a':
      $format = 'mp4';
      break;
  }
  $format = $format.":";
	$title = substr($file,0,-4);
	$extension = substr($file,-3);
	$parse_url = parse_url($url);

	/* Handle Prefixes */
	$mbrjs = '';
	switch($type) {
		case "mbr":
			$file = $title.'-low.'.$extension;
			$filehd = $title.'.'.$extension;
            $mbrjs = '"hd-2":{
                "file" : "'.$format.$filehd.'",
                "state" : "true"
            },';
			break;
		case "live":
		  $format = "";
		  $app = $app."-live";
		  break;
	}	
	
	/* Set Server Variables */
	$USER->video_allowed = 'true';
	$USER->video_file = $file;

	/* Streaming Paths */
	$srtpath 	= 'http://'.$parse_url['host'].':8080/'.$app.'/'.$srt;
	$html5path 	= 'http://'.$parse_url['host'].'/'.$app.'/'.$format.$file.'/playlist.m3u8';
	$rtmppath 	= $url.'/'.$app;
	$jwplayerpath 	= $CFG->wwwroot.'/filter/sscwowza/jwplayer/';
	$swfpath = $jwplayerpath.'SecureToken.swf';
	$jwjspath 	= $jwplayerpath.'jwplayer.js';
	$swfjspath = $jwplayerpath.'swfobject.js';
	$skinpath 	=	 $jwplayerpath.'skins/glow/glow.xml';

if ($srt != '') {
  /* Interactive Timeline */
	$srt_file = file($srtpath);
  $lines = array();
  foreach ($srt_file as $line) {
    $clean_line = trim($line);

    if (!is_numeric($clean_line)) {
      array_push($lines,$line);
    }
  }

  $timecodes = array();
  $text = array();
  $index = 0;
  $paragraph = '';

  foreach ($lines as $line) {
    $line = trim($line);
    if (preg_match('/^[0-9]{2}:[0-9]{2}/',$line)) {
      array_push($timecodes,$line);
      $index++;
    } else {
      if ($index < count($lines) - 1) {
        $next = $lines[$index+1];
      } else  {
        $next = '';
      }
        $index++;
        if (preg_match('/^[0-9]{2}:[0-9]{2}/',$next)) {
          $paragraph = $paragraph .' '. $line;

          array_push($text,trim($paragraph));
          $paragraph = '';
        } else {
          $paragraph = $paragraph .' '. $line;
          if ($next == '') {
            array_push($text,trim($paragraph));
          }
        }
    }
  }
  $starts = array();
  $ends = array();
  $times = array();
  foreach ($timecodes as $timecode) {
  	#calculate start times
    $parts = split(':',$timecode);
    $hours = $parts[0];
    $minutes = $parts[1];
    $seconds = substr($parts[2],0,2);
    $timecode_in_seconds = ((int)$hours * 3600) + ((int)$minutes * 60) + ((int)$seconds);
    $timecode_standard = $hours.':'.$minutes.':'.$seconds;
    array_push($starts,$timecode_in_seconds);
    array_push($times,$timecode_standard);

  	#calculate end times
  	$end = split('-->',$timecode);
    $parts = split(':',$end[1]);
    $hours = $parts[0];
    $minutes = $parts[1];
    $seconds = substr($parts[2],0,2);
    $timecode_in_seconds = ((int)$hours * 3600) + ((int)$minutes * 60) + ((int)$seconds);
    $timecode_standard = $hours.':'.$minutes.':'.$seconds;
  	array_push($ends,$timecode_in_seconds);
  }
  
  $index = 0;
  $timeline = '<div class="sscwowzafilter-timeline" style="width:'.$width.'px;">';
  foreach($text as $line) {
    $timeline .= '<a onclick="jwplayer().seek('.$starts[$index].')" href="javascript:void(0)" id="t'.$starts[$index].'">'.$times[$index].'</a> - '.$line.'<br/>';
    $index++; 
  }
	$timeline .='</div>';
}
	
	
	if ($srt != '') {
		$srtjs = '"captions-2":{
						
						"file":"'.$srtpath.'",
						"back": "true"
				},';
	} else {
		$srtjs = "";
	}

	/* Print out the Player Output */
    return
    '
<div id="player-'.$file.'"></div>
	<script type="text/javascript" src="/theme/uclashared/javascript/jquery-1.5.2.min.js"></script>
	<script type="text/javascript" src="'.$jwjspath.'"></script>
		<script type="text/javascript" src="'.$swfjspath.'"></script>
	<script type="text/javascript">
	jwplayer("player-'.$file.'").setup({
		"id" : "'.$file.'",
		"skin" : "'.$skinpath.'",
		"width" : "'.$width.'",
		"height" : "'.$height.'",
		"file" : "'.$format.$file.'",
		"stretching" : "exactfit",
		"streamer" : "'.$rtmppath.'",
		"plugins" : {
					    '.$srtjs.'
							'.$mbrjs.'
						"gapro-2":{}
					}
					,
		"modes" :
			[
				{type: "flash",
					src: "'.$swfpath.'",
				},
				{type: "html5",
					config: {
						file: "'.$html5path.'",
						provider: "video"
					}
				}
			]
		});
	</script>
	<script type="text/javascript">
			var ios = false;
			if((navigator.userAgent.match(/iPhone/i)) || 
			 (navigator.userAgent.match(/iPod/i)) || (navigator.userAgent.match(/iPad/i))) {
				var ios = true;
			}
			var version = deconcept.SWFObjectUtil.getPlayerVersion();
			if (version[\'major\'] < 10 && ios == false )
			{
				document.write(\'You have an outdated version of Flash. Please Update Your Flash Player: <br/><a href="http://www.adobe.com/go/getflashplayer" target="_blank" border="0"><img src="'.$jwplayerpath.'160x41_Get_Flash_Player.jpg"></a>\');
			}
			</script>'.$timeline;
}
