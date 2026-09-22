<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
use app\middleware\CheckToken;
use think\facade\Route;

// 根路径进入主面板（未登录由中间件跳转登录页）
Route::get('/', 'Panel/index')->middleware(CheckToken::class);

// 登录 / 登出
Route::get('login', 'Login/index');
Route::post('login', 'Login/doLogin');
Route::post('logout', 'Login/doLogout');

// 登录 GIF 验证码
Route::get('captcha/gif', 'Captcha/gif');

// 登录后主面板（需登录态）
Route::group('panel', function () {
    Route::get('/', 'Panel/index');
    Route::get('records', 'Panel/records');
    Route::get('budget', 'Panel/budget');
    Route::get('report', 'Panel/report');
    Route::get('settings', 'Panel/settings');
})->middleware(CheckToken::class);
