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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Mallto\Tool\Exception\ResourceException;

trait OpenidCheckTrait
{

    /**
     * 校验openid
     * 并且把request中的openid参数替换成不包含时间戳的参数
     *
     * @param        $request
     * @param string $openidKeyName
     * @return mixed
     * @throws AuthenticationException
     */
    public function checkOpenid(Request $request, $openidKeyName = 'identifier')
    {
        $orginalOpenid = $request->$openidKeyName;
//        \Log::info($orginalOpenid);
        //检查是否被使用过
        if (Cache::has($orginalOpenid)) {
            $times = Cache::get($orginalOpenid);
            if ($times >= 2) {
                throw new AuthenticationException("openid已被使用");
            } else {
                Cache::put($orginalOpenid, 2, 5);
            }
        } else {
            Cache::put($orginalOpenid, 1, 5);
        }

        $openid = decrypt($orginalOpenid);
//        \Log::info($openid);

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
}
