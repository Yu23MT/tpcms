<?php
/**
 * Created by PhpStorm.
 * User: Lenovo
 * Date: 2020/10/21
 * Time: 10:45
 */

namespace app\common\validate;


use think\Db;
use think\Validate;

class LoginDataVal extends Validate
{
   protected  $rule = [
       "name"=>"require|userExists",
       "password"=>"require"
   ];
   protected $message = [
       "name.require" =>"请输入账号",
       "password.require"=>"请输入账号密码"
   ];
   protected function userExists($name)
   {
       $result = Db::table("author")->where("user_name",$name)->find();
       if (empty($result)) {
           return "当前账号不存在";
       }else{
           return true;
       }
   }
   /*protected function passwordIsSure($data)
   {
       $result = Db::table("author")->where("user_name",$data)->where("password",$data)->find();

       if (empty($result)) {
           return "密码错误";
       }else{
           return true;
       }
   }*/
}