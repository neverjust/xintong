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
use app\model\Dialogue as dialogueModel;
use app\model\DialoguePic as dialoguePicModel;
use app\model\ProblemPic as problemPicModel;
use app\model\Email as Email;


class Teacher extends Controller
{
    function initialize()
    {
        session_start();
        $this->problemModel = new problemModel();
        $this->teacherModel = new teacherModel();
        $this->studentModel = new studentModel();
        $this->typeModel = new typeModel();
        $this->dialogueModel = new dialogueModel();
        $this->dialoguePicModel = new dialoguePicModel();
        $this->problemPicModel = new problemPicModel();
        $this->email = new Email();

    }
    function getProblems()
    {
        if (!isset($_SESSION['teacher']))
            return msg('',3001,'用户未登录');
        $teacher = $this->teacherModel->where('openid',$_SESSION['teacher'])->find();
        $problems = $this->problemModel->where('teacher_id',$teacher['id'])->order("timestamp desc")->select();
        return msg($problems,2000,'');
    }

    function getProblem()
    {
        $data = json_decode(file_get_contents('php://input'),true);
        $args = array('problem_id');
        if(judgeEmpty($data, $args))
            return msg($_POST,3002,'参数不完全');
        $problem = $this->problemModel->where('id',$data['problem_id'])->find();
        $problem['student'] = $this->studentModel->find($problem['student_id']);
        $problem['teacher'] = $this->teacherModel->find($problem['teacher_id']);
        $problem['pictures'] = $this->problemPicModel->where('problem_id',$data['problem_id'])->select();
        $result['problem'] = $problem;
        $result['dialogues'] = $this->dialogueModel->where('problem_id',$problem['id'])->select();
        foreach ($result['dialogues'] as $dialogue) {
            $dialogue['student'] = $this->studentModel->find($dialogue['student_id']);
            $dialogue['teacher'] = $this->teacherModel->find($dialogue['teacher_id']);
            $dialogue['pictures'] = $this->dialoguePicModel->where('dialogue_id',$dialogue['id'])->select();
        }
        return msg($result,2000,'');
    }



    function reply(){
        if (!isset($_SESSION['teacher']))
            return msg('',3001,'用户未登录');
        $data = json_decode(file_get_contents('php://input'),true);
        $args = array('problem_id','content','pictures');
        if(judgeEmpty($data, $args))
            return msg($_POST,3002,'参数不完全');

        $teacher = $this->teacherModel->where('openid',$_SESSION['teacher'])->find();
        $problem = $this->problemModel->where('id',$data['problem_id'])->find();
        $student = $this->studentModel->find($problem['student_id']);
        if (!$problem['status']) 
            return msg('',2005,'该问题已经结束');

        $newDialogue = model('Dialogue');
        $newDialogue->problem_id = $data['problem_id'];
        $newDialogue->content = $data['content'];
        $newDialogue->student_id = $student['id'];
        $newDialogue->teacher_id = $teacher['id'];
        $newDialogue->dialogue_from = 1;
        $newDialogue->save();
        $problem['timestamp']  = date("Y-m-d H:i:s",time());
        $problem->save();
        if(!empty($data['pictures'])){
            $paths = savePictures($data['pictures']);
            if (isset($student['email'])) 
                $this->email->send($student['email'],$teacher['name'],"有新消息回复","请到小程序上查看详情");
            foreach ($paths as $picpath) {
                $newPic['dialogue_id'] = $newDialogue->id;
                $newPic['path'] = $picpath;
                $add[]=$newPic;
            }
            $this->dialoguePicModel->saveAll($add);
        }
        return msg('s',2000,'');
    }

    function forward()
    {
        if (!isset($_SESSION['teacher']))
            return msg('',3001,'用户未登录');
        $data = json_decode(file_get_contents('php://input'),true);
        $args = array('problem_id','teacher_id');
        if(judgeEmpty($data, $args))
            return msg($_POST,3002,'参数不完全');
        $problem = $this->problemModel->where('id',$data['problem_id'])->find();
        $problem['teacher_id'] = $data['teacher_id'];
        $problem->save();
        $old_teacher = $this->teacherModel->where('openid',$_SESSION['teacher'])->find();
        $teacher = $this->teacherModel->find($data['teacher_id']);
        $this->email->send($teacher['email'],$old_teacher['name'],"收到其他老师转接的问题","请到小程序上查看详情");
        return msg('',2000,'');
    }

    function end()
    {
        if (!isset($_SESSION['teacher']))
            return msg('',3001,'用户未登录');
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
