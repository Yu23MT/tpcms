<?php

namespace app\http\middleware;

use think\Controller;
use think\facade\Session;

class Check extends Controller
{
    public function handle($request, \Closure $next)
    {

        if (Session::get("account") == null) {
            $this->error("您还没有登录",'admin/login/index');
        }
        return $next($request);
    }

}
