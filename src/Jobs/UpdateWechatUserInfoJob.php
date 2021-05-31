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
use Illuminate\Support\Facades\Redis;
use Mallto\Tool\Utils\AppUtils;
use Mallto\Tool\Utils\TimeUtils;
use Mallto\User\Data\User;
use Mallto\User\Data\UserProfile;
use Mallto\User\Domain\WechatUsecase;

class UpdateWechatUserInfoJob implements ShouldQueue
{

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 2;


    ///**
    // * The number of seconds to wait before retrying the job.
    // *
    // * @var int
    // */
    //public $retryAfter = 60;

    private $openid;

    private $userId;

    private $subject;


    /**
     * UpdateWechatUserInfoJob constructor.
     *
     * @param $openid
     * @param $userId
     * @param $uuid
     */
    public function __construct($openid, $userId, $subject)
    {
        $this->openid = $openid;
        $this->userId = $userId;
        $this->subject = $subject;
    }

    ///**
    // * Determine the time at which the job should timeout.
    // *
    // * @return \DateTime
    // */
    //public function retryUntil()
    //{
    //    return now()->addSeconds(120);
    //}

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Illuminate\Auth\AuthenticationException
     * @throws \Illuminate\Contracts\Redis\LimiterTimeoutException
     */
    public function handle(WechatUsecase $wechatUsecase)
    {
        if (config('queue.default') === 'redis') {
            Redis::funnel('update_wechat_info_' . $this->userId)
                ->limit(1)
                ->then(function () use ($wechatUsecase) {
                    $this->updateInfo($wechatUsecase);
                }, function () {
                    // Could not obtain lock...

                });
        } else {
            $this->updateInfo($wechatUsecase);
        }
    }


    private function updateInfo($wechatUsecase)
    {
        $user = User::find($this->userId);

        if ($user) {
            //查询是否一天内更新过
            $exist = UserProfile::where("user_id", $this->userId)
                ->where("updated_at", ">", Carbon::now()->addDays(-1)->toDateTimeString())
                ->whereNotNull('wechat_user')
                ->exists();

            if ( ! $exist) {
                $wechatUserInfo = $wechatUsecase->getUserInfo(
                    $this->subject->wechat_uuid ?? $this->subject->uuid,
                    AppUtils::decryptOpenid($this->openid));

                try {
                    UserProfile::updateOrCreate([
                        'user_id' => $this->userId,
                    ],
                        [
                            "wechat_user" => $wechatUserInfo->toArray(),
                            "updated_at"  => TimeUtils::getNowTime(),
                        ]
                    );
                } catch (\PDOException $e) {
                    // Handle integrity violation SQLSTATE 23000 (or a subclass like 23505 in Postgres) for duplicate keys
                    if (0 === strpos($e->getCode(), '23505')) {
                        //已经存在了,忽略该异常
                    } else {
                        throw $e;
                    }
                }
            }
        }
    }
}
