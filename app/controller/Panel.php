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

    /**
     * 收支记录（占位）
     */
    public function records()
    {
        return $this->placeholder('收支记录', 'records');
    }

    /**
     * 预算管理（占位）
     */
    public function budget()
    {
        return $this->placeholder('预算管理', 'budget');
    }

    /**
     * 报表统计（占位）
     */
    public function report()
    {
        return $this->placeholder('报表统计', 'report');
    }

    /**
     * 系统设置（占位）
     */
    public function settings()
    {
        return $this->placeholder('系统设置', 'settings');
    }

    /**
     * 渲染未开发模块的占位页（共用同一模板）
     */
    protected function placeholder(string $pageTitle, string $active)
    {
        return view('panel/placeholder', [
            'user'       => AuthService::userByToken($this->request),
            'active'     => $active,
            'page_title' => $pageTitle,
        ]);
    }
}
