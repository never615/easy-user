<?php
/*
 * Copyright (c) 2022. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

namespace Mallto\User\Domain;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * User: never615 <never615.com>
 * Date: 2022/10/31
 * Time: 3:38 PM
 */
class OpenidUtils
{

    /**
     * 校验openid
     * 并且把request中的openid参数替换成不包含时间戳的参数
     *
     * @param Request $request
     * @param string  $openidKeyName
     * @param bool    $checkTimes
     *
     * @return mixed
     * @throws AuthenticationException
     */
    public static function checkAndParseOpenid(
        Request $request,
        $openidKeyName = 'identifier',
        $checkTimes = true
    ) {

        $orginalOpenid = $request->$openidKeyName;

        $openid = self::decryptOpenidAndCheck($orginalOpenid, $checkTimes);

        $inputs = $request->all();

        $inputs[$openidKeyName] = encrypt($openid);

        $request->replace($inputs);

        return $request;
    }


    /**
     * 校验openid
     * 并返回解密后的 openid
     *
     * @param string $orginalOpenid
     * @param bool   $checkTimes
     *
     * @return mixed
     * @throws AuthenticationException
     */
    public static function decryptOpenidAndCheck(
        $orginalOpenid,
        $checkTimes = true
    ) {
        //openid可以使用的次数
        $limitTimes = 10;
        //openid可以使用时间限制/s
        $limitTime = 60 * 60 * 4;

        if (in_array(config("app.env"), [ "test", "integration" ])) {
            $limitTimes = 10000;
            $limitTime = 60 * 60 * 24 * 3;
        }

        $openids = self::getOpenidFromOriginalOpenids($orginalOpenid);

        //检查是否被使用过
        if (count($openids) > 1) {
            $timestamp = $openids[1];
            $openid = $openids[0];

            if (empty($openid)) {
                throw new AuthenticationException("openid为空");
            }

            //检查时效性
            $second = time() - $timestamp;
            if ($second >= $limitTime) {
                throw new AuthenticationException("openid过期");
            }

            if ($checkTimes) {
                if (Cache::has($orginalOpenid)) {
                    $times = Cache::get($orginalOpenid);
                    if ($times >= $limitTimes) {
                        throw new AuthenticationException("openid已被使用");
                    } else {
                        Cache::put($orginalOpenid, $times + 1, $limitTime);
                    }
                } else {
                    Cache::put($orginalOpenid, 1, $limitTime);
                }
            }
        } else {
            throw new AuthenticationException("无效的 openid");
        }

        return $openid;
    }


    /**
     * 从原始数据中获取openid数据和时间戳
     *
     *
     * @param $orginalOpenid
     *
     * @return array
     * @throws AuthenticationException
     */
    public static function getOpenidFromOriginalOpenids($orginalOpenid)
    {
        if (empty($orginalOpenid)) {
            throw new AuthenticationException('openid为空,请检查微信授权或刷新重试');
        }

        try {
            $openid = decrypt($orginalOpenid);
        } catch (DecryptException $decryptException) {
            //解析失败尝试url解码在进行解析
            $openid = urldecode($orginalOpenid);
            try {
                $openid = decrypt($openid);
            } catch (DecryptException $decryptException) {
                \Log::error("解析openid失败3");
                \Log::warning($openid);
                throw new AuthenticationException("授权失败,openid解析失败");
            }
        }

        return explode("|||", $openid);
    }


    /**
     * @param string $openid 包含时间戳
     *
     * @return mixed|string
     * @throws AuthenticationException
     */
    public static function decryptOpenid($openid)
    {
        //if (empty($openid)) {
        //    throw new AuthenticationException('openid为空,请检查微信授权或刷新重试');
        //}
        //
        //try {
        //    $openid = decrypt($openid);
        //} catch (DecryptException $decryptException) {
        //    //解析失败尝试url解码在进行解析
        //    $openid = urldecode($openid);
        //    try {
        //        $openid = decrypt($openid);
        //    } catch (DecryptException $decryptException) {
        //        \Log::error("解析openid失败3");
        //        \Log::warning($openid);
        //        throw new AuthenticationException("授权失败,openid解析失败");
        //    }
        //}
        //
        //$openids = explode("|||", $openid);

        $openids = self::getOpenidFromOriginalOpenids($openid);

        if (count($openids) > 1) {
            $openid = $openids[0];
        }

        return $openid;
    }


    /**
     * 解析加密的 openid,带解析的 openid 不含时间戳
     *
     * @param string $openid 不包含时间戳
     *
     * @return string
     * @throws AuthenticationException
     */
    public static function decryptOpenidWithOutTimestamp($openid)
    {
        if (empty($openid)) {
            throw new AuthenticationException('openid为空,请检查微信授权或刷新重试');
        }

        try {
            $openid = decrypt($openid);
        } catch (DecryptException $decryptException) {
            //解析失败尝试url解码在进行解析
            $openid = urldecode($openid);
            try {
                $openid = decrypt($openid);
            } catch (DecryptException $decryptException) {
                \Log::error("解析openid失败 decryptOpenidWithOutTimestamp");
                \Log::warning($openid);
                throw new AuthenticationException("授权失败,openid解析失败");
            }
        }

        return $openid;
    }

}
