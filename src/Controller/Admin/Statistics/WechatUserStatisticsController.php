<?php
/**
 * Copyright (c) 2018. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

namespace Mallto\User\Controller\Admin\Statistics;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Mallto\Admin\Data\Subject;
use Mallto\Admin\SubjectUtils;
use Mallto\Tool\Domain\Traits\StatisticsTraits;
use Mallto\Tool\Exception\ResourceException;
use Mallto\User\Data\WechatUserCumulate;

/**
 * 微信统计数据
 * Class WechatUserStatisticsController
 *
 * @package Overtrue\LaravelWeChat\Controllers\Admin
 */
class WechatUserStatisticsController extends Controller
{

    use StatisticsTraits;


    /**
     * 微信用户累计数据
     *
     * @param Request $request
     *
     * @return
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

                $results = WechatUserCumulate::where("type", $dateType)
                    ->where("ref_date", ">=", $started)
                    ->where("ref_date", "<=", $ended)
                    ->where("subject_id", $subjectId)
                    ->select("ref_date", "cumulate_user as wechat_cumulate_user")
                    ->get();
                break;
            case 'month':
                $startedCarbon = Carbon::createFromFormat("Y-m", $started);
                $endedCarbon = Carbon::createFromFormat("Y-m", $ended);
                if ($startedCarbon->copy()->addMonth(31)->toDateString() < $endedCarbon->toDateString()) {
                    throw new ResourceException("按月查询,间隔不能超过31个月");
                }

                $results = WechatUserCumulate::where("type", $dateType)
                    ->where("ref_date", ">=", $startedCarbon->format('Y-m'))
                    ->where("ref_date", "<=", $endedCarbon->format('Y-m'))
                    ->where("subject_id", $subjectId)
                    ->select("ref_date", "cumulate_user as wechat_cumulate_user")
                    ->get();

                //合并当月数据
                $currentYearData = WechatUserCumulate::where('type', 'day')
                    ->orderBy("ref_date", 'desc')
                    ->where("subject_id", $subjectId)
                    ->first();
                if ($currentYearData) {
                    $results = $results->concat([
                            [
                                'ref_date'             => Carbon::now()->format('Y-m'),
                                'wechat_cumulate_user' => $currentYearData->cumulate_user,
                            ],
                        ]
                    );
                }
                break;
            case 'year':
                $startedCarbon = Carbon::createFromFormat("Y", $started);
                $endedCarbon = Carbon::createFromFormat("Y", $ended);

                if ($startedCarbon->copy()->addYear(31)->toDateString() < $endedCarbon->toDateString()) {
                    throw new ResourceException("按年查询,间隔不能超过31年");
                }

                $results = WechatUserCumulate::where("type", $dateType)
                    ->where("ref_date", ">=", $startedCarbon->format("Y"))
                    ->where("ref_date", "<=", $endedCarbon->format("Y"))
                    ->where("subject_id", $subjectId)
                    ->select("ref_date", "cumulate_user as wechat_cumulate_user")
                    ->get();

                //合并当年数据
                $currentYearData = WechatUserCumulate::where('type', 'day')
                    ->orderBy("ref_date", 'desc')
                    ->where("subject_id", $subjectId)
                    ->first();

                if ($currentYearData) {
                    $results = $results->concat([
                            [
                                'ref_date'             => Carbon::now()->format('Y'),
                                'wechat_cumulate_user' => $currentYearData->cumulate_user,
                            ],
                        ]
                    );
                }

                break;
        }

        return $this->addDataToResult($results, $dateType, $startedCarbon, $endedCarbon,
            'wechat_cumulate_user');
    }


    /**
     * 新增用户数据
     *
     * @param Request $request
     *
     * @return array
     */
    public function newUser(Request $request)
    {
        $started = $request->users_new_started_at;
        $ended = $request->users_new_ended_at;
        $dateType = $request->users_new_date_type;

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

                $results = WechatUserCumulate::where("type", $dateType)
                    ->where("ref_date", ">=", $started)
                    ->where("ref_date", "<=", $ended)
                    ->where("subject_id", $subjectId)
                    ->select("ref_date", "new_user as wechat_new_user")
                    ->orderBy('ref_date', 'asc')
                    ->get();
                break;
            case 'month':
                $startedCarbon = Carbon::createFromFormat("Y-m", $started);
                $endedCarbon = Carbon::createFromFormat("Y-m", $ended);
                if ($startedCarbon->copy()->addMonth(31)->toDateString() < $endedCarbon->toDateString()) {
                    throw new ResourceException("按月查询,间隔不能超过31个月");
                }

                $results = WechatUserCumulate::where("type", $dateType)
                    ->where("ref_date", ">=", $startedCarbon->format('Y-m'))
                    ->where("ref_date", "<=", $endedCarbon->format('Y-m'))
                    ->where("subject_id", $subjectId)
                    ->select("ref_date", "new_user as wechat_new_user")
                    ->orderBy('ref_date', 'asc')
                    ->get();

                //合并当月数据
                $currentMonthData = WechatUserCumulate::where('type', 'day')
                    ->orderBy("ref_date", 'desc')
                    ->where("subject_id", $subjectId)
                    ->orderBy('ref_date', 'asc')
                    ->first();

                $lastMonthData = WechatUserCumulate::where('type', 'month')
                    ->orderBy("ref_date", 'desc')
                    ->where("subject_id", $subjectId)
                    ->orderBy('ref_date', 'asc')
                    ->first();

                if ($currentMonthData && $lastMonthData) {
                    $results = $results->concat([
                            [
                                'ref_date'        => Carbon::now()->format('Y-m'),
                                'wechat_new_user' => $currentMonthData->cumulate_user - $lastMonthData->cumulate_user,
                            ],
                        ]
                    );
                }

                break;
            case 'year':
                $startedCarbon = Carbon::createFromFormat("Y", $started);
                $endedCarbon = Carbon::createFromFormat("Y", $ended);

                if ($startedCarbon->copy()->addYear(31)->toDateString() < $endedCarbon->toDateString()) {
                    throw new ResourceException("按年查询,间隔不能超过31年");
                }

                $results = WechatUserCumulate::where("type", $dateType)
                    ->where("ref_date", ">=", $startedCarbon->format("Y"))
                    ->where("ref_date", "<=", $endedCarbon->format("Y"))
                    ->where("subject_id", $subjectId)
                    ->select("ref_date", "new_user as wechat_new_user")
                    ->get();

                //合并当年数据
                $currentYearData = WechatUserCumulate::where('type', 'day')
                    ->orderBy("ref_date", 'desc')
                    ->where("subject_id", $subjectId)
                    ->first();

                $lastYearData = WechatUserCumulate::where('type', 'year')
                    ->orderBy("ref_date", 'desc')
                    ->where("subject_id", $subjectId)
                    ->first();

                if ($currentYearData && $lastYearData) {
                    $newUser = $currentYearData->cumulate_user - $lastYearData->cumulate_user;
                    $results = $results->concat([
                            [
                                'ref_date'        => Carbon::now()->format('Y'),
                                'wechat_new_user' => $newUser,
                            ],
                        ]
                    );
                }

                break;
        }

        return $this->addDataToResult($results, $dateType, $startedCarbon, $endedCarbon, 'wechat_new_user');
    }


    private function getSubjectId($request)
    {
        if ($request->subject_uuid) {
            return Subject::where("uuid", $request->subject_uuid)->firstOrFail()->id;
        }

        return SubjectUtils::getSubjectId();
    }

}
