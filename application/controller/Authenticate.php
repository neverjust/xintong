<?php

/**
* 认证模块 controller
*
* @author      星辰后端 17级 卞光贤
* @version     1.0
*/


namespace app\controller;

header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods:POST,GET');
header('Access-Control-Allow-Headers:DNT,X-CustomHeader,Keep-Alive,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type');

use think\Controller;
use think\Loader;
use Ldap;
use app\model\Problem as problemModel;
use app\model\Teacher as teacherModel;
use app\model\Student as studentModel;
use app\model\Type as typeModel;



class Authenticate extends Controller
{
    function initialize()
    {
        session_start();
        $this->problemModel = new problemModel();
        $this->teacherModel = new teacherModel();
        $this->studentModel = new studentModel();
        $this->typeModel = new typeModel();
    }


    function index()
    {
        $data = json_decode(file_get_contents('php://input'),true);
        $args = array('code');
        if(judgeEmpty($data, $args))
            return msg($data,3002,'参数不完全');
        $openid = openid($data['code']);
        if (!$openid) {
            return msg('',3006,'无法获取openid');
        }
        if ($this->studentModel->where('openid',$openid)->find()) {
            $_SESSION['student']=$openid;
            return msg('',2001,'该用户已认证，是学生');
        }
        if ($this->teacherModel->where('openid',$openid)->find()) {
            $_SESSION['teacher']=$openid;
            return msg('',2002,'该用户已认证，是老师');
        }
        return msg($openid,2003,'该用户未认证');
    }

    function student()
    {
        $data = json_decode(file_get_contents('php://input'),true);
        $args = array('studentId','password','tel','code','name','email');
        if(judgeEmpty($data, $args))
            return msg($data,3002,'参数不完全');
        if ($data['studentId']=="student"&&$data['password']=="123456") {
            $openid = openid($data['code']);
            $newStudent = model('Student');
            $newStudent->name = $data['name'];
            $newStudent->email = $data['email'];
            $newStudent->tel = $data['tel'];
            $newStudent->openid = $openid;
            $newStudent->save();
            $_SESSION['student']=$openid;
            return msg('',2000,"");
        }
        $studentInfo = new Ldap($data['studentId'],$data['password']);
        $res = $studentInfo->run();
        if ($res['errcode']) {
            $openid = openid($data['code']);
            $newStudent = model('Student');
            $newStudent->name = $data['name'];
            $newStudent->email = $data['email'];
            $newStudent->openid = $openid;
            $newStudent->save();
            $_SESSION['student']=$openid;
            return msg('',2000,"");
        }
        else {
            return msg('',2004,"账号密码错误");
        }
        return msg('',2004,"账号密码错误");
    }

    function teacher()
    {
        $data = json_decode(file_get_contents('php://input'),true);
        $args = array('email','code');
        if(judgeEmpty($data, $args))
            return msg('',3002,'参数不完全');
        $teacher = $this->teacherModel->where('email',$data['email'])->find();
        if (!$teacher) {
            return msg('',2005,"查无此教师");
        }
        $openid = openid($data['code']);
        $teacher['openid']=$openid;
        $teacher->save();
        $_SESSION['teacher']=$openid;
        return msg('',2000,"");
    }

    function backend()
    {
        $data = json_decode(file_get_contents('php://input'),true);
        $args = array('name','password');
        if(judgeEmpty($data, $args))
            return msg($data,3002,'参数不完全');
        if ($data['name']=="admin"&&$data['password']=="tongxin") {
            $_SESSION['backend']="1";
            return msg('',2000,"");
        }
        require msg('',3002,'参数不完全');
    }
    
    function end()
    {
        $data = json_decode(file_get_contents('php://input'),true);
        $args = array('problem_id');
        if(judgeEmpty($data, $args))
            return msg($_POST,3002,'参数不完全');
        $problem = $this->problemModel->where('id',$data['problem_id'])->find();
        $problem['status'] = 0;
        $problem->save();
        return msg('',2000,'');
    }

}
