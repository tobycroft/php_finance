<?php
declare (strict_types = 1);

namespace app\service;

use app\model\User;
use app\model\UserToken;
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
}
