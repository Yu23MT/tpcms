<?php
/**
 * Created by PhpStorm.
 * User: Lenovo
 * Date: 2020/10/29
 * Time: 9:55
 */

namespace app\admin\controller;


use think\Controller;
use think\facade\Session;

class Base extends Controller
{
    protected function initialize()
    {
       // $this->validateLogin();
    }
    protected function validateLogin ()
    {
/*
        if (Session::get("account") == null) {
            $this->error("您还未登录,请先登录");
        }*/
    }
}