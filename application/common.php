<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
define('PICTURE_RELATIVE_PATH', '/uploads/pictures/');


function savePictures($pictures)
{
    foreach ($pictures as $pic) {
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $pic, $result)){
            $type = $result[2];
            //保存位置--图片名
            $image_name=randomName($type);
            $image_file_path = PICTURE_RELATIVE_PATH.date('Ymd');
            $image_file = PUBLIC_PATH.$image_file_path;
            $imge_real_url = $image_file.'/'.$image_name;
            $imge_web_url = $image_file_path.'/'.$image_name;
            if (!file_exists($image_file)){
                    mkdir($image_file, 7777);
                } 
            $decode=base64_decode(str_replace($result[1], '', $pic));
            if (file_put_contents($imge_real_url, $decode)){
                $urls[] = $imge_web_url;
            }else{
                return false;
            }
        }
        else {
            return false;
        }
    }
    return $urls;
}



function randomName($suffix){
    $name = date("His",time())."_".random(10, '123456789abcdefghijklmnpqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ');
    $file_name = $name.".".$suffix;
    return $file_name;
}
function random($length, $chars = '0123456789') {
    $hash = '';
    $max = strlen($chars) - 1;
    for($i = 0; $i < $length; $i++) {
    $hash .= $chars[mt_rand(0, $max)];
    }
    return $hash;
}

/**  
* 前后端统一接口
*
* @access public 
* @param array  $data      传输的数据
* @param int    $errorCode 错误代码
* @param string $message   错误信息
* @return json
*/ 
function msg($data,$errorCode,$message)
{
    $info = ['data'=>$data,'errorCode'=>$errorCode,'errorMsg'=>$message];
    return json_encode($info,JSON_UNESCAPED_UNICODE);
}
function test(){
    return $_POST['data'];
}
/**
 * @param $data 数据
 * @param $args 必须的元素
 * @return boolen 返回
 */
function judgeEmpty($data,$args)
{
    foreach ($args as $key) {
        if (!array_key_exists($key, $data)) {
            return true;
        }
    }
    return false;
}

function appId()
{
	return "wx3bf464b35594481c";
}
function appSecret()
{
	return "7626c78c6fdf7188076c319415f3d844";
}

function pushMeetingMessage($openid,$name,$content,$from="student")
{
    $accessToken = getAccessToken();
    $url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=".$accessToken;
    $json_template = $this->json_tempalte($openid,$from,$name,$content);
    $res= curl_post($url,urldecode($json_template));
    return $res;
}

function getAccessToken()
{
    $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".appId()."&secret=".appSecret();
    $result = json_decode(http_request($url),true);
    return($result['access_token']); 
}

function json_tempalte($openid,$from="student",$name,$content){
    switch ($from) {
        case 'student':
            $template_id = "Delfw6dQ7NWVhVfXPgob3-s20cqMOHH3La16wmqto3Y";
            break;
        case 'teacher':
            $template_id = "eUZjs5a-B35dHtDk1M3qFHZpUgD0IWTjsw6xI0M0Kww";
            break;
        default:
            return false;
    }
        $template=array(
        'touser'=> $openid,  //用户openid
        'template_id'=> $template_id, //在公众号下配置的模板id
        
        'data'=>array(
            'keyword1'=>array('value'=>urlencode($name),'color'=>'#FF0000'),
            'keyword2'=>array('value'=>urlencode($content),'color'=>'#FF0000'),)
        );
        $json_template=json_encode($template);
        return $json_template;
    }
    
function openId($code)
{
	$appId = appId();
    $appSecret = appSecret();

    $url = "https://api.weixin.qq.com/sns/jscode2session?appid=".$appId."&secret=".$appSecret."&js_code=".$code."&grant_type=authorization_code";
    $weixin  = file_get_contents($url);
    $jsondecode = json_decode($weixin);
    $array = get_object_vars($jsondecode);//转换成数组（用户数据组）
    if(!isset($array['openid']))
        return false;
    $openid = $array['openid'];//输出openid
    
    return $openid;

}
function curl_post($url,$data=array()){
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	// POST数据
	curl_setopt($ch, CURLOPT_POST, 1);
	// 把post的变量加上
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	$output = curl_exec($ch);
	curl_close($ch);
	return $output;
}

function http_request($url,$data = null){ 
    $curl = curl_init(); 
    curl_setopt($curl, CURLOPT_URL, $url); 
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE); 
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE); 
    if (!empty($data)){ 
        curl_setopt($curl, CURLOPT_POST, 1); 
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data); 
    } 
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); 
    $output = curl_exec($curl); 
    curl_close($curl); 
    return $output; 
}

function httpRequest($url, $postData=array()){
    // （1）初始化
    $ch = curl_init();
    // （2）设置选项
    // 设置请求的url
    curl_setopt($ch, CURLOPT_URL, $url);
    // 将curl_exec()获取的数据以字符串返回，而不是直接输出。
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    if(!empty($postData)){
        // 设置请求方式为post
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    }
    //curl注意事项，如果发送的请求是https，必须要禁止服务器端校检SSL证书
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    // （3）执行
    $result = curl_exec($ch); 
    // （4）关闭
    curl_close($ch);
    return $result;
}

function getUserInfor($openid){
    //获取access_token值
    $APPID = appId();
    $SECRET = appSecret();
    $token_url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$APPID.'&secret='.$SECRET;
    $data = json_decode(httpRequest($token_url),true); 
    $accessToken = $data['access_token'];
    $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$accessToken."&openid=".$openid;
    $userinfo = json_decode(httpRequest($url),true); 
    return($userinfo);
}