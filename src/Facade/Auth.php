<?php

declare (strict_types = 1);

namespace Aoxiang\WebmanSunctum\Facade;

/**
 * Class Auth
 *
 * @package Aoxiang\Aoxiang\Facade
 * @see     \Aoxiang\WebmanSunctum\Auth
 * @mixin \Aoxiang\WebmanSunctum\Auth
 * @method guard(string $name) static 设置用户角色
 * @method login($data, int $access_time = 0, int $refresh_time = 0) static 登入
 * @method logout() static 退出登入
 * @method attempt(array $data) static 字段检验登入
 * @method createToken() static 创建token
 * @method isLogin() static 判断登录
 * @method user() static 获取登录用户信息
 * @method userWithoutCache() static 获取登录用户信息
 */
class Auth
{
    protected static $_instance = null;


    public static function instance()
    {
        if( !static::$_instance ){
            static::$_instance = new \Aoxiang\WebmanSunctum\Auth();
        }

        return static::$_instance;
    }

    /**
     * @param $name
     * @param $arguments
     *
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        return static::instance()->{$name}(... $arguments);
    }
}
