<?php
/**
 * Created by PhpStorm.
 * User: Lenovo
 * Date: 2020/10/28
 * Time: 9:13
 */

namespace app\admin\controller;
use app\common\validate\LoginDataVal;
use app\common\validate\ValCode;
use think\captcha\Captcha;
use think\Controller;
use think\Db;
use think\Request;
use think\Session;

class Login extends Controller
{
    /**登录页面渲染
     * @param $request
     * @return mixed
     */
    public function index()
    {
        return $this->fetch();
    }
    /**自定义验证码；
     * @return \think\Response
     */
    public function verify()
    {
        $config =    [
            // 验证码字体大小
            'fontSize'    =>    30,
            // 验证码位数
            'length'      =>    3,
            // 关闭验证码杂点
            'useNoise'    =>    false,
            //关闭干扰线
            'useCurve'    =>   false
        ];
        $captcha = new Captcha($config);
        return $captcha->entry();
    }

    /**登录
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */

    public function userLogin(Request $request)
    {
        if ($request->isAjax()) {
            $data = $request->post();
            $validate = new LoginDataVal();
            $loginErrorCount = \think\facade\Cookie::get("loginErrorCount") ?? 0;
            if (!empty($data["captcha"])) {
                $validateCode =  new ValCode();
                if (!$validateCode->check($data)) {
                    return json(["code"=>2,"msg"=>$validateCode->getError()]);
                }
            }
            if (!$validate->check($data)) {
                \think\facade\Cookie::set("loginErrorCount",$loginErrorCount + 1);
                return json(["code"=>2,"msg"=>$validate->getError(),"error"=>$loginErrorCount >= 2 ? true : false]);
            }
            $name = $data["name"];
            $result = Db::table("author")->where("user_name",$name)->find();
            if (password_verify($data['password'],$result['password'])) {
                if ( $result["status"] == 1) {
                    \think\facade\Cookie::set("loginErrorCount","");
                    \think\facade\Session::set("account",$result);
                    return json(["code"=>1,"msg"=>'登录成功']);
                }else {
                    \think\facade\Cookie::set("loginErrorCount",$loginErrorCount + 1);
                    return json(["code"=>2,"msg"=>"点前账号被禁用","error"=>$loginErrorCount >= 2 ? true : false]);
                }
            }else{
                \think\facade\Cookie::set("loginErrorCount",$loginErrorCount + 1);
                return json(["code"=>2,"msg"=>'密码错误']);
            }
        }

    }
    /**
     * 账号退出
     */
    public function quit()
    {
        \think\facade\Session::pull("account");
        $this->success("退出成功","index/index/index");
    }
}