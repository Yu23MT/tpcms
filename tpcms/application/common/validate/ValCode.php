<?php
/**
 * Created by PhpStorm.
 * User: Lenovo
 * Date: 2020/10/21
 * Time: 19:46
 */

namespace app\common\validate;


use think\Validate;

class ValCode extends Validate
{
    protected $rule = [
        'captcha|验证码'=>'require|captcha'
    ];
    protected $message = [
        'captcha.require'=>"请输入验证码",
        'captcha.captcha'=>"验证码错误"
    ];
}