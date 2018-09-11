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
use Mallto\Admin\Data\Subject;
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
    private $code;
    private $mobile;
    private $key;
    private $subjectId;


    /**
     * UpdateWechatUserInfoJob constructor.
     *
     * @param $code
     * @param $mobile
     * @param $key
     * @param $subjectId
     */
    public function __construct($code, $mobile, $key, $subjectId)
    {
        $this->code = $code;
        $this->mobile = $mobile;
        $this->key = $key;
        $this->subjectId = $subjectId;
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
            $smsUsecase = app(SmsUsecase::class);
            $smsUsecase->aliSend($this->code, $this->mobile, $this->key, $subject);
        } else {
            \Log::error("发送短信失败,主体未找到");
        }

    }
}
