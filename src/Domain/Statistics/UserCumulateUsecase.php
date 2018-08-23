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
use Mallto\User\Data\User;
use Mallto\User\Data\UserCumulate;


/**
 * Class UserCumulateUsecase
 *
 * @package Mallto\User\Domain\Statistics
 */
class UserCumulateUsecase
{

    /**
     * 计算每天/每月/每年的累计用户数和新增用户数
     */
    public function handle()
    {
        Subject::whereNotNull("uuid")
            ->chunk(10, function ($subjects) {
                foreach ($subjects as $subject) {
                    //计算开始时间
                    $lastStatistics = UserCumulate::where('subject_id', $subject->id)
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
                        //截止到$from 晚上24时的累计用户
                        $cumulate = User::where("created_at", "<",
                            $from->copy()->addDay()->startOfDay()->format('Y-m-d H:i:s'))
                            ->where("subject_id", $subject->id)
                            ->count();

                        //$from 一天新增用户
                        $newUser = User::where("created_at", ">=",
                            $from->startOfDay()->format('Y-m-d H:i:s'))
                            ->where("created_at", "<",
                                $from->copy()->addDay()->startOfDay()->format('Y-m-d H:i:s'))
                            ->where("subject_id", $subject->id)
                            ->count();

                        UserCumulate::updateOrCreate([
                            "subject_id" => $subject->id,
                            "ref_date"   => $from->format("Y-m-d"),
                            "type"       => 'day',
                        ], [
                            "cumulate_user" => $cumulate,
                            "new_user"      => $newUser,
                        ]);

                        //如果当前统计的时间是月度的最后一天,则创建一条月度统计数据
                        if ($from->isLastOfMonth()) {
                            //截止到$from 晚上24时的当月新增用户
                            $newUserMonth = User::where("created_at", ">=",
                                $from->copy()->startOfMonth()->format('Y-m-d H:i:s'))
                                ->where("created_at", "<",
                                    $from->copy()->addDay()->startOfDay()->format('Y-m-d H:i:s'))
                                ->where("subject_id", $subject->id)
                                ->count();

                            UserCumulate::updateOrCreate([
                                "subject_id" => $subject->id,
                                "ref_date"   => $from->format("Y-m"),
                                "type"       => 'month',
                            ], [
                                "cumulate_user" => $cumulate,
                                "new_user"      => $newUserMonth,
                            ]);
                        }

                        //如果当前统计的时间是年度的最后一天,则创建一条年度统计数据
                        if ($from->copy()->format("Y-m-d") == ($from->copy()->lastOfYear()->format("Y-m-d"))) {
                            //截止到$from 晚上24时的当月新增用户
                            $newUserYear = User::where("created_at", ">=",
                                $from->copy()->startOfYear()->format('Y-m-d H:i:s'))
                                ->where("created_at", "<",
                                    $from->copy()->addDay()->startOfDay()->format('Y-m-d H:i:s'))
                                ->where("subject_id", $subject->id)
                                ->count();

                            UserCumulate::updateOrCreate([
                                "subject_id" => $subject->id,
                                "ref_date"   => $from->format("Y-m"),
                                "type"       => 'year',
                            ], [
                                "cumulate_user" => $cumulate,
                                "new_user"      => $newUserYear,
                            ]);
                        }


                        $from = $from->addDay();
                    }
                }
            });


    }


}
