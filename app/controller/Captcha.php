<?php
declare (strict_types = 1);

namespace app\controller;

use app\BaseController;
use app\service\CaptchaService;

/**
 * 登录动态 GIF 验证码
 */
class Captcha extends BaseController
{
    /**
     * 输出 GIF 验证码图片，ident 通过响应头 X-Captcha-Ident 返回
     */
    public function gif()
    {
        $result = CaptchaService::createGif();
        if (empty($result)) {
            return json(['code' => 1, 'msg' => '验证码生成失败，请稍后再试', 'data' => null]);
        }

        return response($result['gif'], 200, [
            'Content-Type'   => 'image/gif',
            'Cache-Control'  => 'no-store, no-cache, must-revalidate',
            'X-Captcha-Ident' => $result['ident'],
        ]);
    }
}
