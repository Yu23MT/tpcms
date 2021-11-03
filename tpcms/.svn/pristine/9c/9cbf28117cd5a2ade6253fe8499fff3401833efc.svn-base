<?php
/**
 * Created by PhpStorm.
 * User: Lenovo
 * Date: 2020/10/14
 * Time: 11:45
 */

namespace app\common\validate;


use think\Validate;

class updateValidate extends Validate
{
    protected $rule = [
        "title" => "require",
        "content" => "require",
        "image_id"=>"require",
    ];
    protected $message = [
        "user_name.require" => "好人不留名？",
        "user_mes.require" => "请指教",
        "image_id.require" => "你图片呢"
    ];
}