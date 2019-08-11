<?php

namespace app\model;

use think\Model;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Email extends Model
{
    function send($to, $nickname, $title, $content){
        // 实例化
        $mail = new PHPMailer(true);
        try {
            //Server settings
            $mail->SMTPDebug = 0;                                 // Enable verbose debug output   2调试模式 0用户模式
            $mail->isSMTP();                                      // Set mailer to use SMTP
            $mail->CharSet = 'utf-8';                         // 设置右键格式编码
            $mail->Host = 'smtp.126.com';                          // Specify main and backup SMTP servers   smtp服务器的名称
            $mail->SMTPAuth = true;                               // Enable SMTP authentication
            $mail->Username = 'xxxian1999@126.com';                 // SMTP username
            $mail->Password = 'xxxian1999';                           // SMTP password   此为QQ授权码。
            $mail->SMTPSecure = 'ssl';                            // Enable TLS encryption, `ssl` also accepted   目前规定必须使用ssl，非ssl的协议已经不支持了
            $mail->Port = 465;                                    // TCP port to connect to   ssl协议，端口号一般是465
            //Recipients
            $mail->setFrom('xxxian1999@126.com', $nickname); // 设置右键发送人信息(邮箱, 昵称)
            $mail->addAddress($to, "接受者");     // 设置收件人信息(邮箱, 昵称)
            //Content
            $mail->isHTML(false);                                  // Set email format to HTML
            $mail->Subject = $title;                          // 设置发送的邮件标题
            $mail->Body    = $content;                            // 设置邮件发送内容
            $mail->send();
        } catch (Exception $e) {
            exception($mail->ErrorInfo,1001);                 // 失败抛出错误信息
        }
    } 
    
}
