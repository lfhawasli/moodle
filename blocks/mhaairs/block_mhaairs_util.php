<?php
define('token_validity_interval', 300);

function mh_get_time_stamp() {
    return gmdate("Y-m-d\TH:i:sP");
}

function mh_create_token($user_id, $course_id = false) {
    $result = 'userid='.$user_id.';time='.mh_get_time_stamp();
    if($course_id){
        $result = 'courseid='.$course_id.';'.$result;
    }
    return $result;
}

function mh_create_token2($customer, $user_id, $user_name, $course_id = false, $course_internal_id = false, $link_type = null, $role_name = null, $course_name = null) {
    $parameters = array('customer' => $customer,
                        'userid'   => $user_id,
						'username' => $user_name,
                        'time'     => mh_get_time_stamp());

    if (!empty($course_id)) {
        $parameters['courseid'] = $course_id;
    }
    if (!empty($course_internal_id)) {
        $parameters['courseinternalid'] = $course_internal_id;
    }
    if (!empty($link_type)) {
        $parameters['linktype'] = $link_type;
    }

	if (!empty($role_name)) {
		$parameters['role'] = $role_name;
	}

	if (!empty($course_name)) {
		$parameters['coursename'] = $course_name;
	}

    $result = '';
    foreach ($parameters as $name => $value) {
        if (!empty($result)) {
            $result .= '&';
        }
        $result .= "$name=$value";
    }

    return $result;
}

function mh_encode_token($token, $secret, $alg = 'md5') {
    return mh_hex_encode(''.md5($token.$secret).';'.$token);
}

function mh_encode_token2($token, $secret, $alg = 'md5') {
    return mh_hex_encode(''.md5($token.$secret).';'.$token);
}

function mh_get_token($token)
{
    try{
        $pos = strpos($token, ';');
        return substr($token, $pos + 1, strlen($token) - $pos);
    }catch(Exception $e)
    {
        return '';
    }
}

function mh_get_hash($token)
{
    try{
        $pos = strpos($token, ';');
        return substr($token, 0, $pos);
    }catch(Exception $e)
    {
        return '';
    }
}

function mh_get_token_value($token, $name)
{
    try
    {
        $parts = explode(';',$token);
        foreach($parts as $part)
        {
            $pair = explode('=', $part);
            if(count($pair)>0)
            {
                if(0===strcasecmp($pair[0],$name))
                {
                    return $pair[1];
                }
            }
        }
    }
    catch(Exception $e){
    }
    return FALSE;
}

function mh_is_token_valid($token_text, $secret, $delay = 25200, $alg = 'md5', &$trace = '')
{
    //return true;
    $trace = $trace.";token validation";
    try{
        $decoded_token = mh_hex_decode($token_text);
        $trace = $trace.";decoded_token=".$decoded_token;
        $token = mh_get_token($decoded_token);
        $hash = mh_get_hash($decoded_token);
        $trace = $trace.";hash=".$hash;
        $true_hash = md5($token.$secret);
        if($true_hash === $hash)
        {
            $trace = $trace."the hash is good;";
            $token_time_text = mh_get_token_value($decoded_token,"time");
            $token_time = strtotime($token_time_text);//new DateTime($token_time_text);
            $current_time = time();
            $interval = ((int)$current_time) - ((int)$token_time) ;
            $trace = $trace.";interval=".$interval;
            return $interval < $delay && $interval >= -$delay;
        } else {
            $trace = $trace."the hash is bad;";
        }
    }
    catch(Exception $e){
		$trace = $trace.'Exception in is_token_valid:'.$e->getMessage();
    }
    return FALSE;
}

function mh_hex_encode($data)
{
    try
    {
        return bin2hex($data);
    }
    catch(Exception $e){
        return FALSE;
    }
}

function mh_hex_decode($data)
{
    try{
        return mh_hex2bin($data);
    }
    catch(Exception $e){
    }
}

if(function_exists('json_encode'))
{
    function mh_var2json( $var )
    {
        return json_encode($var);
    }
}
else{
    function mh_var2json( $var ){
        //handling primitive types
        if( is_int($var) || is_float($var) ) {
            return $var;
        }
        if( is_bool($var) ) return ($var)? "true" : "false" ; //
        if( is_null($var) ) return "null" ; //
        if( is_string($var) ) return '"'.addcslashes($var, '"').'"' ; //

        if(is_object($var))
        {
            $construct = array();
            foreach( $var as $key => $value ){
                $prop_name = addslashes($key);
                $prop_value = mh_var2json( $value );
                // Add to staging array:
                $construct[] = "\"$prop_name\":$prop_value";
            }
            $result = "{" . implode( ",", $construct ) . "}"; //format JSON 'object'
            return $result;
        }
        $associative = count( array_diff( array_keys($var), array_keys( array_keys( $var )) ));
        if( $associative ){
            $construct = array();
            foreach( $var as $key => $value ){
                $key_name = '';
                if( is_int($key) ){
                    $key_name = "key_$key";
                }else{$key_name = addslashes("$key");
                }
                $key_value = mh_var2json( $value );
                $construct[] = '"'.$key_name.'":'.$key_value;
            }
            $result = "{" . implode( ",", $construct ) . "}"; //format JSON 'object'
        } else { // If the array is a vector (not associative):
            $construct = array();
            foreach( $var as $value ){
                $construct[] = mh_var2json( $value );
            }
            $result = "[" . implode( ",", $construct ) . "]"; //format JSON 'array'
        }
        return $result;
    }
}

function mh_hex2bin($str) {
    $bin = "";
    $i = 0;
    do {
        $bin .= chr(hexdec($str{$i}.$str{($i + 1)}));
        $i += 2;
    } while ($i < strlen($str));
    return $bin;
}

function handle_illegal_chars($str) {
	if (strpos($str, "-") !== false) {
		$str = '`'.$str.'`';
	}
	$str = str_replace(";", "\;", str_replace("'", "''", $str));
	return $str;
}

class MHUserInfo
{
    const SUCCESS = 0;
    const FAILURE = 1;

    public $Status;
    public $User;
    public $Courses;
    public $Message;

    function __construct($Status)
    {
        $this->Status = $Status;
        $this->Courses = array();
    }
    function add_courses($courses, $rolename)
    {
        foreach($courses as $course)
        {
            $lcourse = clone $course;
            $lcourse->rolename = $rolename;
            array_push($this->Courses, $lcourse);
        }
    }
    function add_course($course, $rolename)
    {
        $lcourse = clone $course;
        $lcourse->rolename = $rolename;
        array_push($this->Courses, $lcourse);
    }
    function set_user($user)
    {
        $this->User = $user;
        if($this->User){
            $this->User->password = null;
        }
    }
}

class MHAuthenticationResult
{
    const SUCCESS = 0;
    const FAILURE = 1;

    public $Status;
    public $EffectiveUserId;
    public $RedirectURL;
    public $Attributes;
	public $Message;
    function __construct($Status, $EffectiveUserId, $ErrorDetails)
    {
        $this->Status = $Status;
        $this->EffectiveUserId = $EffectiveUserId;
        $this->Attributes = array();
		$this->Message = $ErrorDetails;
    }
}

function mh_validate_login($token, $secret, $username, $password)
{
    $trace = '';
    $result = new MHAuthenticationResult(MHAuthenticationResult::FAILURE, '', '');
    if(mh_is_token_valid($token, $secret, token_validity_interval, 'md5', $trace) || empty($secret)){
        $user = authenticate_user_login($username, $password);
        if($user != false){
            $result = new MHAuthenticationResult(MHAuthenticationResult::SUCCESS, $user->username, '');
        }
        else{
            $result = new MHAuthenticationResult(MHAuthenticationResult::FAILURE, '', $trace.'User Authentication Failed');
        }
    }
    return $result;
}

function mh_get_user_info($token, $secret)
{
    global $DB;
    $trace = '';
    $userinfo = new MHUserInfo(MHUserInfo::FAILURE);
    $userinfo->Message = 'error:token is invalid';
    $userid = NULL;
    if(mh_is_token_valid($token, $secret, token_validity_interval, 'md5', $trace) || empty($secret))
    {
        try{
            $userinfo = new MHUserInfo(MHUserInfo::SUCCESS);
            $username = mh_get_token_value(mh_hex_decode($token), "userid");
            $user = NULL;
            if(!empty($username))
            {
                $studentRoles = $DB->get_records('role', array('archetype'=>'student'));
                $editingTeacherRoles = $DB->get_records('role', array('archetype'=>'editingteacher'));
                $teacherRoles = $DB->get_records('role', array('archetype'=>'teacher'));
                $user = $DB->get_record("user", array("username" => $username) );
                $userid = $user->id;
                $userinfo->set_user($user);
                $trace = $trace.';user is set';

                $courses = enrol_get_users_courses($userid, true);
                foreach ($courses as $course) {
                    $context = get_context_instance(CONTEXT_COURSE, $course->id);
                    foreach ($editingTeacherRoles as $role)
                    {
                        $roleid = $role->id;
                        $ras = $DB->get_records('role_assignments', array('roleid'=>$roleid, 'contextid'=>$context->id, 'userid'=>$userid));
                        if(count($ras) > 0){
                            $userinfo->add_course($course, 'instructor');
                        }
                    }
                    foreach ($teacherRoles as $role)
                    {
                        $roleid = $role->id;
                        $ras = $DB->get_records('role_assignments', array('roleid'=>$roleid, 'contextid'=>$context->id, 'userid'=>$userid));
                        if(count($ras) > 0){
                            $userinfo->add_course($course, 'instructor');
                        }
                    }
                    foreach ($studentRoles as $role)
                    {
                        $roleid = $role->id;
                        $ras = $DB->get_records('role_assignments', array('roleid'=>$roleid, 'contextid'=>$context->id, 'userid'=>$userid));
                        if(count($ras) > 0){
                            $userinfo->add_course($course, 'student');
                        }
                    }
                }
                $trace = $trace.';courses are set';
                $userinfo->Message = '';
            }
        }
        catch(Exception $e)
        {
            $userinfo = new MHUserInfo(MHUserInfo::FAILURE);
            $userinfo->Message = "ex:".$e->getMessage()." trace:".$trace;
            $userinfo->username = $username;
            $userinfo->userid = $userid;
        }
    }else { $userinfo->Message = "trace:".$trace;
    }
    return $userinfo;

}
