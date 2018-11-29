<?php
/**
 * Copyright (c) 2017. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

namespace Mallto\User\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Mallto\Admin\Data\Subject;
use Mallto\Admin\SubjectUtils;
use Mallto\Tool\Domain\Sms\Sms;
use Mallto\Tool\Exception\ResourceException;
use Mallto\Tool\Utils\TimeUtils;
use Mallto\User\Domain\SmsUsecase;

class SmsNotifyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 3600;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    private $mobile;
    private $subjectId;
    private $use;


    /**
     * UpdateWechatUserInfoJob constructor.
     *
     * @param $mobile
     * @param $subjectId
     * @param $use
     */
    public function __construct($mobile, $subjectId, $use)
    {
        $this->mobile = $mobile;
        $this->subjectId = $subjectId;
        $this->use = $use;
    }


    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $subject = Subject::find($this->subjectId);

        if ($subject) {
            $sign = SubjectUtils::getSubectConfig2("sms_sign", "墨兔", $subject);
            $sms = app(Sms::class);
            $code = mt_rand(1000, 9999);

            try {
                //todo 模板号写死,待优化
                $result = $sms->sendSms($this->mobile, $sign, "SMS_141255069", [
                    "code" => $code,
                ]);

                if ($result) {
                    $smsUsecase = app(SmsUsecase::class);

                    $key = $smsUsecase->getSmsCacheKey($this->use, $subject->id, $this->mobile);
                    $sendAtCacheKey = $smsUsecase->getSmsSendAtCacheKey($this->use, $subject->id, $this->mobile);

                    //记录验证码,用来处理验证码五分钟内有效
                    Cache::put($key, $code, 5);
                    //记录发送时间,用来处理一分钟之内只能请求一个验证码
                    Cache::put($sendAtCacheKey, TimeUtils::getNowTime(), 1);

                    //增加主体消费的短信数量
                    $subject->increment('sms_count');
                }

            } catch (ResourceException $exception) {
//                \Log::warning("短信验证码发送失败");
//                \Log::warning($exception);
            }
        } else {
            \Log::error("发送短信失败,主体未找到");
        }

    }


}
