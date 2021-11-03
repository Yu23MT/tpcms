<?php
/**
 * Created by PhpStorm.
 * User: Lenovo
 * Date: 2020/10/29
 * Time: 20:52
 */

namespace app\admin\controller;


use app\common\model\Author;
use app\common\validate\AddUserVal;
use think\Controller;
use think\Db;
use think\facade\Session;
use think\Request;

class User extends Controller
{
    protected $middleware = ['Check'];
    public function index ()
    {
        $user = Session::get('account');
        $userType = $user['admin'];
        if ($user['admin'] == 1) {
            $users = Db::table("author")->select();
        }else {
            $users = Db::table("author")->where('id',$user['id'])->select();
        }
        $this->assign("users",$users);
        $this->assign('userType',$userType);
        return $this->fetch();
    }
    public function add_user()
    {
        return $this->fetch();
    }
    public function addUser(Request $request)
    {
        $data = $request->param();
        //验证；
        $validate = new AddUserVal();
        if (!$validate->check($data)) {
            echo json_encode(['code'=>0,'msg'=>$validate->getError()]);
        }
        $user = new Author();
        $user->save($data);
        echo json_encode(['code'=>1,'msg'=>"增加成功"]);
    }
    public function del_user(Request $request)
    {
        $delId = $request->param('id');
        $result = Db::table('author')->where('id',$delId)->delete();
        if ($result <= 0) $this->error("删除失败");
        $this->success('删除成功','admin/user/index');
    }
    public function exit_user(Request $request)
    {
        $exitId = $request->param('id');
        $data = Db::table('author')->where('id',$exitId)->find();
        if (empty($data)) $this->error('没有当前数据');
        $this->assign('data',$data);
        return $this->fetch();
    }
    public function update_user(Request $request)
    {
        $nowId = $request->param('id');
        $data = $_POST;
        $data['update_time'] = time();
        $result = Db::table('author')->where('id',$nowId)->update($data);
        if ($result <= 0) {
            echo json_encode(['code'=>2,'msg'=>'修改失败']);
        }else {
            echo json_encode(['code'=>1,'msg'=>'修改成功']);
        }
    }
}