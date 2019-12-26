<?php
/**
 * Copyright (c) 2018. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

namespace Mallto\User\Domain;

use GuzzleHttp\Exception\ClientException;
use Mallto\Tool\Utils\SignUtils;

/**
 * Created by PhpStorm.
 * User: never615 <never615.com>
 * Date: 2018/8/23
 * Time: 下午7:25
 */
class  WechatUsecase extends \Mallto\Tool\Domain\Net\AbstractAPI
{

    protected $slug = 'open_platform';


    public function getUserInfo($uuid, $openid)
    {
        if (config("app.env") == 'production' || config("app.env") == 'staging') {
            $baseUrl = "https://wechat.mall-to.com";
        } else {
            $baseUrl = "https://test-wechat.mall-to.com";
        }

        $requestData = [
            'uuid'   => $uuid,
            'openid' => $openid,
        ];

        $sign = SignUtils::sign($requestData, config('other.mallto_app_secret'));

        try {
            $content = $this->parseJSON('get', [
                $baseUrl . '/api/wechat/user',
                array_merge($requestData, [
                    'sign' => $sign,
                ]),
                [
                    'headers' => [
                        'app-id'       => config("other.mallto_app_id"),
                        'REQUEST-TYPE' => 'SERVER',
                        'UUID'         => $uuid,
                        'Accept'       => 'application/json',
                    ],
                ],
            ]);

            return $content;
        } catch (ClientException $clientException) {
            \Log::warning("请求微信用户信息失败");
            \Log::warning($clientException);

            $response = $clientException->getResponse()->getBody()->getContents();
            $content = json_decode($response, true);
            \Log::warning($content);

            throw new \Mallto\Tool\Exception\HttpException(422, "获取微信信息失败,请在微信内重新打开");
        }

    }


    public function cumulate($uuid, $from, $to, $type = 'day')
    {

        if (config("app.env") == 'production' || config("app.env") == 'staging') {
            $baseUrl = "https://wechat.mall-to.com";
        } else {
            $baseUrl = "https://test-wechat.mall-to.com";
        }

        $requestData = [
            'from' => $from,
            'to'   => $to,
            'type' => $type,
        ];

        $sign = SignUtils::sign($requestData, config('other.mallto_app_secret'));

        try {

            $content = $this->parseJSON('post', [
                $baseUrl . '/api/statistics/user/cumulate_data',
                array_merge($requestData, [
                    'sign' => $sign,
                ]),
                [
                    'headers' => [
                        'app-id'       => config('other.mallto_app_id'),
                        'REQUEST-TYPE' => 'SERVER',
                        'UUID'         => $uuid,
                        'Accept'       => 'application/json',
                    ],
                ],
            ]);

            return $content;
        } catch (ClientException $clientException) {
            $code = $clientException->getCode();
            if ($code == '422') {
                return false;
            } else {

                \Log::warning("请求微信统计数据失败");
                \Log::warning($clientException->getMessage());
                \Log::warning($clientException->getResponse()->getBody()->getContents());

                throw $clientException;
            }
        }
    }


    /**
     * 不同的实现需要重写此方法 标准的json请求使用
     * Check the array data errors, and Throw exception when the contents contains error.
     *
     * @param array $contents
     *
     * @return array
     * @throws \Mallto\Tool\Exception\ThirdPartException
     */
    protected function checkAndThrow(
        array $contents
    ) {
        // TODO: Implement checkAndThrow() method.
    }
}
