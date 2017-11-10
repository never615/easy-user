<?php
/**
 * Copyright (c) 2017. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

namespace Mallto\User\Domain;

use Encore\Admin\AppUtils;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Mallto\Tool\Exception\ThirdPartException;
use Mallto\Tool\Exception\ValidationHttpException;

/**
 * Created by PhpStorm.
 * User: never615
 * Date: 13/07/2017
 * Time: 6:57 PM
 */
class PublicUsecase
{
    /**
     * 发送短信验证码
     *
     * @param $mobile
     * @param $subjectId
     * @return mixed
     */
    public function sendSms($mobile, $subjectId)
    {
        $data['mobile'] = $mobile;
        $validator = Validator::make($data,
            ['mobile' => ['required', 'mobile'],]
        );

        if ($validator->fails()) {
            throw new ValidationHttpException($validator->errors()->first());
        }

        $code = mt_rand(1000, 9999);

        if (Cache::has('code'.$subjectId.$mobile)) {
            //如果验证码还没过期,用户再次请求则重复发送一次验证码
            $code = Cache::get('code'.$subjectId.$mobile);
            Cache::put('code'.$subjectId.$mobile, $code, 10);
//            throw new RateLimitExceededException();
        } else {
            Cache::put('code'.$subjectId.$mobile, $code, 10);
        }

        $subject = AppUtils::getSubject();
        $name = $subject->name;
        //模板id
        $tplValue = urlencode("#code#=$code&#app#=$name");


        $client = new Client();
        $response = $client->request('GET',
            "http://v.juhe.cn/sms/send", [
                "query" => [
                    "mobile"    => $mobile,
                    "tpl_id"    => "36548",
                    "tpl_value" => $tplValue,
                    "key"       => "c5f32ac02366e464f51a566bb9073af0",
                ],
            ]);

        $res = json_decode($response->getBody(), true);
        if ($res['error_code'] != 0) {
            throw new ThirdPartException($res['reason']);
        } else {
            //增加主体消费的短信数量
            $subject->increment('sms_count');
        }
    }
}