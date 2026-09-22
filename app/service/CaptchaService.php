<?php
declare (strict_types = 1);

namespace app\service;

use Tobycroft\AossSdk\Captcha;
use Tobycroft\AossSdk\CaptchaRet;
use think\facade\Cache;
use think\facade\Db;

/**
 * AOSS 验证码服务（滑动拼图）
 *
 * 当前使用 SDK 的 Captcha（平台 /v1/captcha 接口，v1.24.5 默认远端 upload.tuuz.cc:433）。
 * AOSS 平台 v2 captcha 上线后，仅需在 sdk() 中切换为 v2 版 SDK 调用，对外方法签名保持不变。
 */
class CaptchaService
{
    // system_param 中存放 AOSS 项目 token 的 key
    public const PARAM_KEY_CAPTCHA_TOKEN = 'captcha_token';

    // 验证通过凭据有效期（秒）
    public const PASS_TTL = 300;

    /**
     * 构建 AOSS 验证码 SDK 实例（v2 切换点）
     */
    public static function sdk(): Captcha
    {
        return new Captcha(self::captchaToken());
    }

    /**
     * 从 system_param 读取 AOSS 项目 token（带缓存）
     */
    public static function captchaToken(): string
    {
        return (string) Cache::remember('aoss_captcha_token', function () {
            return (string) Db::table('system_param')
                ->where('key', self::PARAM_KEY_CAPTCHA_TOKEN)
                ->value('value');
        }, 300);
    }

    /**
     * 生成滑动拼图验证码，返回 ['ident' => string, 'data' => array]，失败返回空数组
     */
    public static function createSlide(): array
    {
        $ident = 'slide_' . bin2hex(random_bytes(16));
        $data = self::sdk()->slide($ident);
        if ($data === false) {
            return [];
        }

        return ['ident' => $ident, 'data' => $data];
    }

    /**
     * 校验滑动拼图验证码
     */
    public static function checkSlide(string $ident, int $x): CaptchaRet
    {
        return self::sdk()->slide_check($ident, $x);
    }

    /**
     * 验证通过后签发一次性登录凭据（存入缓存，用时销毁）
     */
    public static function issuePass(): string
    {
        $pass = bin2hex(random_bytes(16));
        Cache::set('captcha_pass:' . $pass, 1, self::PASS_TTL);

        return $pass;
    }

    /**
     * 校验并消耗一次性登录凭据
     */
    public static function consumePass(string $pass): bool
    {
        $pass = trim($pass);
        if ($pass === '') {
            return false;
        }

        $key = 'captcha_pass:' . $pass;
        if (!Cache::get($key)) {
            return false;
        }
        Cache::delete($key);

        return true;
    }
}
