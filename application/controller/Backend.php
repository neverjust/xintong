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


use app\model\Problem as problemModel;
use app\model\Teacher as teacherModel;
use app\model\Student as studentModel;
use app\model\Type as typeModel;
use app\model\Dialogue as dialogueModel;
use app\model\DialoguePic as dialoguePicModel;
use app\model\ProblemPic as problemPicModel;

use think\Controller;
use think\Loader;
use Ldap;



class Backend extends Controller
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
    }
    function getStudents()
    {
        $teahcers = $this->studentModel->select();
        return msg($teahcers,2000,'');
    }

    function getProblems()
    {
        $problems = $this->problemModel->order("timestamp desc")->select();
        return msg($problems,2000,'');
    }

    function getProblem()
    {
        $data = json_decode(file_get_contents('php://input'),true);
        $args = array('problem_id');
        if(judgeEmpty($data, $args))
            return msg("",3002,'参数不完全');
        $problem = $this->problemModel->where('id',$data['problem_id'])->find();
        $problem['pictures'] = $this->problemPicModel->where('problem_id',$data['problem_id'])->select();
        $problem['student'] = $this->studentModel->find($problem['student_id']);
        $problem['teacher'] = $this->teacherModel->find($problem['teacher_id']);
        $result['problem'] = $problem;
        $result['dialogues'] = $this->dialogueModel->where('problem_id',$problem['id'])->select();
        foreach ($result['dialogues'] as $dialogue) {
            $dialogue['student'] = $this->studentModel->find($dialogue['student_id']);
            $dialogue['teacher'] = $this->teacherModel->find($dialogue['teacher_id']);
            $dialogue['pictures'] = $this->dialoguePicModel->where('dialogue_id',$dialogue['id'])->select();
        }
        return msg($result,2000,'');
    }

    function newType()
    {
        $data = json_decode(file_get_contents('php://input'),true);
        $args = array('name','teacher_id');
        if(judgeEmpty($data, $args))
            return msg($data,3002,'参数不完全'); 
        $newType['name'] = $data['name'];
        $newType['teacher_id'] = $data['teacher_id'];
        $add[] = $newType;
        $this->typeModel->saveAll($add);
        return msg('',2000,'');
    }

    function changeType()
    {
        $data = json_decode(file_get_contents('php://input'),true);
        $args = array('id','name','teacher_id');
        if(judgeEmpty($data, $args))
            return msg($data,3002,'参数不完全'); 
        $type = $this->typeModel->where('id',$data['id'])->find();
        if (!$type)
            return msg('',3001,'未找到该分类问题');
        $type['name']=$data['name'];
        $type['teacher_id']=$data['teacher_id'];
        $type->save();
        return msg('',2000,'');
    }
    function getTypes()
    {
        $types = $this->typeModel->select();
        foreach ($types as $type) {
            $type['teacher'] = $this->teacherModel->find($type['teacher_id']);
        }
        return msg($types,2000,'');
    }
    function delType()
    {
        $data = json_decode(file_get_contents('php://input'),true);
        $args = array('id');
        if(judgeEmpty($data, $args))
            return msg($data,3002,'参数不完全'); 
        $this->typeModel->destroy($data['id']);
        return msg('',2000,'');
    }

    function newTeacher()
    {
        $data = json_decode(file_get_contents('php://input'),true);
        $args = array('name','email');
        if(judgeEmpty($data, $args))
            return msg($data,3002,'参数不完全'); 
        $newTeacher['name'] = $data['name'];
        $newTeacher['email'] = $data['email'];
        $add[] = $newTeacher;
        $this->teacherModel->saveAll($add);
        return msg('',2000,'');
    }
    function changeTeacher()
    {
        $data = json_decode(file_get_contents('php://input'),true);
        $args = array('id','name','email');
        if(judgeEmpty($data, $args))
            return msg($data,3002,'参数不完全'); 
        $teacher = $this->teacherModel->where('id',$data['id'])->find();
        if (!$teacher)
            return msg('',3001,'未找到该老师');
        $teacher['name']=$data['name'];
        $teacher['email']=$data['email'];
        $teacher->save();
        return msg('',2000,''); 
    }

    function getTeachers()
    {
        $teahcers = $this->teacherModel->select();
        return msg($teahcers,2000,'');
    }

    function delTeacher()
    {
        $data = json_decode(file_get_contents('php://input'),true);
        $args = array('id');
        if(judgeEmpty($data, $args))
            return msg($data,3002,'参数不完全');
        $this->teacherModel->destroy($data['id']);
        return msg('',2000,'');
    }

}
