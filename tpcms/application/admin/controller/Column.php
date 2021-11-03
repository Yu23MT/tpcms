<?php
/**
 * Created by PhpStorm.
 * User: Lenovo
 * Date: 2020/10/28
 * Time: 9:12
 */

namespace app\admin\controller;


use app\common\column\GetColumn;
use app\common\validate\AddColumnVal;
use think\Controller;
use think\Db;
use think\facade\Session;
use think\Request;

class Column extends Controller
{

    /**栏目管理渲染
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function cp_category()
    {
        $account = Session::get('account');
        if ($account['admin'] == 1) {
            $data = GetColumn::get();
            $this->assign("column",$data);
            return $this->fetch();
        }
        echo '<h1>看什么看,你又不是超级管理员</h1>';
    }

    /**增加栏目；
     * @param Request $request
     */
    public function addColumn(Request $request)
    {

        if ($request->isPost()) {
            $data = $request->param("add");
            if (empty($data)) $this->error("数据别为空啊",null,"",1);
            //验证
            $dataCount = count($data);
            $validate = new AddColumnVal();
            foreach ($data as $key=>$value) {
                if (!$validate->check($value)){
                    $this->error($validate->getError());
                }
            }
            //处理；
            foreach ($data as $key=>$value){
                $data[$key]["create_time"] = time();
                $data[$key]["update_time"] = time();
                $data[$key]["all_id"] = $value['f_id'] == 0 ? ",0," : ",0,".$value['f_id'].",";
            }
            Db::startTrans();

            $result = Db::table("column")->insertAll($data);
            if ($result != $dataCount) {
                Db::rollback();
                $this->error("数据增加失败,请检查数据是否输入成功");
            }
            Db::commit();
            $this->success("增加成功","admin/column/cp_category");
        }
    }

    /**栏目管理页面删除栏目；
     * @param Request $request
     */
    public function delColumn(Request $request)
    {
        $nowId = $request->param("id");
        if (empty($nowId)) $this->error("请给我参数啊");
        $ArticleIsExists = Db::table("article")->where("cid",$nowId)->select();
        if (!empty($ArticleIsExists)) {
            $this->error("栏目下还有文章,不可删除");
        }else{
            $childExists = Db::table("column")->where("f_id",$nowId)->select();
            if (empty($childExists)) {
                $result = Db::table("column")->where("id",$nowId)->delete();
                if ($result > 0) {
                    $this->success("删除成功","admin/column/cp_category");
                }
            }else {
                $this->error("删除失败,此栏目还有子栏目");
            }
        }

    }

    /**修改栏目渲染和遍历数据；
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function cp_category_edit(Request $request)
    {
        //栏目ID
        $nowId = $request->param("id");
        if (empty($nowId)) $this->error("缺少必要参数");
        $allIntColumn = Db::table("column")->where("f_id",0)->select();
        $this->assign("column",$allIntColumn);
        //获取栏目详细信息
        $data = Db::table("column")->where("id",$nowId)->find();

        $this->assign("data",$data);
        return $this->fetch();
    }

    /**保存栏目修改方法；
     * @param Request $request
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function saveColumn(Request $request)
    {
        if ($request->isPost()) {
            $data = $request->post();
            $nowId = $request->param("id");
            if (empty($nowId)) $this->error("缺少必要参数");
            $result = Db::table("column")->where("id",$nowId)->update($data);

            if ($result <= 0) $this->error("修改失败,请确认数据再提交");
            $this->success("修改成功","admin/column/cp_category");
        }
    }
}