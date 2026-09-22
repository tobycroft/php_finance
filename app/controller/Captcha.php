<?php
declare (strict_types = 1);

namespace app\controller;

use app\BaseController;
use app\service\CaptchaService;

/**
 * 登录滑动拼图验证码
 */
class Captcha extends BaseController
{
    /**
     * 生成滑动拼图验证码
     */
    public function slideCreate()
    {
        $result = CaptchaService::createSlide();
        if (empty($result)) {
            return json(['code' => 1, 'msg' => '验证码生成失败，请稍后再试', 'data' => null]);
        }

        // data: { ident, bg, block, y, bg_width, bg_height, block_size, pad_top, pad_left }
        return json(['code' => 0, 'msg' => 'ok', 'data' => $result]);
    }

    /**
     * 校验滑动结果，成功则签发一次性登录凭据 pass
     */
    public function slideCheck()
    {
        $ident = trim((string) $this->request->post('ident', ''));
        $x = (int) $this->request->post('x', 0);

        if ($ident === '') {
            return json(['code' => 1, 'msg' => '验证码标识缺失，请刷新重试', 'data' => null]);
        }

        $ret = CaptchaService::checkSlide($ident, $x);
        if (!$ret->isSuccess()) {
            return json(['code' => 1, 'msg' => (string) ($ret->getError() ?: '验证失败，请重试'), 'data' => null]);
        }

        return json(['code' => 0, 'msg' => '验证成功', 'data' => ['pass' => CaptchaService::issuePass()]]);
    }
}
