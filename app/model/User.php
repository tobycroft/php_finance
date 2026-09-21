<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * 用户表模型
 */
class User extends Model
{
    // 表名 fi_user（表前缀 fi_ 在 .env 中配置）
    protected $name = 'user';

    // 时间字段
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
}
