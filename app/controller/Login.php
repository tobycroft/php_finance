<?php
declare (strict_types = 1);

namespace app\controller;

use app\BaseController;
use app\service\AuthService;
use app\service\CaptchaService;

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

        // GIF 验证码校验（code=2 提示前端刷新验证码）
        $captchaIdent = trim((string) $this->request->post('captcha_ident', ''));
        $captchaCode = trim((string) $this->request->post('captcha_code', ''));
        $captchaRet = CaptchaService::checkCode($captchaIdent, $captchaCode);
        if (!$captchaRet->isSuccess()) {
            return json(['code' => 2, 'msg' => '验证码错误或已过期', 'data' => null]);
        }

        // 登录失败限流：同一用户名+IP 10 分钟内最多 5 次
        $ip = (string) $this->request->ip();
        if (AuthService::isLoginBlocked($username, $ip)) {
            return json(['code' => 1, 'msg' => '登录失败次数过多，请 10 分钟后再试', 'data' => null]);
        }

        $tokenRow = AuthService::attemptLogin($username, $password, $this->request);
        if (!$tokenRow) {
            AuthService::recordLoginFail($username, $ip);
            return json(['code' => 1, 'msg' => '用户名或密码错误', 'data' => null]);
        }

        AuthService::clearLoginFail($username, $ip);

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
