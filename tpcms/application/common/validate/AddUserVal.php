<?php
/**
 * Created by PhpStorm.
 * User: Lenovo
 * Date: 2020/10/30
 * Time: 19:12
 */

namespace app\common\validate;


use think\Db;
use think\Validate;

class AddUserVal extends Validate
{
    protected  $rule = [
        "user_name" => "require|user_name_exits",
        "password" => "require",
        "phone" => "require|phone_exits",
        "email" => "require"
    ];
    protected $message = [
        "user_name.require"=>"账号名不能为空",
        "password.require"=>"请输入密码",
        "phone.require"=>"请输入手机号",
        "email.require"=>"请输入电子邮箱"
    ];
    protected function user_name_exits ($value)
    {
        $result = Db::table("author")->where('user_name',$value)->find();

        if (!empty($result)) {
            return "当前用户已存在";
        }else {
            return true;
        }
    }
    protected function phone_exits ($value)
    {
        $result = Db::table("author")->where('phone',$value)->find();
        if (!empty($result)) {
            return "手机号码被注册过了";
        }else {
            return true;
        }
    }
}