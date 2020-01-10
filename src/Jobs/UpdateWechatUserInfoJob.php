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
use Illuminate\Support\Carbon;
use Mallto\Tool\Utils\AppUtils;
use Mallto\Tool\Utils\TimeUtils;
use Mallto\User\Data\User;
use Mallto\User\Data\UserProfile;

class UpdateWechatUserInfoJob implements ShouldQueue
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

    private $openid;

    private $userId;

    private $uuid;


    /**
     * UpdateWechatUserInfoJob constructor.
     *
     * @param $openid
     * @param $userId
     * @param $uuid
     */
    public function __construct($openid, $userId, $uuid)
    {
        $this->openid = $openid;
        $this->userId = $userId;
        $this->uuid = $uuid;
    }


    /**
     * Execute the job.
     *
     * @return void
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function handle()
    {
        $user = User::find($this->userId);

        if ($user) {
            //查询是否有一个小时内更新过
            $exist = UserProfile::where("user_id", $this->userId)
                ->where("updated_at", ">", Carbon::now()->addHour(-1)->toDateTimeString())
                ->whereNotNull('wechat_user')
                ->exists();

            if ( ! $exist) {
                $wechatUsecase = app(\Mallto\User\Domain\WechatUsecase::class);
                $wechatUserInfo = $wechatUsecase->getUserInfo($this->uuid,
                    AppUtils::decryptOpenid($this->openid));

                UserProfile::updateOrCreate([
                    'user_id' => $this->userId,
                ],
                    [
                        "wechat_user" => $wechatUserInfo->toArray(),
                        "updated_at"  => TimeUtils::getNowTime(),
                    ]
                );
            }
        }
    }
}
