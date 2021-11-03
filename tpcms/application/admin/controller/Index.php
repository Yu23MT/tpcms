<?php
/**
 * Created by PhpStorm.
 * User: Lenovo
 * Date: 2020/10/15
 * Time: 14:42
 */

namespace app\admin\controller;


use think\facade\Session;


class Index extends Base
{
    /**后台管理页面主页渲染
     * @return mixed
     */
    public function index ()
    {

        $this->assign("status",Session::get("account")['user_name']);

        return $this->fetch();
    }

    /**主页渲染
     * @return mixed
     */
    public function cp_index ()
    {
        return $this->fetch();
    }

}