<?php
// Get config.php
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');

// Hex to binary stuff
function hex2bin($h)
{
        if (!is_string($h))
                return null;
        $r = '';
        for ($a=0;$a<strlen($h);$a+=2)
        {
                $r .= chr(hexdec($h{$a}.$h{($a+1)}));
        }
        return $r;
}

// Get global user variable
global $USER;

$isValid = true;
if (!isset($USER->video_allowed)) {
	$isValid = false;
}

if (! $isValid) {
	header('HTTP/1.0 403 Forbidden');
} else {
	header('Content-Type: binary/octet-stream');
	header('Pragma: no-cache');
	echo hex2bin('7BFA375DEDE2756571EFD3487F6AEB4E');
	exit(); // this is needed to ensure cr/lf is not added to output
}
