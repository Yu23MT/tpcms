<?php
/**
 * Created by PhpStorm.
 * User: Lenovo
 * Date: 2020/10/15
 * Time: 18:47
 */

namespace app\common\validate;


use think\Db;
use think\Validate;

class addValidate extends Validate
{
    protected $rule = [
            "title" => "require|max:30|titleExists",
            "cid"=> "require",
            "author_id" => "require",
            "keywords" => "require",
            "content" => "require|min:30"
        ];
    protected $message = [
            "title.require" => "请输入标题",
            "cid.require" => "请选择文章栏目",
            "author_id.require" => "请输入作者名",
            "keywords.require" => "缺少关键字",
            "content.require" => "文章内容呢"
    ];
    protected function titleExists($title)
    {
        $result = Db::table("article")->where("title",$title)->find();
        if (empty($result)) {
            return true;
        }else {
            return "当前文章以存在";
        }
    }
}