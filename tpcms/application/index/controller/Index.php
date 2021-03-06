<?php
namespace app\index\controller;

use think\cache\driver\Redis;
use think\Controller;
use think\Cookie;
use think\Db;
use think\Request;


class Index extends Controller
{
    /**主页渲染和所有文章便利
     * @return mixed
     */

    public function index()
    {
        $this->getColumn();
        $this->assign("nowColumn");
        $data = Db::table("article")
            ->alias("a")
            ->field("a.*,b.user_name,c.*")
            ->order("a.create_time desc")
            ->join("author b","a.author_id=b.id")
            ->join("article_info c","c.article_id=a.id")
            ->where("a.delete_time","0")
            ->paginate(5,false,['query'=>\request()->param()]);

        $this->assign("data",$data);


        return $this->fetch();
    }

    /**获取所有父栏目；
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getColumn() {
        $column = Db::table("column")->where("f_id",0)->select();
        $redis = new Redis();

        foreach ($column as $key=> $value) {
           $article_num =  $redis->get('article_num'.$value['id']) == false ? 0 :  $redis->get('article_num'.$value['id']);
            $column[$key]['article_num'] = $article_num;
        }
        $this->assign("parentColumn",$column);
    }
    public function show(Request $request)
    {
        $nowId = $request->param("id");

        if (empty($nowId)) $this->error("缺少必要参数");

        $addPv = Db::table("article")->where("id", $nowId)->setInc("pv", 1);
        if ($addPv != 1) $this->error("发生错误");
        $nowData = Db::table("article")
            ->alias("a")
            ->field("a.*,b.*,c.name,d.user_name")
            ->join("article_info b", "a.id=b.article_id")
            ->join("column c", "c.id=a.cid")
            ->join("author d", "a.author_id=d.id")
            ->where("a.id", $nowId)
            ->where("a.delete_time","0")
            ->find();
        $this->assign("data", $nowData);
        //设置浏览历史；
        $this->cookie_history($nowData['id'],$nowData['title']);

        $f_id = Db::table("column")->where("id",$nowData['cid'])->value('f_id');
        $this->assign("nowColumn",$f_id);

        $this->getColumn();
        $childColumn = Db::table("column")->where("f_id",$nowId)->select();
        $this->assign("column",$childColumn);

        $isHotData = Db::table("article")->where("is_hot",1)->select();
        $this->assign("isHot",$isHotData);
        //下一篇文章
        $nextArticle = Db::table("article")
            ->where("id",">", $nowId)
            ->where("delete_time","0")
            ->order("id asc")
            ->limit($nowId,1)
            ->find();
        $this->assign("nextArticle",$nextArticle);

        //上一篇
        $upArticle = Db::table("article")
            ->where("id","<", $nowId)
            ->where("delete_time","0")
            ->order("id desc")
            ->limit($nowId,1)
            ->find();

        $this->assign("upArticle",$upArticle);
         return $this->fetch();

    }

    public function list(Request $request)
    {
        $nowId = $request->param("id");

        //判断是否由当前栏目
        $changeColumn = Db::table("column")->where("id",$nowId)->find();
        if (empty($changeColumn)) $this->error("没有当前数据");
        $this->assign("nowColumn",$changeColumn['f_id'] == 0 ? $changeColumn['id'] : $changeColumn['f_id']);
        $this->assign("changeColumn",$changeColumn['name']);
        //遍历右侧内容栏目
        $childId = Db::table("column")->where("f_id",$nowId)->select();
        if (empty($childId)) {
            $childId = Db::table("column")->where("id",$nowId)->select();
        }
        $childId = array_column($childId,'id');
        //获取数据；
        $data = Db::table("article")
            ->alias("a")
            ->field("a.*,b.user_name,c.*")
            ->order("a.create_time desc")
            ->where("a.cid","in",$childId)
            ->where("a.delete_time","0")
            ->join("author b","a.author_id=b.id")
            ->join("article_info c","c.article_id=a.id")
            ->paginate(8,false,['query'=>\request()->param()]);
        $this->assign("data",$data);
        $this->getColumn();

        $childColumn = Db::table("column")->where("f_id",$nowId)->select();
        if (empty($childColumn)) $childColumn = Db::table("column")->where("f_id",$changeColumn['f_id'])->select();
        $redis = new Redis();

        foreach ($childColumn as $key=> $value) {
            $article_num =  $redis->get('article_num'.$value['id']) == false ? 0 :  $redis->get('article_num'.$value['id']);
            $childColumn[$key]['article_num'] = $article_num;
        }

        $this->assign("childColumn",$childColumn);

        $isHotData = Db::table("article")->where("is_hot",1)->select();
        $this->assign("isHot",$isHotData);

        return $this->fetch();
    }
    protected function cookie_history($id,$title)
    {
        $dealInfo['id'] = $id;
        $dealInfo['title'] = $title;
        $cookie_history[] = json_encode($dealInfo);
        if (empty(cookie('history'))){//cookie空，初始一个
            cookie('history',$cookie_history);
        }else{
            $new_history = array_merge(cookie('history'),$cookie_history);//添加新浏览数据
            $history = array_unique($new_history);
            if (count($history) > 6){
                $history = array_slice($history,-6);
            }

            cookie('history',$history);
        }
    }



    public function demo()
    {
        //获取器
       /* $articleModel = Article::get(66);
        dump($articleModel->is_hot);
        dump($articleModel->is_hot_text);*/
       //设置器
        /*$user = new Author();
        $user->user_name = "er三";
        $user->password = "123456";
        //$arr = ["user_name"=>"小东","password"=>"456123"];
        $reuslt = $user->save();
        dump($reuslt);*/

        //$user::destroy(9);

        /*$data = Author::where("status",1)->all();
        dump($data);*/

       /* $result = Author::destroy(1);
        dump($result);*/

      /* $data = Article::with("article_info")->select();
        foreach ($data as $value) {
            var_dump($value->content);
        }*/
     /*$user = Article::get(90);
     dump($user->keywords);*/

    }

}
