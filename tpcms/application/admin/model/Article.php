<?php
/**
 * Created by PhpStorm.
 * User: Lenovo
 * Date: 2020/10/17
 * Time: 10:05
 */

namespace app\admin\model;


use think\Model;
use think\model\concern\SoftDelete;

class Article extends Model
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    protected $createTime = 'create_time';
    protected $autoWriteTimestamp = true;
    protected $defaultSoftDelete = 0;

    public function articleInfo ()
    {
        return $this->hasOne("ArticleInfo","article_id","id");
    }
    public function keyword()
    {
        return $this->belongsToMany("Keyword","article_keyword_relation","keyword_id","article_id");
    }
}