<?php
declare (strict_types = 1);

namespace app\middleware;

use app\service\AuthService;
use Closure;
use think\Request;
use think\Response;

/**
 * 登录态校验中间件：Header/Cookie 中携带有效 token 才能访问
 */
class CheckToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = AuthService::userByToken($request);
        if (!$user) {
            // AJAX / 非 GET 请求返回 JSON，页面请求跳转到登录页
            if ($request->isAjax() || !$request->isGet()) {
                return json(['code' => 401, 'msg' => '请先登录', 'data' => null]);
            }

            return redirect((string) url('/login'));
        }

        return $next($request);
    }
}
