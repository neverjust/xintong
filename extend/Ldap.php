<?php

class Ldap
{
	function __construct($studentId,$password)
	{
		$this->studentId = $studentId??null;
		$this->password = $password??null;
	}
	function uestcLdap($stuno) 
	{
        $understu=true;  //标记是本科生
        if(strlen($stuno)==12 && is_numeric($stuno)){   //是研究生
            $understu=false;
        }
        $error = '';
        $ldap = array(
            'user'  =>  $stuno,
            'pass'  =>  'UOq6KUScLu',
            'host'  =>  '192.168.123.214',
            'port'  =>  30001,
            'dn'    =>  'uid=xgWX,ou=Manager,dc=uestc,dc=edu,dc=cn',
			'pass'  =>  'UOq6KUScLu',
            'base'  =>  'dc=uestc,dc=edu,dc=cn',
            'student' => 'ou=student,ou=People,',
            'undergraduate' => 'ou=UndergraduateAlumni,ou=Alumni,ou=People,'
        );
        $ldap['conn'] = ldap_connect($ldap['host'], $ldap['port']);
        if (!$ldap['conn']) {
            return array('error' => ldap_error($ldap['conn']));
        }
        $ldap['bind'] = ldap_bind($ldap['conn'], $ldap['dn'], $ldap['pass']);
        if (!$ldap['bind']) {
            $error = array('error' => ldap_error($ldap['conn']));
            ldap_close($ldap['conn']);
            return $error;
        }
        $ldap['result'] = ldap_search($ldap['conn'], $ldap['student'].$ldap['base'],'uid='.$ldap['user']);
        if ($ldap['result']) {
            $ldap['info'] = ldap_get_entries($ldap['conn'], $ldap['result']);
            if(!isset($ldap['info'][0]['userpassword'][0])){
                $ldap['result'] = ldap_search($ldap['conn'], $ldap['undergraduate'].$ldap['base'],'uid='.$ldap['user']);
                if ($ldap['result']) {
                    $ldap['info'] = ldap_get_entries($ldap['conn'], $ldap['result']);
                }
                else {
                    $error = array('error' => ldap_error($ldap['conn']));
                    ldap_close($ldap['conn']);
                    return $error;
                }
            }
        } else {
            $error = array('error' => ldap_error($ldap['conn']));
            ldap_close($ldap['conn']);
            return $error;
        }
        if ($ldap['info']) {    //获取到了信息
            //检测信息格式是否变化
            if(!isset($ldap['info'][0]['userpassword'][0]) || !$ldap['info'][0]['cn'][0] || !$ldap['info'][0]['sn'][0]){
                ldap_close($ldap['conn']);
                $error=['error'=>'LDAP返回格式出现变化，请尽快查看'];
                return $error;
            }
            //提取用户信息
            $stuinfo = array();
            $stuinfo['passwordHash'] = $ldap['info'][0]['userpassword'][0];
            $stuinfo['name'] =  $ldap['info'][0]['cn'][0];
            if (!$stuinfo['name']) {
                $stuinfo['name'] = $ldap['info'][0]['sn'][0];
            }
            $return = $stuinfo;
        }else{
            $return =  array('error' => ldap_error($ldap['conn']));
        }
        ldap_close($ldap['conn']);
        return $return;
    }
    function checkStuPwd(string $password, string $passwordHash):bool{
        $ohash = base64_decode(substr($passwordHash,6));
        $osalt = substr($ohash,20);
        $ohash = substr($ohash,0,20);
        $nhash = pack("H*",sha1($password.$osalt));
        return $ohash == $nhash;
    }
    public function run()
    {
    	$studentId=$this->studentId;
		$password=$this->password;
		$userInfo=$this->uestcLdap($studentId);
		if(!isset($userInfo['error']) && $this->checkStuPwd($password,$userInfo['passwordHash']))
	        return ['errcode'=>1,'errmsg'=>'','name'=>$userInfo["name"]];
	    else
	        return ['errcode'=>0,'errmsg'=>'不存在该用户','name'=>''];
	}
}