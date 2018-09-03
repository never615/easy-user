<?php
/**
 * Copyright (c) 2017. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

namespace Mallto\User\Domain;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Mallto\Admin\SubjectUtils;
use Mallto\Tool\Domain\Sms\Sms;
use Mallto\Tool\Exception\ResourceException;
use Mallto\Tool\Exception\ValidationHttpException;

/**
 * Created by PhpStorm.
 * User: never615
 * Date: 13/07/2017
 * Time: 6:57 PM
 */
class SmsUsecase
{

    const USE_RESET = "reset";
    const USE_REGISET = "register";

    /**
     * 检查验证码
     *
     * @param        $verifyObj ,校验的对象,如:手机号,邮箱等
     * @param        $code
     * @param string $use
     * @param null   $subjectId
     * @return bool
     */
    public function checkVerifyCode($verifyObj, $code, $use = "register", $subjectId = null)
    {
        if (!$subjectId) {
            $subjectId = SubjectUtils::getSubjectId();
        }

        $key = $this->getSmsCacheKey($use, $subjectId, $verifyObj);

        $tempCode = Cache::get($key);

        if ($tempCode != $code) {
            if (!in_array(config("app.env"), ["production", "staging"]) && $code == "000000") {
                return true;
            } else {
                throw  new ResourceException("验证码错误");
            }
        }

        return true;
    }

    /**
     * 获取验证码的cache key
     *
     * @param $use
     * @param $subjectId
     * @param $mobile
     * @return string
     */
    private function getSmsCacheKey($use, $subjectId, $mobile)
    {
        return 'code'.$use.$subjectId.$mobile;
    }

    /**
     * 发送短信验证码
     *
     * @param        $mobile
     * @param        $subjectId
     * @param string $use
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendSms($mobile, $subjectId, $use = 'register')
    {
        $data['mobile'] = $mobile;
        $validator = Validator::make($data,
            ['mobile' => ['required', 'mobile'],]
        );

        if ($validator->fails()) {
            throw new ValidationHttpException($validator->errors()->first());
        }

        $key = $this->getSmsCacheKey($use, $subjectId, $mobile);

        $code = mt_rand(1000, 9999);


//        return $this->juheSend($code, $mobile, $key);
        return $this->aliSend($code, $mobile, $key);

    }

    /**
     * 使用聚合接口发送短信
     *
     * @param $code
     * @param $mobile
     * @param $key
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function juheSend($code, $mobile, $key)
    {
        $subject = SubjectUtils::getSubject();
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
            $this->aliSend($code, $mobile, $key);

            return true;

//            throw new ThirdPartException("聚合:".$res['reason']);
        } else {
            Cache::put($key, $code, 10);

            //增加主体消费的短信数量
            $subject->increment('sms_count');

            return true;
        }
    }


    /**
     * 使用阿里云接口发送短信
     *
     * @param $code
     * @param $mobile
     * @param $key
     * @return mixed
     */
    private function aliSend($code, $mobile, $key)
    {

        $sign = SubjectUtils::getSubectConfig2("sms_sign", "墨兔");

        $sms = app(Sms::class);
        $result = $sms->sendSms($mobile, $sign, "SMS_141255069", [
            "code" => $code,
        ]);
        if ($result) {
            Cache::put($key, $code, 5);

            $subject = SubjectUtils::getSubject();

            //增加主体消费的短信数量
            $subject->increment('sms_count');
        }

        return response()->nocontent();
    }

}
