<?php
declare (strict_types = 1);

namespace app\service;

use Tobycroft\AossSdk\Captcha;
use Tobycroft\AossSdk\CaptchaRet;
use think\facade\Cache;
use think\facade\Db;

/**
 * AOSS 验证码服务（动态 GIF 验证码）
 *
 * 当前使用 SDK 的 Captcha（平台 /v1/captcha 接口，v1.24.5 默认远端 upload.tuuz.cc:433）。
 * AOSS 平台 v2 captcha 上线后，仅需在 sdk() 中切换为 v2 版 SDK 调用，对外方法签名保持不变。
 */
class CaptchaService
{
    // system_param 中存放 AOSS 项目 token 的 key
    public const PARAM_KEY_CAPTCHA_TOKEN = 'captcha_token';

    // 验证码有效期（秒）
    public const CODE_TTL = 300;

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
     * 生成动态 GIF 验证码，返回 ['ident' => string, 'gif' => string]，失败返回空数组
     */
    public static function createGif(): array
    {
        $ident = 'gif_' . bin2hex(random_bytes(16));
        $gif = self::sdk()->gif_number($ident);
        if ($gif === false || !str_starts_with((string) $gif, 'GIF')) {
            return [];
        }

        return ['ident' => $ident, 'gif' => $gif];
    }

    /**
     * 校验验证码（ident 与生成时一致，有效期 CODE_TTL 秒）
     */
    public static function checkCode(string $ident, string $code): CaptchaRet
    {
        return self::sdk()->check_in_time($ident, $code, self::CODE_TTL);
    }
}
