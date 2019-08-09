<?php

/** 
* 用户模块 controller
*
* @author      星辰后端 17级 卞光贤
* @version     1.0
*/

namespace app\controller;

use think\Controller;
use think\Loader;


class Index extends Controller
{
	function initialize()
    {
        session_start();
    }
    function index(){
        return 1;
    }

}
