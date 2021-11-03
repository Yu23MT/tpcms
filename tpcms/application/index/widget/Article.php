<?php
/**
 * Created by PhpStorm.
 * User: Lenovo
 * Date: 2020/10/27
 * Time: 10:34
 */

namespace app\index\widget;


use think\cache\driver\Redis;
use think\Db;

class Article
{
    /**栏目分层获取游览历史
     * @return string
     */
    public function history()
    {
        $history = $this->getHistory();
        $html = "";
        $html .= "<div class='si-each'>";
        $html .= "<div class='si-title'>浏览历史</div>";
        $html .= " <div class='si-p2'>";
        foreach ($history as $value) {
            $html .= "<p><a href=='http://www.tp5.com/news-{$value['id']}'>";
            $html .= " {$value['title']}</a></p>";
        }
        $html .= " </div></div>";
       return $html;
    }
    public function getHotArticle ()
    {
        $isHotData = Db::table("article")->where("is_hot",1)->select();
        $isHotDataCount = count($isHotData);
        $redis = new Redis();
        $redis->auth('chenhoulv');

        $html = "";
        $html .= "<div class=\"si-each\">";
        $html .= "<div class=\"si-title\"><span class=\"si-p3-top\">TOP {$isHotDataCount}</span> 热门文章</div>";
        $html .= "<div class=\"si-p3\">";
        foreach ($isHotData as $value) {
            $html .= "<p><a href='http://www.tp5.com/news-{$value['id']}'>{$value['title']}</a></p>";
        }
        $html .= " </div></div>";
        return $html;
    }
    protected function getHistory()
    {
        $history = \cookie("history");
        if (empty($history)) $history = [];
        $list=[];
        $dataCount = count($history) - 1;
        for ($i = $dataCount; $i>= 0 ; $i--) {
            $list[$i] = json_decode($history[$i],true);
        }
        return $list;
    }
}