<?php
/**
 * Copyright (c) 2017. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

/**
 * Created by PhpStorm.
 * User: never615 <never615.com>
 * Date: 02/11/2017
 * Time: 2:56 PM
 */

namespace Mallto\User\Domain\Traits;


use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Mallto\Tool\Exception\ResourceException;

trait OpenidCheckTrait
{

    /**
     * 校验openid
     * 并且把request中的openid参数替换成不包含时间戳的参数
     *
     * @param Request $request
     * @param string  $openidKeyName
     * @param bool    $checkTimes
     * @return mixed
     * @throws AuthenticationException
     */
    public function checkOpenid(Request $request, $openidKeyName = 'identifier', $checkTimes = true)
    {
        $orginalOpenid = $request->$openidKeyName;
//        \Log::info($orginalOpenid);
        //检查是否被使用过

        if ($checkTimes) {
            if (Cache::has($orginalOpenid)) {
                $times = Cache::get($orginalOpenid);
                if ($times >= 2) {
                    throw new AuthenticationException("openid已被使用");
                } else {
                    Cache::put($orginalOpenid, $times + 1, 5);
                }
            } else {
                Cache::put($orginalOpenid, 1, 5);
            }
        }

        try {
            $openid = decrypt($orginalOpenid);
        } catch (DecryptException $decryptException) {
            \Log::error("解析openid失败");
            \Log::warning($orginalOpenid);
            throw new AuthenticationException("授权失败,openid解析失败");
        }

        $openids = explode("|||", $openid);
        if (count($openids) > 1) {
            $timestamp = $openids[1];
            $openid = $openids[0];

            if (empty($openid)) {
                throw new ResourceException("openid为空");
            }

            //检查时效性
            $minutes = (time() - $timestamp) / 60;
            if ($minutes >= 5) {
                throw new AuthenticationException("openid过期");
            }

            $inputs = $request->all();

            $inputs[$openidKeyName] = encrypt($openid);


            $request->replace($inputs);
        }


        return $request;
    }


    /**
     * 解析openid
     *
     * @param $openid
     * @return string
     */
    public function parseOpenid($openid)
    {
        $openid = decrypt($openid);
        $openids = explode("|||", $openid);
        if (count($openids) > 1) {
            $openid = $openids[0];
        }

        return encrypt($openid);
    }
}
