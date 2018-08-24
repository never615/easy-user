<?php
/**
 * Copyright (c) 2017. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */


/**
 * Created by PhpStorm.
 * User: never615
 * Date: 14/07/2017
 * Time: 12:25 PM
 */

namespace Mallto\User\Domain\Statistics;


use GuzzleHttp\Exception\ClientException;
use Mallto\Tool\Domain\Net\AbstractAPI;
use Mallto\Tool\Exception\ThirdPartException;
use Mallto\Tool\Utils\SignUtils;

/**
 * 微信统计数据
 * Class WechatStatistics
 *
 * @package Mallto\User\Domain\Statistics
 */
class WechatStatistics extends AbstractAPI
{
    protected $slug = 'open_platform';


    public function cumulate($uuid, $from, $to, $type='day')
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

        $sign = SignUtils::sign($requestData, '81eaaa7cd5b8aafc51aa1e5392ae25f2');


        try {

            $content = $this->parseJSON('post', [
                $baseUrl.'/api/statistics/user/cumulate_data',
                array_merge($requestData, [
                    'sign' => $sign,
                ]),
                [
                    'headers' => [
                        'app-id'       => '1',
                        'REQUEST-TYPE' => 'SERVER',
                        'UUID'         => $uuid,
                        'Accept'       => 'application/json',
                    ],
                ],
            ]);
            return $content;
        } catch (ClientException $clientException) {
            \Log::error("请求微信统计数据失败");
            \Log::warning($clientException->getMessage());
            \Log::warning($clientException->getResponse()->getBody()->getContents());

            throw $clientException;
        }
    }


    /**
     * 不同的实现需要重写此方法 标准的json请求使用
     * Check the array data errors, and Throw exception when the contents contains error.
     *
     * @param array $contents
     *
     * @return array
     * @throws ThirdPartException
     */
    protected
    function checkAndThrow(
        array $contents
    ) {
        // TODO: Implement checkAndThrow() method.
    }
}
