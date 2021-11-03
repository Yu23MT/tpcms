<?php
/**
 * Created by PhpStorm.
 * User: Lenovo
 * Date: 2020/10/22
 * Time: 19:54
 */

namespace app\common\model;


use think\Model;

class Article extends Model
{
    public function getIsHotTextAttr($value,$data)
    {
        $status = [0=>'普通',1=>'最热'];
        /*return $status[$value];*/
        return $status[$data['is_hot']];
    }

    ///获取主表文章内容；
    public function getContentAttr()
    {
        return $this->articleInfo->content;
    }

    /**主表和附表关联
     * @param $value
     * @return string
     */
    public function articleInfo ()
    {
        return $this->hasOne("ArticleInfo","article_id","id");
    }

    public function keywords()
    {
        return $this->belongsToMany("Keyword","article_keyword_relation","keyword_id","article_id");
    }

}