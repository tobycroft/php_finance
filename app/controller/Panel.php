<?php
declare (strict_types = 1);

namespace app\controller;

use app\BaseController;
use app\service\AuthService;

/**
 * 登录后主面板
 */
class Panel extends BaseController
{
    /**
     * 主面板（仪表盘）
     */
    public function index()
    {
        // CheckToken 中间件已保证登录态，此处直接获取用户
        $user = AuthService::userByToken($this->request);

        return view('panel/index', [
            'user'       => $user,
            'active'     => 'dashboard',
            'page_title' => '仪表盘',
        ]);
    }
}
