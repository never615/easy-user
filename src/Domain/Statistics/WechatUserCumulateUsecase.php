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
use Mallto\Admin\Data\Subject;
use Mallto\User\Data\WechatUserCumulate;


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
     * @var WechatStatistics
     */
    private $wechatStatistics;


    /**
     * WechatUserCumulateUsecase constructor.
     *
     * @param WechatStatistics $wechatStatistics
     */
    public function __construct(WechatStatistics $wechatStatistics)
    {
        $this->wechatStatistics = $wechatStatistics;
    }

    public function handle()
    {
        Subject::whereNotNull("uuid")
            ->chunk(10, function ($subjects) {
                foreach ($subjects as $subject) {
                    //计算开始时间
                    $lastStatistics = WechatUserCumulate::where('subject_id', $subject->id)
                        ->where("type", 'day')
                        ->orderBy('ref_date', 'desc')
                        ->first();
                    if ($lastStatistics) {
                        $from = Carbon::createFromFormat('Y-m-d', $lastStatistics->ref_date);
                    } else {
                        $from = Carbon::createFromFormat('Y-m-d', '2015-12-01');
                    }

//                    $from = Carbon::createFromFormat('Y-m-d', '2018-07-01');
                    $to = Carbon::now();
                    while ($to->gte($from)) {

                        $datas1 = $this->wechatStatistics->cumulate($subject->uuid,
                            $from->copy()->format('Y-m-d'),
                            $from->copy()->addDays(30)->format('Y-m-d')
                        );

                        $datas2 = $this->wechatStatistics->cumulate($subject->uuid,
                            $from->copy()->format('Y-m'),
                            $from->copy()->addDays(30)->format('Y-m'),
                            'month'
                        );

                        $datas3 = $this->wechatStatistics->cumulate($subject->uuid,
                            $from->copy()->format('Y'),
                            $from->copy()->addDays(30)->format('Y'),
                            'year'
                        );

                        $datas = array_merge($datas1, $datas2, $datas3);

                        $datas = array_map(function ($data) use ($subject) {
                            $data['subject_id'] = $subject->id;

                            return $data;
                        }, $datas);


                        WechatUserCumulate::insert($datas);

                        $from = $from->addDays(31);
                    }


                }
            });


    }


}
