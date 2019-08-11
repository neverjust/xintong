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




class Student extends Controller
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
    function update()
    {
        if (!isset($_SESSION['student']))
            return msg('',3001,'用户未登录');
        $data = json_decode(file_get_contents('php://input'),true);
        $args = array('email','name','tel');
        if (judgeEmpty($data, $args))
            return msg('',3002,'参数不完全');
        $student = $this->studentModel->where('openid',$_SESSION['student'])->find();
        $student['email'] = $data['email'];
        $student['name'] = $data['name'];
        $student['tel'] = $data['tel'];
        $student->save();
        require msg('',2000,'');;

    }
    
    function getTypes()
    {
        $types = $this->typeModel->select();
        return msg($types,2000,'');
    }
    function getTeachers()
    {
        $teahcers = $this->teacherModel->select();
        return msg($teahcers,2000,'');
    }

    public function test()
    {
        $data = json_decode(file_get_contents('php://input'),true);
        return msg(empty($data['pictures']),2000,'');
    }


    function newProblem()
    {
        if (!isset($_SESSION['student']))
            return msg('',3001,'用户未登录');
        $data = json_decode(file_get_contents('php://input'),true);
        $args = array('type','title','content','pictures');
        if (judgeEmpty($data, $args))
            return msg('',3002,'参数不完全');
        $student = $this->studentModel->where('openid',$_SESSION['student'])->find();
        $type = $this->typeModel->find($data['type']);
        $newProblem = model('Problem');
        $newProblem->title = $data['title'];
        $newProblem->content = $data['content'];
        $newProblem->type_id = $data['type'];
        $newProblem->student_id = $student['id'];
        $newProblem->teacher_id = $type['teacher_id'];
        $newProblem->save();
        $teacher = $this->teacherModel->where('id',$type['teacher_id'])->find();
        $emailResult = $this->email->send($teacher['email'],$student['name'],$data['title'],"student");
        if (!empty($data['pictures'])) {
            $paths = savePictures($data['pictures']);
            if (!$paths)
                return msg('',3004,'保存图片出错');
            foreach ($paths as $picpath) {
                $newPic['problem_id'] = $newProblem->id;
                $newPic['path'] = $picpath;
                $add[]=$newPic;
            }
            $result = $this->problemPicModel->saveAll($add);
        }
        return msg("",2000,'');
    }


    function getProblems()
    {
        if (!isset($_SESSION['student']))
            return msg('',3001,'用户未登录');
        $student = $this->studentModel->where('openid',$_SESSION['student'])->find();
        $problems = $this->problemModel->where('student_id',$student['id'])->order("time desc")->select();
        return msg($problems,2000,'');
    }

    function getProblem()
    {
        if (!isset($_SESSION['student']))
            return msg('',3001,'用户未登录');
        $data = json_decode(file_get_contents('php://input'),true);
        $args = array('problem_id');
        if(judgeEmpty($data, $args))
            return msg($_POST,3002,'参数不完全');
        $problem = $this->problemModel->where('id',$data['problem_id'])->find();
        $problem['student'] = $this->studentModel->find($problem['student_id']);
        $problem['teacher'] = $this->teacherModel->find($problem['teacher_id']);
        $problem['pictures'] = $this->problemPicModel->where('id',$data['problem_id'])->select();
        $result['problem'] = $problem;
        $result['dialogues'] = $this->dialogueModel->where('problem_id',$problem['id'])->select();
        foreach ($result['dialogues'] as $dialogue) {
            $dialogue['student'] = $this->studentModel->find($dialogue['student_id']);
            $dialogue['teacher'] = $this->teacherModel->find($dialogue['teacher_id']);
            $dialogue['pictures'] = $this->dialoguePicModel->where('dialogu_id',$dialogue['id'])->select();
        }
        return msg($result,2000,'');
    }

    function reply(){
        if (!isset($_SESSION['student']))
            return msg('',3001,'用户未登录');
        $data = json_decode(file_get_contents('php://input'),true);
        $args = array('problem_id','content','pictures');
        if(judgeEmpty($data, $args))
            return msg($_POST,3002,'参数不完全');
        $student = $this->studentModel->where('openid',$_SESSION['student'])->find();
        $problem = $this->problemModel->where('id',$data['problem_id'])->find();
        if (!$problem['status']) 
            return msg(’‘,2005,'该问题已经结束');
        $teacher = $this->teacherModel->where('id',$problem['teacher_id'])->find();
        $newDialogue = model('Dialogue');
        $newDialogue->problem_id = $data['problem_id'];
        $newDialogue->content = $data['content'];
        $newDialogue->student_id = $student['id'];
        $newDialogue->teacher_id = $teacher['id'];
        $newDialogue->dialogue_from = 0;
        $newDialogue->save();
        $problem['timestamp']  = $newDialogue->timestamp;
        $problem->save();
        if (!empty($data['pictures'])) {
            $paths = savePictures($data['pictures']);
            $this->email->send($teacher['email'],$student['name'],"有消息回复","student");
            foreach ($paths as $picpath) {
                $newPic['dialogue_id'] = $newDialogue->id;
                $newPic['path'] = $picpath;
                $add[]=$newPic;
            }
            $this->dialoguePicModel->saveAll($add);
        }
        return msg('',2000,'');
    }

}
