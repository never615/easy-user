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
        $openid = $request->$openidKeyName;
        $openid = decrypt($openid);
        $openids = explode("|||", $openid);
        $timestamp = $openids[1];
        $openid = $openids[0];

        if (empty($openid)) {
            throw new ResourceException("openid为空");
        }

        //1.检查时效性
        $minutes = (time() - $timestamp) / 60;
        if ($minutes >= 5) {
            throw new AuthenticationException("openid过期");
        }

        //2.检查是否被使用过
        if (Cache::has($openid)) {
            throw new AuthenticationException("openid已被使用");
        } else {
            Cache::put($openid, $openid, 5);
        }


        $input=[
            $openidKeyName => encrypt($openid),
        ];
        $request->replace($input);

        return $request;
    }
}
