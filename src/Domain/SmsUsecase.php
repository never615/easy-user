<?php
/**
 * Copyright (c) 2017. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

namespace Mallto\User\Domain;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Mallto\Admin\Data\Subject;
use Mallto\Admin\SubjectUtils;
use Mallto\Mall\SubjectConfigConstants;
use Mallto\Tool\Domain\Sms\Sms;
use Mallto\Tool\Domain\Sms\SmsCodeUsecase;
use Mallto\Tool\Exception\ResourceException;
use Mallto\Tool\Exception\ValidationHttpException;
use Mallto\Tool\Utils\TimeUtils;

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
     * @var SmsCodeUsecase
     */
    private $smsCodeUsecase;
    /**
     * @var Sms
     */
    private $sms;

    /**
     * SmsUsecase constructor.
     *
     * @param SmsCodeUsecase $smsCodeUsecase
     * @param Sms            $sms
     */
    public function __construct(SmsCodeUsecase $smsCodeUsecase, Sms $sms)
    {
        $this->smsCodeUsecase = $smsCodeUsecase;
        $this->sms = $sms;
    }


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
     * 发送短信验证码
     *
     * 使用队列任务发送的方式的话:
     * dispatch(new SmsNotifyJob($mobile, SubjectUtils::getSubjectId(), $use))->onQueue("high");
     *
     * @param        $mobile
     * @param        $subjectId
     * @param string $use
     * @return mixed
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

        $subject = Subject::findOrFail($subjectId);

        $sign = SubjectUtils::getConfigByOwner(SubjectConfigConstants::OWNER_CONFIG_SMS_SIGN, $subject, "墨兔");
        $code = mt_rand(1000, 9999);

        //检查一分钟内只能发送一个
        if (Cache::has($this->getSmsSendAtCacheKey($use, $subjectId, $mobile))) {
            throw new ResourceException("一分钟以内只能发送一次");
        }

        $result = $this->sms->sendSms($mobile, $sign,
            SubjectUtils::getConfigByOwner(SubjectConfigConstants::OWNER_CONFIG_SMS_TEMPLATE_CODE, $subject,
                "SMS_141255069"), [
                "code" => $code,
            ]);

        if ($result) {
            $smsUsecase = app(SmsUsecase::class);

            $key = $smsUsecase->getSmsCacheKey($use, $subject->id, $mobile);
            $sendAtCacheKey = $smsUsecase->getSmsSendAtCacheKey($use, $subject->id, $mobile);

            //记录验证码,用来处理验证码五分钟内有效
            Cache::put($key, $code, 5 * 60);
            //记录发送时间,用来处理一分钟之内只能请求一个验证码
            Cache::put($sendAtCacheKey, TimeUtils::getNowTime(), 1 * 60);

            //添加短信发送记录
            try {
                $this->smsCodeUsecase->create($mobile, $code, $subjectId);
            } catch (\Exception $e) {
                \Log::error($e);
            }


            //增加主体消费的短信数量
            $subject->increment('sms_count');
        }
    }


    /**
     * 获取验证码的cache key
     *
     * @param $use
     * @param $subjectId
     * @param $mobile
     * @return string
     */
    public function getSmsCacheKey($use, $subjectId, $mobile)
    {
        return 'code'.$use.$subjectId.$mobile;
    }

    /**
     * 获取验证码发送时间 key
     *
     * @param $use
     * @param $subjectId
     * @param $mobile
     * @return string
     */
    public function getSmsSendAtCacheKey($use, $subjectId, $mobile)
    {
        return 'code_send_at'.$use.$subjectId.$mobile;
    }


}
