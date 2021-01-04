<?php
/**
 * Copyright (c) 2018. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

namespace Mallto\User\Domain;

use GuzzleHttp\Exception\ClientException;
use Mallto\Tool\Domain\Net\AbstractAPI;
use Mallto\Tool\Utils\SignUtils;

/**
 * Class MiniProgramUsecase
 *
 * @package Mallto\User\Domain
 */
class  MiniProgramUsecase extends AbstractAPI
{

    /**
     * @var string $slug
     */
    protected $slug = 'mini_program_oauth';


    /**
     * 小程序授权
     *
     * @param $code
     * @param $appId
     *
     * @return \Illuminate\Support\Collection
     * @throws \Exception
     */
    public function oauth($code, $appId)
    {
        $localEnv = config("app.env");

        if ($localEnv === 'production' || $localEnv === 'staging') {
            $baseUrl = "https://wechat.mall-to.com";
        } else {
            $baseUrl = "http://easy-wechat.test";
        }

        $requestData = [
            'code'   => $code,
            'app_id' => $appId,
        ];

        $sign = SignUtils::sign($requestData, config('other.mallto_app_secret'));

        try {
            $content = $this->parseJSON('get', [
                $baseUrl . '/wechat/miniprogram/oauth',
                array_merge($requestData, [
                    'sign' => $sign,
                ]),
                [
                    'headers' => [
                        'app-id'       => config('other.mallto_app_id'),
                        'REQUEST-TYPE' => 'SERVER',
                        'UUID'         => '1008',
                        'Accept'       => 'application/json',
                    ],
                ],
            ]);

            return $content;
        } catch (ClientException $clientException) {
            //这种方法能拿到异常的内容
            //$clientException->getResponse()->getBody()->getContents();
            \Log::warning("请求微信授权失败");
            \Log::warning($clientException->getResponse()->getBody()->getContents());
            \Log::warning($clientException);
            throw $clientException;
        } catch (\Exception $exception) {
            \Log::error("请求微信授权失败");
            \Log::error($exception);
            throw $exception;
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
    protected function checkAndThrow(array $contents)
    {

    }
}
