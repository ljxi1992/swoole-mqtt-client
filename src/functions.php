<?php
/**
 * Created by PhpStorm.
 * User: 梁杰熙
 * Date: 2021/1/14
 * Time: 19:03
 */
declare(strict_types=1);

//
if (! function_exists('getInstance')) {
    function getInstance($class)
    {
        return ($class)::getInstance();
    }
}
//获取配置
if (! function_exists('config')) {
    function config($name, $default = null)
    {
        return getInstance('\Mqtt\Config')->get($name, $default);
    }
}

