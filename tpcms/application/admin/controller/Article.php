<?php
/**
 * Created by PhpStorm.
 * User: Lenovo
 * Date: 2020/10/28
 * Time: 9:12
 */

namespace app\admin\controller;


use app\common\column\GetColumn;
use app\common\validate\addValidate;
use app\http\middleware\Check;
use think\cache\driver\Redis;
use think\Db;
use think\facade\Session;
use think\Image;
use think\Request;

class Article extends Check
{
    protected $middleware = ['Check'];
    /**发表文章页面渲染；
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */

    public function index()
    {

        $account_name = Session::get("account");
        $this->assign("account", $account_name);
        $data = GetColumn::get();
        $this->assign("data",$data);
        return $this->fetch("");
    }

    /**上传文件和处理
     * @return string
     */

    public function uploadImage()
    {
        if (empty(request()->file())) {
            return "";
        }
        $file = request()->file("image");
        $image = Image::open($file);
        $path = './uploads' . "/" . date("Y-m/d");
        $name = substr(md5(mt_rand(000000, 999999)), 0, 30) . "." . "jpg";
        $height = ($image->height() > 220) ? 220 : $image->height();
        $width = ($image->width() > 780) ? 780 : $image->width();
        $nowDir = getcwd();
        if (!file_exists($path)) mkdir($path, 0777, true);
        $result = $image->thumb($width, $height)->text("远东IT学院", $nowDir . "/static/font/STKAITI.TTF", "28", '#ffffff', 4)
            ->save($path . "/" . $name);
        if ($result) {
            return '/uploads' . "/" . date("Y-m/d") . "/" . $name;
        } else {
            $this->error("图片上传失败");
        }
    }

    /**增加数据；
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function add(Request $request)
    {
        if ($request->isPost()) {
            $data = $request->post();

            if (!empty($data["save"])) $data["status"] = 0;
            $data["imagePath"] = $this->uploadImage();

            $data["content"] = substr($data["content"], 3, -6);
            $data['description'] = empty($data['description']) ? substr($data["content"], 0, 30) : $data['description'];
            $authorId = Db::table("author")->where("user_name", $data["author_id"])->value("id");
            if (empty($authorId)) $this->error("没有当前作者");
            $data["author_id"] = $authorId;
            $validate = new addValidate();
            if (!$validate->check($data)) {
                $this->error($validate->getError());
            }

            $model = new \app\admin\model\Article();
            //开启事务
            $model->startTrans();
            $result = $model->allowField(true)->save($data);
            if (!$result) {
                $model->rollback();
                $this->error("增加文章失败");
            }
            $article_id = $model->id;
            $article = $model::get($article_id);
            $article->articleInfo()->save($data);
            $keywordArr = array_unique(explode(",", $data["keywords"]));
            $keywordIsExist = Db::table("keyword")->whereIn("keyword", $keywordArr)->select();
            $keywordId = array_column($keywordIsExist, "id");

            $article->keyword()->saveall($keywordId);

            $existsKeyword = array_column($keywordIsExist, "keyword");
            $notExistsKeywords = array_diff($keywordArr, $existsKeyword);
            $insertKeywordArr = [];

            foreach ($notExistsKeywords as $value) {
                $insertKeywordArr[]["keyword"] = $value;
            }
            if (!empty($insertKeywordArr)) $article->keyword()->saveAll($insertKeywordArr);
            $model->commit();
            //累计文章数量到redis;
            $redis = new Redis();
            $redis->auth('chenhoulv');
            $nowColumn = Db::table('column')->where('id',$data['cid'])->find();
            $redis->set("article_num".$nowColumn['f_id'],($redis->get("article_num".$nowColumn['f_id'])) + 1);
            $redis->set("article_num".$nowColumn['id'],($redis->get("article_num".$nowColumn['id'])) + 1);
            $this->success("增加成功", "article/index");
        }
    }

    /**文章管理 兼数据遍历 分页
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function article($data = [])
    {
        //文章遍历
        if (empty($data)) {
            $account = Session::get("account");
            $accountType =  $account['admin'];
            if ($accountType == 1) {
                $data = Db::table('article')
                    //起别名
                    ->alias('a')
                    ->order("a.create_time desc")
                    ->field("a.*,b.name,c.user_name")
                    ->join('column b', 'a.cid=b.id')
                    ->join('author c',"a.author_id=c.id")
                    ->where('delete_time',0)
                    ->paginate(5, false, ['query' => \request()->param()]);
            }else {
                $data = \app\admin\model\Article::table('article')
                    //起别名
                    ->alias('a')
                    ->order("a.create_time desc")
                    ->field("a.*,b.name,c.user_name")
                    ->join('column b', 'a.cid=b.id')
                    ->join('author c', 'a.author_id=c.id')
                    ->where("a.author_id",$account['id'])
                    ->paginate(5, false, ['query' => \request()->param()]);
            }

        }

        $this->assign("data", $data);
        //上方栏目遍历
        $column = GetColumn::get();
        $this->assign("column", $column);

        return $this->fetch();
    }

    /**内容管理页面修改
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function cp_article_edit_2 (Request $request)
    {
        $column = GetColumn::get();

        $this->assign("column",$column);
        $nowId = $request->param("id");
        if (empty($nowId)) {
            $this->error("缺少必要参数");
        }
        $data = Db::table("article")
            ->alias("a")
            ->field("a.*,b.*,d.user_name")
            ->join("article_info b","a.id=b.article_id")
            ->join("article_keyword_relation c","a.id=c.article_id")
            ->join("author d","a.author_id=d.id")
            ->where("a.id",$nowId)->find();
        $keywordIdArr = Db::table("article_keyword_relation")->where("article_id",$nowId)->select();
        $keywordArrStr = "";
        foreach ($keywordIdArr as $value) {
            $keywordArrStr .= $value["keyword_id"] .",";
        }
        $keywordArrStr =substr($keywordArrStr,0,-1);
        $keywordArr = Db::table("keyword")->where("id","in",$keywordArrStr)->select();
        $keywordStr = "";
        foreach ($keywordArr as $value) {
            $keywordStr .= $value["keyword"] .",";
        }
        $keywordStr =substr($keywordStr,0,-1);
        $data["keyword"] = $keywordStr;

        $redis = new Redis();
        $redis->auth('chenhoulv');
        $nowColumn = Db::table('column')->where('id',$data['cid'])->find();
        $redis->set("article_num".$nowColumn['f_id'],($redis->get("article_num".$nowColumn['f_id'])) - 1);
        $redis->set("article_num".$nowColumn['id'],($redis->get("article_num".$nowColumn['id'])) - 1);

        $this->assign("data",$data);
        return $this->fetch();
    }

    /**  保存
     * @param Request $request
     */
    public function save (Request $request)
    {
        if ($request->isPost()) {
            $data = $request->param();
            $nowId = $data["id"];

            //查看是否有当前作者
            $authorId = Db::table("author")->where("user_name",$data["author_id"])->value("id");
            if (empty($authorId)) $this->error("没有当前作者");
            $data["author_id"] = $authorId;
            $imagePath = $this->uploadImage();
            if (!empty($imagePath)) $data['imagePath'] = $imagePath;

            $model = new \app\admin\model\Article();
            $result = $model->save($data,['id' => $nowId]);
            if (!$result) $this->error("文章修改失败");

            Db::startTrans();
            $info["imagePath"] = $data['imagePath'];
            $info["content"] = substr($data["content"],3,-6);
            $info['description'] = empty($data['description']) ?  substr($data["content"],0,30) : $data['description'];
            $result = Db::table("article_info")->where("article_id",$nowId)->update($info);
            if ($result < 0) {
                Db::rollback();
                $this->error("文章详情修改失败");
            }
            Db::table("article_keyword_relation")->where("article_id",$nowId)->delete();
            $article = $model::get($nowId);

            $keywordArr = array_unique(explode(",",$data["keywords"]));
            $keywordIsExist = Db::table("keyword")->whereIn("keyword",$keywordArr)->select();
            $keywordId = array_column($keywordIsExist,"id");

            $article->keyword()->saveall($keywordId);

            $existsKeyword = array_column($keywordIsExist,"keyword");
            $notExistsKeywords = array_diff($keywordArr,$existsKeyword);
            $insertKeywordArr = [];

            foreach ($notExistsKeywords as $value) {
                $insertKeywordArr[]["keyword"] = $value;
            }
            if(!empty($insertKeywordArr)) $article->keyword()->saveAll($insertKeywordArr);
            $model->commit();

            $redis = new Redis();
            $redis->auth('chenhoulv');
            $nowColumn = Db::table('column')->where('id',$data['cid'])->find();
            $redis->set("article_num".$nowColumn['f_id'],($redis->get("article_num".$nowColumn['f_id'])) + 1);
            $redis->set("article_num".$nowColumn['id'],($redis->get("article_num".$nowColumn['id'])) + 1);
            $this->success("修改成功","admin/article/article");
        }
    }
    /**删除文章
     * @param Request $request
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function del (Request $request) {
        $nowId = $request->param("id");
        if (empty($nowId)) $this->error("缺少必要参数啊");
        $article = new \app\admin\model\Article();
        $result = $article::destroy($nowId);
        if ($result) {
            $redis = new Redis();
            $nowData = Db::table('article')->where('id',$nowId)->find();
            $nowColumn = Db::table('column')->where('id',$nowData['cid'])->find();
            $redis->set("article_num".$nowColumn['f_id'],($redis->get("article_num".$nowColumn['f_id'])) - 1);
            $redis->set("article_num".$nowColumn['id'],($redis->get("article_num".$nowColumn['id'])) - 1);
            $this->success("删除成功,可以去回收站还原");
        }else{
            $this->error("删除失败");
        }
    }

    public function isHot(Request $request) {
        $nowId = $request->param("id");
        $user = \app\common\model\Article::get($nowId);
        if ($user['is_hot'] == 0) {
            $user->is_hot = 1;
        }else {
            $user->is_hot = 0;
        }
        $user->save();
        $this->success("修改成功","admin/article/article");
    }

    /**文章管理页面 筛选栏目
     * @param Request $request
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function CColumn(Request $request)
    {
        $nowColumn = $request->param("cid");

        if ($nowColumn != 0 ) {
            $account = Session::get("account");
            $accountType =  $account['admin'];
            if ($accountType == 1) {
                $data = Db::table('article')
                    //起别名
                    ->alias('a')
                    ->where("cid",$nowColumn)
                    ->field("a.*,b.name,c.user_name")
                    ->join('column b','a.cid=b.id')
                    ->join('author c','a.author_id=c.id')
                    ->paginate(1,false,['query'=>\request()->param()]);
                $this->article($data);
            }else {
                $data = Db::table('article')
                    //起别名
                    ->alias('a')
                    ->where("cid",$nowColumn)
                    ->field("a.*,b.name,c.user_name")
                    ->join('column b','a.cid=b.id')
                    ->join('author c','a.author_id=c.id')
                    ->where("a.author_id",$account['id'])
                    ->paginate(1,false,['query'=>\request()->param()]);
                $this->article($data);
            }


        }else {
            $this->article();
        }
        $this->assign("nowColumn",$nowColumn);
        return $this->fetch("article");

    }

    /**设置文章排序方式；
     * @param Request $request
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function setSort(Request $request)
    {
        $sort = $request->param("order");
        $setSortArr = ["time-asc"=>"ASC","time-desc"=>"DESC"];
        if (array_key_exists($sort,$setSortArr)) {
            $sort = $setSortArr[$sort];
            $account = Session::get("account");
            $accountType =  $account['admin'];
            if ($accountType == 1) {
                $data = Db::table('article')
                    ->alias('a')
                    ->field("a.*,b.name,c.user_name")
                    ->join('column b','a.cid=b.id')
                    ->join('author c','a.author_id=c.id')
                    ->order("create_time",$sort)
                    ->paginate(2,false,['query'=>\request()->param()]);
            }else {
                $data = Db::table('article')
                    ->alias('a')
                    ->field("a.*,b.name,c.user_name")
                    ->join('column b','a.cid=b.id')
                    ->join('author c','a.author_id=c.id')
                    ->order("create_time",$sort)
                    ->where("a.author_id",$account['id'])
                    ->paginate(2,false,['query'=>\request()->param()]);
            }

        }
        $this->article($data);
        return $this->fetch("article");
    }

    /**关键字文章搜索
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getKeywordArt(Request $request)
    {
        $keyword = $request->param("search");
        if (empty($keyword)) {
            $this->error("请输入文章关键字啊");
        }
        $account = Session::get("account");
        $accountType =  $account['admin'];
        if ($accountType == 1) {
            $data = Db::table('article')
                ->alias('a')
                ->field("a.*,b.name,c.user_name")
                ->join('column b','a.cid=b.id')
                ->join('author c','a.author_id=c.id')
                ->whereLike("a.title","%".$keyword."%")
                ->paginate(2,false,['query'=>\request()->param()]);
            $this->article($data);
        }else {
            $data = Db::table('article')
                ->alias('a')
                ->field("a.*,b.name,c.user_name")
                ->join('column b','a.cid=b.id')
                ->join('author c','a.author_id=c.id')
                ->whereLike("a.title","%".$keyword."%")
                ->where("a.author_id",$account['id'])
                ->paginate(2,false,['query'=>\request()->param()]);
            $this->article($data);
        }

        return $this->fetch("article");
    }

    /**文章回收站
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function recycle()
    {
        $account = Session::get("account");
        if ($account['admin'] == 1) {
            $data = \app\admin\model\Article::onlyTrashed()
                ->alias('a')
                ->order("a.create_time")
                ->field("a.*,b.name,c.user_name")
                ->join('column b','a.cid=b.id')
                ->join('author c','a.author_id=c.id')
                ->paginate(2,false,['query'=>\request()->param()]);
            $this->assign("data",$data);
            return $this->fetch();
        }
        echo '<h1>看什么看,你又不是超级管理员</h1>';
    }

    /**文章还原；
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function restore(Request $request)
    {
        $nowId = $request->param("id");
        $user = \app\admin\model\Article::onlyTrashed()->find($nowId);
        $result = $user->restore();
        if ($result) {
            $redis = new Redis();
            $nowData = Db::table('article')->where('id',$nowId)->find();
            $nowColumn = Db::table('column')->where('id',$nowData['cid'])->find();
            $redis->set("article_num".$nowColumn['f_id'],($redis->get("article_num".$nowColumn['f_id'])) + 1);
            $redis->set("article_num".$nowColumn['id'],($redis->get("article_num".$nowColumn['id'])) + 1);
            $this->success("还原成功");
        }
    }
    public function demo ($id)
    {
        echo '这里的id是'.$id;
    }
}