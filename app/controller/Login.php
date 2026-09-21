<?php
declare (strict_types = 1);

namespace app\controller;

use app\BaseController;
use app\service\AuthService;

/**
 * 登录 / 登出
 */
class Login extends BaseController
{
    /**
     * 登录页
     */
    public function index()
    {
        // 已登录用户直接进入面板
        if (AuthService::userByToken($this->request)) {
            return redirect((string) url('/panel'));
        }

        return view('login/index');
    }

    /**
     * 提交登录，返回 JSON（token 由前端保存，后续请求在 Header 中携带）
     */
    public function doLogin()
    {
        $username = trim((string) $this->request->post('username', ''));
        $password = (string) $this->request->post('password', '');

        $tokenRow = AuthService::attemptLogin($username, $password, $this->request);
        if (!$tokenRow) {
            return json(['code' => 1, 'msg' => '用户名或密码错误', 'data' => null]);
        }

        return json([
            'code' => 0,
            'msg'  => '登录成功',
            'data' => [
                'token'    => $tokenRow->token,
                'username' => $username,
            ],
        ]);
    }

    /**
     * 退出登录
     */
    public function doLogout()
    {
        AuthService::logout($this->request);

        return json(['code' => 0, 'msg' => '已退出登录', 'data' => null]);
    }
}
