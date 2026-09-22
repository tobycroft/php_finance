<?php
declare (strict_types = 1);

namespace app\service;

use app\model\User;
use app\model\UserToken;
use think\facade\Cache;
use think\Request;

/**
 * 登录鉴权服务
 *
 * Token 用于保持用户登录状态，客户端请求时在 Header 中携带 token 字段，
 * 页面跳转场景兜底支持同域名 Cookie（Cookie 本质上也是随 Header 发送的）。
 */
class AuthService
{
    // Header / Cookie 中的 token 字段名
    public const TOKEN_FIELD = 'token';

    // Web 端 Token 类型
    public const TOKEN_TYPE_WEB = 'web';

    // 登录失败限流：窗口期内同一用户名+IP 最大失败次数
    public const LOGIN_FAIL_LIMIT = 5;
    public const LOGIN_FAIL_WINDOW = 600;

    /**
     * @var array 当前请求周期内的 token => User 运行时缓存
     */
    protected static array $userCache = [];

    /**
     * 从请求中提取 token：优先 Header，其次 Cookie
     */
    public static function getToken(Request $request): string
    {
        $token = trim((string) $request->header(self::TOKEN_FIELD, ''));
        if ($token === '') {
            $token = trim((string) $request->cookie(self::TOKEN_FIELD, ''));
        }

        // Token 固定为 64 位 hex，格式不符直接拒绝，避免无效值打到数据库
        if (!preg_match('/^[0-9a-f]{64}$/', $token)) {
            return '';
        }

        return $token;
    }

    /**
     * 用户名密码登录校验，成功则签发 Token 并返回 Token 记录
     */
    public static function attemptLogin(string $username, string $password, Request $request): ?UserToken
    {
        if ($username === '' || $password === '') {
            return null;
        }

        // 用户名最长 100（字段为 varchar(100)），超长直接判定失败
        if (mb_strlen($username) > 100) {
            return null;
        }

        $user = User::where('username', $username)->find();
        if (!$user || (int) $user->status !== 1) {
            return null;
        }

        // 存量用户密码为 32 位 md5，兼容校验；后续如升级加密方式在此处扩展
        if (!hash_equals((string) $user->password, hash('md5', $password))) {
            return null;
        }

        return self::issueToken($user, $request);
    }

    /**
     * 为用户签发新 Token
     */
    public static function issueToken(User $user, Request $request): UserToken
    {
        $tokenRow = UserToken::create([
            'uid'         => (int) $user->id,
            'token'       => bin2hex(random_bytes(32)),
            'type'        => self::TOKEN_TYPE_WEB,
            'device_type' => mb_substr((string) $request->header('user-agent', 'unknown'), 0, 50),
            'ip'          => (string) $request->ip(),
        ]);

        return $tokenRow;
    }

    /**
     * 根据请求中的 token 获取当前登录用户，未登录返回 null
     */
    public static function userByToken(Request $request): ?User
    {
        $token = self::getToken($request);
        if ($token === '') {
            return null;
        }

        if (array_key_exists($token, self::$userCache)) {
            return self::$userCache[$token];
        }

        $tokenRow = UserToken::where('token', $token)->find();
        if (!$tokenRow) {
            return self::$userCache[$token] = null;
        }

        $user = User::find((int) $tokenRow->uid);
        if (!$user || (int) $user->status !== 1) {
            $user = null;
        }

        return self::$userCache[$token] = $user;
    }

    /**
     * 登出：删除当前请求携带的 Token 记录
     */
    public static function logout(Request $request): void
    {
        $token = self::getToken($request);
        if ($token !== '') {
            UserToken::where('token', $token)->delete();
            unset(self::$userCache[$token]);
        }
    }

    /* ------------------------------ 登录失败限流 ------------------------------ */

    public static function loginFailKey(string $username, string $ip): string
    {
        return 'login_fail:' . md5($username . '|' . $ip);
    }

    /**
     * 是否已达到登录失败次数上限
     */
    public static function isLoginBlocked(string $username, string $ip): bool
    {
        return (int) Cache::get(self::loginFailKey($username, $ip), 0) >= self::LOGIN_FAIL_LIMIT;
    }

    /**
     * 记录一次登录失败
     */
    public static function recordLoginFail(string $username, string $ip): void
    {
        $key = self::loginFailKey($username, $ip);
        Cache::set($key, (int) Cache::get($key, 0) + 1, self::LOGIN_FAIL_WINDOW);
    }

    /**
     * 登录成功后清除失败计数
     */
    public static function clearLoginFail(string $username, string $ip): void
    {
        Cache::delete(self::loginFailKey($username, $ip));
    }
}
