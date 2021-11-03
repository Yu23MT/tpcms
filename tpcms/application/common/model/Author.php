<?php
/**
 * Created by PhpStorm.
 * User: Lenovo
 * Date: 2020/10/22
 * Time: 20:22
 */

namespace app\common\model;


use think\Db;
use think\Exception;
use think\Model;
use think\model\concern\SoftDelete;

class Author extends Model
{
    protected $autoWriteTimestamp = true;
    protected $insert = ['status' => 1,'admin'=>0];
    public function setPasswordAttr ($value)
    {
        return password_hash($value,PASSWORD_DEFAULT);
    }
}