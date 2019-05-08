<?php
/**
 * Copyright (c) 2018. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

/**
 * Created by PhpStorm.
 * User: never615 <never615.com>
 * Date: 02/11/2017
 * Time: 5:43 PM
 */

namespace Mallto\User\Domain\Statistics;

use Carbon\Carbon;
use GuzzleHttp\Exception\ClientException;
use Mallto\Admin\Data\Subject;
use Mallto\User\Data\WechatUserCumulate;
use Mallto\User\Domain\WechatUsecase;


/**
 * 从微信平台拉取微信用户统计数据,保存到业务平台数据库,以便于统计展示
 *
 * Class WechatUserCumulateUsecase
 *
 * @package Mallto\User\Domain\Statistics
 */
class WechatUserCumulateUsecase
{

    /**
     * @var WechatUsecase
     */
    private $wechatUsecase;


    /**
     * WechatUserCumulateUsecase constructor.
     *
     * @param WechatUsecase $wechatUsecase
     */
    public function __construct(WechatUsecase $wechatUsecase)
    {
        $this->wechatUsecase = $wechatUsecase;
    }

    public function handle()
    {
        if (!config('other.mallto_app_id')) {
            //如果没有配置请求开放平台所要使用的appId,则不进行后续请求
            return;
        }

        Subject::whereNotNull("uuid")
            ->chunk(10, function ($subjects) {
                foreach ($subjects as $subject) {
                    //计算开始时间
                    $lastStatistics = WechatUserCumulate::where('subject_id', $subject->id)
                        ->where("type", 'day')
                        ->orderBy('ref_date', 'desc')
                        ->first();
                    if ($lastStatistics) {
                        $from = Carbon::createFromFormat('Y-m-d', $lastStatistics->ref_date)->addDay();
                    } else {
                        $from = Carbon::createFromFormat('Y-m-d', '2015-12-01');
                    }

//                    $from = Carbon::createFromFormat('Y-m-d', '2018-07-01');
                    $to = Carbon::now()->addDay(-1);


                    while ($to->gte($from)) {
                        try {
                            $datas1 = $this->wechatUsecase->cumulate($subject->uuid,
                                $from->copy()->format('Y-m-d'),
                                $from->copy()->addDays(30)->format('Y-m-d')
                            );

                            if ($datas1 == false) {
                                break;
                            }

                        } catch (ClientException $clientException) {
                            break;
                        }


                        if ($datas1->count() == 0) {
                            $from = $from->addDays(31);
                            continue;
                        }


                        $datas2 = $this->wechatUsecase->cumulate($subject->uuid,
                            $from->copy()->format('Y-m'),
                            $from->copy()->addDays(30)->format('Y-m'),
                            'month'
                        );

                        $datas3 = $this->wechatUsecase->cumulate($subject->uuid,
                            $from->copy()->format('Y'),
                            $from->copy()->addDays(30)->format('Y'),
                            'year'
                        );

                        $datas = $datas1->merge($datas2)->merge($datas3);

                        $datas = $datas->transform(function ($data) use ($subject) {
                            $data['subject_id'] = $subject->id;

                            return $data;
                        });

                        WechatUserCumulate::insert($datas->toArray());

                        $from = $from->addDays(31);

                    }
                }
            });


    }


}
