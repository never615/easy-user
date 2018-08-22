<?php
/**
 * Copyright (c) 2018. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

namespace Mallto\User\Controller\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Mallto\Admin\SubjectUtils;
use Mallto\Tool\Exception\ResourceException;
use Mallto\User\Data\UserCumulate;

/**
 * Created by PhpStorm.
 * User: never615 <never615.com>
 * Date: 2018/8/22
 * Time: 下午12:03
 */
class UserStatisticsController extends Controller
{

    /**
     * 用户累计数据
     *
     * @param Request $request
     * @return array
     */
    public function cumulateUser(Request $request)
    {
        $started = $request->users_cumulate_started_at;
        $ended = $request->users_cumulate_ended_at;
        $dateType = $request->users_cumulate_date_type;


        $subjectId = $this->getSubjectId($request);

        $results = [];

        //检查日期范围
        switch ($dateType) {
            case 'day':
                $startedCarbon = Carbon::createFromFormat("Y-m-d", $started);
                $endedCarbon = Carbon::createFromFormat("Y-m-d", $ended);

                if ($startedCarbon->copy()->addDay(31)->toDateString() < $endedCarbon->toDateString()) {
                    throw new ResourceException("按天查询,间隔不能超过31天");
                }

                $results = UserCumulate::where("type", $dateType)
                    ->where("ref_date", ">=", $started)
                    ->where("ref_date", "<=", $ended)
                    ->where("subject_id", $subjectId)
                    ->select("ref_date", "cumulate_user as 有效用户")
                    ->get();


                break;
            case 'month':
                $startedCarbon = Carbon::createFromFormat("Y-m", $started);
                $endedCarbon = Carbon::createFromFormat("Y-m", $ended);
                if ($startedCarbon->copy()->addMonth(31)->toDateString() < $endedCarbon->toDateString()) {
                    throw new ResourceException("按月查询,间隔不能超过31个月");
                }

                $results = UserCumulate::where("type", $dateType)
                    ->where("ref_date", ">=", $startedCarbon->format('Y-m'))
                    ->where("ref_date", "<=", $endedCarbon->format('Y-m'))
                    ->where("subject_id", $subjectId)
                    ->select("ref_date", "cumulate_user as 有效用户")
                    ->get();

                //合并当月数据
                $currentMonthData = UserCumulate::where('type', 'day')
                    ->orderBy("ref_date", 'desc')
                    ->where("subject_id", $subjectId)
                    ->first();

                $results = $results->concat([
                        [
                            'ref_date'      => Carbon::now()->format('Y-m'),
                            'cumulate_user' => $currentMonthData->cumulate_user,
                        ],
                    ]
                );
                break;
            case 'year':
                $startedCarbon = Carbon::createFromFormat("Y", $started);
                $endedCarbon = Carbon::createFromFormat("Y", $ended);

                if ($startedCarbon->copy()->addYear(31)->toDateString() < $endedCarbon->toDateString()) {
                    throw new ResourceException("按年查询,间隔不能超过31年");
                }

                $results = UserCumulate::where("type", $dateType)
                    ->where("ref_date", ">=", $startedCarbon->format("Y"))
                    ->where("ref_date", "<=", $endedCarbon->format("Y"))
                    ->where("subject_id", $subjectId)
                    ->select("ref_date", "cumulate_user as 有效用户")
                    ->get();

                //合并当年数据
                $currentMonthData = UserCumulate::where('type', 'day')
                    ->orderBy("ref_date", 'desc')
                    ->where("subject_id", $subjectId)
                    ->first();

                $results = $results->concat([
                        [
                            'ref_date'      => Carbon::now()->format('Y'),
                            'cumulate_user' => $currentMonthData->cumulate_user,
                        ],
                    ]
                );

                break;
        }


        return $results;
    }


    /**
     * 用户新增数据
     *
     * @param Request $request
     */
    public function newUser(Request $request)
    {
    }


    private function getSubjectId($request)
    {
        return SubjectUtils::getSubjectId();
    }


}