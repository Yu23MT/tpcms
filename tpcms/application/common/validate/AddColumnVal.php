<?php
/**
 * Created by PhpStorm.
 * User: Lenovo
 * Date: 2020/10/20
 * Time: 11:30
 */

namespace app\common\validate;


use think\Db;
use think\Validate;

class AddColumnVal extends Validate
{
    protected $rule = [
        "sort"=>"integer",
        "name"=>"require|ColumnExists",
        "f_id"=>"require|integer"
    ];
    protected $message = [
        "name.require"=>"栏目名不能为空",
        "f_id.require"=>"数据发生错误"
    ];
    protected function ColumnExists($data)
    {
        $data = Db::table("column")->where("name",$data)->find();
        if (empty($data)) {
            return true;
        }else {
            return "此用户已经存在";
        }
    }

}