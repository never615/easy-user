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
use Mallto\User\Domain\SmsVerifyCodeUsecase;
use Illuminate\Support\Facades\Log;

class SmsNotifyJob implements ShouldQueue
{

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


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
        $smsUseCase = app(SmsVerifyCodeUsecase::class);

        try {
            $smsUseCase->sendSmsVerifyCode($this->mobile, $this->subjectId, $this->use);
        } catch (\Exception $exception) {
            Log::warning("短信验证码发送失败");
            Log::warning($exception->getMessage());
        }
    }

}
