<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * 用户状态 Token 表模型
 */
class UserToken extends Model
{
    // 表名 fi_token（表前缀 fi_ 在 .env 中配置）
    protected $name = 'token';

    // 时间字段
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
}
